<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FullCulqi_Payments extends FullCulqi_Entities {

	protected $post_type = 'culqi_payments';

	public function addmetabox() {
		add_meta_box('culqi_payments_basic', __('Basic', 'letsgo'), [ $this, 'metabox_basic' ], $this->post_type, 'normal', 'high');
		add_meta_box('culqi_payments_source', __('Source', 'letsgo'), [ $this, 'metabox_source' ], $this->post_type, 'normal', 'high');
	}


	public function add_name_column($columns) {

		$settings = fullculqi_get_settings();

		$columns[ 'title' ] = __('ID', 'letsgo');
		unset($columns[ 'date' ]);

		foreach($columns as $key_column => $value_column) {	
			$ok_columns[$key_column] = $value_column;

			if( $key_column == 'title' ) {			
				$ok_columns['culqi_creation']	= __( 'Creation', 'letsgo' );
				$ok_columns['culqi_email']		= __( 'Email', 'letsgo' );
				$ok_columns['culqi_currency']	= __( 'Currency', 'letsgo' );
				$ok_columns['culqi_amount']		= __( 'Amount', 'letsgo' );
				$ok_columns['culqi_refunded']	= __( 'Refunded', 'letsgo' );

				if( $settings['woo_payment'] == 'yes' )
					$ok_columns['culqi_order_id']	= __( 'Order', 'letsgo' );
			}
		}
		
		return apply_filters('fullculqi/payments/manage_columns/name', $ok_columns, $columns);
	}


	public function add_value_column($column, $post_id) {

		$basic 		= get_post_meta($post_id, 'culqi_basic', true);
		$customer 	= get_post_meta($post_id, 'culqi_customer', true);

		switch($column) {
			case 'culqi_id'			: $value_column = get_post_meta($post_id,'culqi_id', true); break;
			case 'culqi_creation'	: $value_column = $basic['culqi_creation']; break;
			case 'culqi_email'		: $value_column = $customer['culqi_email']; break;
			case 'culqi_currency'	: $value_column = $basic['culqi_currency']; break;
			case 'culqi_amount'		: $value_column = $basic['culqi_amount']; break;
			case 'culqi_refunded'	: $value_column = $basic['culqi_amount_refunded']; break;

			case 'culqi_order_id'	:
								$order_id = get_post_meta($post_id,'culqi_order_id', true);
								$order_url = admin_url(sprintf('post.php?post=%d&action=edit', $order_id));

								$value_column = sprintf('<a target="_blank" href="%s">%s</a>', $order_url, $order_id);
				break;
		}

		echo apply_filters('fullculqi/payments/manage_columns/value', $value_column, $column, $post_id);
	}

	public function metabox_basic() {
		global $post;

		$basic 		= get_post_meta($post->ID, 'culqi_basic', true);
		$customer 	= get_post_meta($post->ID, 'culqi_customer', true);

		$args = array(
					'id'			=> get_post_meta($post->ID, 'culqi_id', true),
					'ip'			=> get_post_meta($post->ID, 'culqi_ip', true),
					'order_id'		=> get_post_meta($post->ID, 'culqi_order_id', true),
					'creation'		=> $basic['culqi_creation'],
					'currency'		=> $basic['culqi_currency'],
					'amount'		=> $basic['culqi_amount'],
					'refunded'		=> $basic['culqi_amount_refunded'],
					'card_brand'	=> $basic['culqi_card_brand'],
					'card_type'		=> $basic['culqi_card_type'],
					'card_number'	=> $basic['culqi_card_number'],
					'email'			=> $customer['culqi_email'],
					'first_name'	=> $customer['culqi_first_name'],
					'last_name'		=> $customer['culqi_last_name'],
					'city'			=> $customer['culqi_city'],
					'country'		=> $customer['culqi_country'],
					'phone'			=> $customer['culqi_phone'],
				);

		$args = apply_filters('fullculqi/payments/metabox_basic/args', $args, $post);
		fullculqi_get_template('admin/layouts/metaboxes/metabox_payment_basic.php', $args);
	}

	public function metabox_source() {
		global $post;
		$args = array(
					'data' => get_post_meta($post->ID, 'culqi_data', true),
				);

		$args = apply_filters('fullculqi/payments/metabox_source/args', $args, $post);
		fullculqi_get_template('admin/layouts/metaboxes/metabox_payment_source.php', $args);	
	}


	static public function sync_posts($records = 100) {
		$payments = FullCulqi_Provider::list_payments($records);

		if( $payments['status'] == 'ok' ) {

			global $wpdb;

			$query = 'SELECT
							p.ID AS post_id,
							m.meta_value AS culqi_id
						FROM
							'.$wpdb->posts.' AS p
						INNER JOIN
							'.$wpdb->postmeta.' AS m
						ON
							p.ID = m.post_id
						WHERE
							p.post_type = "culqi_payments" AND
							m.meta_key = "culqi_id" AND
							m.meta_value <> ""';

			$query = apply_filters('fullculqi/payments/sync_posts/query', $query, $payments, $post);

			$results = $wpdb->get_results($query);
			$keys = array();

			// Keys Post Type 
			foreach($results as $result)
				$keys[$result->culqi_id] = $result->post_id;

			// Culqi Payments
			foreach( $payments['data'] as $data ) {

				if( isset($keys[$data->id]) ) { //update

					$post_id = $keys[$data->id];

				} else { //insert

					$args = array(
								'post_title'	=> $data->id,
								'post_type'		=> 'culqi_payments',
								'post_status'	=> 'publish'
							);

					$post_id = wp_insert_post($args);
				}


				$totime = (int)($data->creation_date/1000);
				$amount = round($data->amount/100, 2);
				$refund = round($data->amount_refunded/100, 2);

				update_post_meta($post_id, 'culqi_id', $data->id);
				update_post_meta($post_id, 'culqi_data', $data);
				update_post_meta($post_id, 'culqi_ip', esc_html($data->source->client->ip));

				if( isset($data->source->metadata->order_id) )
					update_post_meta($post_id, 'culqi_order_id', esc_html($data->source->metadata->order_id));
				else
					update_post_meta($post_id, 'culqi_order_id', '');

				$basic = array(
							'culqi_creation'		=> date('Y-m-d H:i:s', $totime),
							'culqi_amount'			=> $amount,
							'culqi_amount_refunded'	=> $refund,
							'culqi_currency'		=> $data->currency_code,
							'culqi_card_brand'		=> $data->source->iin->card_brand,
							'culqi_card_type'		=> $data->source->iin->card_type,
							'culqi_card_number'		=> $data->source->card_number,
						);

				update_post_meta($post_id, 'culqi_basic', array_map('esc_html', $basic));

				$customer = array(
							'culqi_email'		=> $data->email,
							'culqi_first_name'	=> $data->antifraud_details->first_name,
							'culqi_last_name'	=> $data->antifraud_details->last_name,
							'culqi_city'		=> $data->antifraud_details->address_city,
							'culqi_country'		=> $data->antifraud_details->country_code,
							'culqi_phone'		=> $data->antifraud_details->phone,
						);

				update_post_meta($post_id, 'culqi_customer', array_map('esc_html', $customer));
			}

			do_action('fullculqi/payments/sync_posts/after', $payments, $post);

			return array('status' => 'ok');

		} else {
			$payments = apply_filters('fullculqi/payments/sync_posts/fail', $payments, $post);
			return $payments;
		}
	}
}
?>