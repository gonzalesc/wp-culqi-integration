<?php
/**
 * Webhooks Class
 * @since  1.0.0
 * @package Includes / Webhooks
 */
class FullCulqi_Webhooks {

	protected $limit = 50;

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

		// Webhook History
		$this->register( $input );

		switch( $input->type ) {
			case 'order.status.changed' : FullCulqi_Orders::update( $data ); break;
		}

		do_action( 'fullculqi/webhooks/to_receive', $input, $data );
	}


	private function register( $input ) {

		$webhooks_saved = get_option( 'fullculqi_webhooks', [] );

		// Delete if it has many elements
		if( count( $webhooks_saved ) > $limit )
			array_pop( $webhooks_saved );

		$webhooks_in = [
			'event_id'		=> $input->id,
			'event_name'	=> $input->type,
			'creation_date'	=> fullculqi_convertToDate( $input->creation_date ),
		];

		array_unshift( $webhooks_saved, $webhooks_in );

		return true;
	}
}

new FullCulqi_Webhooks();