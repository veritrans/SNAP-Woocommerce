<?php 
/**
 * Customizable payment finish page, for internet banking channel, mainly BCA KlikPay
 * Output HTML that display payment result of an internet banking transactions
 * based on order/transaction id found in GET/POST request
 */

// reference: https://www.cloudways.com/blog/creating-custom-page-template-in-wordpress/
require_once(dirname(__FILE__) . '/class.midtrans-gateway.php');
$mt = new WC_Gateway_Midtrans();
$isProduction = ($mt->environment == 'production') ? true : false;

require_once(dirname(__FILE__) . '/../lib/veritrans/Veritrans.php');
Veritrans_Config::$isProduction = $isProduction;
Veritrans_Config::$serverKey = $isProduction ? $mt->server_key_v2_production : $mt->server_key_v2_sandbox ;
try {
	if(isset($_GET['id'])){ // handler for BCA_Klikpay finish redirect
		$midtrans_notification = Veritrans_Transaction::status($_GET['id']);	
	}else if(isset($_POST['response'])){ // handler for CIMB CLICKS finish redirect
		$response = preg_replace('/\\\\/', '', $_POST['response']);		
		$midtrans_notification = json_decode($response);	
	}
} catch (Exception $e) {
	error_log('Failed to do Midtrans Get Status: '.print_r($e,true));
	$midtrans_notification = new stdClass();
	$midtrans_notification->transaction_status = 'not found';
}

// OR uncomment this to redirect it to midtrans plugin callback handler
// echo "loading... <script>window.location = '".get_site_url(null, '/')."?wc-api=WC_Gateway_Midtrans&id=".$_GET['id']."'</script>";

get_header(); // WP Header
?>
 
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        // Customize this part
		if($midtrans_notification->transaction_status == 'settlement'){
			// TODO implement what to do when payment success
			?> 
				<h3>Payment Success!</h3>
				<hr>
				<p>We have received your payment, your order is being processed. Thank you!</p>
			<?php
		}else if($midtrans_notification->transaction_status == 'pending'){
			// TODO implement what to do when payment pending
			?> 
				<h3>Order is Awaiting Your Payment</h3>
				<hr>
				<p>Please complete the payment as instructed earlier. Thank you!</p>
			<?php
		}else{
			// TODO implement what to do when payment failed
			?> 
				<h3>Payment Is Not Received</h3>
				<hr>
				<p>Your payment is not yet completed. Please complete your payment or do another checkout. Thank you!</p>
			<?php
		}
        ?>
    </main><!-- .site-main -->
</div><!-- .content-area -->

<!-- WP Sidebar & Footer -->
<?php 
// get_sidebar(); // uncomment this if you need sidebar
get_footer(); 
?>