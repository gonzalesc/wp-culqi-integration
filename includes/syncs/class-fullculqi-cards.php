<?php
/**
 * Customers Class
 * @since  1.0.0
 * @package Includes / Sync / Customers
 */
class FullCulqi_Cards {

	/**
	 * Create Card
	 * @param  array  $args
	 * @return array
	 */
	public static function create( $args = [] ) {
		global $culqi;

		$args = apply_filters( 'fullculqi/cards/create/args', $args );

		try {
			$card = $culqi->Cards->create( $args );
		} catch(Exception $e) {
			return [ 'status' => 'error', 'data' => $e->getMessage() ];
		}

		if( ! isset( $card->object ) || $card->object == 'error' )
			return [ 'status' => 'error', 'data' => $customer->merchant_message ];

		do_action( 'fullculqi/cards/create', $card );

		return apply_filters( 'fullculqi/cards/create/success', [
			'status'	=> 'ok',
			'data'		=> [ 'culqi_card_id' => $card->id ]
		] );
	}
}