<?php
/**
 * Updater Class
 * @since  1.0.0
 * @package Includes / Updater
 */
class FullCulqi_Updater {

	/**
	 * Construct
	 */
	public function __construct() {
		
		// Check available updates
		add_action( 'admin_notices', [ $this, 'check_available_updates' ] );

		// Process available updates
		add_action( 'wp_ajax_update_2_0_0', [ $this, 'process_update_2_0_0' ] );
	}

	/**
	 * Chec Available Updates
	 * @return mixed
	 */
	public function check_available_updates() {
		$plugin = get_file_data( FULLCULQI_FILE, [ 'Version' => 'Version' ] );

		// Compare version 2.0.0
		if( version_compare( $plugin['Version'], '2.0.0', '>=' ) &&
			! get_option( 'fullculqi_2_0_0_updated', false ) ) {

			// Check if it has posts
			$count_posts = wp_count_posts( 'culqi_charges' );
			
			if( isset( $count_posts->publish ) && $count_posts->publish == 0 )
				$this->screen_update_2_0_0();
			else
				update_option( 'fullculqi_2_0_0_updated', true );
		}
			
	}

	/**
	 * Screen box 2.0.0
	 * @return html
	 */
	public function screen_update_2_0_0() {

		$args = [
			'title'		=> esc_html__( 'Culqi Integration update required', 'fullculqi' ),
			'content'	=> esc_html__( 'Culqi Integration plugin has been updated to 2.0.0 version! To keep things running smoothly, we have to update your database to the newest version', 'fullculqi' ),
			'text_button'	=> esc_html__( 'Continue', 'fullculqi' ),
			'link_button'	=> add_query_arg([
					'action'	=> 'update_2_0_0',
					'wpnonce'	=> wp_create_nonce( 'fullculqi-wpnonce' ),
					'return'	=> urlencode( fullculqi_get_current_admin_url() ),
				],
				admin_url( 'admin-ajax.php' )
			),
			'class_title'	=> 'notice-title',
			'class_box'		=> 'notice notice-warning notice-large',
			'class_button'	=> 'button button-primary',
			'version'		=> '2.0.0',
		];

		fullculqi_get_template( 'resources/layouts/admin/notice-box.php', $args );
	}



	/**
	 * Upgrade to 2.0.0 or higher
	 * @return mixed
	 */
	public function process_update_2_0_0() {

		// Run a security check.
		check_ajax_referer( 'fullculqi-wpnonce', 'wpnonce' );

		// Check the permissions
		if( ! current_user_can( 'manage_options' ) )
			return;
	
		// Chek if this version was updated
		if( get_option( 'fullculqi_2_0_0_updated', false ) )
			return;

		// Return to URL
		$return = isset( $_GET['return'] ) ? urldecode( $_GET['return'] ) : admin_url();

		// Charges
		$args = [
			'post_type'		=> 'culqi_payments',
			'numberposts'	=> -1,
		];

		$posts = get_posts( $args );

		if( $posts ) {

			foreach( $posts as $post ) {
				
				// Get			
				$basic = get_post_meta( $post->ID, 'culqi_basic', true );

				// Update
				update_post_meta( $post->ID, 'culqi_creation_date', $basic['culqi_creation'] );

				// Process
				unset( $basic['culqi_creation'] );
				unset( $basic['culqi_card_brand'] );
				unset( $basic['culqi_card_type'] );
				unset( $basic['culqi_card_number'] );

				// Delete || New values
				update_post_meta( $post->ID, 'culqi_basic', $basic );

				// Change the CPT
				wp_update_post( [ 'ID' => $post->ID, 'post_type' => 'culqi_charges' ] );

				// Modify by 3rd parties
				do_action( 'fullculqi/update/2_0_0/charges', $post->ID );
			}
		}

		do_action( 'fullculqi/update/2_0_0/after' );

		update_option( 'fullculqi_2_0_0_updated', true );

		wp_redirect( $return );
		die();
	}
}

new FullCulqi_Updater();