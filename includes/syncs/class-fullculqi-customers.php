<?php
/**
 * Customers Class
 * @since  1.0.0
 * @package Includes / Sync / Customers
 */
class FullCulqi_Customers {

	/**
	 * Sync from Culqi
	 * @param  integer $records
	 * @return mixed
	 */
	public static function sync( $records = 100 ) {
		global $culqi;

		// Connect to the API Culqi
		try {
			$customers = $culqi->Customers->all( [ 'limit' => $records ] );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $customers->data ) || empty( $customers->data ) )
			return [ 'status' => 'error', 'data' => $customers->merchant_message ];


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
						p.post_type = "culqi_customers" AND
						m.meta_key = "culqi_id" AND
						m.meta_value <> ""';

		$results = $wpdb->get_results( $query );
		$keys = [];

		// Keys Post Type 
		foreach( $results as $result )
			$keys[ $result->culqi_id ] = $result->post_id;

		// Culqi customers
		foreach( $customers->data as $customer ) {

			$post_id = 0;

			// Check if is update
			if( isset( $keys[ $customer->id ] ) )
				$post_id = $keys[ $customer->id ];

			$post_id = self::create_wppost( $customer, $post_id );

			do_action( 'fullculqi/customers/sync/loop', $customer, $post_id );
		}

		do_action( 'fullculqi/customers/sync/after', $customers );

		return [ 'status' => 'ok' ];
	}


	/**
	 * Get Customer from meta values
	 * @param  integer $wpuser_id
	 * @return bool
	 */
	public static function get( $wpuser_id = 0 ) {

		// Check in the WP_USERS
		$culqi_customer_id = get_user_meta( $wpuser_id, 'culqi_id', true );

		if( ! empty( $culqi_customer_id ) )
			return $culqi_customer_id;

		global $wpdb;

		// Check in the Customer CPT
		$meta_key = 'culqi_user_id';
		$meta_value = absint( $wpuser_id );

		$query = 'SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key=%s && meta_value=%s';
		$query = $wpdb->prepare( $query, $meta_key, $meta_value );
		$post_id = $wpdb->get_var( $query );

		if( empty( $post_id ) )
			return false;

		$culqi_customer_id = get_post_meta( $post_id, 'culqi_id', true );

		if( empty( $culqi_customer_id ) )
			return false;

		return $culqi_customer_id;
	}


	
	/**
	 * Create Customer
	 * @param  integer $wpuser_id
	 * @param  array   $post_data
	 * @return mixed
	 */
	public static function create( $wpuser_id = 0, $post_data = [] ) {

		$country_code = sanitize_text_field( $post_data['country_code'] );

		$order = wc_get_order( absint( $post_data['order_id'] ) );

		if( ! $order )
			return false;

		global $culqi;

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );


		$args = [
			'email'		=> $order->get_billing_email(),
			'metadata'	=> [ 'user_id' => $wpuser_id ],
		];

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_phone 			= $order->get_billing_phone();
		$billing_address_1 		= $order->get_billing_address_1();
		$billing_city 			= $order->get_billing_city();
		$billing_country 		= $order->get_billing_country();


		if( ! empty( $billing_first_name ) )
			$args['first_name'] = $billing_first_name;

		if( ! empty( $billing_last_name ) )
			$args['last_name'] = $billing_last_name;

		if( ! empty( $billing_phone ) )
			$args['phone_number'] = $billing_phone;

		if( ! empty( $billing_address_1 ) )
			$args['address'] = $billing_address_1;

		if( ! empty( $billing_city ) )
			$args['address_city'] = $billing_city;

		if( ! empty( $billing_country ) )
			$args['country_code'] = $billing_country;
		else
			$args['country_code'] = $country_code;


		$args_customer = apply_filters( 'fullculqi/customers/create/args', $args, $order );

		try {
			$customer = $culqi->Customers->create( $args_customer );
		} catch( Exception $e ) {
			$log->set_error( $e->getMessage() );
			return false;
		}

		if( ! isset( $customer->object ) || $customer->object == 'error' ) {
			$log->set_error( $customer->merchant_message );
			return false;
		}

		$notice = sprintf( esc_html__( 'Culqi Customer Created: %s', 'fullculqi' ), $customer->id );
		$log->set_notice( $notice );

		// Update meta culqi id in wc order
		update_post_meta( $order->get_id(), 'culqi_customer_id', $customer->id );

		// Update meta culqi id in user meta
		update_user_meta( $wpuser_id, 'culqi_id', $customer->id );

		// Create Order Post
		$post_id = self::create_wppost( $customer );

		// Log
		$notice = sprintf( esc_html__( 'Post Customer Created: %s', 'fullculqi' ), $post_id );
		$log->set_notice( $notice );

		// Update meta post in wc order
		update_post_meta( $order->get_id(), 'post_customer_id', $post_id );

		do_action( 'fullculqi/customers/create', $order, $customer );

		return apply_filters( 'fullculqi/customers/create/success', $customer->id, $order );
	}


	/**
	 * Get or Create Customer
	 * @param  integer $wpuser_id
	 * @param  array   $post_data
	 * @return mixed
	 */
	public static function get_or_create( $wpuser_id = 0, $post_data = [] ) {

		$culqi_customer_id = self::get( $wpuser_id );

		if( ! empty( $culqi_customer_id ) )
			return $culqi_customer_id;

		$culqi_customer = self::create( $wpuser_id, $post_data );

		if( $culqi_customer['status'] == 'ok' )
			return $culqi_customer['data'];

		return false;
	}


	/**
	 * Create Order Post
	 * @param  integer $post_id
	 * @param  objt $customer
	 * @return integer
	 */
	public static function create_wppost( $customer, $post_id = 0 ) {

		if( empty( $post_id ) ) {

			// Create Post
			$args = [
				'post_title'	=> $customer->id,
				'post_type'		=> 'culqi_customers',
				'post_status'	=> 'publish',
			];

			$post_id = wp_insert_post( $args );
		}

		$creation = intval( $customer->creation_date/1000 );
		$names = $customer->antifraud_details->first_name . ' ' . $customer->antifraud_details->last_name;

		update_post_meta( $post_id, 'culqi_id', $customer->id );
		update_post_meta( $post_id, 'culqi_data', $customer );
		update_post_meta( $post_id, 'culqi_email', $customer->email );

		$basic = [
			'culqi_creation'	=> date('Y-m-d H:i:s', $creation),
			'culqi_first_name'	=> $customer->antifraud_details->first_name,
			'culqi_last_name'	=> $customer->antifraud_details->last_name,
			'culqi_names'		=> $names,
			'culqi_address'		=> $customer->antifraud_details->address,
			'culqi_city'		=> $customer->antifraud_details->address_city,
			'culqi_country'		=> $customer->antifraud_details->country_code,
			'culqi_phone'		=> $customer->antifraud_details->phone,
		];

		update_post_meta( $post_id, 'culqi_basic', $basic );

		// Get WPUser
		$user = get_user_by( 'email', $customer->email );

		if( $user ) {
			update_post_meta( $post_id, 'culqi_user_id', $user->ID );
			
			update_user_meta( $user->ID, 'culqi_id', $customer->id );
			update_user_meta( $user->ID, 'culqi_post_id', $post_id );
		}


		do_action( 'fullculqi/customers/wppost', $customer, $post_id );

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
				a.post_type = "culqi_customers"',
			$wpdb->posts,
			$wpdb->term_relationships,
			$wpdb->postmeta
		);

		$wpdb->query( $query );

		do_action( 'fullculqi/customers/delete' );

		return true;
	}
}