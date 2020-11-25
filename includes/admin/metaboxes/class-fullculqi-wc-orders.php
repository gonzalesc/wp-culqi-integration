<?php
/**
 * Orders Class
 * @since  1.0.0
 * @package Includes / Admin / Metaboxes / Orders
 */
class FullCulqi_Metaboxes_WC_Orders {
	/**
	 * Construct
	 * @return mixed
	 */
	public function __construct() {
		
		// Metaboxes to Shop Order CPT
		add_action( 'add_meta_boxes_shop_order', [ $this, 'metaboxes'], 10, 1 );
	}

	/**
	 * Add Meta Boxes to Shop Order CPT
	 * @param  WP_POST $post
	 * @return mixed
	 */
	public function metaboxes( $post ) {

		$culqi_log = get_post_meta( $post->ID, 'culqi_log', true );

		if( empty( $culqi_log ) )
			return;

		add_meta_box(
			'fullculqi_payment_log',
			esc_html__( 'FullCulqi Logs', 'fullculqi' ),
			[ $this, 'metabox_log' ],
			'shop_order',
			'normal',
			'core'
		);
	}

	/**
	 * Metaboxes Log
	 * @param  WP_POST $post
	 * @return mixed
	 */
	public function metabox_log( $post ) {

		$args = [
			'logs' => get_post_meta( $post->ID, 'culqi_log', true )
		];

		fullculqi_get_template( 'resources/layouts/admin/metaboxes/order_log.php', $args );
	}
}

new FullCulqi_Metaboxes_WC_Orders();