<?php

class Midtrans_Shipping_Gosend extends WC_Shipping_Method {
  
  public function __construct() {
    $this->id                 = 'midtrans_shipping_gosend'; 
    $this->method_title       = __( 'Midtrans GoSend', 'Midtrans' );  
    $this->method_description = __( 'Midtrans Shipping for GoSend', 'Midtrans' ); 
    $this->availability = 'including';
    $this->countries = array(
      'ID'
    );
    $this->init_form_fields();
    $this->init_settings();
    
    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'midtrans_admin_scripts' ));
    add_filter( 'woocommerce_checkout_fields', array( $this, 'update_fields' ), 10 );
  }

  // set default country, currently only available for ID
  public function update_fields($fields) {
    $fields['billing']['billing_country'] = array(
      'type'      => 'select',
      'label'     => __('Country', 'Midtrans'),
      'options' 	=> array('ID' => 'Indonesia')
    );
    $fields['shipping']['shipping_country'] = array(
      'type'      => 'select',
      'label'     => __('Country', 'Midtrans'),
      'options' 	=> array('ID' => 'Indonesia')
    );
    return $fields;
}
  
  function midtrans_admin_scripts() {
    wp_enqueue_script( 'admin-midtrans', MT_PLUGIN_DIR . 'js/admin-scripts.js', array('jquery') );
    wp_localize_script( 'admin-midtrans', 'midtrans_params',  $this->midtrans_params() );
  }
  
  function midtrans_params() {
    return array(
      'location_selected' => $this->get_option( 'origin' ),
      'ajaxurl' => admin_url( 'admin-ajax.php' )
    );
  }
  
  function init_form_fields() { 
    $this->form_fields = array(
      'enabled' => array(
        'title' => __( 'Enable/Disable', 'Midtrans' ),
        'type' => 'checkbox',
        'label' => __( 'Enable Midtrans Shipping for GoSend', 'Midtrans' ),
        'default' => 'no'
      ),
      'title' => array(
        'title' => __( 'Title', 'Midtrans' ),
        'type' => 'text',
        'description' => __( 'Title which the user sees during checkout.', 'Midtrans' ),
        'default' => __( 'GoSend via Midtrans', 'Midtrans' ),
      ),
      'origin' => array(
        'title' => __( 'Origin Location', 'Midtrans' ),
        'type' => 'select',
        'default' => '',
        'description' => __( 'Select origin location to calculate shipping cost.', 'Midtrans' ),
        'class' => 'gosend_origin'
      ),
    );
  }

  public static function gosend_origin_location() {
    $api = new MT_SHIPPR_API();
    $get = $api->geocoder(array('address' => $_REQUEST['q']));
    $results = array();
    foreach($get['data']['address'] as $row){
      $item = array(
        'address' => $row['address']
      );
      array_push($results, $item);
    }
    echo json_encode($results);
    die();
  }

  public function calculate_shipping( $package = array() ) {
    if ($package['destination']['country'] != 'ID') return false;

    $total_weight = 0;
    foreach ( $package['contents'] as $values ) {
      $_product = $values['data'];
      $product_weight = (int) $_product->get_weight();
      $product_quantity = (int) $values['quantity'];
      $weight = $product_weight * (int) $product_quantity;
      $total_weight += $weight;
    }
    if ($total_weight == 0) return false;

    $weight_in_grams = wc_get_weight( $total_weight, 'g' );
    $state = $this->state_name_by_code( $package['destination']['state'] );
    $city = $package['destination']['city'];
    $address = $package['destination']['address'].' '.$package['destination']['address_2'];
    $destination = $address.' '.$city.' '.$state;
    
    $body = array(
      'origin' => $this->get_option('origin'),
      'destination' => $destination,
      'weight_in_grams' => $weight_in_grams,
      'providers' => array('gosend')
    );

    $api = new MT_SHIPPR_API();
    $rates = $api->rates($body);
    if ($rates['status'] === 'error') return false;

    // get gosend services
    $services = $rates['data']['rates'][0]['services'];
    if (count($services) == 0) return false;

    foreach ($services as $service) {
      $rate = array(
        'id' => $this->set_rate_id($service['name']),
        'label' => 'Gosend '.$service['name'],
        'cost' => $service['price']
      );
      $this->add_rate( $rate );
    }
  }

  function state_name_by_code( $code ) {
    $counties = new WC_Countries();
    $states = $counties->get_states( 'ID' );
    return $states[ $code ];
  }

  function set_rate_id( $name ) {
    $name = str_replace(' ', '_', $name);
    return 'gosend_'.strtolower($name);
  }
}
