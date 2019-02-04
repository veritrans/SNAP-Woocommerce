<?php
  /**
   * ### Midtrans Payment Plugin for Wordrpress-WooCommerce ###
   *
   * This plugin allow your Wordrpress-WooCommerce to accept payment from customer using Midtrans Payment Gateway solution.
   *
   * @category   Wordrpress-WooCommerce Payment Plugin
   * @author     Rizda Dwi Prasetya <rizda.prasetya@midtrans.com>
   * @link       http://docs.midtrans.com
   * (This plugin is made based on Payment Plugin Template by WooCommerce)
   *
   * LICENSE: This program is free software; you can redistribute it and/or
   * modify it under the terms of the GNU General Public License
   * as published by the Free Software Foundation; either version 2
   * of the License, or (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program; if not, write to the Free Software
   * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
   */

    /**
     * Midtrans Payment Gateway Class
     */
    class WC_Gateway_Midtrans extends WC_Payment_Gateway {

      /**
       * Constructor
       */
      function __construct() {
        /**
         * Fetch config option field values and set it as private variables
         */
        $this->id           = 'midtrans';
        $this->icon         = apply_filters( 'woocommerce_midtrans_icon', '' );
        $this->method_title = __( 'Midtrans', 'woocommerce' );
        $this->has_fields   = true;
        $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Midtrans', home_url( '/' ) ) );

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get Settings
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->select_midtrans_payment = $this->get_option( 'select_midtrans_payment' );
        
        $this->client_key_v2_sandbox         = $this->get_option( 'client_key_v2_sandbox' );
        $this->server_key_v2_sandbox         = $this->get_option( 'server_key_v2_sandbox' );
        $this->client_key_v2_production         = $this->get_option( 'client_key_v2_production' );
        $this->server_key_v2_production         = $this->get_option( 'server_key_v2_production' );

        $this->api_version        = 2;
        $this->environment        = $this->get_option( 'select_midtrans_environment' );
        
        $this->to_idr_rate        = $this->get_option( 'to_idr_rate' );

        $this->enable_3d_secure   = $this->get_option( 'enable_3d_secure' );
        $this->enable_savecard   = $this->get_option( 'enable_savecard' );
        $this->enable_redirect   = $this->get_option( 'enable_redirect' );
        $this->custom_expiry   = $this->get_option( 'custom_expiry' );
        $this->custom_fields   = $this->get_option( 'custom_fields' );
        $this->enable_map_finish_url   = $this->get_option( 'enable_map_finish_url' );
        $this->ganalytics_id   = $this->get_option( 'ganalytics_id' );
        $this->enable_immediate_reduce_stock   = $this->get_option( 'enable_immediate_reduce_stock' );
        // $this->enable_sanitization = $this->get_option( 'enable_sanitization' );
        $this->enable_credit_card = $this->get_option( 'credit_card' );
        $this->enable_mandiri_clickpay = $this->get_option( 'mandiri_clickpay' );
        $this->enable_cimb_clicks = $this->get_option( 'cimb_clicks' );
        $this->enable_permata_va = $this->get_option( 'bank_transfer' );
        $this->enable_bri_epay = $this->get_option( 'bri_epay' );
        $this->enable_telkomsel_cash = $this->get_option( 'telkomsel_cash' );
        $this->enable_xl_tunai = $this->get_option( 'xl_tunai' );
        $this->enable_bbmmoney = $this->get_option( 'bbmmoney' );
        $this->enable_mandiri_bill = $this->get_option( 'mandiri_bill' );
        $this->enable_indomaret = $this->get_option('cstore');
        $this->enable_indosat_dompetku = $this->get_option('indosat_dompetku');
        $this->enable_mandiri_ecash = $this->get_option('mandiri_ecash');

        $this->client_key         = ($this->environment == 'production')
            ? $this->client_key_v2_production
            : $this->client_key_v2_sandbox;

        $this->log = new WC_Logger();

        // Register hook for handling HTTP notification (HTTP call to `http://[your web]/?wc-api=WC_Gateway_Midtrans`)
        add_action( 'woocommerce_api_wc_gateway_midtrans', array( &$this, 'midtrans_vtweb_response' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        // Hook for adding JS script to admin config page 
        add_action( 'admin_print_scripts-woocommerce_page_woocommerce_settings', array( &$this, 'midtrans_admin_scripts' ));
        add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'midtrans_admin_scripts' ));
        // Create action to be called when HTTP notification is valid
        add_action( 'valid-midtrans-web-request', array( $this, 'successful_request' ) );
        // Hook for displaying payment page HTML on receipt page
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
        // Hook for adding custom HTML on thank you page (for payement/instruction url)
        add_action( 'woocommerce_thankyou', array( $this, 'view_order_and_thankyou_page' ) );
        // Hook for adding custom HTML on view order menu from customer (for payement/instruction url)
        add_action( 'woocommerce_view_order', array( $this, 'view_order_and_thankyou_page' ) );
      }

      /**
       * Enqueue Javascripts
       * Add JS script file to admin page
       */
      function midtrans_admin_scripts() {
        wp_enqueue_script( 'admin-midtrans', MT_PLUGIN_DIR . 'js/admin-scripts.js', array('jquery') );
      }

      /**
       * Admin Panel Options
       * HTML that will be displayed on Admin Panel
       * @access public
       * @return void
       */
      public function admin_options() { ?>
        <h3><?php _e( 'Midtrans', 'woocommerce' ); ?></h3>
        <p><?php _e('Allows payments using Midtrans.', 'woocommerce' ); ?></p>
        <table class="form-table">
          <?php
            // Generate the HTML For the settings form. generated from `init_form_fields`
            $this->generate_settings_html();
          ?>
        </table><!--/.form-table-->
        <?php
      }

      /**
       * Initialise Gateway Settings Form Fields
       * Method ini digunakan untuk mengatur halaman konfigurasi admin
       */
      function init_form_fields() {
        
        $v2_sandbox_key_url = 'https://dashboard.sandbox.midtrans.com/settings/config_info';
        $v2_production_key_url = 'https://dashboard.midtrans.com/settings/config_info';
        /**
         * Build array of configurations that will be displayed on Admin Panel
         */
        $this->form_fields = array(
          'enabled' => array(
            'title' => __( 'Enable/Disable', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable Midtrans Payment', 'woocommerce' ),
            'default' => 'yes'
          ),
          'title' => array(
            'title' => __( 'Title', 'woocommerce' ),
            'type' => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
            'default' => __( 'Online Payment via Midtrans', 'woocommerce' ),
            'desc_tip'      => true,
          ),
          'description' => array(
            'title' => __( 'Customer Message', 'woocommerce' ),
            'type' => 'textarea',
            'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
            'default' => ''
          ),
          'select_midtrans_environment' => array(
            'title' => __( 'Environment', 'woocommerce' ),
            'type' => 'select',
            'default' => 'sandbox',
            'description' => __( 'Select the Midtrans Environment', 'woocommerce' ),
            'options'   => array(
              'sandbox'    => __( 'Sandbox', 'woocommerce' ),
              'production'   => __( 'Production', 'woocommerce' ),
            ),
          ),
          'merchant_id' => array(
            'title' => __("Merchant ID", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your Midtrans Merchant ID (e.g M012345). Get the ID <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
            'default' => '',
          ),
          'client_key_v2_sandbox' => array(
            'title' => __("Client Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Sandbox</b> Midtrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
            'default' => '',
            'class' => 'sandbox_settings toggle-midtrans',
          ),
          'server_key_v2_sandbox' => array(
            'title' => __("Server Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Sandbox</b> Midtrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
            'default' => '',
            'class' => 'sandbox_settings toggle-midtrans'
          ),
          'client_key_v2_production' => array(
            'title' => __("Client Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Production</b> Midtrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_production_key_url),
            'default' => '',
            'class' => 'production_settings toggle-midtrans',
          ),
          'server_key_v2_production' => array(
            'title' => __("Server Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Production</b> Midtrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_production_key_url),
            'default' => '',
            'class' => 'production_settings toggle-midtrans'
          ),
          'enable_3d_secure' => array(
            'title' => __( 'Enable 3D Secure', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable 3D Secure?', 'woocommerce' ),
            'description' => __( 'You must enable 3D Secure.
                Please contact us if you wish to disable this feature in the Production environment.', 'woocommerce' ),
            'default' => 'yes'
          ),
          'enable_savecard' => array(
            'title' => __( 'Enable Save Card', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable Save Card?', 'woocommerce' ),
            'description' => __( 'This will allow your customer to save their card on the payment popup, for faster payment flow on the following purchase', 'woocommerce' ),
            'class' => 'toggle-advanced',
            'default' => 'no'
          ),
          'enable_redirect' => array(
            'title' => __( 'Redirect payment page', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable payment page redirection?', 'woocommerce' ),
            'description' => __( 'This will redirect customer to Midtrans hosted payment page instead of popup payment page on your website. <br>Leave it disabled if you are not sure', 'woocommerce' ),
            'class' => 'toggle-advanced',
            'default' => 'no'
          ),
          'custom_expiry' => array(
            'title' => __( 'Custom Expiry', 'woocommerce' ),
            'type' => 'text',
            'description' => __( 'This will allow you to set custom duration on how long the transaction available to be paid.<br> example: 45 minutes', 'woocommerce' ),
            'default' => 'disabled'
          ),
          'custom_fields' => array(
            'title' => __( 'Custom Fields', 'woocommerce' ),
            'type' => 'text',
            'description' => __( 'This will allow you to set custom fields that will be displayed on Midtrans dashboard. <br>Up to 3 fields are available, separate by coma (,) <br> Example:  Order from web, Woocommerce, Processed', 'woocommerce' ),
            'default' => ''
          ),
          'enable_map_finish_url' => array(
            'title' => __( 'Use Dashboard Finish url', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => 'Use dashboard configured payment finish url?',
            'description' => __( 'This will allow use of Dashboard configured payment finish url instead of auto configured url', 'woocommerce' ),
            'default' => 'no'
          ),
          'ganalytics_id' => array(
            'title' => __( 'Google Analytics ID', 'woocommerce' ),
            'type' => 'text',
            'description' => __( 'This will allow you to use Google Analytics tracking on woocommerce payment page. <br>Input your tracking ID ("UA-XXXXX-Y") <br> Leave it blank if you are not sure', 'woocommerce' ),
            'default' => ''
          ),
          'enable_immediate_reduce_stock' => array(
            'title' => __( 'Immediate Reduce Stock', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => 'Immediately reduce item stock on Midtrans payment pop-up?',
            'description' => __( 'By default, item stock only reduced if payment status on Midtrans reach pending/success (customer choose payment channel and click pay on payment pop-up). Enable this if you want to immediately reduce item stock when payment pop-up generated/displayed.', 'woocommerce' ),
            'default' => 'no'
          )
        );
        // Currency conversion rate if currency is not IDR
        if (get_woocommerce_currency() != 'IDR')
        {
          $this->form_fields['to_idr_rate'] = array(
            'title' => __("Current Currency to IDR Rate", 'woocommerce'),
            'type' => 'text',
            'description' => 'The current currency to IDR rate',
            'default' => '10000',
          );
        }
      }
      /**
       * Helper for backward compatibility WC v3 & v2 on getting Order Property
       * @param  [String] $order    Order Object
       * @param  [String] $property Target property
       * @return the property
       */
      function getOrderProperty($order, $property){
        $functionName = "get_".$property;
        if (method_exists($order, $functionName)){ // WC v3
          return (string)$order->{$functionName}();
        } else { // WC v2
          return (string)$order->{$property};
        }
      }

      /**
       * Call Midtrans SNAP API and return the response as asoc array
       * Plugin config and cart/order properties are used as param
       * @param  [String] $order_id 
       * @return [Array]  SNAP API response encoded as associative array
       */
      function create_snap_transaction( $order_id){
        if(!class_exists('Veritrans_Config')){
          require_once(dirname(__FILE__) . '/../lib/veritrans/Veritrans.php'); 
        }
        require_once(dirname(__FILE__) . '/class.midtrans-utils.php');
        
        global $woocommerce;
        $order_items = array();
        $cart = $woocommerce->cart;

        $order = new WC_Order( $order_id );     
      
        Veritrans_Config::$isProduction = ($this->environment == 'production') ? true : false;
        Veritrans_Config::$serverKey = (Veritrans_Config::$isProduction) ? $this->server_key_v2_production : $this->server_key_v2_sandbox;     
        Veritrans_Config::$is3ds = ($this->enable_3d_secure == 'yes') ? true : false;
        Veritrans_Config::$isSanitized = true;
        
        $params = array(
          'transaction_details' => array(
            'order_id' => $order_id,
            'gross_amount' => 0,
          ),
          'vtweb' => array()
        );

        $customer_details = array();
        $customer_details['first_name'] = $this->getOrderProperty($order,'billing_first_name');
        $customer_details['last_name'] = $this->getOrderProperty($order,'billing_last_name');
        $customer_details['email'] = $this->getOrderProperty($order,'billing_email');
        $customer_details['phone'] = $this->getOrderProperty($order,'billing_phone');

        $billing_address = array();
        $billing_address['first_name'] = $this->getOrderProperty($order,'billing_first_name');
        $billing_address['last_name'] = $this->getOrderProperty($order,'billing_last_name');
        $billing_address['address'] = $this->getOrderProperty($order,'billing_address_1');
        $billing_address['city'] = $this->getOrderProperty($order,'billing_city');
        $billing_address['postal_code'] = $this->getOrderProperty($order,'billing_postcode');
        $billing_address['phone'] = $this->getOrderProperty($order,'billing_phone');
        $converted_country_code = Midtrans_Utils::convert_country_code($this->getOrderProperty($order,'billing_country'));
        $billing_address['country_code'] = (strlen($converted_country_code) != 3 ) ? 'IDN' : $converted_country_code ;

        $customer_details['billing_address'] = $billing_address;
        $customer_details['shipping_address'] = $billing_address;
        
        if ( isset ( $_POST['ship_to_different_address'] ) ) {
          $shipping_address = array();
          $shipping_address['first_name'] = $this->getOrderProperty($order,'shipping_first_name');
          $shipping_address['last_name'] = $this->getOrderProperty($order,'shipping_last_name');
          $shipping_address['address'] = $this->getOrderProperty($order,'shipping_address_1');
          $shipping_address['city'] = $this->getOrderProperty($order,'shipping_city');
          $shipping_address['postal_code'] = $this->getOrderProperty($order,'shipping_postcode');
          $shipping_address['phone'] = $this->getOrderProperty($order,'billing_phone');
          $converted_country_code = Midtrans_Utils::convert_country_code($this->getOrderProperty($order,'shipping_country'));
          $shipping_address['country_code'] = (strlen($converted_country_code) != 3 ) ? 'IDN' : $converted_country_code;
          
          $customer_details['shipping_address'] = $shipping_address;
        }
        
        $params['customer_details'] = $customer_details;
        //error_log(print_r($params,true));

        $items = array();

         // Build item_details API params from $Order items
        if( sizeof( $order->get_items() ) > 0 ) {
          foreach( $order->get_items() as $item ) {
            if ( $item['qty'] ) {
              $product = $order->get_product_from_item( $item );

              $midtrans_item = array();

              $midtrans_item['id']    = $item['product_id'];
              $midtrans_item['price']      = ceil($order->get_item_subtotal( $item, false ));
              $midtrans_item['quantity']   = $item['qty'];
              $midtrans_item['name'] = $item['name'];
              
              $items[] = $midtrans_item;
            }
          }
        }

        // Shipping fee as item_details
        if( $order->get_total_shipping() > 0 ) {
          $items[] = array(
            'id' => 'shippingfee',
            'price' => ceil($order->get_total_shipping()),
            'quantity' => 1,
            'name' => 'Shipping Fee',
          );
        }

        // Tax as item_details
        if( $order->get_total_tax() > 0 ) {
          $items[] = array(
            'id' => 'taxfee',
            'price' => ceil($order->get_total_tax()),
            'quantity' => 1,
            'name' => 'Tax',
          );
        }

        // Discount as item_details
        if ( $order->get_total_discount() > 0) {
          $items[] = array(
            'id' => 'totaldiscount',
            'price' => ceil($order->get_total_discount())  * -1,
            'quantity' => 1,
            'name' => 'Total Discount'
          );
        }

        // Fees as item_details
        if ( sizeof( $order->get_fees() ) > 0 ) {
          $fees = $order->get_fees();
          $i = 0;
          foreach( $fees as $item ) {
            $items[] = array(
              'id' => 'itemfee' . $i,
              'price' => ceil($item['line_total']),
              'quantity' => 1,
              'name' => $item['name'],
            );
            $i++;
          }
        }

        // Iterate through the entire item to ensure that currency conversion is applied
        if (get_woocommerce_currency() != 'IDR')
        {
          foreach ($items as &$item) {
            $item['price'] = $item['price'] * $this->to_idr_rate;
            $item['price'] = intval($item['price']);
          }

          unset($item);

          $params['transaction_details']['gross_amount'] *= $this->to_idr_rate;
        }

        $total_amount=0;
        // error_log('print r items[]' . print_r($items,true)); //debugan
        // Sum item details prices as gross_amount
        foreach ($items as $item) {
          $total_amount+=($item['price']*$item['quantity']);
        }
        $params['transaction_details']['gross_amount'] = $total_amount;

        $params['item_details'] = $items;

        // add custom `expiry` API params
        $custom_expiry_params = explode(" ",$this->custom_expiry);
        if ( !empty($custom_expiry_params[1]) && !empty($custom_expiry_params[0]) ){
          $time = time();
          $time += 30; // add 30 seconds to allow margin of error
          $params['expiry'] = array(
            'start_time' => date("Y-m-d H:i:s O",$time), 
            'unit' => $custom_expiry_params[1], 
            'duration'  => (int)$custom_expiry_params[0],
          );
        }
        // add custom_fields API params
        $custom_fields_params = explode(",",$this->custom_fields);
        if ( !empty($custom_fields_params[0]) ){
          $params['custom_field1'] = $custom_fields_params[0];
          $params['custom_field2'] = !empty($custom_fields_params[1]) ? $custom_fields_params[1] : null;
          $params['custom_field3'] = !empty($custom_fields_params[2]) ? $custom_fields_params[2] : null;
        }
        // add savecard API params
        if ($this->enable_savecard =='yes' && is_user_logged_in()){
          $params['user_id'] = crypt( $customer_details['email'].$customer_details['phone'] , Veritrans_Config::$serverKey );
          $params['credit_card']['save_card'] = true;
        }
        // Empty the cart because payment is initiated.
        $woocommerce->cart->empty_cart();
        // error_log(print_r($params,true)); //debug
        
        try {
          $snapResponse = Veritrans_Snap::createTransaction($params);
        } catch (Exception $e) {
          $this->json_print_exception($e);
          exit();
        }
        return $snapResponse;
      }

      /**
       * Helper to print error as expected by Woocommerce ajax call
       * On payment.
       * @param  [error] $e
       * @return [array] JSON encoded error messages.
       */
      function json_print_exception ($e) {
        $errorObj = array(
          'result' => "failure", 
          'messages' => '<div class="woocommerce-error" role="alert"> Midtrans Exception: '.$e->getMessage().'. <br>Plugin Title: '.$this->method_title.'</div>',
          'refresh' => false, 
          'reload' => false
        );
        $errorJson = json_encode($errorObj);
        echo $errorJson;
      }

      /**
       * This function auto-triggered by WC when payment process initiated
       * Serves as WC payment entry point
       * @param  [String] $order_id auto generated by WC
       * @return [array] contains redirect_url of payment for customer
       */
      function process_payment( $order_id ) {
        global $woocommerce;
        
        // Create the order object
        $order = new WC_Order( $order_id );
        // Response object template
        $successResponse = array(
          'result'  => 'success',
          'redirect' => ''
        );

        // If snap token exists on the current $Order, reuse it
        // Prevent duplication of API call, which may throw API error
        if ($order->meta_exists('_mt_payment_snap_token')){
          $successResponse['redirect'] = $order->get_checkout_payment_url( true )."&snap_token=".$order->get_meta('_mt_payment_snap_token');
          return $successResponse;
        }

        $snapResponse = $this->create_snap_transaction($order_id);
        // If `enable_redirect` admin config used, snap redirect
        if(property_exists($this,'enable_redirect') && $this->enable_redirect == 'yes'){
          $redirectUrl = $snapResponse->redirect_url;
        }else{
          $redirectUrl = $order->get_checkout_payment_url( true )."&snap_token=".$snapResponse->token;
        }

        // Store snap token & snap redirect url to $order metadata
        $order->update_meta_data('_mt_payment_snap_token',$snapResponse->token);
        $order->update_meta_data('_mt_payment_url',$snapResponse->redirect_url);
        $order->save();

        if(property_exists($this,'enable_immediate_reduce_stock') && $this->enable_immediate_reduce_stock == 'yes'){
          // Reduce item stock on WC, item also auto reduced on order `pending` status changes
          wc_reduce_stock_levels($order);
        }

        $successResponse['redirect'] = $redirectUrl;
        return $successResponse;
      }

      /**
       * Hook function that will be called on receipt page
       * Output HTML for Snap payment page. Including `snap.pay()` part
       * @param  [String] $order_id generated by WC
       * @return [String] HTML
       */
      function receipt_page( $order_id ) {
        global $woocommerce;
        $pluginName = 'fullpayment';
        // Separated as Shared PHP included by multiple class
        require_once(dirname(__FILE__) . '/payment-page.php'); 

      }

      /**
       * Hook function that will be called on thank you page
       * Output HTML for payment/instruction URL
       * @param  [String] $order_id generated by WC
       * @return [String] HTML
       */
      public function view_order_and_thankyou_page( $order_id ) {
        require_once(dirname(__FILE__) . '/order-view-and-thankyou-page.php');
      }

      /**
       * Helper to response Response early with HTTP 200 for Midtrans notification
       * So Notification Engine can mark notification complete early and faster
       * Also reject HTTP GET request
       * @return void
       */
      public function earlyResponse(){
        if ( $_SERVER['REQUEST_METHOD'] == 'GET' ){
          die('This endpoint should not be opened using browser (HTTP GET). This endpoint is for Midtrans notification URL (HTTP POST)');
          exit();
        }

        ob_start();

        $input_source = "php://input";
        $raw_notification = json_decode(file_get_contents($input_source), true);
        echo "Notification Received: \n";
        print_r($raw_notification);
        
        header('Connection: close');
        header('Content-Length: '.ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
      }

      /**
       * Called by hook function when HTTP notification / API call received
       * Handle Midtrans payment notification
       */
      function midtrans_vtweb_response() {
        if(!class_exists('Veritrans_Config')){
          require_once(dirname(__FILE__) . '/../lib/veritrans/Veritrans.php'); 
        }

        global $woocommerce;
        @ob_clean();

        global $woocommerce;
        
        Veritrans_Config::$isProduction = ($this->environment == 'production') ?
          true: 
          false;
        Veritrans_Config::$serverKey = ($this->environment == 'production') ?
          $this->server_key_v2_production: 
          $this->server_key_v2_sandbox;
        
        // check whether the request is POST or GET, 
        // if request == POST, request is for payment notification, then update the payment status
        if(!isset($_GET['order_id']) && !isset($_GET['id']) && !isset($_POST['response'])){    // Check if POST, then create new notification
          $this->earlyResponse();
          // Handle pdf url update
          $this->handlePendingPaymentPdfUrlUpdate();

          // Verify Midtrans notification
          $midtrans_notification = new Veritrans_Notification();
          // If notification verified, handle it
          if (in_array($midtrans_notification->status_code, array(200, 201, 202, 407))) {
            if (wc_get_order($midtrans_notification->order_id) != false) {
              do_action( "valid-midtrans-web-request", $midtrans_notification );
            }
          }
          exit;
        } 
        // if request == GET, request is for finish OR failed URL, then redirect to WooCommerce's order complete/failed
        else {    
          // error_log('status_code '. $_GET['status_code']); //debug
          // error_log('status_code '. $_GET['transaction_status']); //debug

          // if capture or pending or challenge or settlement, redirect to order received page
          if( isset($_GET['order_id']) && isset($_GET['transaction_status']) && $_GET['status_code'] <= 201)  {
            $order_id = $_GET['order_id'];
            // error_log($this->get_return_url( $order )); //debug
            $order = new WC_Order( $order_id );
            wp_redirect($order->get_checkout_order_received_url());
          } 
          //if deny, redirect to order checkout page again
          else if( isset($_GET['order_id']) && isset($_GET['transaction_status']) && $_GET['status_code'] >= 201){
            wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
          } 
          // if customer click "back" button, redirect to checkout page again
          else if( isset($_GET['order_id']) && !isset($_GET['transaction_status'])){ 
            wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
          // if customer redirected from async payment with POST `response` (CIMB clicks, etc)
          } else if ( isset($_POST['response']) ){ 
            $responses = json_decode( stripslashes($_POST['response']), true);
            $order = new WC_Order( $responses['order_id'] );
            // if async payment paid
            if ( $responses['status_code'] == 200) { 
              wp_redirect($order->get_checkout_order_received_url());
            } 
            // if async payment not paid
            else {
              wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
            }
          // if customer redirected from async payment with GET `id` (BCA klikpay, etc)
          } else if (isset($_GET['id']) || (isset($_GET['wc-api']) && strlen($_GET['wc-api']) >= 25) ){
            // Workaround if id query string is malformed, manual substring
            if (isset($_GET['wc-api']) && strlen($_GET['wc-api']) >= 25) {
              $id = str_replace("WC_Gateway_Midtrans?id=", "", $_GET['wc-api']);
            }
            // else if id query string format is correct
            else {
              $id = $_GET['id'];
            }

            $midtrans_notification = Veritrans_Transaction::status($id);
            $order_id = $midtrans_notification->order_id;
            // if async payment paid
            if ($midtrans_notification->transaction_status == 'settlement'){
              $order = new WC_Order( $order_id );
              wp_redirect($order->get_checkout_order_received_url());              
            } 
            // if async payment not paid
            else {
              wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
            }
          } 
          // if unhandled case, fallback, redirect to home
          else {
            wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
          }
        }

      }
      
      /**
       * Handle Midtrans Notification Object, after payment status changes on Midtrans
       * Will update WC payment status accordingly
       * @param  [Object] $midtrans_notification Object representation of Midtrans JSON
       * notification
       * @return void
       */
      function successful_request( $midtrans_notification ) {

        global $woocommerce;

        $order = new WC_Order( $midtrans_notification->order_id );
        $order->add_order_note(__('Midtrans HTTP notification received: '.$midtrans_notification->transaction_status.'. Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));

       // error_log(var_dump($order));
        if ($midtrans_notification->transaction_status == 'capture') {
          if ($midtrans_notification->fraud_status == 'accept') {
            $order->payment_complete();
            $order->add_order_note(__('Midtrans payment completed: capture. Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
          }
          else if ($midtrans_notification->fraud_status == 'challenge') {
            $order->update_status('on-hold',__('Challanged payment: Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
          }
        }
        else if ($midtrans_notification->transaction_status == 'cancel') {
          $order->update_status('cancelled',__('Cancelled payment: Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
        }
        else if ($midtrans_notification->transaction_status == 'expire') {
          $order->update_status('cancelled',__('Expired payment: Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
        }
        else if ($midtrans_notification->transaction_status == 'deny') {
          // do nothing on deny, allow payment retries
          // $order->update_status('failed',__('Denied payment: Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
        }
        else if ($midtrans_notification->transaction_status == 'settlement') {
          if($midtrans_notification->payment_type != 'credit_card'){
            $order->payment_complete();
            $order->add_order_note(__('Midtrans payment completed: settlement. Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
          }
        }
        else if ($midtrans_notification->transaction_status == 'pending') {
          $order->update_status('on-hold',__('Awaiting payment: Midtrans-'.$midtrans_notification->payment_type,'woocommerce'));
        }

        exit;
      }

      /**
       * Handle API call from payment page to update order with PDF instruction Url
       * @return void
       */
      public function handlePendingPaymentPdfUrlUpdate(){
        try {
          global $woocommerce;
          $requestObj = json_decode(file_get_contents("php://input"), true);
          if( !array_key_exists('pdf_url_update', $requestObj) || 
              !array_key_exists('snap_token_id', $requestObj) ){
            return;
          }
          $snapApiBaseUrl = ($this->environment == 'production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
          $tokenStatusUrl = $snapApiBaseUrl.'/snap/v1/transactions/'.$requestObj['snap_token_id'].'/status';
          $tokenStatusResponse = wp_remote_get( $tokenStatusUrl);
          $tokenStatus = json_decode($tokenStatusResponse['body'], true);
          $paymentStatus = $tokenStatus['transaction_status'];
          $order = new WC_Order( $tokenStatus['order_id'] );
          $orderStatus = $order->get_status();
          // update order status to on-hold if current status is "pending payment"
          if($orderStatus == 'pending' && $paymentStatus == 'pending'){
            $order->update_status('on-hold',__('Midtrans onPending Callback received','woocommerce'));
          }
          if( !array_key_exists('pdf_url', $tokenStatus) ){
            return;
          }
          // store Url as $Order metadata
          $order->update_meta_data('_mt_payment_pdf_url',$tokenStatus['pdf_url']);
          $order->save();

          echo "OK";
          // immediately terminate notif handling, not a notification.
          exit();
        } catch (Exception $e) {
          // var_dump($e); 
          // exit();
        }
      }
    }
