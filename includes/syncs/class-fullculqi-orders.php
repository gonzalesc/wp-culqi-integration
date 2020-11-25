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
			$orders = $culqi->Orders->all( [ 'limit' => $records ] );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $orders->data ) || empty( $orders->data ) )
			return [ 'status' => 'error', 'data' => $orders->merchant_message ];


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
		foreach( $orders->data as $culqi_order ) {

			$post_id = 0;

			// Check if is update
			if( isset( $keys[ $culqi_order->id ] ) )
				$post_id = $keys[ $culqi_order->id ];

			$post_id = self::create_wppost( $culqi_order, $post_id );

			do_action( 'fullculqi/orders/sync/loop', $culqi_order, $post_id );
		}

		do_action( 'fullculqi/orders/sync/after', $orders );

		return [ 'status' => 'ok' ];
	}



	/**
	 * Create Order
	 * @param  WP_POST $order
	 * @return array
	 */
	public static function create( $order ) {

		$method = get_option( 'woocommerce_fullculqi_settings' );

		if( empty( $method ) )
			return [ 'status' => 'error', 'data' => esc_html__( 'Gateway FullCulqi no customized', 'fullculqi' ) ];

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		global $culqi;

		// Antifraud Customer Data
		$client_details = [ 'email' => $order->get_billing_email() ];

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_phone 			= $order->get_billing_phone();

		if( ! empty( $billing_first_name ) )
			$client_details['first_name'] = $billing_first_name;

		if( ! empty( $billing_last_name ) )
			$client_details['last_name'] = $billing_last_name;

		if( ! empty( $billing_phone ) )
			$client_details['phone_number'] = $billing_phone;


		// Description
		$pnames = [];

		foreach( $order->get_items() as $item ) {
			$product = $item->get_product();
			$pnames[] = $product->get_name();
		}

		$desc = count( $pnames ) == 0 ? 'Product' : implode(', ', $pnames);

		$args_order = apply_filters( 'fullculqi/orders/create/args', [
			'amount'			=> fullculqi_format_total( $order->get_total() ),
			'currency_code'		=> $order->get_currency(),
			'description'		=> substr( str_pad( $desc, 5, '_' ), 0, 80 ),
			'order_number'		=> $order->get_order_number(),
			'client_details'	=> $client_details,
			'confirm'			=> false,
			'expiration_date'	=> time() + ( $method['multi_duration'] * HOUR_IN_SECONDS ),
			'metadata'			=> [
				'order_id'			=> $order->get_id(),
				'order_number'		=> $order->get_order_number(),
				'order_key'			=> $order->get_order_key(),
				'customer_email'	=> $order->get_billing_email(),
				'customer_first'	=> $order->get_billing_first_name(),
				'customer_last'		=> $order->get_billing_last_name(),
				'customer_city'		=> $order->get_billing_city(),
				'customer_country'	=> $order->get_billing_country(),
				'customer_phone'	=> $order->get_billing_phone(),
			],
		], $order);

		try {
			$culqi_order = $culqi->Orders->create( $args_order );
		} catch( Exception $e ) {
			$error = sprintf(
				esc_html__( 'Culqi Multipayment Error : %s', 'fullculqi' ),
				$e->getMessage()
			);
			$log->set_error( $error );
			return false;
		}

		if( ! isset( $culqi_order->object ) || $culqi_order->object == 'error' ) {
			$error = sprintf(
				esc_html__( 'Culqi Multipayment Error : %s', 'fullculqi' ),
				$culqi_order->merchant_message
			);
			$log->set_error( $error );
			return false;
		}

		// Update meta culqi id in wc order
		update_post_meta( $order->get_id(), 'culqi_order_id', $culqi_order->id );

		$notice = sprintf(
			esc_html__( 'Culqi Multipayment Created : %s', 'fullculqi' ),  $culqi_order->id
		);
		$log->set_notice( $notice );

		// Create Order Post
		$post_id = self::create_wppost( $culqi_order );

		// Log
		$notice = sprintf( esc_html__( 'Post Multipayment Created: %s', 'fullculqi' ), $post_id );
		$log->set_notice( $notice );

		// Update meta post in wc order
		update_post_meta( $order->get_id(), 'post_order_id', $post_id );

		do_action( 'fullculqi/orders/create', $order, $culqi_order );

		return apply_filters( 'fullculqi/orders/create/success', $culqi_order->id, $order );
	}


	/**
	 * Create the CIP Code
	 * @param  array  $post_data
	 * @return [type]            [description]
	 */
	public static function confirm( $post_data = [] ) {

		if( ! isset( $post_data['order_id'] ) || ! isset( $post_data['cip_code'] ) )
			return false;

		$method = get_option( 'woocommerce_fullculqi_settings', [] );

		if( empty( $method ) )
			return false;


		// Variables
		$order = wc_get_order( absint( $post_data['order_id'] ) );
		$cip_code = esc_html( $post_data['cip_code'] );

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		$notice = sprintf(
			esc_html__( 'Culqi Multipayment CIP: %s', 'fullculqi' ), $cip_code
		);

		$log->set_notice( $notice );
		$order->add_order_note( $notice );

		// Status Orders
		if( $method['multi_status'] == 'wc-completed' )
			$order->payment_complete();
		else
			$order->update_status( $method['multi_status'] );


		// Update CIP CODE in WPPost Order
		$post_id = get_post_meta( $order->get_id(), 'post_order_id', true );
		update_post_meta( $post_id, 'culqi_cip', $cip_code );

		// Update CIP CODE in WC Order
		update_post_meta( $order->get_id(), 'culqi_cip', $cip_code );

		do_action( 'fullculqi/orders/confirm', $order );

		return true;
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

		do_action( 'fullculqi/orders/update', $culqi_order, $order, $log );
	}


	/**
	 * Create Order Post
	 * @param  integer $post_id
	 * @param  objt $culqi_order
	 * @return integer
	 */
	public static function create_wppost( $culqi_order, $post_id = 0 ) {

		if( empty( $post_id ) ) {

			// Create Post
			$args = [
				'post_title'	=> $culqi_order->id,
				'post_type'		=> 'culqi_orders',
				'post_status'	=> 'publish',
			];

			$post_id = wp_insert_post( $args );
		}

		$creation = intval( $culqi_order->creation_date/1000 );
		$expiration = intval( $culqi_order->expiration_date/1000 );
		$amount = round( $culqi_order->amount/100, 2 );

		update_post_meta( $post_id, 'culqi_id', $culqi_order->id );
		update_post_meta( $post_id, 'culqi_cip', $culqi_order->payment_code );
		update_post_meta( $post_id, 'culqi_data', $culqi_order );
		update_post_meta( $post_id, 'culqi_status', $culqi_order->state );

		$basic = [
			'culqi_creation'		=> date( 'Y-m-d H:i:s', $creation ),
			'culqi_expiration'		=> date( 'Y-m-d H:i:s', $expiration ),
			'culqi_amount'			=> $amount,
			'culqi_currency'		=> $culqi_order->currency_code,
		];

		update_post_meta( $post_id, 'culqi_basic', $basic );

		// Customers
		$customer = [
			'culqi_email'		=> '',
			'culqi_first_name'	=> '',
			'culqi_last_name'	=> '',
			'culqi_city'		=> '',
			'culqi_country'		=> '',
			'culqi_phone'		=> '',
		];

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

		
		update_post_meta( $post_id, 'culqi_customer', $customer );


		// WC Orders
		if( isset( $culqi_order->metadata->order_id ) &&
			get_post_type( $culqi_order->metadata->order_id ) == 'shop_order'
		)
			update_post_meta( $post_id, 'culqi_order_id', $culqi_order->metadata->order_id );
		else
			update_post_meta( $post_id, 'culqi_order_id', '' );


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