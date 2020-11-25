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
	public static function create( $post_data = [] ) {

		$token_id		= sanitize_text_field( $post_data['token_id'] );
		$country_code	= sanitize_text_field( $post_data['country_code'] );
		$installments	= sanitize_text_field( $post_data['installments'] );

		$order = wc_get_order( absint( $post_data['order_id'] ) );

		if( ! $order )
			return false;

		global $culqi;

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		// Description
		$pnames = [];

		foreach( $order->get_items() as $item ) {
			$product = $item->get_product();
			$pnames[] = $product->get_name();
		}

		$desc = count( $pnames ) == 0 ? 'Product' : implode(', ', $pnames);
		

		// Antifraud Customer Data
		$antifraud = [ 'email' => $order->get_billing_email() ];

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_address_1 		= $order->get_billing_address_1();
		$billing_phone 			= $order->get_billing_phone();
		$billing_city 			= $order->get_billing_city();
		$billing_country 		= $order->get_billing_country();

		if( ! empty( $billing_first_name ) )
			$antifraud['first_name'] = $billing_first_name;

		if( ! empty( $billing_last_name ) )
			$antifraud['last_name'] = $billing_last_name;

		if( ! empty( $billing_address_1 ) )
			$antifraud['address'] = $billing_address_1;

		if( ! empty( $billing_city ) )
			$antifraud['address_city'] = $billing_city;

		if( ! empty( $billing_country ) )
			$antifraud['country_code'] = $billing_country;
		elseif( ! empty($country_code) )
			$antifraud['country_code'] = $country_code;

		if( ! empty( $billing_phone ) )
			$antifraud['phone_number'] = $billing_phone;
		

		// Metadata Order
		$metadata = [
			'order_id'		=> $order->get_id(),
			'order_number'	=> $order->get_order_number(),
			'order_key'		=> $order->get_order_key(),
		];

		//$args_payment = apply_filters('fullculqi/checkout/simple_args', [
		$args = apply_filters('fullculqi/charges/create/args', [
			'amount'			=> fullculqi_format_total( $order->get_total() ),
			'currency_code'		=> $order->get_currency(),
			'description'		=> substr( str_pad( $desc, 5, '_' ), 0, 80 ),
			'capture'			=> true,
			'email'				=> $order->get_billing_email(),
			'installments'		=> $installments,
			'source_id'			=> $token_id,
			'metadata'			=> $metadata,
			'antifraud_details'	=> $antifraud,
		], $order );


		try {
			$charge = $culqi->Charges->create( $args );
		} catch(Exception $e) {
			$log->set_error( $e->getMessage() );
			return false;
		}

		// Check request from Culqi
		if( ! isset( $charge->object ) || $charge->object == 'error' ) {
			$log->set_error( $charge->merchant_message );
			return false;
		}

		// Meta value
		update_post_meta( $order->get_id(), 'culqi_charge_id', $charge->id );

		// Log
		$notice = sprintf(
			esc_html__( 'Culqi Charge created: %s', 'fullculqi' ),
			$charge->id
		);

		$order->add_order_note( $notice );
		$log->set_notice( $notice );

		// Create wppost
		$post_id = self::create_wppost( $charge );

		// Log
		$notice = sprintf( esc_html__( 'Post Charge Created: %s', 'fullculqi' ), $post_id );
		$log->set_notice( $notice );

		// Update PostID in WC-Order
		update_post_meta( $order->get_id(), 'post_charge_id', $post_id );

		// Settings WC
		$method = get_option( 'woocommerce_fullculqi_settings', [] );
			
		if( $method['status_success'] == 'wc-completed')
			$order->payment_complete();
		else {
			$order->update_status( $method['status_success'],
				sprintf(
					esc_html__( 'Status changed by FullCulqi (to %s)', 'fullculqi' ),
					$method['status_success']
				)
			);
		}

		do_action( 'fullculqi/charges/create', $order, $charge );
		
		return apply_filters( 'fullculqi/charges/create/success', $charge->id, $order );
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

		$creation = intval( $charge->creation_date/1000 );
		$capture = intval( $charge->capture_date/1000 );

		$amount = round( $charge->amount/100, 2 );
		$refund = round( $charge->amount_refunded/100, 2 );

		update_post_meta( $post_id, 'culqi_id', $charge->id );
		update_post_meta($post_id, 'culqi_capture', $charge->capture);
		update_post_meta($post_id, 'culqi_capture_date', $capture);
		update_post_meta( $post_id, 'culqi_data', $charge );
		update_post_meta( $post_id, 'culqi_ip', $charge->source->client->ip );

		// Status
		$status = $charge->capture ? 'captured' : 'authorized';
		update_post_meta( $post_id, 'culqi_status', $status );

		// WC Order
		if( isset( $charge->metadata->order_id ) )
			update_post_meta( $post_id, 'culqi_order_id', esc_html( $charge->metadata->order_id ) );

		$basic = [
			'culqi_creation'		=> date( 'Y-m-d H:i:s', $creation ),
			'culqi_amount'			=> $amount,
			'culqi_amount_refunded'	=> $refund,
			'culqi_currency'		=> $charge->currency_code,
			'culqi_card_brand'		=> $charge->source->iin->card_brand,
			'culqi_card_type'		=> $charge->source->iin->card_type,
			'culqi_card_number'		=> $charge->source->card_number,
		];

		update_post_meta( $post_id, 'culqi_basic', array_map( 'esc_html', $basic ) );

		$customer = [
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