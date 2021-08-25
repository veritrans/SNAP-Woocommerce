<?php
    /**
     * Midtrans Payment Request Gateway Class
     * Duplicated from `class.midtrans-gateway.php`
     * Check `class.midtrans-gateway.php` file for proper function comments
     *
     * Developed specifically for Chrome Payment Request API
     * In collaboration with Google Dev representative
     * Which function as Browser level credit card storage function
     */
    class WC_Gateway_Midtrans_Paymentrequest extends WC_Gateway_Midtrans_Abstract {

      /**
       * Constructor
       */
      function __construct() {
        $this->id           = 'midtrans_paymentrequest';
        $this->method_title = __( $this->pluginTitle(), 'midtrans-woocommerce' );
        $this->method_description = $this->getSettingsDescription();

        parent::__construct();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );// Payment page hook
      }

      public function admin_options() { ?>
        <h3><?php _e( $this->pluginTitle(), 'midtrans-woocommerce' ); ?></h3>
        <p><?php _e('Allows credit card payments using Midtrans.', 'midtrans-woocommerce' ); ?></p>
        <table class="form-table">
          <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
          ?>
        </table><!--/.form-table-->
        <?php
      }

      function init_form_fields() {
        parent::init_form_fields();
        WC_Midtrans_Utils::array_insert( $this->form_fields, 'enable_3d_secure', array(
          'acquring_bank' => array(
            'title' => __( 'Acquiring Bank', 'midtrans-woocommerce'),
            'type' => 'text',
            'label' => __( 'Acquiring Bank', 'midtrans-woocommerce' ),
            'description' => __( 'You should leave it empty, it will be auto configured. </br> Alternatively may specify your card-payment acquiring bank for this payment option. </br> Options: BCA, BRI, DANAMON, MAYBANK, BNI, MANDIRI, CIMB, etc (Only choose 1 bank).' , 'midtrans-woocommerce' ),
            'default' => ''
          ),
          'bin_number' => array(
            'title' => __( 'Allowed CC BINs', 'midtrans-woocommerce'),
            'type' => 'text',
            'label' => __( 'Allowed CC BINs', 'midtrans-woocommerce' ),
            'description' => __( 'Leave this blank if you dont understand!</br> Fill with CC BIN numbers (or bank name) that you want to allow to use this payment button. </br> Separate BIN number with coma Example: 4,5,4811,bni,mandiri', 'midtrans-woocommerce' ),
            'default' => ''
          )
        ));
      }

      function process_payment( $order_id ) {
        global $woocommerce;
        
        //create the order object
        $order = new WC_Order( $order_id );
        // Get response object template
        $successResponse = $this->getResponseTemplate( $order );
        // Get data for charge to midtrans API
        $params = $this->getPaymentRequestData( $order_id );

        // add credit card payment
        $params['enabled_payments'] = ['credit_card'];

        // add bank & channel migs params
        if (strlen($this->get_option('acquring_bank')) > 0)
          $params['credit_card']['bank'] = strtoupper ($this->get_option('acquring_bank'));

        if (strlen($this->get_option('bin_number')) > 0){
          // add bin params
          $bins = explode(',', $this->get_option('bin_number'));
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

      function receipt_page( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $gross_amount = $order->get_total();
        $pluginName = 'cc_paymentrequest';
        require_once(dirname(__FILE__) . '/payment-page.php'); 
      }

      /**
       * @return string
       */
      public function pluginTitle() {
        return "Midtrans Adv: Card in-Browser Payment UI";
      }

      /**
       * @return string
       */
      protected function getDefaultTitle () {
        return __('Credit Card Payment via Midtrans', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getSettingsDescription() {
        return __('Alternative Card Payment form using in-browser payment UI (leave it disabled if not sure).', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getDefaultDescription () {
        return __('Pay with Credit Card', 'midtrans-woocommerce');
      }
    }
