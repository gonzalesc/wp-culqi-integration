<?php
/**
 * Metaboxes Class
 * @since  1.0.0
 * @package Includes / Admin / Metaboxes / Metaboxes
 */
abstract class FullCulqi_Metaboxes {

	/**
	 * Construct
	 */
	public function __construct() {

		// Script JS & CSS
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Metaboxes
		add_action( 'add_meta_boxes_' . $this->post_type, [ $this, 'metaboxes' ], 10, 1 );

		// Column Name
		add_filter( 'manage_' . $this->post_type . '_posts_columns', [ $this, 'column_name' ] );

		// Column Value
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', [ $this, 'column_value' ], 10, 2);
	}


	/**
	 * Add Script in Metaboxes
	 * @return mixed
	 */
	public function enqueue_scripts() {
		global $pagenow, $post;

		$post_type = [ 'culqi_charges', 'culqi_customers', 'culqi_orders' ];
		$pages = [ 'post-new.php', 'edit.php', 'post.php' ];

		if ( in_array( $this->post_type, $post_type ) && in_array( $pagenow, $pages ) ) {
			
			wp_enqueue_style(
				'fullculqi-css',
				FULLCULQI_URL . 'resources/assets/css/admin-metaboxes.css'
			);


			// Loading Gif
			$img_loading = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url( 'images/spinner.gif' )
			);

			// Success Icon
			$img_success = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url( 'images/yes.png' )
			);

			// Failure Icon
			$img_failure = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url('images/no.png')
			);


			if( $pagenow == 'edit.php' && $_GET['post_type'] == $this->post_type ) {

				wp_enqueue_script(
					'fullculqi-js',
					FULLCULQI_URL . 'resources/assets/js/admin-metaboxes.js',
					[ 'jquery' ], false, true
				);

				wp_localize_script( 'fullculqi-js', 'fullculqi_vars',
					apply_filters('fullculqi/metaboxes/localize', [
						'url_ajax'			=> admin_url( 'admin-ajax.php' ),
						'img_loading'		=> $img_loading,
						'img_success'		=> $img_success,
						'img_failure'		=> $img_failure,
						'sync_id'			=> $this->post_type,
						'sync_text'			=> esc_html__( 'Sync from Culqi', 'fullculqi' ),
						'sync_confirm'		=> esc_html__( 'Do you want to start the sync?', 'fullculqi' ),
						'sync_notify'		=> 'notify_' . $this->post_type,
						'sync_loading'		=> esc_html__( 'Synchronizing. It may take several minutes.', 'fullculqi' ),
						'sync_success'		=> esc_html__( 'Synchronization completed.', 'fullculqi' ),
						'sync_continue'		=> esc_html__( 'Oh! there are more items. Please wait.', 'fullculqi' ),
						'sync_failure'		=> esc_html__( 'Error in the synchronization.', 'fullculqi' ),
						'nonce'				=> wp_create_nonce( 'fullculqi-wpnonce' ),
					] )
				);
			}

			
			$allowed_pages = [ 'post-new.php', 'post.php' ];

			if( in_array( $pagenow, $allowed_pages ) && $this->post_type == 'culqi_charges' ) {

				wp_enqueue_script(
					'fullculqi-charges-js',
					FULLCULQI_URL . 'resources/assets/js/admin-charges.js',
					[ 'jquery' ], false, true
				);

				wp_localize_script( 'fullculqi-charges-js', 'fullculqi_charges_vars',
					apply_filters('fullculqi/metaboxes/charges/localize', [
						'url_ajax'			=> admin_url( 'admin-ajax.php' ),
						'img_loading'		=> $img_loading,
						'img_success'		=> $img_success,
						'img_failure'		=> $img_failure,
						'refund_confirm'	=> esc_html__( 'Do you want to start the refund?', 'fullculqi' ),
						'refund_loading'	=> esc_html__( 'Processing the refund.', 'fullculqi' ),
						'refund_success'	=> esc_html__( 'Refund completed.', 'fullculqi' ),
						'refund_failure'	=> esc_html__( 'Refund Error.', 'fullculqi' ),
						'nonce'				=> wp_create_nonce( 'fullculqi-wpnonce' ),
					] )
				);
			}
		}

		do_action( 'fullculqi/metaboxes/enqueue_scripts' );
	}
}
?>