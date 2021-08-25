<?php
    /**
     * Midtrans Promo Payment Gateway Class
     * Duplicated from `class.midtrans-gateway.php`
     * Check `class.midtrans-gateway.php` file for proper function comments
     *
     * Using WC Coupon mechanism to apply discount 
     * then lock user to pay with specific payment channel
     * which is often required as Midtrans promo campaign
     */
    class WC_Gateway_Midtrans_Promo extends WC_Gateway_Midtrans_Abstract {

      /**
       * Constructor
       */
      function __construct() {
        $this->id           = 'midtrans_promo';
        $this->method_title = __( $this->pluginTitle(), 'midtrans-woocommerce' );
        $this->method_description = $this->getSettingsDescription();

        parent::__construct();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );// Payment page hook
      }

      /**
       * Admin Panel Options
       * - Options for bits like 'title' and availability on a country-by-country basis
       *
       * @access public
       * @return void
       */
      public function admin_options() { ?>
        <h3><?php _e( $this->pluginTitle(), 'midtrans-woocommerce' ); ?></h3>
        <p><?php _e($this->getSettingsDescription(), 'midtrans-woocommerce' ); ?></p>
        <table class="form-table">
          <?php
            // Generate the HTML For the settings form.
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
        parent::init_form_fields();
        WC_Midtrans_Utils::array_insert( $this->form_fields, 'enable_3d_secure', array(
          'method_enabled' => array(
            'title' => __( 'Allowed Payment Method', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Customize allowed payment method, separate payment method code with coma. e.g: bank_transfer,credit_card. <br>Leave it default if you are not sure.', 'midtrans-woocommerce' ),
            'default' => 'credit_card'
          ),
          'bin_number' => array(
            'title' => __( 'Allowed CC BINs', 'midtrans-woocommerce'),
            'type' => 'text',
            'label' => __( 'Allowed CC BINs', 'midtrans-woocommerce' ),
            'description' => __( 'Fill with CC BIN numbers (or bank name) that you want to allow to use this payment button. </br> Separate BIN number with coma Example: 4,5,4811,bni,mandiri', 'midtrans-woocommerce' ),
            'default' => ''
          ),
          'promo_code' => array(
            'title' => __( 'Promo Code', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Promo Code that would be used for discount. Leave blank if you are not using promo code.', 'midtrans-woocommerce' ),
            'default' => 'onlinepromomid'
          )
        ));
      }

      function process_payment( $order_id ) {
        global $woocommerce;
        //create the order object
        $order = new WC_Order( $order_id );
        $cart = $woocommerce->cart;

        if ( strlen($this->get_option( 'promo_code' )) > 0 ) {
          $coupon_code = $this->get_option( 'promo_code' );
          // add coupon to $cart for discount
          $cart->add_discount($coupon_code);
          // $order->add_coupon( 'onlinepromo', WC()->cart->get_coupon_discount_amount( 'onlinepromo' ), WC()->cart->get_coupon_discount_tax_amount( 'onlinepromo' ) );
          $order->set_shipping_total( WC()->cart->shipping_total );
          $order->set_discount_total( WC()->cart->get_cart_discount_total() );
          $order->set_discount_tax( WC()->cart->get_cart_discount_tax_total() );
          $order->set_cart_tax( WC()->cart->tax_total);
          $order->set_shipping_tax( WC()->cart->shipping_tax_total );
          $order->set_total( WC()->cart->total );
          $order->save();
        }

        // Get response object template
        $successResponse = $this->getResponseTemplate( $order );
        // Get data for charge to midtrans API
        $params = $this->getPaymentRequestData( $order_id );

        // check enabled payment
        $enabled_payments = explode(',', $this->get_option( 'method_enabled' ) );
        if (empty( $enabled_payments[0] )) 
          $enabled_payments[0] = 'credit_card';
        // var_dump($enabled_payments);
        $params['enabled_payments'] = $enabled_payments; // Disable customize payment method from config
  
        if ( strlen( $this->get_option( 'bin_number' ) ) > 0 ){
          // add bin params
          $bins = explode( ',', $this->get_option( 'bin_number' ) );
          $params['credit_card']['whitelist_bins'] = $bins;
        }
        // Empty the cart because payment is initiated.
        $woocommerce->cart->empty_cart();

        try {
          $snapResponse = WC_Midtrans_API::createSnapTransactionHandleDuplicate( $order, $params, $this->id );
        } catch (Exception $e) {
            $this->setLogError( $e->getMessage() );
            WC_Midtrans_Utils::json_print_exception( $e, $this );
          exit();
        }

        if(property_exists($this,'enable_redirect') && $this->enable_redirect == 'yes'){
          $redirectUrl = $snapResponse->redirect_url;
        }else{
          $redirectUrl = $order->get_checkout_payment_url( true )."&snap_token=".$snapResponse->token;
        }

        // Add snap token & snap redirect url to $order metadata
        $order->update_meta_data('_mt_payment_snap_token',$snapResponse->token);
        $order->update_meta_data('_mt_payment_url',$snapResponse->redirect_url);
        $order->save();

        // set wc order's finish_url on user's session cookie
        $this->set_finish_url_user_cookies($order);

        if(property_exists($this,'enable_immediate_reduce_stock') && $this->enable_immediate_reduce_stock == 'yes'){
          wc_reduce_stock_levels($order);
        }

        $successResponse['redirect'] = $redirectUrl;
        return $successResponse;
      }

      /**
       * Hook function that will be called on receipt page
       * Output HTML for Snap payment page. Including `snap.pay()` part
       * @param  string $order_id generated by WC
       * @return string HTML
       */
      function receipt_page( $order_id ) {
        global $woocommerce;
        $pluginName = 'bin_promo';
        require_once(dirname(__FILE__) . '/payment-page.php'); 

      }

      /**
       * @return string
       */
      public function pluginTitle() {
        return "Midtrans Adv: Promo Payment";
      }

      /**
       * @return string
       */
      protected function getDefaultTitle () {
        return __('Credit Card Promo via Midtrans', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getSettingsDescription() {
        return __('Setup specific Midtrans <a href="https://github.com/veritrans/SNAP-Woocommerce/wiki/03--Promo---discount-payment">payment-method based promo</a>, usually only used if you have promo agreement with bank/payment provider (leave it disabled if not sure).', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getDefaultDescription () {
        return __('Credit Card Promo via Midtrans', 'midtrans-woocommerce');
      }

    }
