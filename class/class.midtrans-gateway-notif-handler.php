<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Midtrans_Notif_Handler class.
 * Handles responses from Midtrans Notification.
 */
class WC_Gateway_Midtrans_Notif_Handler
// extends WC_Gateway_ 
{
	/**
	 * Constructor.
	 *
	 * @param bool   $is_production Use production or not.
	 * @param string $server_key ServerKey to receive HTTP notification from Midtrans.
	 */
	public function __construct() {
    // Register hook for handling HTTP notification (HTTP call to `http://[your web]/?wc-api=WC_Gateway_Midtrans`)
		add_action( 'woocommerce_api_wc_gateway_midtrans', array( $this, 'handleMidtransNotificationRequest' ) );
    // Create action to be called when HTTP notification is valid
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
      die('This endpoint should not be opened using browser (HTTP GET). This endpoint is for Midtrans notification URL (HTTP POST)');
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

    // check whether the request is POST or GET, 
    // if request == POST, request is for payment notification, then update the payment status
    if(empty($sanitized['order_id']) && empty($sanitizedPost['id']) && empty($sanitized['id']) && empty($sanitizedPost['response'])) {    // Check if POST, then create new notification
      $raw_notification = $this->doEarlyAckResponse();
      // Handle pdf url update
      $this->handlePendingPaymentPdfUrlUpdate();
      // Get WooCommerce order
      $wcorder = wc_get_order( $raw_notification['order_id'] );
      // exit if the order id doesn't exist in WooCommerce dashboard
      if (!$wcorder) {
        WC_Midtrans_Logger::log( 'Can\'t find order id' . $raw_notification['order_id'] . ' on WooCommerce dashboard', 'midtrans-error' );
        exit;
      }
      // Get plugin id 
      else $plugin_id = $wcorder->get_payment_method();
      // Verify Midtrans notification
      $midtrans_notification = WC_Midtrans_API::getMidtransNotif( $plugin_id );
      // If notification verified, handle it
      if (in_array($midtrans_notification->status_code, array(200, 201, 202, 407))) {
        if (wc_get_order($midtrans_notification->order_id) != false) {
          do_action( "midtrans-handle-valid-notification", $midtrans_notification );
        }
      }
      exit;
    }
    // if request == GET, request is for finish OR failed URL, then redirect to WooCommerce's order complete/failed
    else { 
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
        $order = new WC_Order( $order_id );
        wp_redirect($order->get_checkout_order_received_url());
      } 
      // if or pending/challenge
      else if( !empty($sanitized['order_id']) && !empty($sanitized['transaction_status']) && $sanitized['status_code'] == 201)  {
        // @FIXME: $this->ignore_pending_status is broken, it doesn't refer to plugin class
        if(property_exists($this,'ignore_pending_status') && $this->ignore_pending_status == 'yes'){
          wp_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
          exit;
        }
        $order_id = $sanitized['order_id'];
        $order = new WC_Order( $order_id );
        wp_redirect($order->get_checkout_order_received_url());
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
        $order = new WC_Order( $responses['order_id'] );
        // if async payment paid
        if ( $responses['status_code'] == 200) { 
          wp_redirect($order->get_checkout_order_received_url());
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
        $order_id = $midtrans_notification->order_id;
        // if async payment paid
        if ($midtrans_notification->transaction_status == 'settlement'){
          $order = new WC_Order( $order_id );
          wp_redirect($order->get_checkout_order_received_url());              
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
   * UNUSED
   * @TODO: Evaluate if this still required/used
   * Handle API call from payment page to update order with PDF instruction Url
   * @return void
   */
  public function handlePendingPaymentPdfUrlUpdate(){
    try {
      global $woocommerce;
      $requestObj = json_decode(file_get_contents("php://input"), true);
      if( !isset($requestObj['pdf_url_update']) || 
          !isset($requestObj['snap_token_id']) ){
        return;
      }
        // @FIXME: $this is broken, it doesn't refer to plugin class
      $snapApiBaseUrl = ($this->environment) ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
      $tokenStatusUrl = $snapApiBaseUrl.'/snap/v1/transactions/'.$requestObj['snap_token_id'].'/status';
      $tokenStatusResponse = wp_remote_get( $tokenStatusUrl);
      $tokenStatus = json_decode($tokenStatusResponse['body'], true);
      $paymentStatus = $tokenStatus['transaction_status'];
      $order = new WC_Order( $tokenStatus['order_id'] );
      $orderStatus = $order->get_status();

      // update order status to on-hold if current status is "pending payment"
      if($orderStatus == 'pending' && $paymentStatus == 'pending'){
        $order->update_status('on-hold',__('Midtrans onPending Callback received','midtrans-woocommerce'));

      }
      if( !isset($tokenStatus['pdf_url']) ){
        return;
      }

      // store Url as $Order metadata
      $order->update_meta_data('_mt_payment_pdf_url',$tokenStatus['pdf_url']);
      $order->save();

      echo esc_html("OK");
      // immediately terminate notif handling, not a notification.
      exit();
    } catch (Exception $e) {
      // var_dump($e); 
      // exit();
    }
  }

  /**
   * Handle Midtrans Notification Object, after payment status changes on Midtrans
   * Will update WC payment status accordingly
   * @param  [Object] $midtrans_notification Object representation of Midtrans JSON
   * notification
   * @return void
   */
  public function handleMidtransValidNotificationRequest( $midtrans_notification ) {
    global $woocommerce;

    $order = new WC_Order( $midtrans_notification->order_id );
    $order->add_order_note(__('Midtrans HTTP notification received: '.$midtrans_notification->transaction_status.'. Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    $order_id = $midtrans_notification->order_id;

    if ($midtrans_notification->transaction_status == 'capture') {
      if ($midtrans_notification->fraud_status == 'accept') {
        // Procces subscription transaction if contains subsctription
        if( class_exists( 'WC_Subscriptions' ) ){
          $this->checkAndHandleWCSubscriptionTxnNotif( $midtrans_notification, $order );
        }
        $order->payment_complete();
        $order->add_order_note(__('Midtrans payment completed: capture. Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));

      }
      else if ($midtrans_notification->fraud_status == 'challenge') {
        $order->update_status('on-hold',__('Challanged payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
      }
    }
    else if ($midtrans_notification->transaction_status == 'cancel') {
      $order->update_status('cancelled',__('Cancelled payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'expire') {
      $order->update_status('cancelled',__('Expired payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'deny') {
      // do nothing on deny, allow payment retries
      // $order->update_status('failed',__('Denied payment: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
    }
    else if ($midtrans_notification->transaction_status == 'settlement') {
      if($midtrans_notification->payment_type != 'credit_card'){
        $order->payment_complete();
        $order->add_order_note(__('Midtrans payment completed: settlement. Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
      }
    }
    else if ($midtrans_notification->transaction_status == 'pending') {
      // Store snap token & snap redirect url to $order metadata
      $order->update_meta_data('_mt_payment_transaction_id',$midtrans_notification->transaction_id);
      $order->save();

      if(property_exists($this,'ignore_pending_status') && $this->ignore_pending_status == 'yes'){
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
    // Validate the refund doesn't charge twice by the refund last index
    $order_notes = wc_get_order_notes(array('order_id' => $midtrans_notification->order_id));
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
    // Process if this is a subscription transaction
    if ( wcs_order_contains_subscription( $midtrans_notification->order_id ) || wcs_is_subscription( $midtrans_notification->order_id ) || wcs_order_contains_renewal( $midtrans_notification->order_id ) ) {
      // if not subscription and wc status pending, don't process (because that's a recurring transaction)
      if ( wcs_order_contains_renewal( $midtrans_notification->order_id) && $order->get_status() == 'pending' ) {
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