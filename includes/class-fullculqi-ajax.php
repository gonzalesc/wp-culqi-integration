<?php
/**
 * Ajax Class
 * @since  1.0.0
 * @package Includes / Ajax
 */
class FullCulqi_Ajax {

	public function __construct() {

		// Create a refund
		add_action( 'wp_ajax_create_culqi_refund', [ $this, 'create_refund' ] );

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

		// Sync Customers from the admin
		add_action( 'wp_ajax_sync_culqi_customers', [ $this, 'sync_customers' ] );

	}

	/**
	 * Sync Charges from Admin
	 * @return json
	 */
	public function sync_charges() {

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$record = isset( $_POST['record'] ) ? intval( $_POST['record'] ) : 100;
		$after_id = isset( $_POST['after_id'] ) ? esc_html( $_POST['after_id'] ) : '';

		$result = FullCulqi_Charges::sync( $record, $after_id );

		if( $result['status'] == 'ok' )
			wp_send_json_success( $result['data'] );
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

		$record = isset( $_POST['record'] ) ? intval( $_POST['record'] ) : 100;
		$after_id = isset( $_POST['after_id'] ) ? esc_html( $_POST['after_id'] ) : '';

		$result = FullCulqi_Orders::sync( $record, $after_id );

		if( $result['status'] == 'ok' )
			wp_send_json_success( $result['data'] );
		else
			wp_send_json_error( $result['data'] );
	}


	/**
	 * Sync Customer from Admin
	 * @return json
	 */
	public function sync_customers() {
		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		$record = isset( $_POST['record'] ) ? intval( $_POST['record'] ) : 100;
		$after_id = isset( $_POST['after_id'] ) ? esc_html( $_POST['after_id'] ) : '';

		$result = FullCulqi_Customers::sync( $record, $after_id );

		if( $result['status'] == 'ok' )
			wp_send_json_success( $result['data'] );
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


	/**
	 * Create Refund from CPT
	 * @return mixed
	 */
	public function create_refund() {

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		// Check if the post exists
		if( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) )
			wp_send_json_error();

		// Charge Post ID
		$post_charge_id = absint( $_POST['post_id'] );

		// 3rd-party
		$refund = apply_filters( 'fullculqi/ajax/refund/process', false, $post_charge_id );

		if( empty( $refund ) ) {

			// Meta Basic from Charges
			$charge_basic = get_post_meta( $post_charge_id, 'culqi_basic', true );
			$amount = floatval( $charge_basic['culqi_amount'] ) - floatval( $charge_basic['culqi_amount_refunded'] );

			// Culqi Charge ID
			$culqi_charge_id = get_post_meta( $post_charge_id, 'culqi_id', true );

			$args = [
				'amount'	=> round( $amount*100, 0 ),
				'charge_id'	=> $culqi_charge_id,
				'reason'	=> 'solicitud_comprador',
				'metadata'	=> [
					'post_id'	=> $post_charge_id,
				],
			];

			$refund = FullCulqi_Refunds::create( $args, $post_charge_id );
		}

		do_action( 'fullculqi/ajax/refund/create', $refund );

		if( $refund['status'] == 'ok' )
			wp_send_json_success();
		else
			wp_send_json_error( $refund['data'] );
	}
}

new FullCulqi_Ajax();
?>