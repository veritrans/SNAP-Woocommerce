Midtrans&nbsp; WooCommerce - Wordpress Payment Gateway Module
=====================================

Midtrans&nbsp; :heart: WooCommerce!
Receive online payment on your WooCommerce store with Midtrans payment gateway integration plugin.

Also [Available on Wordpress plugin store](https://wordpress.org/plugins/midtrans-woocommerce/)

### Description

This plugin will allow secure online payment on your WooCommerce store, without your customer ever need to leave your WooCommerce store! 

Midtrans-WooCommerce is official plugin from [Midtrans](https://midtrans.com). Midtrans is an online payment gateway. We strive to make payments simple & secure for both the merchant and customers. Support various online payment channel. Support WooCommerce v3 & v2.

Please follow [this step by step guide](https://docs.midtrans.com/en/snap/with-plugins?id=wordpress-woocommerce) for complete configuration. If you have any feedback or request, please [do let us know here](https://docs.midtrans.com/en/snap/with-plugins?id=feedback-and-request).

Want to see Midtrans-WooCommerce payment plugins in action? We have some demo web-stores for WooCommerce that you can use to try the payment journey directly, visit the [Midtrans CMS Demo Store](https://docs.midtrans.com/en/snap/with-plugins?id=midtrans-payment-plugin-live-demonstration)

Payment Method Feature:

* Credit card fullpayment and other payment methods.
* E-wallet, Bank transfer, internet banking for various banks
* Credit card Online & offline installment payment.
* Credit card BIN, bank transfer, and other channel promo payment.
* Credit card MIGS acquiring channel.
* Custom expiry.
* Two-click & One-click feature.
* Midtrans Snap all supported payment method.
* Optional: Separated specific payment buttons with its own icons.


### Installation

#### Minimum Requirements

* WordPress v3.9 or greater (tested up to v6.x)
* WooCommerce v2 or greater (tested up to v9.1.2)
* PHP version v5.4 or greater
* MySQL version v5.0 or greater
* PHP CURL enabled server/host

#### Simple Installation
1. Login to your Wordpress admin panel.
2. Go to `Plugins` menu, click `add new`. Search for `Midtrans-WooCommerce` plugin.
3. Install and follow on screen instructions.
4. Proceed to step **5** below.

#### Manual Installation

1. [Download](../../archive/master.zip) the plugin from this repository.
2. Extract the plugin, then rename the folder modules as **midtrans-woocommerce**
3. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory.
4. Install & Activate the plugin from the Plugins menu within the WordPress admin panel.
5. Go to menu **WooCommerce > Settings > Payment > Midtrans > Manage**, fill the configuration fields.
	* Fill **Title** with text button that you want to display to customer
	* Select **Environment**, Sandbox is for testing transaction, Production is for real transaction
	* Fill in the **client key** & **server key** with your corresonding [Midtrans&nbsp; account](https://dashboard.midtrans.com/) credentials
	* Note: key for Sandbox & Production is different, make sure you use the correct one.
	* Other configuration are optional, you may leave it as is.

### Midtrans&nbsp; MAP Configuration

1. Login to your [Midtrans&nbsp; Account](https://dashboard.midtrans.com), select your environment (sandbox/production), go to menu **settings > configuration**
  * Insert `http://[your web]/?wc-api=WC_Gateway_Midtrans` as your Payment Notification URL.
  * Insert `http://[your web]/?wc-api=WC_Gateway_Midtrans` link as Finish/Unfinish/Error Redirect URL.

2. Go to menu **settings > Snap Preference > System Settings**
  * Insert `http://[your web]/?wc-api=WC_Gateway_Midtrans` link as Finish/Unfinish/Error Redirect URL.

### Additional Resource
Note: This section is optional and only for advanced usage.

#### Configurables
Available for customization from plugin config:
- Payment text label of the payment options
- Payment text description of the payment options
- On both configuration fields above can also input html tags as the text, to insert something like image. For example you can input like this to show images:

```html
Online Payment via Midtrans <img src="https://docs.midtrans.com/asset/image/main/midtrans-logo.png">
```

You can change the image, like if you want to show the logo of banks or payment providers that you are accepting.

Additional payment options (radio button) can be activated:
- Installment
- Offline Installment
- Promo / specific payment

#### Customize Payment Icons

You can customize icon that will be shown on payment buttons, from the plugin configuration page on your WooCommerce portal, under `Button Icons` config field.

All available values for the field:
```
midtrans.png, credit_card.png, gopay.png, shopeepay.png, qris.png, other_va.png, bni_va.png, bri_va.png, bca_va.png, permata_va.png, echannel.png, alfamart.png, indomaret.png, akulaku.png, bca_klikpay.png, cimb_clicks.png, danamon_online.png
```

Or refer to [payment-methods folder](/public/images/payment-methods) to see the list of all available file names. The image file will be loaded from that folder.

#### BCA Klikpay Specific

<details><summary>Click to expand info</summary>
<br>
If you are activating BCA Klikpay payment channel, follow this additional step. This step is required to pass BCA UAT on BCA Klikpay.

1. Login to Wordpress Admin Panel / Dashboard
2. Add new page by going to menu **Pages > Add new**
3. Insert this as title: `midtrans-payment-finish`. Makesure the permalink display `[your wordpress url]/midtrans-payment-finish`. Click **Publish/Save**.
4. Login to your [Midtrans&nbsp; Account](https://dashboard.midtrans.com), select your environment (sandbox/production), go to menu **settings > Snap Preference > System Settings**
5. Go to menu **settings > configuration**. Then change Finish Redirect URL to `http://[your wordpress url]/midtrans-payment-finish`.

This is to ensure we have finish page when customer has completed the payment on KlikPay page, and then the payment result will be displayed accordingly on the page. If you want to customize the finish page, edit this file `/class/finish-url-page.php`.

> **Note:** BCA KlikPay requires you to **disable the `Redirect payment page` configuration**, on Midtrans Plugin config page.
> Please ensure you have done this.

If required to change API endpoint/url, these are where you need to change:

- `[plugin folder]/lib/veritrans/Veritrans/Config.php`
	- Replace any Snap API domain: https://app.sandbox.midtrans.com/snap/v1 with UAT API domain
	- Replace any Midtrans API domain: https://api.sandbox.midtrans.com/v2 with UAT API domain

- `[plugin folder]/class/payment-page.php`
	- Replace any Snap API domain: https://app.sandbox.midtrans.com with UAT API domain
</details>

#### Customize Order Status on Payment Paid

You can configure the status that WooCommerce Order should become when an order is successfully paid. This can be useful if you want, for example, order status to become "completed" once paid.

Configure it from **WooCommerce > Settings > Payment > Midtrans > Manage** under configuration field **WC Order Status on Payment Paid**. Select your preferred value from the drop down.

#### Available Custom Hooks

<details><summary>Click to expand info</summary>
<br>

If you are a developer or know how to customize Wordpress, this section may be useful for you in case you want to customize some code/behaviour of this plugin.

This plugin have few available [WP hooks](https://developer.wordpress.org/plugins/hooks/):
- filter: `midtrans_snap_params_main_before_charge` (1 params)
	- For if you want to modify Snap API JSON param on the main gateway, before transaction is created on Midtrans side. The $params is PHP Array representation of [Snap API JSON param](https://snap-docs.midtrans.com/#request-body-json-parameter)
- action: `midtrans_after_notification_payment_complete` (2 params)
	- For if you want to perform action/update WC Order object when the payment is declared as complete upon Midtrans notification received.
- action: `midtrans_on_notification_received` (2 params)
	- For if you want to perform action/update WC Order object upon Midtrans notification received.
- filter: `midtrans_gateway_icon_before_render` (1 params)
	- For if you want to modify payment icons HTML image tag.
- action: `midtrans-handle-valid-notification` (1 params)
	- For if you want to perform something upon valid Midtrans notification received. Note: this is legacy hook, better use the hook above.

Example implementation:
```php
// Custom filter hook to modify Snap params
add_filter( 'midtrans_snap_params_main_before_charge', 'my_midtrans_snap_param_hook' );
function my_midtrans_snap_param_hook( $params ) {
	// example: modify Snap params to add additional item with 0 price
	$params['item_details'][] = array(
		"name" => "My Custom Additional Item",
		"id" => "my-item-01",
		"price" => 0,
		"quantity" => 3,
	);
	// another use case e.g. you can modify $params['transaction_details']['gross_amount'] value to convert to another currency with your own defined rate.
	
	// don't forget to return the $params
    return $params;
}

// Custom action hook to modify WC Order object after payment marked as complete
add_action( 'midtrans_after_notification_payment_complete', 'my_midtrans_complete_hook',$priority = 10, $accepted_args = 2 );
function my_midtrans_complete_hook( $order, $midtrans_notification ) {
	// example: update order status to directly `completed`, instead of default `processing`.
	$order->update_status('completed',__('Completed payment via my custom hook: Midtrans-'.$midtrans_notification->payment_type,'midtrans-woocommerce'));
}

// Custom action hook to modify WC Order object when midtrans notification is received
add_action( 'midtrans_on_notification_received', 'my_midtrans_on_notif_hook',$priority = 10, $accepted_args = 2 );
function my_midtrans_on_notif_hook( $order, $midtrans_notification ) {
	// do as you wish here
}

// Custom filter hook to modify payment icon html image tag
add_filter( 'midtrans_gateway_icon_before_render', 'my_midtrans_gateway_icon_hook' );
function my_midtrans_gateway_icon_hook($image_tag){
	// example: modify payment icon's inline CSS to position it to the left
	return str_replace('style="','style=" float: left; margin-right: 0.5em;',$image_tag);
}
```

For reference on where/which file to apply that code example, [refer here](https://blog.nexcess.net/the-right-way-to-add-custom-functions-to-your-wordpress-site/).

Note: for `midtrans_after_notification_payment_complete` & `midtrans_on_notification_received` hooks, if you are using [custom "WC Order Status on Payment Paid"](https://docs.midtrans.com/en/snap/with-plugins?id=advanced-customize-woocommerce-order-status-upon-payment-paid) config, the final WC Order status value can get overridden by that config. As that config is executed last.

</details>

#### Customizing Snap API parameters

<details><summary>Click to expand info</summary>
<br>

In case you need to do [customization on Snap API parameters](https://docs.midtrans.com/en/snap/advanced-feature) that is not provided by default from this plugin.

##### For All Payments in This Plugin

If you want the API params to be applied to all payment options within this plugin, you can edit: 
- **File** `./abstract/abstract.midtrans-gateway.php`
	- Within **function** [`getPaymentRequestData`](https://github.com/veritrans/SNAP-Woocommerce/blob/607e2b9d46dc287153921fb1630a60f9ecde9b1e/abstract/abstract.midtrans-gateway.php#L154)
	- Before **line** [`return $params;`](https://github.com/veritrans/SNAP-Woocommerce/blob/607e2b9d46dc287153921fb1630a60f9ecde9b1e/abstract/abstract.midtrans-gateway.php#L300)
- There you can modify the `$params` variable, it is an PHP Array representation of [Snap's API JSON param](https://docs.midtrans.com/en/snap/advanced-feature).

For example, you can add "custom finish url":
```php
$params['callbacks'] = array();
$params['callbacks']['finish'] = "https://mywebsite.com/my-custom-finish-url/";

return $params;
```
##### For Specific Payment Option in This Plugin

If you want it to be applied to just some specific Payment Option (e.g: the default/fullpayment only, or installment only, etc.)
- Select the file from folder `./class/`, 
	- Choose the file based on your desired Payment Option, for example file `./class/class.midtrans-gateway-installment.php`
	- Within function `process_payment`
	- Before line `$woocommerce->cart->empty_cart();`
- There you can modify the `$params` variable, it is an PHP Array representation of [Snap's API JSON param](https://docs.midtrans.com/en/snap/advanced-feature).
</details>


#### Manual Clean Up WP Options Config Value of This Plugin

<details><summary>Click to expand info</summary>
<br>

In general use-case, you don't need to do what explained in this section. This section is relevent only in case **you want to know/clean-up/remove** `wp_options` config values created by this plugin. Those config values are located under your WP's database SQL table `wp_options` with record's name prefix `woocommerce_midtrans_`. 
	
You can also find it by executing this SQL on your WP's database to find those values:
```sql
SELECT * FROM `wp_options` WHERE `option_name` LIKE '%woocommerce_midtrans%'
```
Then if you want, you can remove the values from the SQL database (alternatively, you can also modify the SQL `SELECT` command with `DELETE`). 
	
Background: 
	
This plugin was mainly developed by following the official guideline from WooCommerce(WC), where WooCommerce provided their internal API function to create/edit WP options, we don’t use WP options API function directly. It seems the default WC Payment Gateway behavior (when uninstalled) does not include the uninstall clean up procedure to remove wp_options config values. Though that may be by design from WC, they may have decided that Gateway Settings/options should preserved during uninstall, so that upon re-install the Settings is auto-restored. For further explanation you can also [check this link](https://wordpress.org/support/topic/no-clean-uninstall-2/#post-15287583).

</details>

#### Get help

* [SNAP-Woocommerce Wiki](https://github.com/veritrans/SNAP-Woocommerce/wiki)
* [Veritrans registration](https://dashboard.midtrans.com/register)
* [SNAP documentation](http://snap-docs.midtrans.com)
* Can't find answer you looking for? email to [support@midtrans.com](mailto:support@midtrans.com)
