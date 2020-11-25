<?php
/**
 * Webhooks Class
 * @since  1.0.0
 * @package Includes / Webhooks
 */
class FullCulqi_Webhooks {

	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'fullculqi/api/webhooks', [ $this, 'to_receive' ] );
	}

	/**
	 * Receives the notification
	 * @return mixed
	 */
	public function to_receive() {

		$inputJSON	= file_get_contents('php://input');

		if( empty( $inputJSON ) )
			return;

		$input = json_decode( $inputJSON );

		if( $input->object != 'event' )
			return;

		$data = json_decode( $input->data );

		switch( $input->type ) {
			case 'order.status.changed' : FullCulqi_Orders::update( $data ); break;
		}

		do_action( 'fullculqi/webhooks/to_receive', $culqi_order, $order, $log );
	}
}

new FullCulqi_Webhooks();