<?php
class FullCulqi_Checkout {

	static public function simple($order, $culqi_token, $log) {

		$pnames = $provider_payment = array();
		$method_array = fullculqi_get_woo_settings();

		extract($culqi_token);

		$log->set_msg_payment('notice', __('This order is a simple payment','letsgo') );

		foreach ($order->get_items() as $item ) {
			$_product = $item->get_product();
			$pnames[] = $_product->get_name();
		}

		// Antifraud Customer Data
		$antifraud = array( 'email' => $order->get_billing_email() );

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_address_1 		= $order->get_billing_address_1();
		$billing_phone 			= $order->get_billing_phone();
		$billing_city 			= $order->get_billing_city();
		$billing_country 		= $order->get_billing_country();

		if( !empty( $billing_first_name ) )
			$antifraud['first_name'] = $billing_first_name;

		if( !empty( $billing_last_name ) )
			$antifraud['last_name'] = $billing_last_name;

		if( !empty( $billing_address_1 ) )
			$antifraud['address'] = $billing_address_1;

		if( !empty( $billing_city ) )
			$antifraud['address_city'] = $billing_city;

		if( !empty( $billing_country ) )
			$antifraud['country_code'] = $billing_country;

		if( !empty( $billing_phone ) )
			$antifraud['phone_number'] = $billing_phone;
		

		// Metadata Order
		$metadata = array(
						'order_id'	=> $order->get_id(),
						'order_key'	=> $order->get_order_key(),
					);

		$args_payment = array(
							'amount'			=> (int)($order->get_total()*100),
							'currency_code'		=> get_woocommerce_currency(),
							'description'		=> substr(str_pad(implode(', ', $pnames), 5, '_'), 0, 80),
							'capture'			=> true,
							'email'				=> $order->get_billing_email(),
							'installments'		=> $installments,
							'source_id'			=> $token_id,
							'metadata'			=> $metadata,
							'antifraud_details'	=> $antifraud,
						);

		$provider_payment = FullCulqi_Provider::create_payment($args_payment);

		if( $provider_payment['status'] == 'ok' ) {
		
			$note = sprintf(__('Culqi Payment created: %s','letsgo'), $provider_payment['data']->id);
			$order->add_order_note($note);

			$log->set_msg_payment('notice', sprintf(__('Culqi Payment created: %s','letsgo'), $provider_payment['data']->id) );

			$post_id = FullCulqi_Integrator::create_payment($provider_payment['data']);

			$log->set_msg_payment('notice', sprintf(__('Post Payment created : %s','letsgo'), $post_id) );


			if( $method_array['status_success'] == 'wc-completed')
				$order->payment_complete();
			else
				$order->update_status($method_array['status_success']);

			$provider_payment = apply_filters('fullculqi/checkout/simple_success', $provider_payment, $log, $order);
		
		} else {

			$log->set_msg_payment('error', sprintf(__('Culqi Payment error : %s','letsgo'), $provider_payment['msg']) );

			$provider_payment = apply_filters('fullculqi/checkout/simple_error', $provider_payment, $log, $order);
		}
		
		return $provider_payment;
	}
}