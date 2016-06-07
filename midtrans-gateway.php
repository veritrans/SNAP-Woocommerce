<?php
/*
Plugin Name: Midtrans - WooCommerce Payment Gateway
Plugin URI: https://github.com/veritrans/veritrans-woocommerce
Description: Accept all payment directly on your WooCommerce site in a seamless and secure checkout environment with <a href="http://midtrans.co.id" target="_blank">Midtrans.co.id</a>
Version: 2.2.3
Author: Midtrans
Author URI: http://midtrans.co.id
License: GPLv2 or later
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

// Make sure we don't expose any info if called directly
add_action( 'plugins_loaded', 'midtrans_gateway_init', 0 );

function midtrans_gateway_init() {

  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  }

  DEFINE ('VT_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );

  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway.php';

  add_filter( 'woocommerce_payment_gateways', 'add_midtrans_payment_gateway' );
}

function add_midtrans_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Midtrans';
  return $methods;
}
