<?php
/**
 * Refunds Class
 * @since  1.0.0
 * @package Includes / Sync / Refunds
 */
class FullCulqi_Refunds {

	/**
	 * Create Refund
	 * @param  WP_POST $order
	 * @param  float  $amount
	 * @param  string $reason
	 * @return bool
	 */
	public static function create( $order, $amount = 0.00, $reason = '' ) {

		global $culqi;

		// Logs
		$log = new FullCulqi_Logs( $order->get_id() );

		// Meta
		$culqi_charge_id	= get_post_meta( $order->get_id(), 'culqi_charge_id', true );
		$culqi_post_id		= get_post_meta( $order->get_id(), 'post_charge_id', true );

		$args = apply_filters( 'fullculqi/refunds/create/args', [
			'amount'	=> round( $amount*100, 0 ),
			'charge_id'	=> $culqi_charge_id,
			'reason'	=> 'solicitud_comprador',
		], $order );


		try {
			$refunds = $culqi->Refunds->create( $args );
		} catch(Exception $e) {
			$log->set_error( $e->getMessage() );
			return false;
		}


		if( ! isset( $refunds->object ) || $refunds->object == 'error' ) {
			$log->set_error( $refund->merchant_message );
			return false;
		}

		// Logs
		$log->set_notice( sprintf(
			esc_html__( 'Culqi Refund created: %s', 'fullculqi' ),
			$refunds->id
		) );


		update_post_meta( $culqi_post_id, 'culqi_data', $refunds );
		update_post_meta( $culqi_post_id, 'culqi_status', 'refunded' );

		// Save Refund
		$basic = get_post_meta( $culqi_post_id, 'culqi_basic', true );
		$refunds_ids = (array)get_post_meta( $culqi_post_id, 'culqi_ids_refunded', true );
		
		$refunds_ids[ $refunds->id ] = number_format( $refunds->amount / 100, 2, '.', '' );
		
		$basic['culqi_amount_refunded'] = array_sum( $refunds_ids );

		update_post_meta( $culqi_post_id, 'culqi_basic', $basic );
		update_post_meta( $culqi_post_id, 'culqi_ids_refunded', $refunds_ids );

		return true;
	}

}