<?php
class FullCulqi_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_fullculqi_get_payments', [ $this, 'get_payments' ] );
		add_action( 'wp_ajax_fullculqi_delete_all', [ $this, 'delete_all' ] );
		add_action( 'wp_ajax_fullculqi_refund', [ $this, 'refund_payment' ] );
	}

	public function get_payments() {
		global $culqi;

		if( ! wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( [ 'status' => 'error', 'msg' => esc_html__( 'Busted!', 'letsgo' ) ] );

		$output = FullCulqi_Payments::sync_posts(sanitize_key($_POST['records']));
		wp_send_json( $output );
	}


	public function delete_all() {
		global $wpdb;

		if( ! wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( [ 'status' => 'error', 'msg' => esc_html__('Busted!','letsgo') ] );

		$cpt = sanitize_text_field($_POST['cpt']);

		if( in_array( $cpt, fullculqi_get_cpts() ) ) {

			$sql = $wpdb->prepare('DELETE a, b, c
						FROM '.$wpdb->posts.' a
						LEFT JOIN '.$wpdb->term_relationships.' b
						ON (a.ID = b.object_id)
						LEFT JOIN '.$wpdb->postmeta.' c
						ON (a.ID = c.post_id)
						WHERE a.post_type = "%s"', $cpt);

			$wpdb->query( $sql );

			do_action( 'fullculqi/delete_all', array_map( 'esc_html', $_POST ) );

			wp_send_json( [ 'status' => 'ok' ] );
		}

		wp_send_json([
			'status'	=> 'error',
			'msg'		=> sprintf( esc_html__( '%s is not a Fullculqi CPT', 'letsgo' ), $cpt ),
		]);
	}


	public function refund_payment() {
		if ( isset( $_GET['post_id'] ) && check_admin_referer( 'fullculqi-wpnonce' ) ) {

			$culqi_post_id		= absint( wp_unslash( $_GET['post_id'] ) );
			$culqi_charge_id	= get_post_meta( $culqi_post_id, 'culqi_id', true );
			$order_id 			= get_post_meta( $culqi_post_id, 'culqi_order_id', true );
			$basic 				= get_post_meta( $culqi_post_id, 'culqi_basic', true);

			if( ! empty( $order_id ) ) {

				$order 	= wc_get_order( $order_id );
				$refund = wc_create_refund([
					'amount'			=> wc_format_decimal($basic['culqi_amount']),
					'reason'			=> 'solicitud_comprador',
					'order_id'			=> $order_id,
					'line_items'		=> [],
					'refund_payment'	=> true,
					'restock_items'		=> true,
				]);
			
			} else {

				$args = apply_filters('fullculqi/ajax/refund/args', [
					'amount'	=> round( $basic['culqi_amount']*100, 0 ),
					'charge_id'	=> $culqi_charge_id,
					'reason'	=> 'solicitud_comprador'
				], $culqi_post_id);
		
				$response = FullCulqi_Provider::refund_payment( $args );

				if( $response['status'] == 'ok' ) {
					$data = $response['data'];
					
					update_post_meta( $culqi_post_id, 'culqi_data', $data );
					update_post_meta( $culqi_post_id, 'culqi_status', 'refunded' );

					// Save Refund
					$basic = get_post_meta( $culqi_post_id, 'culqi_basic', true );
					$refunds = (array)get_post_meta( $culqi_post_id, 'culqi_ids_refunded', true );
					
					$refunds[ $data->id ] = number_format( $data->amount / 100, 2, '.', '' );
					
					$basic['culqi_amount_refunded'] = array_sum( $refunds );

					update_post_meta( $culqi_post_id, 'culqi_basic', $basic );
					update_post_meta( $culqi_post_id, 'culqi_ids_refunded', $refunds );
				}
			}

			$redirect = sprintf(admin_url('post.php?post=%d&action=edit'), $culqi_post_id);
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : $redirect );
			exit;
		}
	}
}
?>