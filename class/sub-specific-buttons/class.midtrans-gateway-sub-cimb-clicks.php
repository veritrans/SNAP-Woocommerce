<?php
if (! defined('ABSPATH')) { exit; }
/**
 * Class for each sub separated gateway buttons extending Abstract "Sub" class
 */
class WC_Gateway_Midtrans_Sub_CIMB_Clicks extends WC_Gateway_Midtrans_Abstract_Sub {
  function __construct() {
    // used as plugin id
    $this->id = 'midtrans_sub_cimb_clicks';
    // used as Snap enabled_payments params.
    $this->sub_payment_method_params = ['cimb_clicks'];
    // used to display icons on customer side's payment buttons.
    $this->sub_payment_method_image_file_names_str_final = 'cimb_clicks.png';

    parent::__construct();
  }

  public function pluginTitle() {
    return "Midtrans Specific: CIMB OCTO Clicks";
  }
  public function getSettingsDescription() {
    return "Separated payment buttons for this specific the payment methods with its own icons";
  }
  protected function getDefaultTitle () {
    return __('OCTO Clicks', 'midtrans-woocommerce');
  }
  protected function getDefaultDescription () {
    return __('', 'midtrans-woocommerce');
  }
}