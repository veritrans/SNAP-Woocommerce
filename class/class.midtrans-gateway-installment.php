<?php
    /**
     * Midtrans Payment Online Installment Gateway Class
     * Duplicated from `class.midtrans-gateway.php`
     * Check `class.midtrans-gateway.php` file for proper function comments
     */

     class WC_Gateway_Midtrans_Installment extends WC_Gateway_Midtrans_Abstract {

      /**
       * Constructor
       */
      function __construct() {
        $this->id           = 'midtrans_installment';
        $this->method_title = __( $this->pluginTitle(), 'midtrans-woocommerce' );
        $this->method_description = $this->getSettingsDescription();

        parent::__construct();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );// Payment page hook
      }

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

      function init_form_fields() {
        parent::init_form_fields();
        WC_Midtrans_Utils::array_insert( $this->form_fields, 'enable_3d_secure', array(
          'installment_term' => array(
            'title' => __( 'Installment Terms', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Input the desired Installment Terms. Separate with coma. e.g: 3,6,12', 'midtrans-woocommerce' ),
            'default' => '3,6,9,12,15,18,21,24,27,30,33,36'
          ),
          'installment_bank' => array(
            'title' => __( 'Installment Bank(s)', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Input the desired installment bank(s). Separate with coma for multiple banks. e.g: bni,mandiri,bca', 'midtrans-woocommerce' ),
            'default' => 'bni,mandiri,cimb,danamon,mega,bca,maybank,bri'
          ),
          'min_amount' => array(
            'title' => __( 'Minimal Transaction Amount', 'midtrans-woocommerce'),
            'type' => 'int',
            'label' => __( 'Minimal Transaction Amount', 'midtrans-woocommerce' ),
            'description' => __( 'Minimal transaction amount allowed to be paid with installment. (amount in IDR, without comma or period) example: 500000 </br> if the transaction amount is below this value, customer will be redirected to Credit Card fullpayment page', 'midtrans-woocommerce' ),
            'default' => '500000'
          )
        ));
      }

      function process_payment( $order_id ) {
        global $woocommerce;
        
        // Create the order object
        $order = new WC_Order( $order_id );
        // Get response object template
        $successResponse = $this->getResponseTemplate( $order );
        // Get data for charge to midtrans API
        $params = $this->getPaymentRequestData( $order_id );

        // add credit card payment
        $params['enabled_payments'] = ['credit_card'];
        // add installment params with all possible months & banks
        if($params['transaction_details']['gross_amount'] >= $this->get_option( 'min_amount' )) {
          // Build bank & terms array
          $termsStr = explode(',', $this->get_option( 'installment_term' ));
          $terms = array();
          foreach ($termsStr as $termStr) {
            $terms[] = (int)$termStr;
          };
          $banksStr = explode(',', $this->get_option( 'installment_bank' ));
          foreach ($banksStr as $bankStr) {
            $params['credit_card']['installment']['terms'][$bankStr] = $terms;  
          };
          // Add installment param
          $params['credit_card']['installment']['required'] = true;
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
        $pluginName = 'installment_dragon';
        require_once(dirname(__FILE__) . '/payment-page.php'); 

      }

      /**
       * @return string
       */
      public function pluginTitle() {
        return "Midtrans Adv: Online Installment";
      }

      /**
       * @return string
       */
      protected function getDefaultTitle () {
        return __('Credit Card Installment via Midtrans', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getSettingsDescription() {
        return __('Setup Midtrans card payment with On-Us <a href="https://github.com/veritrans/SNAP-Woocommerce/wiki/02---Credit-card-online-and-offline-installment">Installment(Cicilan) feature</a>, only used if you already have agreement with bank (leave it disabled if not sure).', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getDefaultDescription () {
        return __('Minimal transaction amount allowed for credit card installment is Rp. '.$this->get_option( 'min_amount' ). '</br> You will be redirected to fullpayment page if the transaction amount below this value', 'midtrans-woocommerce');
      }

    }