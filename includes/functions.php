<?php

/**
 * Get Settings
 * @return array
 */
function fullculqi_get_settings() {

	$settings = get_option( 'fullculqi_options', [] );
	$settings = wp_parse_args( $settings, fullculqi_get_default() );

	return apply_filters( 'fullculqi/settings', $settings );
}


/**
 * Get Default Settings
 * @return array
 */
function fullculqi_get_default() {
	$default = [
		'commerce'			=> '',
		'public_key'		=> '',
		'secret_key'		=> '',
		'logo_url'			=> '',
	];

	return apply_filters( 'fullculqi/settings_default', $default );
}


/**
 * Allowed Currencies
 * @param  string $type
 * @return array
 */
function fullculqi_currencies( $type = 'name' ) {

	switch($type) {
		case 'symbol' : $output = [  'PEN' => 'S/.', 'USD' => '$' ]; break;
		default :
			$output = [
				'PEN' => esc_html__( 'Peruvian Sol', 'fullculqi' ),
				'USD' => esc_html__( 'Dollars', 'fullculqi' ),
			];
		break;
	}
	
	return apply_filters('fullculqi/currencies', $output, $type);
}


/**
 * Format Price
 * @param  integer $amount
 * @param  string  $currency
 * @return string
 */
function fullculqi_format_price( $amount = 0, $currency = 'PEN' ) {
	$symbols = fullculqi_currencies( 'symbol' );

	$output = $symbols[ $currency ] . ' ' . number_format( $amount, 2 );

	return apply_filters('fullculqi/format_price', $output, $amount, $currency );
}


/**
 * CPTs from Culqi
 * @return mixed
 */
function fullculqi_get_cpts() {
	$array_cpts = [
		'culqi_charges'		=> esc_html__( 'Charges', 'fullculqi' ),
		'culqi_orders'		=> esc_html__( 'Orders', 'fullculqi' ),
		'culqi_customers'	=> esc_html__( 'Customers', 'fullculqi' ),
	];

	return apply_filters( 'fullculqi/cpts', $array_cpts );
}

/**
 * Get the language from the Site
 * @return mixed
 */
function fullculqi_language() {
	
	$lang_locale = $language = get_locale();
	$allows = [ 'es', 'en' ];

	// Locale
	if( strpos( $lang_locale, '_' ) != FALSE ) {
		list( $language, $country ) = array_map(
			'strtolower', explode( '_', $lang_locale )
		);
	}

	// Default
	if( ! in_array( $language, $allows ) )
		$language = $allows[0]; 
	
	return apply_filters( 'fullculqi/language', $language );
}


/**
 * Format Total to Culqi
 * @param  integer $total
 * @return string
 */
function fullculqi_format_total( $total = 0 ) {
	$total_points = number_format( $total, 2, '.', '' );
	$total_raw = strval( $total_points * 100 );
	
	return apply_filters( 'fullculqi/format_total', $total_raw, $total );
}



/**
 * Check if it has posts
 * @return mixed
 */
function fullculqi_have_posts() {

	$cpts = array_keys( fullculqi_get_cpts() );

	foreach( $cpts as $cpt ) {
		$count_posts = wp_count_posts($cpt);

		if( isset($count_posts->publish) && $count_posts->publish != 0 )
			return true;
	}

	return false;
}


/**
 * Print Layout
 * @param  string $template_name
 * @param  array  $args         
 * @param  string $template_path]
 * @return mixed
 */
function fullculqi_get_template( $template_name = '', $args = [], $template_path = '' ) {

	if ( ! empty( $args ) && is_array( $args ) )
		extract( $args );

	if( ! empty( $template_path ) )
		$located = trailingslashit( $template_path ) . $template_name;
	else
		$located = FULLCULQI_DIR . $template_name;
	
	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'fullculqi/global/located', $located, $args );

	if( ! file_exists( $located ) ) {
		printf( esc_html__('File %s is not exists','fullculqi'), $located );
		return;
	}

	do_action( 'fullculqi/template/before', $located, $args );

	include $located;

	do_action( 'fullculqi/template/after', $located, $args );
}


/**
 * Get Charges Statuses
 * @return array
 */
function fullculqi_charges_statuses() {
	$statuses = [
		'authorized'	=> esc_html__( 'Authorized', 'fullculqi' ),
		'captured'		=> esc_html__( 'Captured', 'fullculqi' ),
		'expired'		=> esc_html__( 'Expired', 'fullculqi' ),
		'refunded'		=> esc_html__( 'Refunded', 'fullculqi' ),
	];

	return apply_filters( 'fullculqi/charges/statuses', $statuses );
}

/**
 * Get Multipayments Statuses
 * @return array
 */
function fullculqi_multipayments_statuses() {
	$statuses = [
		'paid'		=> esc_html__( 'Paid', 'fullculqi' ),
		'expired'	=> esc_html__( 'Expired', 'fullculqi' ),
		'deleted'	=> esc_html__( 'Deleted', 'fullculqi' ),
		'pending'	=> esc_html__( 'Pending', 'fullculqi' ),
		'created'	=> esc_html__( 'Created', 'fullculqi' ),
	];

	return apply_filters( 'fullculqi/multipayments/statuses', $statuses );
}