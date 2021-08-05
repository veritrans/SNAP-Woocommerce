<?php
if (! defined('ABSPATH')) { exit; }
/**
 * Class for each sub separated gateway buttons extending Abstract "Sub" class
 */
class WC_Gateway_Midtrans_Sub_BCA_VA extends WC_Gateway_Midtrans_Abstract_Sub {
  function __construct() {
    // used as plugin id
    $this->id = 'midtrans_sub_bca_va';
    // used as Snap enabled_payments params.
    $this->sub_payment_method_params = ['bca_va'];
    // used to display icons on customer side's payment buttons.
    $this->sub_payment_method_image_file_names_str_final = 'bca_va.png';

    parent::__construct();
  }

  public function pluginTitle() {
    return "Midtrans Specific: Bank Transfer BCA VA";
  }
  public function getSettingsDescription() {
    return "Separated payment buttons for this specific the payment methods with its own icons";
  }
  protected function getDefaultTitle () {
    return __('Bank Transfer - BCA VA', 'midtrans-woocommerce');
  }
  protected function getDefaultDescription () {
    return __('Only accept transfer from BCA account.', 'midtrans-woocommerce');
  }
}