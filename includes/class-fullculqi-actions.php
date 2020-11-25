<?php
/**
 * Actions Class
 * @since  1.0.0
 * @package Includes / Actions
 */
class FullCulqi_Actions {
	
	/**
	 * 	Construct
	 */
	public function __construct() {
		add_action( 'fullculqi/api/actions', [ $this, 'actions' ] );
	}

	/**
	 * Actions from the endpoint
	 * @return mixed
	 */
	public function actions() {

		if( ! isset( $_POST['action'] ) )
			return;

		// Run a security check.
		check_ajax_referer( 'fullculqi', 'wpnonce' );

		$return = '';
		$post_data = array_map( 'esc_html', $_POST );

		switch( $post_data['action'] ) {
			case 'order' : $return = FullCulqi_Orders::confirm( $post_data ); break;
			case 'charge' :

				if( is_user_logged_in() ) {

					$culqi_customer_id = FullCulqi_Customers::get_or_create(
						get_current_user_id(), $post_data
					);

					if( ! empty( $culqi_customer_id ) )
						$culqi_card_id = FullCulqi_Cards::create( $culqi_customer_id, $post_data );

					if( ! empty( $culqi_card_id ) )
						$post_data['token_id'] = $culqi_card_id;
				}

				$return = FullCulqi_Charges::create( $post_data );
			break;
		}
		
		$return = apply_filters('fullculqi/actions', $return, $post_data );

		if( ! empty( $return ) )
			wp_send_json_success();
		else
			wp_send_json_error();
	}
}

new FullCulqi_Actions();