<?php
if (! defined('ABSPATH')) { exit; }
/**
 * Class for each sub separated gateway buttons extending Abstract "Sub" class
 */
class WC_Gateway_Midtrans_Sub_Akulaku extends WC_Gateway_Midtrans_Abstract_Sub {
  function __construct() {
    // used as plugin id
    $this->id = 'midtrans_sub_akulaku';
    // used as Snap enabled_payments params.
    $this->sub_payment_method_params = ['akulaku'];
    // used to display icons on customer side's payment buttons.
    $this->sub_payment_method_image_file_names_str_final = 'akulaku.png';

    parent::__construct();
  }

  public function pluginTitle() {
    return "Midtrans Specific: Akulaku";
  }
  public function getSettingsDescription() {
    return "Separated payment buttons for this specific the payment methods with its own icons";
  }
  protected function getDefaultTitle () {
    return __('Akulaku', 'midtrans-woocommerce');
  }
  protected function getDefaultDescription () {
    return __('Pay with Akulaku Account/Installment', 'midtrans-woocommerce');
  }
}