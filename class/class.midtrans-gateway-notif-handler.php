<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Midtrans_Notif_Handler class.
 * Handles responses from Midtrans Notification.
 * @todo : refactor, this shouldn't be a class
 * maybe just a bunch of function to include in main class
 * to avoid complex param & config value passing
 */
class WC_Gateway_Midtrans_Notif_Handler
// extends WC_Gateway_ 
{
	/**
	 * Constructor.
	 * 
	 */
	public function __construct() {
    // Register hook for handling HTTP notification (HTTP call to `http://[your web]/?wc-api=WC_Gateway_Midtrans`)
		add_action( 'woocommerce_api_wc_gateway_midtrans', array( $this, 'handleMidtransNotificationRequest' ) );
    // Create action to be called when HTTP notification is valid
    // @TODO: rename this hook to use snake_case format
    add_action( 'midtrans-handle-valid-notification', array( $this, 'handleMidtransValidNotificationRequest' ) );
  }
    
  /**
   * Helper to response Response early with HTTP 200 for Midtrans notification
   * So Notification Engine can mark notification complete early and faster
   * Also reject HTTP GET request
   * @return void
   */
  public function doEarlyAckResponse() {
    if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
      die('This endpoint is for Midtrans notification URL (HTTP POST). This message will be shown if opened using browser (HTTP GET). You can copy this current URL on your browser address bar and paste it to: "Midtrans Dashboard > Settings > Configuration > Notification Url". This will allow your WooCommerce to receive Midtrans payment status, which will auto sync the payment status.');
      exit();
    }

    ob_start();
    $input_source = "php://input";
    $raw_notification = json_decode(file_get_contents($input_source), true);
    echo esc_html("Notification Received: \n");
    print_r($raw_notification);
    WC_Midtrans_Logger::log( print_r($raw_notification, true), 'midtrans-notif' );
    header('Connection: close');
    header('Content-Length: '.ob_get_length());
    ob_end_flush();
    ob_flush();
    flush();
    return $raw_notification;
  }

  /**
   * Redirect transacting-user to the finish url set when they were checking out
   * if they were the authorized transacting-user, they will have the finish_url on cookie
   * @TAG: finish_url_user_cookies
   */
  public function checkAndRedirectUserToFinishUrl(){
    if(isset($_COOKIE['wc_midtrans_last_order_finish_url'])){
      // authorized transacting-user
      wp_redirect($_COOKIE['wc_midtrans_last_order_finish_url']);
    }else{
      // else, unauthorized user, redirect to shop homepage by default.
      wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
    }
  }

  /**
   * getPluginOptions
   * @param  string $plugin_id plugin id of the paid order
   * @return array  plugin options
   */
  public function getPluginOptions($plugin_id = 'midtrans'){
    // Get current plugin options
    $plugin_options = array();
    try {
      $plugin_options = get_option( 'woocommerce_' . $plugin_id . '_settings' );
    } catch (Exception $e) {
      WC_Midtrans_Logger::log( 'Fail to getPluginOptions', 'midtrans-error' );
    };
    return $plugin_options;
  }

  /**
   * Called by hook function when HTTP notification / API call received
   * Handle Midtrans payment notification
   */
  public function handleMidtransNotificationRequest() {
    @ob_clean();
    global $woocommerce;

    $sanitized = [];
    $sanitized['order_id'] = 
      isset($_GET['order_id'])? sanitize_text_field($_GET['order_id']): null;
    $sanitized['id'] = 
      isset($_GET['id'])? sanitize_text_field($_GET['id']): null;
    $sanitizedPost = [];
    $sanitizedPost['id'] = 
      isset($_POST['id'])? sanitize_text_field($_POST['id']): null;
    $sanitizedPost['response'] = 
      isset($_POST['response'])? sanitize_text_field($_POST['response']): null;

    // @TAG: order-id-suffix-handling
    $sanitized['order_id'] = 
      WC_Midtrans_Utils::check_and_restore_original_order_id($sanitized['order_id']);

    // check whether the request is POST or GET, 
    // @TODO: refactor this conditions, this doesn't quite represent conditions for a POST request
    if(empty($sanitized['order_id']) && empty($sanitizedPost['id']) && empty($sanitized['id']) && empty($sanitizedPost['response'])) { 
      // Request is POST, proceed to create new notification, then update the payment status
      $raw_notification = $this->doEarlyAckResponse();
      
      // @TAG: order-id-suffix-handling
      $raw_notification['order_id'] = 
        WC_Midtrans_Utils::check_and_restore_original_order_id($raw_notification['order_id']);
      // Get WooCommerce order
      $wcorder = wc_get_order( $raw_notification['order_id'] );
      // exit if the order id doesn't exist in WooCommerce dashboard
      if (!$wcorder) {
        WC_Midtrans_Logger::log( 'Can\'t find order id' . $raw_notification['order_id'] . ' on WooCommerce dashboard', 'midtrans-error' );
        exit;
      }
      // Get current plugin id 
      else $plugin_id = $wcorder->get_payment_method();
      if(strpos($plugin_id, 'midtrans_sub') !== false){
        // for sub separated gateway buttons, use main gateway plugin id instead
        $plugin_id = 'midtrans';
      }
      // Verify Midtrans notification
      $midtrans_notification = WC_Midtrans_API::getStatusFromMidtransNotif( $plugin_id );
      // If notification verified, handle it
      if (in_array($midtrans_notification->status_code, array(200, 201, 202, 407))) {
        // @TAG: order-id-suffix-handling
        $order_id = 
          WC_Midtrans_Utils::check_and_restore_original_order_id($midtrans_notification->order_id);
        // @TODO: relocate this check into the function itself, to prevent unnecessary double DB query load
        if (wc_get_order($order_id) != false) {
          // @TODO: rename this hook to use snake_case format
          do_action( "midtrans-handle-valid-notification", $midtrans_notification, $plugin_id );
        }
      }
      exit;
    }
    else { 
      // The request == GET, this will handle redirect url from Snap finish OR failed, proceed to redirect to WooCommerce's order complete/failed page
      $sanitized['transaction_status'] = 
        isset($_GET['transaction_status'])? sanitize_text_field($_GET['transaction_status']): null;
      $sanitized['status_code'] = 
        isset($_GET['status_code'])? sanitize_text_field($_GET['status_code']): null;
      $sanitized['wc-api'] = 
        isset($_GET['wc-api'])? sanitize_text_field($_GET['wc-api']): null;

      // if capture/settlement, redirect to order received page
      if( !empty($sanitized['order_id']) && !empty($sanitized['status_code']) && $sanitized['status_code'] <= 200)  {
        $order_id = $sanitized['order_id'];
        // error_log($this->get_return_url( $order )); //debug
        $this->checkAndRedirectUserToFinishUrl();
      } 
      // if or pending/challenge
      else if( !empty($sanitized['order_id']) && !empty($sanitized['transaction_status']) && $sanitized['status_code'] == 201)  {
        try {
          $order_id = $sanitized['order_id'];
          $order = new WC_Order( $order_id );
          $plugin_id = $order->get_payment_method();

          $plugin_options = $this->getPluginOptions($plugin_id);
          if( array_key_exists('ignore_pending_status',$plugin_options)
            && $plugin_options['ignore_pending_status'] == 'yes'
          ){
            wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
            exit;
          }
        } catch (Exception $e) { } // catch if order not exist on WC
        $this->checkAndRedirectUserToFinishUrl();
      } 
      //if deny, redirect to order checkout page again
      else if( !empty($sanitized['order_id']) && !empty($sanitized['transaction_status']) && $sanitized['status_code'] >= 202){
        wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
      } 
      // if customer click "back" button, redirect to checkout page again
      else if( !empty($sanitized['order_id']) && empty($sanitized['transaction_status'])){ 
        wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
      // if customer redirected from async payment with POST `response` (CIMB clicks, etc)
      } else if ( !empty($sanitizedPost['response']) ){ 
        $responses = json_decode( stripslashes($sanitizedPost['response']), true);

        // @TAG: order-id-suffix-handling
        $responses['order_id'] = 
          WC_Midtrans_Utils::check_and_restore_original_order_id($responses['order_id']);
        $order = new WC_Order( $responses['order_id'] );
        // if async payment paid
        if ( $responses['status_code'] == 200) { 
          $this->checkAndRedirectUserToFinishUrl();
        } 
        // if async payment not paid
        else {
          wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
        }
      // if customer redirected from async payment with GET `id` (BCA klikpay, etc)
      } else if (!empty($sanitized['id']) || (!empty($sanitized['wc-api']) && strlen($sanitized['wc-api']) >= 25) ){
        // Workaround if id query string is malformed, manual substring
        if (!empty($sanitized['wc-api']) && strlen($sanitized['wc-api']) >= 25) {
          $id = str_replace("WC_Gateway_Midtrans?id=", "", $sanitized['wc-api']);
        }
        // else if id query string format is correct
        else {
          $id = $sanitized['id'];
        }
        // @TODO: fix this bug, $sanitized['id'] is transaction_id, which is unknown to WC
        // But actually, BCA Klikpay already handled on finish-url-page.php, evaluate if this still needed
        $plugin_id = wc_get_order( $sanitized['id'] )->get_payment_method();
        $midtrans_notification = WC_Midtrans_API::getMidtransStatus($id, $plugin_id);

        // @TODO remove this order_id? seems unused
        // @TAG: order-id-suffix-handling
        $order_id = 
          WC_Midtrans_Utils::check_and_restore_original_order_id($midtrans_notification->order_id);
        // if async payment paid
        if ($midtrans_notification->transaction_status == 'settlement'){
          $this->checkAndRedirectUserToFinishUrl();
        } 
        // if async payment not paid
        else {
          wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
        }
      } 
      // if unhandled case, fallback, redirect to home
      else {
        wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
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
  public function handleMidtransValidNotificationRequest( $midtrans_notification, $plugin_id = 'midtrans' ) {
    global $woocommerce;
    // @TAG: order-id-suffix-handling
    $order_id = WC_Midtrans_Utils::check_and_restore_original_order_id($midtrans_notification->order_id);
    $order = new WC_Order( $order_id );
    $order->add_order_note(__('Midtrans HTTP notification received: '.$midtrans_notification->transaction_status.'. Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));

    $order->update_meta_data('_mt_payment_type',$midtrans_notification->payment_type);
    $order->save();
    // allow merchant-defined custom action function to perform action on $order upon notif handling
    do_action( 'midtrans_on_notification_received', $order, $midtrans_notification );
    
    /**
     * @TODO: maybe refactor this if-else branch, store the condition into variable e.g: 
     * $is_payment_success = this or that etc. 
     * $is_payment_failed, $is_payment_pending, $is_payment_refund.
     * So the if-else branch only need to check from those var, instead of directly checking the condition.
     */
    if ( $midtrans_notification->transaction_status == 'settlement'
      || ($midtrans_notification->transaction_status == 'capture' && $midtrans_notification->fraud_status == 'accept') ) {
      // success scenario of payment paid

      // Procces subscription transaction if contains subsctription for card transaction
      if( $midtrans_notification->transaction_status == 'capture' && class_exists( 'WC_Subscriptions' ) ){
        $this->checkAndHandleWCSubscriptionTxnNotif( $midtrans_notification, $order );
      }
      $order->payment_complete($midtrans_notification->transaction_id);
      $order->add_order_note(__('Midtrans payment completed: '.$midtrans_notification->transaction_status.'. Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
      // allow merchant-defined custom action function to perform action on $order
      do_action( 'midtrans_after_notification_payment_complete', 
        $order, $midtrans_notification );

      // apply custom order status mapping coming from custom_payment_complete_status config value
      $plugin_options = $this->getPluginOptions($plugin_id);
      if( array_key_exists('custom_payment_complete_status',$plugin_options)
          && $plugin_options['custom_payment_complete_status'] !== 'default'
        ){
        $order->update_status(
          $plugin_options['custom_payment_complete_status'],
          __('Status auto-updated via custom status mapping config: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce')
        );
      }
    }
    else if ($midtrans_notification->transaction_status == 'capture' && $midtrans_notification->fraud_status == 'challenge') {
      $order->update_status('on-hold',__('Challanged payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'cancel') {
      $order->update_status('cancelled',__('Cancelled payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'expire') {
      if ($midtrans_notification->payment_type == 'credit_card'){
        // do nothing on card status expire (happen if 3DS abandoned), allow payment retries
        // @NOTE: but it may means card txn, will never notif-based cancel-order trigger to WC.
        // Fortunately WC seems to have auto cancel-order built in.
      } else {
        $order->update_status('cancelled',__('Expired payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
      }
    }
    else if ($midtrans_notification->transaction_status == 'deny') {
      // do nothing on deny, allow payment retries
      // $order->update_status('failed',__('Denied payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'pending') {
      // Store snap token & snap redirect url to $order metadata
      $order->update_meta_data('_mt_payment_transaction_id',$midtrans_notification->transaction_id);
      $order->save();

      $plugin_options = $this->getPluginOptions($plugin_id);
      if( array_key_exists('ignore_pending_status',$plugin_options)
          && $plugin_options['ignore_pending_status'] == 'yes'
        ){
        exit;
      }
      $order->update_status('on-hold',__('Awaiting payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'refund' || $midtrans_notification->transaction_status == 'partial_refund') {
      $refund_request = $this->validateRefundNotif( $midtrans_notification );
      if ( ! $refund_request ) exit;
      try {
        do_action( "create-refund-request", $midtrans_notification->order_id, $refund_request->refund_amount, $refund_request->reason, $midtrans_notification->transaction_status == 'refund' ? true : false );
        // Create refund note
        $order->add_order_note(sprintf(__('Refunded payment: Midtrans-' . $midtrans_notification->payment_type . ' Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'woocommerce-midtrans'), wc_price($refund_request->refund_amount), $refund_request->refund_key, $refund_request->reason));
      } catch (Exception $e) {
          WC_Midtrans_Logger::log( $e->getMessage(), 'midtrans-error' );
      }
    }
    exit;
  }

  /**
   * Validate Midtrans Refund Notification Object
   * @param  [Object] $midtrans_notification Object representation of Midtrans Refund JSON notification
   * @return object||bool
   */
  public function validateRefundNotif( $midtrans_notification ) {
    // Get the raw post notification
    $input_source = "php://input";
    $raw_notification = json_decode(file_get_contents($input_source), true);
    // Fetch last array index
    $lastArrayIndex = count($midtrans_notification->refunds) - 1;
    // Do not process if the notif contain 'bank_confirmed_at'
    if (isset($raw_notification['refunds'][$lastArrayIndex]['bank_confirmed_at'])) {
      return false;
    }

    $refund_request = $midtrans_notification->refunds[$lastArrayIndex];
    // @TAG: order-id-suffix-handling
    $order_id = 
      WC_Midtrans_Utils::check_and_restore_original_order_id($midtrans_notification->order_id);
    // Validate the refund doesn't charge twice by the refund last index
    $order_notes = wc_get_order_notes(array('order_id' => $order_id));
    foreach($order_notes as $value) {
      if (strpos($value->content, $refund_request->refund_key ) !== false) {
        return false;
      }
    }
    return $refund_request;
  }

  /**
   * Process subscription transaction if contains one of those
   * 
   * @param [Object] $midtrans_notification Object representation of Midtrans JSON notification
   * @param WC_Order $order 
   * @return void
   */
  public function checkAndHandleWCSubscriptionTxnNotif( $midtrans_notification, $order ) {
    // @TAG: order-id-suffix-handling
    $order_id = 
      WC_Midtrans_Utils::check_and_restore_original_order_id($midtrans_notification->order_id);
    // Process if this is a subscription transaction
    if ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) {
      // if not subscription and wc status pending, don't process (because that's a recurring transaction)
      if ( wcs_order_contains_renewal( $order_id) && $order->get_status() == 'pending' ) {
        return false;
      }
        $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'any' ) );
        foreach ( $subscriptions as $subscription ) {
            // Store card token to meta if customer choose save card on previous payment
            if ($midtrans_notification->saved_token_id ) {
              $subscription->update_meta_data('_mt_subscription_card_token',$midtrans_notification->saved_token_id);
              $subscription->save();
            }
            // Customer didn't choose save card option on previous payment
            else {
              $subscription->add_order_note( __( 'Customer didn\'t tick <b>Save Card Info</b>. <br>The next payment on ' . $subscription->get_date('next_payment', 'site') . ' will fail.', 'midtrans-woocommerce'), 1 );
              $order->add_order_note( __('Customer didn\'t tick <b>Save Card Info</b>, next payment will fail', 'midtrans-woocommerce'), 1 );
              $subscription->update_meta_data('_mt_subscription_card_token',$midtrans_notification->saved_token_id);
              $subscription->save();
            }
        }
    }
  }

}