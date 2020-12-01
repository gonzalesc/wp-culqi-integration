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

		if( isset( $customers->object ) && $customers->object == 'error' )
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
		$post_customer_id = get_user_meta( $wpuser_id, 'culqi_post_id', true );

		if( ! empty( $culqi_customer_id ) && ! empty( $post_customer_id ) ) {
			return [
				'culqi_id'	=> $culqi_customer_id,
				'post_id'	=> $post_customer_id
			];
		}

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

		return [
			'culqi_id'	=> $culqi_customer_id,
			'post_id'	=> $post_id
		];
	}


	
	/**
	 * Create Customer
	 * @param  integer $wpuser_id
	 * @param  array   $post_data
	 * @return mixed
	 */
	public static function create( $wpuser_id = 0, $args = [] ) {
		global $culqi;

		$args_customer = apply_filters( 'fullculqi/customers/create/args', $args );

		try {
			$customer = $culqi->Customers->create( $args_customer );
		} catch( Exception $e ) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $customer->object ) || $customer->object == 'error' ) {
			return [ 'status' => 'error', 'data' => $customer->merchant_message ];
		}

		// Update meta culqi id in user meta
		update_user_meta( $wpuser_id, 'culqi_id', $customer->id );

		// Create Order Post
		$post_id = self::create_wppost( $customer );

		do_action( 'fullculqi/customers/create', $post_id, $customer );

		return apply_filters( 'fullculqi/customers/create/success', [
			'status'	=> 'ok',
			'data'		=> [ 'culqi_customer_id' => $customer->id, 'post_customer_id' => $post_id ]
		] );
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

		$names = $customer->antifraud_details->first_name . ' ' . $customer->antifraud_details->last_name;

		update_post_meta( $post_id, 'culqi_id', $customer->id );
		update_post_meta( $post_id, 'culqi_data', $customer );
		update_post_meta( $post_id, 'culqi_email', $customer->email );

		$basic = [
			'culqi_creation'	=> fullculqi_convertToDate( $customer->creation_date ),
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

		// Usermeta
		$query = sprintf(
			'DELETE FROM
				%s
			WHERE
				meta_key IN ("culqi_id", "culqi_post_id")',
			$wpdb->usermeta
		);

		$wpdb->query( $query );

		do_action( 'fullculqi/customers/delete' );

		return true;
	}
}