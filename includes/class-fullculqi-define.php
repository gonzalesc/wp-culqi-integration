<?php
/**
 * Define Class
 * @since  1.0.0
 * @package Includes / Define
 */
class FullCulqi_Define {

	/**
	 * Construct
	 * @return mixed
	 */
	public function __construct() {

		// Load the method payment
		add_action( 'plugins_loaded', [ $this, 'include_file' ] );

		// Include Class
		add_filter( 'woocommerce_payment_gateways', [ $this, 'include_class' ] );
	}


	/**
	 * Include the method payment
	 * @return mixed
	 */
	public function include_file() {

		// Check if WC is installed
		if ( ! class_exists( 'WC_Payment_Gateway' ) )
			return;

		// Check if WC has the supported currency activated
		$supported_currencies = array_keys( fullculqi_currencies() );
		if ( ! in_array( get_woocommerce_currency(), $supported_currencies ) ) {
			add_action( 'admin_notices', [ $this, 'notice_currency'] );
			return;
		}

		require_once FULLCULQI_DIR . 'includes/class-fullculqi-method.php';
	}

	/**
	 * Include the gateway class
	 * @param  array $methods
	 * @return array
	 */
	public function include_class( $methods = [] ) {
		$methods[] = 'WC_Gateway_FullCulqi';
		
		return $methods;
	}


	/**
	 * Notice Currency
	 * @return html
	 */
	public function notice_currency() {
		fullculqi_get_template( 'resources/layouts/admin/notice_currency.php' );
	}
}

new FullCulqi_Define();