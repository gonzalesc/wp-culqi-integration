<?php
/**
 * Customers Class
 * @since  1.0.0
 * @package Includes / Sync / Customers
 */
class FullCulqi_Cards {


	public static function create( $customer_id = '', $post_data = [] ) {

		$token_id 	= sanitize_text_field( $post_data['token_id'] );
		$order = wc_get_order( absint( $post_data['order_id'] ) );

		if( ! $order )
			return false;

		global $culqi;

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		$args = apply_filters( 'fullculqi/cards/create/args', [
			'customer_id'	=> $customer_id,
			'token_id'		=> $token_id,
		], $order );

		
		try {
			$card = $culqi->Cards->create( $args );
		} catch(Exception $e) {
			$log->set_error( $e->getMessage() );
			return false;
		}

		if( ! isset( $card->object ) || $card->object == 'error' )
			$log->set_error( $card->merchant_message );

		do_action( 'fullculqi/cards/create', $order, $card );

		return apply_filters( 'fullculqi/cards/create/success', $card->id, $order );
	}
}