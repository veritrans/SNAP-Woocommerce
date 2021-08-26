<?php
  /**
   * Output HTML to display Snap Payment page
   */

  // ## Output the basic static HTML part
  ?>
  <div style="text-align: center;">

    <a id="pay-button" title="Proceed To Payment!" class="button alt">Loading Payment...</a>

    <div id="payment-instruction" style="display:none;">
      <br>
      <!-- <h3 class="alert alert-info"> Awaiting Your Payment </h3> -->
      <p> Please complete your payment as instructed. If you have already completed your payment, please check your email or "My Order" menu to get update of your order status. </p>
      <a target="_blank" href="#" id="payment-instruction-btn" title="Payment Instruction" class="button alt" >
        Payment Instruction
      </a>
    </div>
  </div>
<?php
  // ## End of output HTML
  
  // ## Dynamic part, javascript and backend stuff below

  // Ensure backward compatibility with WP version <5.7 which don't have these functions
  if( !function_exists('wp_get_script_tag')){
    WC_Midtrans_Utils::polyfill_wp_get_script_tag();
  }
  if( !function_exists('wp_get_inline_script_tag')){
    WC_Midtrans_Utils::polyfill_wp_get_inline_script_tag();
  }
  
  // $order_items = array();
  // $cart = $woocommerce->cart;
  $snap_token = sanitize_text_field($_GET['snap_token']);
  $snap_token = esc_js( 
    htmlspecialchars($snap_token, ENT_COMPAT,'ISO-8859-1', true) );

  $is_production = $this->environment == 'production';
  $snap_api_base_url = $is_production ? 
    "https://app.midtrans.com" : "https://app.sandbox.midtrans.com";
  $snap_script_url = esc_attr($snap_api_base_url."/snap/snap.js");
  $snap_script_tag_id = esc_attr("snap-script");
  $client_key = esc_attr($this->client_key);

  $ganalytics_id = esc_attr($this->ganalytics_id);
  $mixpanel_key_production = "17253088ed3a39b1e2bd2cbcfeca939a";
  $mixpanel_key_sandbox = "9dcba9b440c831d517e8ff1beff40bd9";
  $mixpanel_key = $is_production ? 
    $mixpanel_key_production : $mixpanel_key_sandbox;
  $mixpanel_key = esc_js($mixpanel_key);

  $wp_base_url = home_url( '/' );
  $plugin_backend_url = esc_url($wp_base_url."?wc-api=WC_Gateway_Midtrans");
  $finish_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";
  $pending_url = $finish_url;
  $error_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";

  $is_using_map_finish_url = 
    (isset($this->enable_map_finish_url) && $this->enable_map_finish_url == 'yes')?
    "true":"false";
  $is_ignore_pending_status = 
    (property_exists($this,'ignore_pending_status') && $this->ignore_pending_status == 'yes')?
    "true":"false";
  $is_payment_request_plugin = 
    ($this->id == 'midtrans_paymentrequest')?
    "true":"false";

  $gross_amount = esc_js(isset($gross_amount)?$gross_amount:'0');
  $merchant_id = esc_js($this->get_option('merchant_id'));
  $wc_version = esc_js(WC_VERSION);
  $plugin_name = esc_js($pluginName);
  $plugin_id = esc_js(isset($this->sub_payment_method_id)?$this->sub_payment_method_id:$this->id);
  $midtrans_plugin_version = esc_js(MIDTRANS_PLUGIN_VERSION);

  // Pass backend PHP variables into frontend JS variables
  $inline_js = <<<EOT

    var wc_midtrans = {
      snap_token      : "$snap_token",
      snap_script_url : "$snap_script_url",
      snap_script_tag_id  : "$snap_script_tag_id",
      client_key      : "$client_key",
      ganalytics_id   : "$ganalytics_id",
      mixpanel_key    : "$mixpanel_key",
      plugin_backend_url : "$plugin_backend_url",
      finish_url      : "$finish_url",
      pending_url     : "$pending_url",
      error_url       : "$error_url",
      is_using_map_finish_url   : $is_using_map_finish_url,
      is_ignore_pending_status  : $is_ignore_pending_status,
      is_payment_request_plugin : $is_payment_request_plugin,
      gross_amount    : "$gross_amount",
      merchant_id     : "$merchant_id",
      wc_version      : "$wc_version",
      plugin_name     : "$plugin_name",
      plugin_id     : "$plugin_id",
      midtrans_plugin_version : "$midtrans_plugin_version"
    };
EOT;

  $this_plugin_dir_url = plugin_dir_url( __DIR__ );
  $ga_script_url = $this_plugin_dir_url.'public/js/midtrans-payment-page-ga.js';
  $ganalytics_script_url = 'https://www.google-analytics.com/analytics.js';
  $mp_script_url = $this_plugin_dir_url.'public/js/midtrans-payment-page-mp.js';
  $main_script_url = $this_plugin_dir_url.'public/js/midtrans-payment-page-main.js';

  // Wrap into html script tag, output to html page
  echo wp_get_inline_script_tag(
    $inline_js,
    array(
      'data-cfasync' => 'false',
    )
  );

  // Output Google Analytics script tag
  if(property_exists($this,'ganalytics_id') && strlen($this->ganalytics_id)>0){
    echo wp_get_script_tag(
      array(
        'data-cfasync' => 'false',
        'src' => $ga_script_url,
      )
    );
    echo wp_get_script_tag(
      array(
        'data-cfasync' => 'false',
        'src' => $ganalytics_script_url,
        'async' => 'true',
      )
    );
  }
  // Output mixpanel script tag
  echo wp_get_script_tag(
    array(
      'data-cfasync' => 'false',
      'src' => $mp_script_url,
    )
  );
  // Output snap.js script tag
  echo wp_get_script_tag(
    array(
      'data-cfasync' => 'false',
      'src' => $snap_script_url,
      'id' => $snap_script_tag_id,
      'data-client-key' => $client_key,
    )
  );
  
  // output main.js script tag to html
  echo wp_get_script_tag(
    array(
      'data-cfasync' => 'false',
      'src' => $main_script_url,
    )
  );
