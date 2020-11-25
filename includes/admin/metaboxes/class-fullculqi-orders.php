<?php
/**
 * Metaboxes_Orders Class
 * @since  1.0.0
 * @package Includes / Admin / Metaboxes / Orders
 */
class FullCulqi_Metaboxes_Orders extends FullCulqi_Metaboxes {

	protected $post_type = 'culqi_orders';

	/**
	 * Column Name
	 * @param  array $cols
	 * @return array
	 */
	public function column_name( $cols = [] ) {

		$settings = fullculqi_get_settings();

		$cols[ 'title' ] = esc_html__( 'ID', 'fullculqi' );
		unset( $cols[ 'date' ] );

		foreach($cols as $key_column => $value_column) {	
			$newCols[ $key_column ] = $value_column;

			if( $key_column == 'title' ) {
				$newCols['cip_code']			= esc_html__( 'CIP Code', 'fullculqi' );
				$newCols['culqi_creation']		= esc_html__( 'Creation', 'fullculqi' );
				$newCols['culqi_expiration']	= esc_html__( 'Expiration', 'fullculqi' );
				$newCols['culqi_email']			= esc_html__( 'Email', 'fullculqi' );
				$newCols['culqi_amount']		= esc_html__( 'Amount', 'fullculqi' );
				$newCols['culqi_status']		= esc_html__( 'Status', 'fullculqi' );
				$newCols['culqi_order_id']		= esc_html__( 'Order', 'fullculqi' );
			}
		}
		
		return apply_filters('fullculqi/orders/column_name', $newCols, $cols );
	}

	/**
	 * Column Value
	 * @param  string  $col
	 * @param  integer $post_id
	 * @return mixed
	 */
	public function column_value( $col = '', $post_id = 0 ) {

		$value = '';

		$basic 		= get_post_meta( $post_id, 'culqi_basic', true );
		$customer 	= get_post_meta( $post_id, 'culqi_customer', true );

		switch( $col ) {
			case 'culqi_id'			: $value = get_post_meta( $post_id, 'culqi_id', true );
				break;
			case 'culqi_creation'	: $value = $basic['culqi_creation']; break;
			case 'culqi_expiration'	: $value = $basic['culqi_expiration']; break;
			case 'culqi_email'		: $value = $customer['culqi_email']; break;
			case 'culqi_amount'		:
				$value = fullculqi_format_price( $basic['culqi_amount'] ); break;
			
			case 'culqi_status'		:
				$statuses = fullculqi_multipayments_statuses();
				$status = get_post_meta( $post_id, 'culqi_status', true );

				$value = sprintf(
					'<mark class="culqi_status_2 %s"><span>%s</span></mark>',
					$status, $statuses[$status]
				);

				break;

			case 'culqi_order_id'	:
				$order_id = get_post_meta( $post_id, 'culqi_order_id', true );

				if( ! empty( $order_id ) && class_exists('WooCommerce') ) {
					$order_url = admin_url( sprintf( 'post.php?post=%d&action=edit', $order_id ) );
					$value = sprintf('<a target="_blank" href="%s">%s</a>', $order_url, $order_id);
				}
				break;
		}

		echo apply_filters( 'fullculqi/orders/column_value', $value, $col, $post_id );
	}



	/**
	 * Add Meta Boxes to Shop Order CPT
	 * @param  WP_POST $post
	 * @return mixed
	 */
	public function metaboxes( $post ) {

		// Basic Metabox
		add_meta_box(
			'culqi_orders_basic',
			esc_html__( 'Basic', 'fullculqi'),
			[ $this, 'metabox_basic' ],
			$this->post_type,
			'normal', 'high'
		);

		// Source Metabox
		add_meta_box(
			'culqi_orders_source',
			esc_html__( 'Source', 'fullculqi' ),
			[ $this, 'metabox_source' ],
			$this->post_type,
			'normal', 'high'
		);
	}

	/**
	 * Metabox Basic
	 * @return html
	 */
	public function metabox_basic() {
		global $post;

		$basic 		= get_post_meta( $post->ID, 'culqi_basic', true );
		$customer 	= get_post_meta( $post->ID, 'culqi_customer', true );
		$status 	= get_post_meta( $post->ID, 'culqi_status', true );

		$args = apply_filters( 'fullculqi/orders/metabox_basic/args', [
			'post_id'		=> $post->ID,
			'id'			=> get_post_meta( $post->ID, 'culqi_id', true ),
			'order_id'		=> get_post_meta( $post->ID, 'culqi_order_id', true ),
			'creation'		=> $basic['culqi_creation'],
			'expiration'	=> $basic['culqi_expiration'],
			'currency'		=> $basic['culqi_currency'],
			'amount'		=> $basic['culqi_amount'],
			'statuses'		=> fullculqi_multipayments_statuses(),
			'status'		=> $status,
			'email'			=> $customer['culqi_email'],
			'first_name'	=> $customer['culqi_first_name'],
			'last_name'		=> $customer['culqi_last_name'],
			'city'			=> $customer['culqi_city'],
			'country'		=> $customer['culqi_country'],
			'phone'			=> $customer['culqi_phone'],
		], $post );

		fullculqi_get_template( 'resources/layouts/admin/metaboxes/order_basic.php', $args );
	}


	/**
	 * Metabox Source
	 * @return html
	 */
	public function metabox_source() {
		global $post;
		
		$args = apply_filters( 'fullculqi/orders/metabox_source/args', [
			'data' => get_post_meta( $post->ID, 'culqi_data', true ),
		], $post );

		fullculqi_get_template( 'resources/layouts/admin/metaboxes/order_source.php', $args );	
	}
}

new FullCulqi_Metaboxes_Orders();