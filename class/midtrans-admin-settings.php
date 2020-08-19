<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sandbox_key_url = 'https://dashboard.sandbox.midtrans.com/settings/config_info';
$production_key_url = 'https://dashboard.midtrans.com/settings/config_info';
/**
 * Build array of configurations that will be displayed on Admin Panel
 */
return apply_filters(
	'wc_midtrans_settings',
	array(
        'enabled'       => array(
            'title'     => __( 'Enable/Disable', 'midtrans-woocommerce' ),
            'type'      => 'checkbox',
            'label'     => __( 'Enable Midtrans Payment', 'midtrans-woocommerce' ),
            'default'   => 'no'
        ),
        'title'                     => array(
            'title'         => __( 'Title', 'midtrans-woocommerce' ),
            'type'          => 'text',
            'description'   => __( 'This controls the title which the user sees during checkout.', 'midtrans-woocommerce' ),
            'default'       => $this->getDefaultTitle(),
            'desc_tip'      => true,
        ),
        'description'               => array(
            'title' => __( 'Customer Message', 'midtrans-woocommerce' ),
            'type' => 'textarea',
            'description' => __( 'This controls the description which the user sees during checkout', 'midtrans-woocommerce' ),
            'default'       => $this->getDefaultDescription(),
          ),
        'merchant_id'                => array(
            'title'         => __("Merchant ID", 'midtrans-woocommerce'),
            'type'          => 'text',
            'description'   => sprintf(__('Input your Midtrans Merchant ID (e.g M012345). Get the ID <a href="%s" target="_blank">here</a>', 'midtrans-woocommerce' ),$sandbox_key_url),
            'default'       => '',
        ),
        'select_midtrans_environment' => array(
          'title'           => __( 'Environment', 'midtrans-woocommerce' ),
          'type'            => 'select',
          'default'         => 'sandbox',
          'description'     => __( 'Select the Midtrans Environment', 'midtrans-woocommerce' ),
          'options'         => array(
            'sandbox'           => __( 'Sandbox', 'midtrans-woocommerce' ),
            'production'        => __( 'Production', 'midtrans-woocommerce' ),
          ),
        ),
        'client_key_v2_sandbox'       => array(
            'title'         => __("Client Key - Sandbox", 'midtrans-woocommerce'),
            'type'          => 'text',
            'description'   => sprintf(__('Input your <b>Sandbox</b> Midtrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'midtrans-woocommerce' ),$sandbox_key_url),
            'default'       => '',
            'class'         => 'sandbox_settings toggle-midtrans',
        ),
        'server_key_v2_sandbox'       => array(
            'title'         => __("Server Key - Sandbox", 'midtrans-woocommerce'),
            'type'          => 'text',
            'description'   => sprintf(__('Input your <b>Sandbox</b> Midtrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'midtrans-woocommerce' ),$sandbox_key_url),
            'default'       => '',
            'class'         => 'sandbox_settings toggle-midtrans'
        ),
        'client_key_v2_production'    => array(
            'title'         => __("Client Key", 'midtrans-woocommerce'),
            'type'          => 'text',
            'description'   => sprintf(__('Input your <b>Production</b> Midtrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'midtrans-woocommerce' ),$production_key_url),
            'default'       => '',
            'class'         => 'production_settings toggle-midtrans',
        ),
        'server_key_v2_production'     => array(
            'title'         => __("Server Key", 'midtrans-woocommerce'),
            'type'          => 'text',
            'description'   => sprintf(__('Input your <b>Production</b> Midtrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'midtrans-woocommerce' ),$production_key_url),
            'default'       => '',
            'class'         => 'production_settings toggle-midtrans'
        ),
        'enable_3d_secure'             => array(
            'title'         => __( 'Enable 3D Secure', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => __( 'Enable 3D Secure?', 'midtrans-woocommerce' ),
            'description'   => __( 'You must enable 3D Secure.
                Please contact us if you wish to disable this feature in the Production environment.', 'midtrans-woocommerce' ),
            'default'       => 'yes'
        ),
        'enable_savecard'               => array(
            'title'         => __( 'Enable Save Card', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => __( 'Enable Save Card?', 'midtrans-woocommerce' ),
            'description'   => __( 'This will allow your customer to save their card on the payment popup, for faster payment flow on the following purchase', 'midtrans-woocommerce' ),
            'class'         => 'toggle-advanced',
            'default'       => 'no'
        ),
        'enable_redirect'               => array(
            'title'         => __( 'Redirect payment page', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => __( 'Enable payment page redirection?', 'midtrans-woocommerce' ),
            'description'   => __( 'This will redirect customer to Midtrans hosted payment page instead of popup payment page on your website. <br>Leave it disabled if you are not sure', 'midtrans-woocommerce' ),
            'class'         => 'toggle-advanced',
            'default'       => 'no'
        ),
        'custom_expiry'                 => array(
            'title'         => __( 'Custom Expiry', 'midtrans-woocommerce' ),
            'type'          => 'text',
            'description'   => __( 'This will allow you to set custom duration on how long the transaction available to be paid.<br> example: 45 minutes', 'midtrans-woocommerce' ),
            'default'       => 'disabled'
        ),
        'custom_fields'                 => array(
            'title'         => __( 'Custom Fields', 'midtrans-woocommerce' ),
            'type'          => 'text',
            'description'   => __( 'This will allow you to set custom fields that will be displayed on Midtrans dashboard. <br>Up to 3 fields are available, separate by coma (,) <br> Example:  Order from web, Woocommerce, Processed', 'midtrans-woocommerce' ),
            'default'       => ''
        ),
        'enable_map_finish_url'         => array(
            'title'         => __( 'Use Dashboard Finish url', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => 'Use dashboard configured payment finish url?',
            'description'   => __( 'This will allow use of Dashboard configured payment finish url instead of auto configured url', 'midtrans-woocommerce' ),
            'default'       => 'no'
        ),
        'ganalytics_id'                 => array(
            'title'         => __( 'Google Analytics ID', 'midtrans-woocommerce' ),
            'type'          => 'text',
            'description'   => __( 'This will allow you to use Google Analytics tracking on woocommerce payment page. <br>Input your tracking ID ("UA-XXXXX-Y") <br> Leave it blank if you are not sure', 'midtrans-woocommerce' ),
            'default'       => ''
        ),
        'enable_immediate_reduce_stock' => array(
            'title'         => __( 'Immediate Reduce Stock', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => 'Immediately reduce item stock on Midtrans payment pop-up?',
            'description'   => __( 'By default, item stock only reduced if payment status on Midtrans reach pending/success (customer choose payment channel and click pay on payment pop-up). Enable this if you want to immediately reduce item stock when payment pop-up generated/displayed.', 'midtrans-woocommerce' ),
            'default'       => 'no'
        ),
        'ignore_pending_status'         => array(
            'title'         => __( 'Ignore Midtrans Transaction Pending Status', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
            'label'         => __( 'Ignore Midtrans Transaction Pending Status?', 'midtrans-woocommerce' ),
            'description'   => __( 'This will prevent customer for being redirected to "order received" page, on unpaid async payment type. <br>Backend pending notification will also ignored, and not trigger change to "on-hold". <br>Leave it disabled if you are not sure', 'midtrans-woocommerce' ),
            'class'         => 'toggle-advanced',
            'default'       => 'no'
        ),
        'logging' => array(
            'title'         => __( 'Enable Midtrans Logging', 'midtrans-woocommerce' ),
            'type'          => 'checkbox',
			'label'       => __( 'Log debug messages', 'midtrans-woocommerce' ),
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'midtrans-woocommerce' ),
            'default'       => 'no'
        ),
	)
);