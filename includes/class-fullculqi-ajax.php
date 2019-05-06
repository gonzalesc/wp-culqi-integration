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
			$order_id 		= sanitize_key($_POST['order_id']);
			$token_id		= sanitize_text_field($_POST['token_id']);
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

					$provider_payment = FullCulqi_Checkout::simple($order, compact('token_id', 'installments'), $log );
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


	public function get_payments() {
		global $culqi;

		if( !wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( array('status' => 'error', 'msg' => __('Busted!','letsgo') ));

		$output = FullCulqi_Payments::sync_posts(sanitize_key($_POST['last_records']));
		wp_send_json($output);
	}


	public function delete_all() {
		global $wpdb;

		if( !wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( array('status' => 'error', 'msg' => __('Busted!','letsgo') ));

		$cpt = sanitize_text_field($_POST['cpt']);

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