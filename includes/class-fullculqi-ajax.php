<?php
class FullCulqi_Ajax {

	public function __construct() {

		add_action('wp_ajax_fullculqi', [ $this, 'do_payment'] );
		add_action('wp_ajax_nopriv_fullculqi', [ $this, 'do_payment']);

		add_action('wp_ajax_fullculqi_get_payments', [ $this, 'get_payments'] );
		add_action('wp_ajax_fullculqi_delete_all', [$this, 'delete_all'] );
	}


	public function do_payment() {

		if( isset($_POST) ) {
			global $culqi;

			$order_id 		= esc_html($_POST['order_id']);
			$token_id		= esc_html($_POST['token_id']);
			$installments 	= isset($_POST['installments']) ? (int)esc_html($_POST['installments']) : 0;

			$order = new WC_Order( $order_id );

			if( $order && wp_verify_nonce( $_POST['wpnonce'], 'fullculqi' ) ) {

				$pnames = array();

				$method_array	= fullculqi_get_woo_settings();
				$payment_type	= $method_array['payment_type'];
				$payment_log	= $method_array['payment_log'];

				// Logs
				$log = new FullCulqi_Logs();
				$log->set_settings_payment($payment_log, $order_id);

				
				if( $payment_type == 'simple' ) {

					foreach ($order->get_items() as $item ) {
						$_product = $item->get_product();
						$pnames[] = $_product->get_name();
					}

					// Antifraud Customer Data
					$antifraud = array(
									'first_name'	=> $order->get_billing_first_name(),
									'last_name'		=> $order->get_billing_last_name(),
									'email'			=> $order->get_billing_email(),
									'address'		=> $order->get_billing_address_1(),
									'address_city'	=> $order->get_billing_city(),
									'country_code'	=> $order->get_billing_country(),
									'phone_number'	=> $order->get_billing_phone(),
								);

					// Metadata Order
					$metadata = array(
									'order_id'	=> $order->get_id(),
									'order_key'	=> $order->get_order_key(),
								);

					$args_payment = array(
										'amount'			=> (int)($order->get_total()*100),
										'currency_code'		=> get_woocommerce_currency(),
										'description'		=> implode(', ', $pnames),
										'capture'			=> false,
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

						$log->set_msg_payment('notice', sprintf(__('Culqi Payment created : %s','letsgo'), $provider_payment['data']->id) );

						$post_id = FullCulqi_Integrator::create_payment($provider_payment['data']);

						$log->set_msg_payment('notice', sprintf(__('Post Payment created : %s','letsgo'), $post_id) );


						if( $method_array['status_success'] == 'wc-completed')
							$order->payment_complete();
						else
							$order->update_status($method_array['status_success']);

						$provider_payment = apply_filters('fullculqi/do_payment/simple_success', $provider_payment, $log, $order);
					
					} else {

						$log->set_msg_payment('error', sprintf(__('Culqi Payment error : %s','letsgo'), $provider_payment['msg']) );

						$provider_payment = apply_filters('fullculqi/do_payment/simple_error', $provider_payment, $log, $order);
					}
				}

				$provider_payment = apply_filters('fullculqi/do_payment/create', $provider_payment, $payment_type, $token_id, $log, $order);

				wp_send_json($provider_payment);
			}
		}
		
		die();
	}


	public function get_payments() {
		global $culqi;

		if( !wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( array('status' => 'error', 'msg' => __('Busted!','letsgo') ));

		$output = FullCulqi_Payments::sync_posts(esc_html($_POST['last_records']));
		wp_send_json($output);
	}


	public function delete_all() {
		global $wpdb;

		if( !wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( array('status' => 'error', 'msg' => __('Busted!','letsgo') ));

		$cpt = esc_html($_POST['cpt']);

		if( in_array($cpt, fullculqi_get_cpts() ) ) {

			$sql = $wpdb->prepare('DELETE a, b, c
						FROM '.$wpdb->posts.' a
						LEFT JOIN '.$wpdb->term_relationships.' b
						ON (a.ID = b.object_id)
						LEFT JOIN '.$wpdb->postmeta.' c
						ON (a.ID = c.post_id)
						WHERE a.post_type = "%s"', $cpt);

			$wpdb->query($sql);

			do_action( 'fullculqi/delete_all', array_map('esc_html', $_POST) );

			wp_send_json(array('status' => 'ok'));
		}

		wp_send_json(array(
						'status'	=> 'error',
						'msg'		=> sprintf(__('%s is not a Fullculqi CPT','letsgo'), $cpt)
					)
				);
	}
}
?>