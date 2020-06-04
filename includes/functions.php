<?php

if( !function_exists('fullculqi_get_settings') ) {
	function fullculqi_get_settings() {

		$settings = get_option( 'fullculqi_options', array() );
		$settings = wp_parse_args( $settings, fullculqi_get_default() );

		return apply_filters('fullculqi/global/get_settings', $settings);
	}
}


if( !function_exists('fullculqi_get_default') ) {
	function fullculqi_get_default() {
		$default = [
					'commerce'			=> '',
					'public_key'		=> '',
					'secret_key'		=> '',
					'logo_url'			=> '',
					'woo_payment'		=> 'no',
				];

		return apply_filters('fullculqi/global/get_default', $default);
	}
}


if( !function_exists('fullculqi_get_woo_settings') ) {
	function fullculqi_get_woo_settings() {

		$settings = fullculqi_get_settings();

		if( $settings['woo_payment'] != 'yes' )
			return array();

		$method_string = get_option('woocommerce_fullculqi_settings', array());

		if( !$method_string )
			return fullculqi_get_woo_default();

		$method_array = maybe_unserialize($method_string);
		$method_array = wp_parse_args( $method_array, fullculqi_get_woo_default() );

		return apply_filters('fullculqi/global/get_woo_settings', $method_array);
	}
}


if( !function_exists('fullculqi_get_woo_default') ) {
	function fullculqi_get_woo_default() {

		$default = [
					'enabled'			=> 'yes',
					'title'				=> __('Culqi Full Integration','letsgo'),
					'description'		=> '',
					'status_success'	=> 'wc-processing',
					'installments'		=> 'no',
					'msg_fail'			=> __('Im sorry! an error occurred making the payment. A email was sent to shop manager with your information.','letsgo'),
					'time_modal'		=> 0,
				];

		return apply_filters('fullculqi/global/get_woo_default', $default);
	}
}


if( !function_exists('fullculqi_get_currencies') ) {
	function fullculqi_get_currencies($type = 'name') {

		switch($type) {
			case 'symbol' : 
					$output = array(
									'PEN' => 'S/.',
									'USD' => '$',
								);
					break;
			default :
					$output = array(
									'PEN' => __('Peruvian Sol','letsgo'),
									'USD' => __('Dollars','letsgo'),
								);
						break;
		}
		
		return apply_filters('fullculqi/global/get_currencies', $output, $type);
	}
}



if( !function_exists('fullculqi_get_current_user_id') ) {
	function fullculqi_get_current_user_id($email = null) {
		
		if( is_user_logged_in() )
			return get_current_user_id();
			
		if( $email != null ) {
			$user = get_user_by('email', $email);

			if( $user != false )
				return $user->ID;
		}

		return false;
	}
}


if( !function_exists('fullculqi_format_price') ) {
	function fullculqi_format_price($amount = 0, $currency = 'PEN') {
		$symbols = fullculqi_get_currencies('symbol');

		$output = $symbols[$currency].' '.number_format($amount, 2);

		return apply_filters('fullculqi/global/format_price', $output, $amount, $currency);
	}
}


if( !function_exists('fullculqi_postid_from_meta') ) {

	function fullculqi_postid_from_meta($meta_key = null, $meta_value = null) {
		if( empty($meta_key) || empty($meta_value) )
			return 0;
		
		global $wpdb;

		$key_meta_key = md5($meta_key);
		$key_meta_value = md5($meta_value);
		$key_postid_from_meta = 'fullculqi_postid_'.$key_meta_key.'_'.$key_meta_value;

		$post_id = wp_cache_get( $key_postid_from_meta );
		
		if ( false === $post_id ) {

			$query = $wpdb->prepare('SELECT
										post_id
									FROM
										'.$wpdb->postmeta.'
									WHERE
										meta_key=%s && meta_value=%s'
								,
								$meta_key,
								$meta_value
							);

			$post_id = $wpdb->get_var($query);

			wp_cache_set( $key_postid_from_meta, $post_id );
		}

		return $post_id;
	}
}


if( !function_exists('fullculqi_get_cpts') ) {
	function fullculqi_get_cpts() {
		$array_cpts = ['culqi_payments'];

		return apply_filters('fullculqi/global/get_cpts', $array_cpts);
	}
}

if( !function_exists('fullculqi_get_language') ) {
	function fullculqi_get_language() {
		
		$lang_locale = $language = get_locale();
		$allows = [ 'es', 'en' ];

		// get_locale
		if( strpos($lang_locale, '_') != FALSE )
			list($language, $country) = array_map('strtolower', explode('_', $lang_locale));

		// Default
		if( !in_array($language, $allows) )
			$language = $allows[0];
		
		return apply_filters('fullculqi/global/language', $language);
	}
}


if( !function_exists('fullculqi_format_total') ) {
	function fullculqi_format_total($total) {
		$total_points = number_format($total, 2, '.', '');
		$total_raw = $total_points * 100;
		
		return apply_filters('fullculqi/global/format_total', $total_raw, $total);
	}
}


if( !function_exists('fullculqi_have_posts') ) {
	function fullculqi_have_posts() {
		foreach( fullculqi_get_cpts() as $cpt ) {
			$count_posts = wp_count_posts($cpt);

			if( isset($count_posts->publish) && $count_posts->publish != 0 )
				return true;
		}

		return false;
	}
}


if( !function_exists('fullculqi_get_template') ) {

	function fullculqi_get_template( $template_name, $args = array(), $template_path = '' ) {

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		if( $template_path != '' )
			$located = trailingslashit($template_path) . $template_name;
		else
			$located = FULLCULQI_PLUGIN_DIR . $template_name;
		
		// Allow 3rd party plugin filter template file from their plugin.
		$located = apply_filters( 'fullculqi/global/located', $located, $args);

		if( ! file_exists( $located ) ) {
			printf(__('File %s is not exists','letsgo'), $located);
			return;
		}

		do_action( 'fullculqi/template/before', $located, $args );

		include $located;

		do_action( 'fullculqi/template/after', $located, $args );
	}
}


if( ! function_exists('fullculqi_get_status') ) {

	function fullculqi_get_status() {

		$statuses = [
			'authorized'	=> esc_html__( 'Authorized', 'fullculqi' ),
			'captured'		=> esc_html__( 'Captured', 'fullculqi' ),
			'expired'		=> esc_html__( 'Expired', 'fullculqi' ),
			'refunded'		=> esc_html__( 'Refunded', 'fullculqi' ),
		];

		return apply_filters( 'fullculqi/global/status', $statuses );
	}
}