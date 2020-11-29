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

		// Metaboxes Charges Edit
		add_action(  'fullculqi/charges/basic/print_data', [ $this, 'basic_print_order' ] );
		add_action(  'fullculqi/orders/basic/print_data', [ $this, 'basic_print_order' ] );

		// Ajax Refund
		//add_action( 'fullculqi/refunds/create/args', [ $this, 'create_refund_args' ], 10, 2 );
		add_filter( 'fullculqi/ajax/refund/process', [ $this, 'create_refund_process' ], 10, 2 );
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


	public function basic_print_order( $post_id = 0 ) {

		if( empty( $post_id ) )
			return;

		$args = [
			'order_id' => get_post_meta( $post_id, 'culqi_wc_order_id', true ),
		];
		
		fullculqi_get_template( 'layouts/charge_basic.php', $args, FULLCULQI_WC_DIR );
	}


	/**
	 * Create Args to Refund
	 * @param  array   $args
	 * @param  integer $post_charge_id
	 * @return array
	 */
	public function create_refund_args( $args = [], $post_charge_id = 0 ) {

		if( isset( $args['metadata']['order_id'] ) )
			return $args;

		$order_id = get_post_meta( $post_charge_id, 'culqi_wc_order_id', true );

		$order 	= wc_get_order( $order_id );

		if( ! $order )
			return $args;
		
		$args['metadata']['order_id'] = $order->get_id();
		$args['metadata']['order_key'] = $order->get_order_key();

		return $args;
	}


	/**
	 * Create Refund
	 * @param  array $refund
	 * @param  integer $post_charge_id
	 * @return mixed
	 */
	public function create_refund_process( $refund = [], $post_charge_id = 0 ) {

		if( empty( $post_charge_id ) )
			return [ 'status' => 'error', 'data' => esc_html__( 'Post Charge empty', 'fullculqi' ) ];

		// WC Order ID
		$order_id = get_post_meta( $post_charge_id, 'culqi_wc_order_id', true );

		$order 	= wc_get_order( $order_id );

		if( ! $order )
			return [ 'status' => 'error', 'data' => esc_html__( 'WC Order doesnt exist', 'fullculqi' ) ];

		$log = new FullCulqi_Logs( $order->get_id() );

		// WC Refund
		$basic = get_post_meta( $post_charge_id, 'culqi_basic', true );

		$wc_refund = wc_create_refund( [
			'amount'			=> wc_format_decimal( $basic['culqi_amount'] ),
			'reason'			=> 'solicitud_comprador',
			'order_id'			=> $order_id,
			'line_items'		=> [],
			'refund_payment'	=> true,
			'restock_items'		=> true,
		] );

		return [ 'status' => 'ok' ];
	}
}

new FullCulqi_WC_Admin();