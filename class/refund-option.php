<?php
function refund_option($order_id, $amount, $reason, $dependencies) {
        require_once(dirname(__FILE__) . '/../lib/midtrans/Midtrans.php');
        require_once(dirname(__FILE__) . '/class.midtrans-utils.php');

        \Midtrans\Config::$isProduction = ($dependencies->environment == 'production') ? true : false;
        \Midtrans\Config::$serverKey = (\Midtrans\Config::$isProduction) ? $dependencies->server_key_v2_production : $dependencies->server_key_v2_sandbox;

        $order = wc_get_order( $order_id );
        $params = array(
            'refund_key' => 'RefundID' . $order_id . '-' . current_time('timestamp'),
            'amount' => $amount,
            'reason' => $reason
        );

        try {
          $response = \Midtrans\Transaction::refund($order_id, $params);
        } catch (Exception $e) {
          $error_message = strpos($e->getMessage(), '412') ? $e->getMessage() . ' Note: Refund via Midtrans only for specific payment method, please consult to your midtrans PIC for more information' : $e->getMessage();
          return $error_message;
        }

        if ($response->status_code == 200) {
            $refund_message = sprintf(__('Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'woocommerce-midtrans'), wc_price($response->refund_amount), $response->refund_key, $reason);
            $order->add_order_note($refund_message);
            return $response->status_code;
        }
}