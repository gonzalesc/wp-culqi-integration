<?php
class FullCulqi_WC {
	public function __construct() {

		add_action('woocommerce_api_fullculqi_create_payment', [ $this, 'do_payment' ]);
		add_action('woocommerce_api_fullculqi_create_order', [ $this, 'do_order' ]);

		add_action('woocommerce_api_fullculqi_update_order', [ $this, 'update_order' ]);

	}

	public function do_payment() {

		if( isset($_POST) ) {
			$order_id 		= sanitize_key($_POST['order_id']);
			$token_id		= sanitize_text_field($_POST['token_id']);
			$country_code	= sanitize_text_field($_POST['country_code']);
			$installments 	= isset($_POST['installments']) ? (int)sanitize_key($_POST['installments']) : 0;

			$order = new WC_Order( $order_id );

			if( $order && wp_verify_nonce( $_POST['wpnonce'], 'fullculqi' ) ) {

				$provider_payment = array();

				// Logs
				$log = new FullCulqi_Logs();
				$log->set_settings_payment($order_id);


				if( apply_filters('fullculqi/do_payment/conditional', false, $order, $log) ) {
					
					$provider_payment = apply_filters('fullculqi/do_payment/create', $provider_payment, $token_id, $log, $order);

				} else {

					$provider_payment = FullCulqi_Checkout::simple($order, compact('token_id', 'installments', 'country_code'), $log );
				}


				// If empty
				if( count($provider_payment) == 0 ) {

					$log->set_msg_payment('error', __('Culqi Provider Payment error : There was not set any payment','letsgo') );

					$provider_payment = array( 'status' => 'error' );
				}

				wp_send_json($provider_payment);
			}
		}
		
		die();
	}


	public function do_order() {
		if( isset($_POST) ) {
			$order_id 		= sanitize_key($_POST['order_id']);
			$cip_code		= sanitize_key($_POST['cip_code']);

			$order = new WC_Order( $order_id );

			if( $order && wp_verify_nonce( $_POST['wpnonce'], 'fullculqi' ) ) {

				$provider_order = array();

				// Logs
				$log = new FullCulqi_Logs();
				$log->set_settings_payment($order_id);


				if( apply_filters('fullculqi/do_order/conditional', false, $order, $log) ) {
					
					$provider_order = apply_filters('fullculqi/do_order/create', $provider_order, $cip_code, $log, $order);

				} else {

					$provider_order = FullCulqi_Checkout::process_order($order, $cip_code, $log );
				}


				// If empty
				if( count($provider_order) == 0 ) {

					$log->set_msg_payment('error', __('Culqi Provider Order error : There was not set any payment','letsgo') );

					$provider_order = array( 'status' => 'error' );
				}

				wp_send_json($provider_order);
			}
		}
		
		die();
	}


	public function update_order() {

		update_option('kalep_1', 'peticion 1');
		
		$inputJSON	= file_get_contents('php://input');
		$input 		= json_decode($inputJSON);

		update_option('kalep_3', print_r($input,true));
		update_option('kalep_2', print_r($inputJSON,true));

		http_response_code(200);
		echo wp_send_json( ['result' => 'success' ] );
		
		die();

		$data 		= json_decode($input->data);

		if( $input->object == 'event' && $input->type == 'order.status.changed' ) {
		
			global $wpdb, $woocommerce;

			$order_id = fullculqi_postid_from_meta('culqi_cip', $data->payment_code);

			if( $order_id ) {

				$order = new WC_Order( $order_id );

				switch($data->state) {
					case 'paid' :
						$order->payment_complete();
						break;

					case 'expired' : 
						$order->update_status( 'failed', __('The order was not paid on time','letsgo') );
						break;

					case 'deleted' :
						$order->update_status( 'cancelled', __('The order was not paid on time','letsgo') );
						break;
				}

				echo wp_send_json( ['result' => 'success' ] );
				http_response_code(200);
			}
		}
		die();
	}
}
?>