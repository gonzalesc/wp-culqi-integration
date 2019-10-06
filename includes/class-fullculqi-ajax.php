<?php
class FullCulqi_Ajax {

	public function __construct() {

		add_action('wp_ajax_fullculqi_get_payments', [ $this, 'get_payments' ] );
		add_action('wp_ajax_fullculqi_delete_all', [ $this, 'delete_all' ] );
	}

	public function get_payments() {
		global $culqi;

		if( !wp_verify_nonce( $_POST['wpnonce'], 'fullculqi-wpnonce' ) )
			wp_send_json( array('status' => 'error', 'msg' => __('Busted!','letsgo') ));

		$output = FullCulqi_Payments::sync_posts(sanitize_key($_POST['records']));
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