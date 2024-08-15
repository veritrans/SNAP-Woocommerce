<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Midtrans_API class.
 * @TODO: refactor this messy class
 * 
 * Communicates with Midtrans API.
 */
class WC_Midtrans_API {

	/**
	 * Server Key.
	 * @var string
	 */
	private static $server_key = '';

	/**
	 * Midtrans Environment.
	 * @var string
	 */
    private static $environment = '';
    
	/**
	 * Plugin Options.
	 * @var string
	 */
	private static $plugin_options;
	
	/**
	 * Set Server Key.
	 * @param string $key
	 */
	public static function set_server_key( $server_key ) {
		self::$server_key = $server_key;
    }

	/**
	 * Set Midtrans Environment.
	 * @param string $key
	 */
	public static function set_environment( $environment ) {
		self::$environment = $environment;
    }

    // @TODO: maybe handle when $plugin_id is invalid (e.g: `all`), it result in invalid $plugin_options, then empty serverKey, then it will cause failure on getStatusFromMidtransNotif. Make $plugin_options default value to `midtrans` plugin when serverKey not found?.
	/**
	 * Fetch Plugin Options and Set as self/private vars
	 * @param string $plugin_id
	 */
	public static function fetchAndSetCurrentPluginOptions ( $plugin_id="midtrans" ) {
		self::$plugin_options = get_option( 'woocommerce_' . $plugin_id . '_settings' );
	}

	/**
	 * Get Server Key.
	 * @return string
	 */
	public static function get_server_key() {
		if ( ! self::$server_key ) {
			$plugin_options = self::$plugin_options;
			if ( isset( $plugin_options['server_key_v2_production'], $plugin_options['server_key_v2_sandbox'] ) ) {
				self::set_server_key( self::get_environment() == 'production' ? $plugin_options['server_key_v2_production'] : $plugin_options['server_key_v2_sandbox'] );
			}
		}
		return self::$server_key;
	}

    /**
	 * Get Midtrans Environment.
	 * @return string
	 */
	public static function get_environment() {
		if ( ! self::$environment ) {
			$plugin_options = self::$plugin_options;
			if ( isset( $plugin_options['select_midtrans_environment'] ) ) {
				self::set_environment( $plugin_options['select_midtrans_environment'] );
			}
		}
		return self::$environment;
	}

    /**
     * Fetch Midtrans API Configuration from plugin id and set as self/private vars.
     * @return void
     */
    public static function fetchAndSetMidtransApiConfig( $plugin_id="midtrans" ) {
        if(strpos($plugin_id, 'midtrans_sub') !== false){
          // for sub separated gateway buttons, use main gateway plugin id instead
          $plugin_id = 'midtrans';
        }
		self::fetchAndSetCurrentPluginOptions( $plugin_id );
        Midtrans\Config::$isProduction = (self::get_environment() == 'production') ? true : false;
        Midtrans\Config::$serverKey = self::get_server_key();     
        Midtrans\Config::$isSanitized = true;

        // setup custom HTTP client header as identifier ref:
        // https://github.com/omarxp/Midtrans-Drupal8/blob/3d4e4b4af46e96c742667c7a2925cf70dfaa9e2a/src/PluginForm/MidtransOfflineInstallmentForm.php#L39-L42
        try {
            Midtrans\Config::$curlOptions[CURLOPT_HTTPHEADER][] = 'x-midtrans-wc-plu-version: '.MIDTRANS_PLUGIN_VERSION;
            Midtrans\Config::$curlOptions[CURLOPT_HTTPHEADER][] = 'x-midtrans-wc-plu-wc-version: '.WC_VERSION;
            Midtrans\Config::$curlOptions[CURLOPT_HTTPHEADER][] = 'x-midtrans-wc-plu-php-version: '.phpversion();
        } catch (Exception $e) { }
    }

    /**
     * Same as createSnapTransaction, but it will auto handle exception
     * 406 duplicated order_id exception from Snap API, by calling WC_Midtrans_Utils::generate_non_duplicate_order_id
     * @param  object $order the WC Order instance.
     * @param  array $params Payment options.
     * @param  string $plugin_id ID of the plugin class calling this function
     * @return object Snap response (token and redirect_url).
     * @throws Exception curl error or midtrans error.
     */
    public static function createSnapTransactionHandleDuplicate( $order, $params, $plugin_id="midtrans") {
        try {
            $response = self::createSnapTransaction($params, $plugin_id);
        } catch (Exception $e) {
            // Handle: Snap order_id duplicated, retry with suffixed order_id
            if( strpos($e->getMessage(), 'transaction_details.order_id sudah digunakan') !== false) {
                self::setLogRequest( $e->getMessage().' - Attempt to auto retry with suffixed order_id', $plugin_id );
                // @TAG: order-id-suffix-handling
                $params['transaction_details']['order_id'] = 
                    WC_Midtrans_Utils::generate_non_duplicate_order_id($params['transaction_details']['order_id']);
                $response =  self::createSnapTransaction($params, $plugin_id);
                
                // store the suffixed order id to order metadata
                // @TAG: order-id-suffix-handling-meta
                $order->update_meta_data('_mt_suffixed_midtrans_order_id', $params['transaction_details']['order_id']);
            } else {
                throw $e;
            }
        }
        return $response;
    }

    /**
     * Create Snap Token.
     * @param  array $params Payment options.
     * @return object Snap response (token and redirect_url).
     * @throws Exception curl error or midtrans error.
     */
    public static function createSnapTransaction( $params, $plugin_id="midtrans" ) {
        self::fetchAndSetMidtransApiConfig( $plugin_id );
		self::setLogRequest( print_r( $params, true ), $plugin_id );
        return Midtrans\Snap::createTransaction( $params );
	}
	
    /**
     * Create Recurring Transaction for Subscription Payment.
     * @param  array $params Payment options.
     * @return object Core API response (token and redirect_url).
     * @throws Exception curl error or midtrans error.
     */
    public static function createRecurringTransaction( $params, $plugin_id = 'midtrans_subscription' ) {
		self::fetchAndSetMidtransApiConfig( $plugin_id );
		self::setLogRequest( print_r( $params, true ), $plugin_id );
		return Midtrans\CoreApi::charge( $params );
    }

	/**
     * Create Refund.
	 * 
	 * @param int $order_id.
     * @param  array $params Payment options.
     * @return object Refund response.
     * @throws Exception curl error or midtrans error.
     */
    public static function createRefund( $order_id, $params, $plugin_id="midtrans" , $payment_type = null) {
		self::fetchAndSetMidtransApiConfig( $plugin_id );
		self::setLogRequest( print_r( $params, true ), $plugin_id );
		return Midtrans\Transaction::refund($order_id, $params, $payment_type);
    }

    /**
     * Get Midtrans Notification.
     * @return object Midtrans Notification response.
     */
    public static function getStatusFromMidtransNotif( $plugin_id="midtrans") {
        self::fetchAndSetMidtransApiConfig( $plugin_id );
        return new Midtrans\Notification();
    }

    /**
     * Retrieve transaction status. Default ID is main plugin, which is "midtrans"
     * @param string $id Order ID or transaction ID.
     * @return object Midtrans response.
     */
    public static function getMidtransStatus( $order_id, $plugin_id="midtrans", $paymentType = null ) {
        self::fetchAndSetMidtransApiConfig( $plugin_id );
        return Midtrans\Transaction::status( $order_id , $paymentType);
    }

	/**
	 * Cancel transaction.
	 * 
	 * @param string $id Order ID or transaction ID.
	 * @param string $plugin_id Plugin id.
	 * @return object Midtrans response.
	 */
    public static function CancelTransaction( $id, $plugin_id="midtrans" ) {
		self::fetchAndSetMidtransApiConfig( $plugin_id );
		self::setLogRequest('Request Cancel Transaction ' . $id, $plugin_id );
        return Midtrans\Transaction::cancel( $id );
    }

    /**
     * Set log request on midtrans logger.
	 * 
     * @param string $message payload request.
     * @return void
     */
	public static function setLogRequest( $message, $plugin_id="midtrans" ) {
		WC_Midtrans_Logger::log( $message, 'midtrans-request', $plugin_id, current_time( 'timestamp') );
	  }
}