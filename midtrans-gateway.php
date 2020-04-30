<?php
/*
Plugin Name: Midtrans - WooCommerce Payment Gateway
Plugin URI: https://github.com/veritrans/SNAP-Woocommerce
Description: Accept all payment directly on your WooCommerce site in a seamless and 
secure checkout environment with <a href="http://midtrans.co.id" target="_blank">Midtrans.co.id</a>
Version: 2.18.2
Author: Midtrans
Author URI: http://midtrans.co.id
License: GPLv2 or later
WC requires at least: 2.0.0
WC tested up to: 4.0.1
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

  /**
   * ### Midtrans Payment Plugin for Wordrpress-WooCommerce ###
   *
   * This plugin allow your Wordrpress-WooCommerce to accept payment from customer using Midtrans Payment Gateway solution.
   *
   * @category   Wordrpress-WooCommerce Payment Plugin
   * @author     Rizda Dwi Prasetya <rizda.prasetya@midtrans.com>
   * @link       http://docs.midtrans.com
   * (This plugin is made based on Payment Plugin Template by WooCommerce)
   */

// Make sure we don't expose any info if called directly
add_action( 'plugins_loaded', 'midtrans_gateway_init', 0 );

function midtrans_gateway_init() {

  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  }

  DEFINE ('MT_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
  DEFINE ('MT_PLUGIN_VERSION', get_file_data(__FILE__, array('Version' => 'Version'), false)['Version'] );

  require_once dirname( __FILE__ ) . '/lib/midtrans/Midtrans.php';
  require_once dirname( __FILE__ ) . '/abstract/abstract.midtrans-gateway.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-notif-handler.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-api.php';

  require_once dirname( __FILE__ ) . '/class/class.midtrans-utils.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-logger.php';

  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-paymentrequest.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-installment.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-installmentoff.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-promo.php';
  // Add this payment method if WooCommerce Subscriptions plugin activated
  if( class_exists( 'WC_Subscriptions' ) ) require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-subscription.php';

  add_filter( 'woocommerce_payment_gateways', 'add_midtrans_payment_gateway' );
}

function add_midtrans_payment_gateway( $methods ) {
  /**
   * Payment methods are separated as different method/class so it will be separated
   * as different payment button. This is needed because each of 'feature' like Promo,
   * require special backend treatment (i.e. applying discount and locking payment channel). 
   * Especially Offline Installment, it requires `whitelist_bins` so it should not be combined 
   * with other payment feature.
   */
  $methods[] = 'WC_Gateway_Midtrans';
  $methods[] = 'WC_Gateway_Midtrans_Paymentrequest';
  $methods[] = 'WC_Gateway_Midtrans_Installment';
  $methods[] = 'WC_Gateway_Midtrans_InstallmentOff';
  $methods[] = 'WC_Gateway_Midtrans_Promo';
  // Add this payment method if WooCommerce Subscriptions plugin activated
  if( class_exists( 'WC_Subscriptions' ) ) $methods[] = 'WC_Gateway_Midtrans_Subscription';
  return $methods;
}
/**
 * BCA Klikpay, CIMB Clicks, and other direct banking payment channel will need finish url
 * to handle redirect after payment complete, especially BCA, may require custom finish url
 * required by BCA team as UAT process.
 */
function handle_finish_url_page()
{
  if(is_page('payment-finish')){ 
    include(dirname(__FILE__) . '/class/finish-url-page.php');
    die();
  }
}
add_action( 'wp', 'handle_finish_url_page' );

/**
 * Handle a custom '_mt_payment_transaction_id' query var to get orders with the '_mt_payment_transaction_id' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function handle_custom_query_var( $query, $query_vars ) {
	if ( ! empty( $query_vars['_mt_payment_transaction_id'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_mt_payment_transaction_id',
			'value' => esc_attr( $query_vars['_mt_payment_transaction_id'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2 );