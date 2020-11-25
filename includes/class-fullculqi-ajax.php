<?php
/**
 * Ajax Class
 * @since  1.0.0
 * @package Includes / Ajax
 */
class FullCulqi_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_fullculqi_get_payments', [ $this, 'get_payments' ] );
		add_action( 'wp_ajax_fullculqi_refund', [ $this, 'refund_payment' ] );

		// Delete All Charges
		add_action( 'wp_ajax_delete_culqi_charges', [ $this, 'delete_charges' ] );

		// Delete All Orders
		add_action( 'wp_ajax_delete_culqi_orders', [ $this, 'delete_orders' ] );

		// Delete All Customers
		add_action( 'wp_ajax_delete_culqi_customers', [ $this, 'delete_customers' ] );

		// Sync Charges from the admin
		add_action( 'wp_ajax_sync_culqi_charges', [ $this, 'sync_charges' ] );

		// Sync Orders from the admin
		add_action( 'wp_ajax_sync_culqi_orders', [ $this, 'sync_orders' ] );

	}

	/**
	 * Sync Charges from Admin
	 * @return json
	 */
	public function sync_charges() {

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$result = FullCulqi_Charges::sync();

		if( $result['status'] == 'ok' )
			wp_send_json_success();
		else
			wp_send_json_error( $result['data'] );
	}


	/**
	 * Sync Charges from Admin
	 * @return json
	 */
	public function sync_orders() {

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$result = FullCulqi_Orders::sync();

		if( $result['status'] == 'ok' )
			wp_send_json_success();
		else
			wp_send_json_error( $result['data'] );
	}


	

	/**
	 * Delete all the charges posts
	 * @return mixed
	 */
	public function delete_charges() {
		global $wpdb;

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$result = FullCulqi_Charges::delete_wpposts();
		
		if( $result )
			wp_send_json_success();
		else
			wp_send_json_error();
	}

	/**
	 * Delete all the orders posts
	 * @return mixed
	 */
	public function delete_orders() {
		global $wpdb;

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$result = FullCulqi_Orders::delete_wpposts();
		
		if( $result )
			wp_send_json_success();
		else
			wp_send_json_error();
	}

	/**
	 * Delete all the customers posts
	 * @return mixed
	 */
	public function delete_customers() {
		global $wpdb;

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$result = FullCulqi_Customers::delete_wpposts();
		
		if( $result )
			wp_send_json_success();
		else
			wp_send_json_error();
	}
}

new FullCulqi_Ajax();
?>