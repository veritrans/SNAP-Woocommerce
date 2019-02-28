Midtrans&nbsp; WooCommerce - Wordpress Payment Gateway Module
=====================================

Midtrans&nbsp; :heart: WooCommerce!
Receive online payment on your WooCommerce store with Midtrans payment gateway integration plugin.

Also [Available on Wordpress plugin store](https://wordpress.org/plugins/midtrans-woocommerce/)

### Description

This plugin will allow secure online payment on your WooCommerce store, without your customer ever need to leave your WooCommerce store! With beautiful responsive payment interface built-in.
Midtrans&nbsp; is an online payment gateway. They strive to make payments simple for both the merchant and customers.
Support various online payment channel.
Support WooCommerce v3 & v2.

Payment Method Feature:

* Credit card fullpayment and other payment methods.
* Bank transfer, internet banking for various banks
* Credit card Online & offline installment payment.
* Credit card BIN, bank transfer, and other channel promo payment.
* Credit card MIGS acquiring channel.
* Custom expiry.
* Two-click & One-click feature.
* Midtrans Snap all payment method fullpayment.


### Installation

#### Minimum Requirements

* WordPress v3.9 or greater (tested up to v5.0.0)
* WooCommerce v2 or greater (tested up to v3.5.2)
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
5. Go to menu **WooCommerce > Settings > Checkout > Midtrans**, fill the configuration fields.
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

Note: This section is optional.
If you are activating BCA Klikpay payment channel, follow this additional step. This step is required to pass BCA UAT on BCA Klikpay.

1. Login to Wordpress Admin Panel / Dashboard
2. Add new page by going to menu **Pages > Add new**
3. Insert this as title: `payment-finish`. Makesure the permalink display `[your wordpress url]/payment-finish`. Click **Publish/Save**.
4. Login to your [Midtrans&nbsp; Account](https://dashboard.midtrans.com), select your environment (sandbox/production), go to menu **settings > Snap Preference > System Settings**
5. Go to menu **settings > configuration**. Then change Finish Redirect URL to `http://[your wordpress url]/payment-finish`.

This is to ensure we have finish page when customer has completed the payment on KlikPay page, and then the payment result will be displayed accordingly on the page. If you want to customize the finish page, edit this file `/class/finish-url-page.php`.

#### Get help

* [SNAP-Woocommerce Wiki](https://github.com/veritrans/SNAP-Woocommerce/wiki)
* [Veritrans registration](https://dashboard.midtrans.com/register)
* [SNAP documentation](http://snap-docs.midtrans.com)
* Can't find answer you looking for? email to [support@midtrans.com](mailto:support@midtrans.com)
