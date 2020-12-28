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
		add_action( 'upgrader_process_complete', [ $this, 'upgrader_process' ], 10, 2 );
	}


	/**
	 * Plugin Upgrader
	 * @param  WP_Upgrader	$upgrader
	 * @param  array  		$options
	 * @return mixed
	 */
	public function upgrader_process( $upgrader, $options = [] ) {

		// Check the params
		if( $options['action'] != 'update' || $options['type'] != 'plugin' || ! isset( $options['plugins'] ) )
			return;

		foreach( $options['plugins'] as $plugin ) {
			
			if( $plugin == FULLCULQI_BASE ) {
				//set_transient( 'fullculqi_updated', 1 );
				
				$this->upgrader_compare();
				break;
			}
		}

		return true;
	}

	/**
	 * Compare version to calls the method
	 * @return mixed
	 */
	public function upgrader_compare() {

		$plugin = get_file_data( FULLCULQI_FILE, [ 'Version' => 'Version' ] );

		// Compare version
		if( version_compare( $plugin['Version'], '2.0.0', '>=' ) )
			$this->upgrader_2_0_0();

		return true;
	}

	/**
	 * Upgrade to 1.6.0 or higher
	 * @return mixed
	 */
	public function upgrader_2_0_0() {
		
		// Chek if this version was updated
		if( get_option( 'fullculqi_2_0_0_updated', false ) )
			return;


		// Charges
		$args = [
			'post_type'		=> 'culqi_payments',
			'numberposts'	=> -1,
		];

		$posts = get_posts( $args );

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
			do_action( 'fullculqi/upgrader/2_0_0/charges', $post->ID );
		}

		do_action( 'fullculqi/upgrader/2_0_0/after' );

		update_option( 'fullculqi_2_0_0_updated', true );

		return true;
	}
}

new FullCulqi_Updater();