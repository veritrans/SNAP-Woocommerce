<?php
/*
Plugin Name: Midtrans - WooCommerce Payment Gateway
Plugin URI: https://github.com/veritrans/SNAP-Woocommerce
Description: Accept all payment directly on your WooCommerce site in a seamless and secure checkout environment with <a href="http://midtrans.co.id" target="_blank">Midtrans.co.id</a>
Version: 2.4.0
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

  /**
   * ### Midtrans Payment Plugin for Wordrpress-WooCommerce ###
   *
   * This plugin allow your Wordrpress-WooCommerce to accept payment from customer using Midtrans Payment Gateway solution.
   *
   * @category   Wordrpress-WooCommerce Payment Plugin
   * @author     Rizda Dwi Prasetya <rizda.prasetya@midtrans.com>
   * @version    2.3.0
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

  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-installment.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-installmentoff.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-installmentmigs.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-migs.php';
  require_once dirname( __FILE__ ) . '/class/class.midtrans-gateway-promo.php';

  add_filter( 'woocommerce_payment_gateways', 'add_midtrans_payment_gateway' );
}

function add_midtrans_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Midtrans';
  $methods[] = 'WC_Gateway_Midtrans_Installment';
  $methods[] = 'WC_Gateway_Midtrans_InstallmentOff';
  $methods[] = 'WC_Gateway_Midtrans_InstallmentMIGS';
  $methods[] = 'WC_Gateway_Midtrans_MIGS';
  $methods[] = 'WC_Gateway_Midtrans_Promo';
  return $methods;
}
