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
		self::fetchAndSetCurrentPluginOptions( $plugin_id );
        Midtrans\Config::$isProduction = (self::get_environment() == 'production') ? true : false;
        Midtrans\Config::$serverKey = self::get_server_key();     
        Midtrans\Config::$isSanitized = true;
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
    public static function createRefund( $order_id, $params, $plugin_id="midtrans" ) {
		self::fetchAndSetMidtransApiConfig( $plugin_id );
		self::setLogRequest( print_r( $params, true ), $plugin_id );
		return Midtrans\Transaction::refund($order_id, $params);
    }

    /**
     * Get Midtrans Notification.
     * @return object Midtrans Notification response.
     */
    public static function getMidtransNotif( $plugin_id="midtrans") {
        self::fetchAndSetMidtransApiConfig( $plugin_id );
        return new Midtrans\Notification();
    }

    /**
     * Retrieve transaction status. Default ID is main plugin, which is "midtrans"
     * @param string $id Order ID or transaction ID.
     * @return object Midtrans response.
     */
    public static function getMidtransStatus( $order_id, $plugin_id="midtrans" ) {
        self::fetchAndSetMidtransApiConfig( $plugin_id );
        return Midtrans\Transaction::status( $order_id );
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