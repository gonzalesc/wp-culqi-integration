<?php
/**
 * Charges Class
 * @since  1.0.0
 * @package Includes / Sync / Charges
 */
class FullCulqi_Charges {

	/**
	 * Sync from Culqi
	 * @param  integer $records
	 * @return mixed
	 */
	public static function sync( $records = 100 ) {
		global $culqi;

		// Connect to the API Culqi
		try {
			$charges = $culqi->Charges->all( [ 'limit' => $records ] );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( isset( $charges->object ) && $charges->object == 'error' )
			return [ 'status' => 'error', 'data' => $charges->merchant_message ];


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
						p.post_type = "culqi_charges" AND
						m.meta_key = "culqi_id" AND
						m.meta_value <> ""';

		$results = $wpdb->get_results( $query );
		$keys = [];

		// Keys Post Type 
		foreach( $results as $result )
			$keys[ $result->culqi_id ] = $result->post_id;

		// Culqi charges
		foreach( $charges as $charge ) {

			$post_id = 0;

			// Check if is update
			if( isset( $keys[ $charge->id ] ) )
				$post_id = $keys[ $charge->id ];

			// Create Charge Post
			$post_id = self::create_wppost( $charge, $post_id );
		}

		do_action( 'fullculqi/charges/sync/after', $charges );

		return [ 'status' => 'ok' ];
	}


	/**
	 * Create a charge
	 * @param  array  $post_data
	 * @return bool
	 */
	public static function create( $args = [] ) {
		global $culqi;

		$args = apply_filters( 'fullculqi/charges/create/args', $args );

		try {
			$charge = $culqi->Charges->create( $args );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		// Check request from Culqi
		if( ! isset( $charge->object ) || $charge->object == 'error' ) {
			return [ 'status' => 'error', 'data' => $charge->merchant_message ];
		}

		// Create wppost
		$post_id = self::create_wppost( $charge );

		do_action( 'fullculqi/charges/create', $post_id, $charge );
		
		return apply_filters( 'fullculqi/charges/create/success', [
			'status'	=> 'ok',
			'data'		=> [ 'culqi_charge_id' => $charge->id, 'post_charge_id' => $post_id ]
		] );
	}


	/**
	 * Create WPPosts
	 * @param  object  $charge  
	 * @param  integer $post_id 
	 * @return mixed
	 */
	public static function create_wppost( $charge, $post_id = 0 ) {

		if( empty( $post_id ) ) {
			
			$args = [
				'post_title'	=> $charge->id,
				'post_type'		=> 'culqi_charges',
				'post_status'	=> 'publish'
			];

			$post_id = wp_insert_post( $args );
		}

		$creation_date = intval( $charge->creation_date/1000 );
		$capture_date = intval( $charge->capture_date/1000 );

		$amount = round( $charge->amount/100, 2 );
		$refund = round( $charge->amount_refunded/100, 2 );

		update_post_meta( $post_id, 'culqi_id', $charge->id );
		update_post_meta( $post_id, 'culqi_capture', $charge->capture );
		update_post_meta( $post_id, 'culqi_capture_date', date( 'Y-m-d H:i:s', $capture_date ) );
		update_post_meta( $post_id, 'culqi_data', $charge );
		
		update_post_meta( $post_id, 'culqi_ip', $charge->source->client->ip );

		// Customer
		$post_customer_id = $culqi_customer_id = false;

		// If it use customer process
		if( isset( $charge->source->object ) && $charge->source->object == 'card' ) {
			$culqi_customer_id = $charge->source->customer_id;
		}


		// Status
		$status = $charge->capture ? 'captured' : 'authorized';
		update_post_meta( $post_id, 'culqi_status', $status );

		// Meta Values
		if( isset( $charge->metadata ) && ! empty( $charge->metadata ) ) {
			$post_customer_id = isset( $charge->metadata->post_customer ) ? $charge->metadata->post_customer : false;

			update_post_meta( $post_id, 'culqi_metadata', $charge->metadata );
		}

		$basic = [
			'culqi_creation'		=> date( 'Y-m-d H:i:s', $creation_date ),
			'culqi_amount'			=> $amount,
			'culqi_amount_refunded'	=> $refund,
			'culqi_currency'		=> $charge->currency_code,
		];

		/*
			'culqi_card_brand'		=> $charge->source->iin->card_brand,
			'culqi_card_type'		=> $charge->source->iin->card_type,
			'culqi_card_number'		=> $charge->source->card_number,
		 */

		update_post_meta( $post_id, 'culqi_basic', array_map( 'esc_html', $basic ) );

		$customer = [
			'culqi_id'			=> $culqi_customer_id,
			'post_id'			=> $post_customer_id,
			'culqi_email'		=> $charge->email,
			'culqi_first_name'	=> $charge->antifraud_details->first_name,
			'culqi_last_name'	=> $charge->antifraud_details->last_name,
			'culqi_city'		=> $charge->antifraud_details->address_city,
			'culqi_country'		=> $charge->antifraud_details->country_code,
			'culqi_phone'		=> $charge->antifraud_details->phone,
		];

		update_post_meta( $post_id, 'culqi_customer', array_map( 'esc_html', $customer ) );

		do_action( 'fullculqi/charges/wppost', $charge, $post_id );

		return $post_id;
	}


	/**
	 * Delete Posts
	 * @return [type] [description]
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
				a.post_type = "culqi_charges"',
			$wpdb->posts,
			$wpdb->term_relationships,
			$wpdb->postmeta
		);


		$wpdb->query( $query );

		do_action( 'fullculqi/charges/delete' );

		return true;
	}
}