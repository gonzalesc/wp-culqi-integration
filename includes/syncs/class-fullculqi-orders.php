<?php
/**
 * Orders Class
 * @since  1.0.0
 * @package Includes / Sync / Orders
 */
class FullCulqi_Orders {

	/**
	 * Sync from Culqi
	 * @param  integer $records
	 * @return mixed
	 */
	public static function sync( $records = 100 ) {
		global $culqi;

		// Connect to the API Culqi
		try {
			$culqi_orders = $culqi->Orders->all( [ 'limit' => $records ] );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $culqi_orders->data ) || empty( $culqi_orders->data ) )
			return [ 'status' => 'error', 'data' => $culqi_orders->merchant_message ];


		global $wpdb;

		$query = 'SELECT
						p.ID AS post_id,
						m.meta_value AS culqi_id
					FROM
						'.$wpdb->posts.' AS p
					INNER JOIN
						'.$wpdb->postmeta.' AS m
					ON
						p.ID = m.post_id
					WHERE
						p.post_type = "culqi_orders" AND
						m.meta_key = "culqi_id" AND
						m.meta_value <> ""';

		$results = $wpdb->get_results( $query );
		$keys = [];

		// Keys Post Type 
		foreach( $results as $result )
			$keys[ $result->culqi_id ] = $result->post_id;

		// Culqi orders
		foreach( $culqi_orders->data as $culqi_order ) {

			$post_id = 0;

			// Check if is update
			if( isset( $keys[ $culqi_order->id ] ) )
				$post_id = $keys[ $culqi_order->id ];

			$post_id = self::create_wppost( $culqi_order, $post_id );

			do_action( 'fullculqi/orders/sync/loop', $culqi_order, $post_id );
		}

		do_action( 'fullculqi/orders/sync/after', $culqi_orders );

		return [ 'status' => 'ok' ];
	}



	/**
	 * Create Order
	 * @param  array $args_order
	 * @return array
	 */
	public static function create( $args_order = [] ) {

		global $culqi;

		try {
			$culqi_order = $culqi->Orders->create( $args_order );
		} catch( Exception $e ) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $culqi_order->object ) || $culqi_order->object == 'error' ) {
			return [ 'status' => 'error', 'data' => $culqi_order->merchant_message ];
		}

		do_action( 'fullculqi/orders/create', $culqi_order );

		return apply_filters( 'fullculqi/orders/create/success', [
			'status'	=> 'ok',
			'data'		=> [ 'culqi_order_id' => $culqi_order->id ]
		] );
	}


	/**
	 * Create the CIP Code
	 * @param  string  $culqi_order_id
	 * @param  int  $post_customer_id
	 * @return mixed
	 */
	public static function confirm( $culqi_order_id = '', $post_customer_id = 0 ) {

		if( empty( $culqi_order_id ) ) {
			return [
				'status' => 'error',
				'data' => esc_html__( 'Culqi Order ID empty', 'fullculqi' )
			];
		}

		global $culqi;

		try {
			$culqi_order = $culqi->Orders->get( $culqi_order_id );
		} catch( Exception $e ) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}


		if( ! isset( $culqi_order->object ) || $culqi_order->object == 'error' ) {
			return [ 'status' => 'error', 'data' => $culqi_order->merchant_message ];
		}

		// Create post
		$post_id = self::create_wppost( $culqi_order, 0, $post_customer_id );

		do_action( 'fullculqi/orders/confirm', $culqi_order );

		return apply_filters( 'fullculqi/orders/confirm/success', [
			'status'	=> 'ok',
			'data'		=> [ 'culqi_order_id' => $culqi_order->id, 'post_order_id' => $post_id ]
		] );
	}


	/**
	 * Update Order from webhook
	 * @param  object $culqi_order
	 * @return mixed
	 */
	public static function update( $culqi_order ) {
		
		if( ! isset( $culqi_order->metadata->order_id ) ||
			! isset( $culqi_order->payment_code )
		) return;

		$order_id = absint( $culqi_order->metadata->order_id );
		$cip_code = esc_html( $culqi_order->payment_code );
		
		$order = new WC_Order( $order_id );

		if( ! $order )
			return;

		$post_id = get_post_meta( $order_id, 'post_order_id', true );

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		// Payment Settings
		$method = get_option( 'woocommerce_fullculqi_settings', [] );

		switch( $culqi_order->state ) {
			case 'paid' :

				$notice = sprintf(
					esc_html__( 'The CIP %s was paid', 'fullculqi' ),
					$cip_code
				);

				$order->add_order_note( $notice );
				$log->set_notice( $notice );

				// Status
				if( $method['status_success'] == 'wc-completed')
					$order->payment_complete();
				else {
					$order->update_status( $method_array['status_success'],
						sprintf(
							esc_html__( 'Status changed by FullCulqi (to %s)', 'fullculqi' ),
							$method['status_success']
						)
					);
				}

				break;

			case 'expired' :

				$error = sprintf(
					esc_html__( 'The CIP %s expired', 'fullculqi' ),
					$cip_code
				);

				$log->set_error( $error );
				$order->update_status( 'cancelled', $error );

				break;

			case 'deleted' :

				$error = sprintf(
					esc_html__( 'The CIP %s was deleted', 'fullculqi' ),
					$cip_code
				);
				
				$log->set_error( $error );
				$order->update_status( 'cancelled', $error );

				break;
		}

		// Post status
		update_post_meta( $post_id, 'culqi_data', $culqi_order );
		update_post_meta( $post_id, 'culqi_status', $culqi_order->state );
		update_post_meta( $post_id, 'culqi_status_date', date('Y-m-d H:i:s') );

		do_action( 'fullculqi/orders/update', $culqi_order, $order );
	}


	/**
	 * Create Order Post
	 * @param  integer $post_id
	 * @param  objt $culqi_order
	 * @param  integer $post_customer_id
	 * @return integer
	 */
	public static function create_wppost( $culqi_order, $post_id = 0, $post_customer_id = 0 ) {

		if( empty( $post_id ) ) {

			// Create Post
			$args = [
				'post_title'	=> $culqi_order->id,
				'post_type'		=> 'culqi_orders',
				'post_status'	=> 'publish',
			];

			$post_id = wp_insert_post( $args );
		}

		//$creation = intval( $culqi_order->creation_date/1000 );
		//$expiration = intval( $culqi_order->expiration_date/1000 );
		$creation = intval( $culqi_order->creation_date );
		$expiration = intval( $culqi_order->expiration_date );
		$amount = round( $culqi_order->amount/100, 2 );

		update_post_meta( $post_id, 'culqi_id', $culqi_order->id );
		update_post_meta( $post_id, 'culqi_cip', $culqi_order->payment_code );
		update_post_meta( $post_id, 'culqi_data', $culqi_order );
		update_post_meta( $post_id, 'culqi_status', $culqi_order->state );
		update_post_meta( $post_id, 'culqi_status_date', date('Y-m-d H:i:s') );

		$basic = [
			'culqi_creation'		=> date( 'Y-m-d H:i:s', $creation ),
			'culqi_expiration'		=> date( 'Y-m-d H:i:s', $expiration ),
			'culqi_amount'			=> $amount,
			'culqi_currency'		=> $culqi_order->currency_code,
		];

		update_post_meta( $post_id, 'culqi_basic', $basic );

		// Metavalues
		if( isset( $culqi_order->metadata ) && ! empty( $culqi_order->metadata ) )
			update_post_meta( $post_id, 'culqi_metadata', $culqi_order->metadata );

		// Customers
		$customer = [
			'post_id'	=> 0,
			'culqi_email'		=> '',
			'culqi_first_name'	=> '',
			'culqi_last_name'	=> '',
			'culqi_city'		=> '',
			'culqi_country'		=> '',
			'culqi_phone'		=> '',
		];

		// Save customer
		if( ! empty( $post_customer_id ) )
			$customer[ 'post_id' ] = $post_customer_id;

		if( isset( $culqi_order->metadata->customer_email ) )
			$customer[ 'culqi_email' ] = $culqi_order->metadata->customer_email;

		if( isset( $culqi_order->metadata->customer_first ) )
			$customer[ 'culqi_first_name' ] = $culqi_order->metadata->customer_first;

		if( isset( $culqi_order->metadata->customer_last ) )
			$customer[ 'culqi_last_name' ] = $culqi_order->metadata->customer_last;

		if( isset( $culqi_order->metadata->customer_city ) )
			$customer[ 'culqi_city' ] = $culqi_order->metadata->customer_city;

		if( isset( $culqi_order->metadata->customer_country ) )
			$customer[ 'culqi_country' ] = $culqi_order->metadata->customer_country;

		if( isset( $culqi_order->metadata->customer_phone ) )
			$customer[ 'culqi_phone' ] = $culqi_order->metadata->customer_phone;

		// Customer
		update_post_meta( $post_id, 'culqi_customer', $customer );




		// WC Orders
		/*if( isset( $culqi_order->metadata->order_id ) &&
			get_post_type( $culqi_order->metadata->order_id ) == 'shop_order'
		)
			update_post_meta( $post_id, 'culqi_order_id', $culqi_order->metadata->order_id );
		else
			update_post_meta( $post_id, 'culqi_order_id', '' );*/


		do_action( 'fullculqi/orders/wppost', $culqi_order, $post_id );

		return $post_id;
	}

	/**
	 * Delete Posts
	 * @return mixed
	 */
	public static function delete_wpposts() {
		global $wpdb;

		$query = sprintf(
			'DELETE
				a, b, c
			FROM
				%s a
			LEFT JOIN
				%s b
			ON
				(a.ID = b.object_id)
			LEFT JOIN
				%s c
			ON
				(a.ID = c.post_id)
			WHERE
				a.post_type = "culqi_orders"',
			$wpdb->posts,
			$wpdb->term_relationships,
			$wpdb->postmeta
		);


		$wpdb->query( $query );

		do_action( 'fullculqi/orders/delete' );

		return true;
	}
}