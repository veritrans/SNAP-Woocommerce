<?php
    /**
     * Midtrans Offline Installment Payment Gateway Class
     * Duplicated from `class.midtrans-gateway.php`
     * Check `class.midtrans-gateway.php` file for proper function comments
     */
    class WC_Gateway_Midtrans_InstallmentOff extends WC_Gateway_Midtrans_Abstract {

      /**
       * Constructor
       */
      function __construct() {
        $this->id           = 'midtrans_installment_offline';
        $this->method_title = __( $this->pluginTitle(), 'midtrans-woocommerce' );
        $this->method_description = $this->getSettingsDescription();
        
        parent::__construct();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
        // Hook for displaying payment page HTML on receipt page
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
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

      /**
       * Initialise Gateway Settings Form Fields
       * Method ini digunakan untuk mengatur halaman konfigurasi admin
       */
      public function init_form_fields() {
        parent::init_form_fields();
        WC_Midtrans_Utils::array_insert( $this->form_fields, 'enable_3d_secure', array(
          'installment_term' => array(
            'title' => __( 'Installment Terms', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Input the desired Installment Terms. Separate with coma. e.g: 3,6,12', 'midtrans-woocommerce' ),
            'default' => '3,6,12'
          ),
          'acquiring_bank' => array(
            'title' => __( 'Acquiring Bank', 'midtrans-woocommerce' ),
            'type' => 'text',
            'description' => __( 'Input the desired acquiring bank. e.g: bni </br>Leave blank if you are not sure', 'midtrans-woocommerce' ),
            'default' => ''
          ),
          'min_amount' => array(
            'title' => __( 'Minimal Transaction Amount', 'midtrans-woocommerce'),
            'type' => 'int',
            'label' => __( 'Minimal Transaction Amount', 'midtrans-woocommerce' ),
            'description' => __( 'Minimal transaction amount allowed to be paid with installment. (amount in IDR, without comma or period) example: 500000 </br> if the transaction amount is below this value, customer will be redirected to Credit Card fullpayment page', 'midtrans-woocommerce' ),
            'default' => '500000'
          ),
          'bin_number' => array(
            'title' => __( 'Allowed CC BINs', 'midtrans-woocommerce'),
            'type' => 'text',
            'label' => __( 'Allowed CC BINs', 'midtrans-woocommerce' ),
            'description' => __( 'Fill with CC BIN numbers (or bank name) that you want to allow to use this payment button. </br> Separate BIN number with coma Example: 4,5,4811,bni,mandiri', 'midtrans-woocommerce' ),
            'default' => ''
          )
        ));
      }

      /**
       * Process the payment based on type.
       * @param  int $order_id
       * @return array $successResponse
       */
      public function process_payment( $order_id ) {
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
        if($params['transaction_details']['gross_amount'] >= $this->get_option( 'min_amount' ))
        {
          // Build bank & terms array
          $termsStr = explode(',', $this->get_option( 'installment_term' ) );
          $terms = array();
          foreach ($termsStr as $termStr) {
            $terms[] = (int)$termStr;
          };

          if (strlen( $this->get_option( 'acquiring_bank' )) > 0){
            $params['credit_card']['bank'] = $this->get_option( 'acquiring_bank' );
          }

          // Add installment param
          $params['credit_card']['installment']['required'] = true;
          $params['credit_card']['installment']['terms'] = 
            array(
              'offline' => $terms
              );
        }

        if (strlen($this->get_option( 'bin_number' )) > 0){
          // add bin params
          $bins = explode(',', $this->get_option( 'bin_number' ));
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

        // If `enable_redirect` admin config used, snap redirect
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
          // Reduce item stock on WC, item also auto reduced on order `pending` status changes
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
      public function receipt_page( $order_id ) {
        global $woocommerce;
        $pluginName = 'installment_offline';
        // Separated as Shared PHP included by multiple class
        require_once(dirname(__FILE__) . '/payment-page.php');
      }

      /**
       * @return string
       */
      public function pluginTitle() {
        return "Midtrans Adv: Offline Installment";
      }

      /**
       * @return string
       */
      protected function getDefaultTitle () {
        return __('Credit Card Installment for other bank via Midtrans', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getSettingsDescription() {
        return __('Setup Midtrans card payment with Off-Us <a href="https://github.com/veritrans/SNAP-Woocommerce/wiki/02---Credit-card-online-and-offline-installment">Installment(Cicilan) feature</a>, only used if you already have agreement with bank (leave it disabled if not sure).', 'midtrans-woocommerce');
      }

      /**
       * @return string
       */
      protected function getDefaultDescription () {
        return __('Minimal transaction amount allowed for credit card installment is Rp. ' . $this->get_option( 'min_amount' ) . '</br> You will be redirected to fullpayment page if the transaction amount below this value', 'midtrans-woocommerce');
      }

    }
