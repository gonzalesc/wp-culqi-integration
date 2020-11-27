<?php
/**
 * WooCommerce Class
 * @since  1.0.0
 * @package Includes / 3rd-party / plugins / WooCommerce
 */
class FullCulqi_WC_Admin {

	public function __construct() {
		// Metaboxes to Shop Order CPT
		add_action( 'add_meta_boxes_shop_order', [ $this, 'metaboxes'], 10, 1 );

		// Metaboxes Charges columns
		add_filter( 'fullculqi/charges/column_name', [ $this, 'column_name' ], 10, 2 );
		add_filter( 'fullculqi/charges/column_value', [ $this, 'column_value' ], 10, 3 );
		add_filter( 'fullculqi/orders/column_name', [ $this, 'column_name' ], 10, 2 );
		add_filter( 'fullculqi/orders/column_value', [ $this, 'column_value' ], 10, 3 );
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

		fullculqi_get_template( 'layouts/order_log.php', $args, FULLCULQI_WC_DIR );
	}


	/**
	 * Charges Column Name
	 * @param  [type] $newCols [description]
	 * @param  [type] $cols    [description]
	 * @return [type]          [description]
	 */
	public function column_name( $newCols, $cols ) {

		if( ! class_exists( 'WooCommerce' ) )
			return $newCols;

		$newCols['culqi_wc_order_id']	= esc_html__( 'WC Order', 'fullculqi' );

		return $newCols;
	}


	/**
	 * Charge Column Value
	 * @param  string  $value
	 * @param  string  $col
	 * @param  integer $post_id
	 * @return mixed
	 */
	public function column_value( $value = '', $col = '', $post_id = 0 ) {
		if( $col != 'culqi_wc_order_id' )
			return $value;

		$value = '';
		$order_id = get_post_meta( $post_id, 'culqi_wc_order_id', true );

		if( ! empty( $order_id ) ) {
			$value = sprintf(
				'<a target="_blank" href="%s">%s</a>',
				get_edit_post_link( $order_id ), $order_id
			);
		}

		return $value;
	}
}

new FullCulqi_WC_Admin();