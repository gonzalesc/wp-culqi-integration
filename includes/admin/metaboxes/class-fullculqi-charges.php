<?php
/**
 * Metaboxes_Charges Class
 * @since  1.0.0
 * @package Includes / Admin / Metaboxes / Charges
 */
class FullCulqi_Metaboxes_Charges extends FullCulqi_Metaboxes {

	protected $post_type = 'culqi_charges';


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
				$newCols['culqi_creation']	= esc_html__( 'Creation', 'fullculqi' );
				$newCols['culqi_email']		= esc_html__( 'Email', 'fullculqi' );
				$newCols['culqi_currency']	= esc_html__( 'Currency', 'fullculqi' );
				$newCols['culqi_amount']	= esc_html__( 'Amount', 'fullculqi' );
				$newCols['culqi_refunded']	= esc_html__( 'Refunded', 'fullculqi' );
				$newCols['culqi_status']	= esc_html__( 'Status', 'fullculqi' );
			}
		}
		
		return apply_filters('fullculqi/charges/column_name', $newCols, $cols );
	}

	/**
	 * Column Value
	 * @param  string  $col
	 * @param  integer $post_id
	 * @return mixed
	 */
	public function column_value( $col = '', $post_id = 0 ) {

		$basic 		= get_post_meta( $post_id, 'culqi_basic', true );
		$customer 	= get_post_meta( $post_id, 'culqi_customer', true );

		// Temporal
		if( metadata_exists( 'post', $post_id, 'culqi_status' ) )
			$status = get_post_meta($post_id, 'culqi_status', true);
		else
			$status = 'captured';

		$value = '';

		switch( $col ) {
			case 'culqi_id'			: $value = get_post_meta( $post_id, 'culqi_id', true );
				break;
			case 'culqi_creation'	: $value = $basic['culqi_creation']; break;
			case 'culqi_email'		:
				
				if( ! empty( $customer['post_id'] ) ) {
					$value = sprintf(
						'<a target="_blank" href="%s">%s</a>',
						get_edit_post_link( $customer['post_id'] ), $customer['culqi_email']
					);
				} else 
					$value = $customer['culqi_email'];

				break;

			case 'culqi_currency'	: $value = $basic['culqi_currency']; break;
			case 'culqi_amount'		: $value = $basic['culqi_amount']; break;
			case 'culqi_refunded'	: $value = $basic['culqi_amount_refunded']; break;
			case 'culqi_status'		:

				$statuses = fullculqi_charges_statuses();

				$value = sprintf(
					'<mark class="culqi_status_2 %s"><span>%s</span></mark>',
					$status, $statuses[$status]
				);

				break;
		}

		echo apply_filters( 'fullculqi/charges/column_value', $value, $col, $post_id );
	}



	/**
	 * Add Meta Boxes to Shop Order CPT
	 * @param  WP_POST $post
	 * @return mixed
	 */
	public function metaboxes( $post ) {

		// Basic Metabox
		add_meta_box(
			'culqi_charges_basic',
			esc_html__( 'Basic', 'fullculqi'),
			[ $this, 'metabox_basic' ],
			$this->post_type,
			'normal', 'high'
		);

		// Source Metabox
		add_meta_box(
			'culqi_charges_source',
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

		// Temporal
		if( metadata_exists( 'post', $post->ID, 'culqi_status' ) )
			$status = get_post_meta( $post->ID, 'culqi_status', true );
		else
			$status = 'captured';

		// Temporal
		if( metadata_exists( 'post', $post->ID, 'culqi_capture' ) )
			$capture = get_post_meta( $post->ID, 'culqi_capture', true );
		else
			$capture = 1;

		// Temporal
		if( metadata_exists( 'post', $post->ID, 'culqi_capture_date' ) )
			$capture_date = get_post_meta( $post->ID, 'culqi_capture_date', true );
		else
			$capture_date = $basic['culqi_creation'];

		$args = apply_filters( 'fullculqi/charges/metabox_basic/args', [
			'post_id'		=> $post->ID,
			'id'			=> get_post_meta( $post->ID, 'culqi_id', true ),
			'ip'			=> get_post_meta( $post->ID, 'culqi_ip', true ),
			'order_id'		=> get_post_meta( $post->ID, 'culqi_order_id', true ),
			'creation_date'	=> $basic['culqi_creation'],
			'currency'		=> $basic['culqi_currency'],
			'amount'		=> $basic['culqi_amount'],
			'refunded'		=> $basic['culqi_amount_refunded'],
			'statuses'		=> fullculqi_charges_statuses(),
			'status'		=> $status,
			'capture'		=> $capture,
			'capture_date'	=> $capture_date,
			'email'			=> $customer['culqi_email'],
			'first_name'	=> $customer['culqi_first_name'],
			'last_name'		=> $customer['culqi_last_name'],
			'city'			=> $customer['culqi_city'],
			'country'		=> $customer['culqi_country'],
			'phone'			=> $customer['culqi_phone'],
		], $post );

		fullculqi_get_template( 'resources/layouts/admin/metaboxes/charge_basic.php', $args );
	}


	/**
	 * Metabox Source
	 * @return html
	 */
	public function metabox_source() {
		global $post;
		
		$args = apply_filters( 'fullculqi/charges/metabox_source/args', [
			'data' => get_post_meta( $post->ID, 'culqi_data', true ),
		], $post );

		fullculqi_get_template( 'resources/layouts/admin/metaboxes/charge_source.php', $args );	
	}
}

new FullCulqi_Metaboxes_Charges();