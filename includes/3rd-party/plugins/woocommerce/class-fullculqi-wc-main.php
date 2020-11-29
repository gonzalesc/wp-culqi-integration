<?php
/**
 * WooCommerce Class
 * @since  1.0.0
 * @package Includes / 3rd-party / plugins / WooCommerce
 */
class FullCulqi_WC {

	public $log;

	public function __construct() {
		// Load the method payment
		add_action( 'plugins_loaded', [ $this, 'include_file' ] );

		// Include Class
		add_filter( 'woocommerce_payment_gateways', [ $this, 'include_class' ] );

		// Actions
		add_action( 'fullculqi/api/wc-actions', [ $this, 'actions' ] );

		// Update Order
		add_action( 'fullculqi/orders/update', [ $this, 'update' ] );
	}


	/**
	 * Include the method payment
	 * @return mixed
	 */
	public function include_file() {

		// Check if WC is installed
		if ( ! class_exists( 'WC_Payment_Gateway' ) )
			return;

		// Check if WC has the supported currency activated
		$supported_currencies = array_keys( fullculqi_currencies() );
		if ( ! in_array( get_woocommerce_currency(), $supported_currencies ) ) {
			add_action( 'admin_notices', [ $this, 'notice_currency'] );
			return;
		}

		require_once FULLCULQI_WC_DIR . 'class-fullculqi-wc-method.php';
	}

	/**
	 * Include the gateway class
	 * @param  array $methods
	 * @return array
	 */
	public function include_class( $methods = [] ) {
		$methods[] = 'WC_Gateway_FullCulqi';
		
		return $methods;
	}

	/**
	 * Actions
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
			case 'order' : $return = $this->order( $post_data ); break;
			case 'charge' : $return = $this->charge( $post_data ); break;
		}

		$return = apply_filters( 'fullculqi/wc-actions', $return, $post_data );

		if( $return )
			wp_send_json_success();
		else
			wp_send_json_error();
	}


	/**
	 * Create Order
	 * @param  array  $post_data
	 * @return mixed
	 */
	public function order( $post_data = [] ) {

		if( ! isset( $post_data['order_id'] ) || ! isset( $post_data['cip_code'] ) )
			return false;

		// Settings WC
		$method = get_option( 'woocommerce_fullculqi_settings', [] );

		if( empty( $method ) )
			return false;

		// Variables
		$order = wc_get_order( absint( $post_data['order_id'] ) );

		if( ! $order  )
			return false;

		// CIP CODE
		$cip_code = esc_html( $post_data['cip_code'] );

		// Log
		$this->log = new FullCulqi_Logs( $order->get_id() );


		// Culqi Customer ID
		$post_customer_id = false;
		if( $this->customer( $order ) ) {
		
			$culqi_customer_id = get_post_meta( $order->get_id(), 'culqi_customer_id', true );
			$post_customer_id = get_post_meta( $order->get_id(), 'post_customer_id', true );
		}


		$notice = sprintf(
			esc_html__( 'Culqi Multipayment CIP: %s', 'fullculqi' ), $cip_code
		);

		$this->log->set_notice( $notice );
		$order->add_order_note( $notice );

		// Status Orders
		if( $method['multi_status'] == 'wc-completed' )
			$order->payment_complete();
		else
			$order->update_status( $method['multi_status'] );

		// Update CIP CODE in WC Order
		update_post_meta( $order->get_id(), 'culqi_cip', $cip_code );

		// Get Culqi Order ID
		$culqi_order_id = get_post_meta( $order->get_id(), 'culqi_order_id', true );


		// From Culqi
		$culqi_order = FullCulqi_Orders::confirm( $culqi_order_id, $post_customer_id );

		if( $culqi_order['status'] != 'ok' ) {

			$error = sprintf(
				esc_html__( 'Culqi Multipayment Error: %s', 'fullculqi' ), $culqi_order['data']
			);
			$this->log->set_notice( $error );

			return false;
		}

		$culqi_order_id = $culqi_order['data']['culqi_order_id'];
		$post_order_id = $culqi_order['data']['post_order_id'];

		// Log
		$notice = sprintf(
			esc_html__( 'Post Multipayment Created: %s', 'fullculqi' ), $post_order_id
		);
		$this->log->set_notice( $notice );

		// Update meta post in wc order
		update_post_meta( $order->get_id(), 'post_order_id', $post_order_id );

		// Update WC Order IN in Culqi Orders
		update_post_meta( $post_order_id, 'culqi_wc_order_id', $order->get_id() );

		return true;
	}

	/**
	 * Create Charge
	 * @param  array  $post_data
	 * @return bool
	 */
	public function charge( $post_data = [] ) {

		// Settings WC
		$method = get_option( 'woocommerce_fullculqi_settings' );

		if( empty( $method ) )
			return false;

		// Get WC Order
		$order = wc_get_order( absint( $post_data['order_id'] ) );
		$token = sanitize_text_field( $post_data['token_id'] );
		$installments = sanitize_text_field( $post_data['installments'] );

		if( ! $order )
			return false;

		// Instance Logs
		$this->log = new FullCulqi_Logs( $order->get_id() );
		
		// If the user is logged
		if( $this->customer( $order ) ) {

			$culqi_customer_id = get_post_meta( $order->get_id(), 'culqi_customer_id', true );
			$post_customer_id = get_post_meta( $order->get_id(), 'post_customer_id', true );

			// Create Card
			if( ! empty( $culqi_customer_id ) ) {

				$args_card = [
					'customer_id'	=> $culqi_customer_id,
					'token_id'		=> $token,
				];

				$culqi_card = FullCulqi_Cards::create( $args_card );

				if( $culqi_card['status'] == 'ok' ) {
					$token = $culqi_card['data']['culqi_card_id'];
				} else {
					$error = sprintf(
						esc_html__( 'Culqi Card Error: %s', 'fullculqi' ),
						$culqi_card['data']
					);
					$this->log->set_error( $error );
				}
			}
		}

		// Charges

		$pnames = [];

		foreach( $order->get_items() as $item ) {
			$product = $item->get_product();
			$pnames[] = $product->get_name();
		}

		$desc = count( $pnames ) == 0 ? 'Product' : implode(', ', $pnames);
		

		// Antifraud Customer Data
		$antifraud_charges = [ 'email' => $order->get_billing_email() ];

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_address_1 		= $order->get_billing_address_1();
		$billing_phone 			= $order->get_billing_phone();
		$billing_city 			= $order->get_billing_city();
		$billing_country 		= $order->get_billing_country();

		if( ! empty( $billing_first_name ) )
			$antifraud['first_name'] = $billing_first_name;

		if( ! empty( $billing_last_name ) )
			$antifraud['last_name'] = $billing_last_name;

		if( ! empty( $billing_address_1 ) )
			$antifraud['address'] = $billing_address_1;

		if( ! empty( $billing_city ) )
			$antifraud['address_city'] = $billing_city;

		if( ! empty( $billing_country ) )
			$antifraud['country_code'] = $billing_country;
		elseif( ! empty($country_code) )
			$antifraud['country_code'] = $country_code;

		if( ! empty( $billing_phone ) )
			$antifraud['phone_number'] = $billing_phone;
		

		// Metadata Order
		$metadata_charges = [
			'order_id'			=> $order->get_id(),
			'order_number'		=> $order->get_order_number(),
			'order_key'			=> $order->get_order_key(),
			'post_customer'		=> isset( $post_customer_id ) ? $post_customer_id : false,
		];

		$args_charges = [
			'amount'			=> fullculqi_format_total( $order->get_total() ),
			'currency_code'		=> $order->get_currency(),
			'description'		=> substr( str_pad( $desc, 5, '_' ), 0, 80 ),
			'capture'			=> true,
			'email'				=> $order->get_billing_email(),
			'installments'		=> $installments,
			'source_id'			=> $token,
			'metadata'			=> $metadata_charges,
			'antifraud_details'	=> $antifraud_charges,
		];

		$culqi_charge = FullCulqi_Charges::create( $args_charges );

		if( $culqi_charge['status'] != 'ok' ) {

			$error = sprintf(
				esc_html__( 'Culqi Charge Error: %s', 'fullculqi' ),
				$culqi_customer['data']
			);

			$this->log->set_notice( $error );

			return false;
		}
			
		
		$culqi_charge_id = $culqi_charge['data']['culqi_charge_id'];
		$post_charge_id = $culqi_charge['data']['post_charge_id'];

		// Meta value
		update_post_meta( $order->get_id(), 'culqi_charge_id', $culqi_charge_id );

		// Log
		$notice = sprintf(
			esc_html__( 'Culqi Charge Created: %s', 'fullculqi' ),
			$culqi_charge_id
		);

		$order->add_order_note( $notice );
		$this->log->set_notice( $notice );


		// Log
		$notice = sprintf(
			esc_html__( 'Post Charge Created: %s', 'fullculqi' ), $post_charge_id
		);
		$this->log->set_notice( $notice );

		// Update PostID in WC-Order
		update_post_meta( $order->get_id(), 'post_charge_id', $post_charge_id );

		// Update OrderID in CulqiCharges
		update_post_meta( $post_charge_id, 'culqi_wc_order_id', $order->get_id() );

		// Change Status		
		if( $method['status_success'] == 'wc-completed')
			$order->payment_complete();
		else {
			$order->update_status( $method['status_success'],
				sprintf(
					esc_html__( 'Status changed by FullCulqi (to %s)', 'fullculqi' ),
					$method['status_success']
				)
			);
		}

		return true;
	}


	/**
	 * Create Customer
	 * @param  WP_OBJECT $order
	 * @return mixed
	 */
	public function customer( $order ) {

		if( ! is_user_logged_in() )
			return false;

		$culqi_customer = FullCulqi_Customers::get( get_current_user_id() );

		if( ! empty( $culqi_customer ) ) {

			// Log Notice
			$notice = sprintf(
				esc_html__( 'Culqi Customer: %s', 'fullculqi' ), $culqi_customer['culqi_id']
			);
			$this->log->set_notice( $notice );

			// Update meta culqi id in wc order
			update_post_meta( $order->get_id(), 'culqi_customer_id', $culqi_customer['culqi_id'] );

			// Log
			$notice = sprintf(
				esc_html__( 'Post Customer: %s', 'fullculqi' ), $culqi_customer['post_id']
			);
			$this->log->set_notice( $notice );

			// Update meta post in wc order
			update_post_meta( $order->get_id(), 'post_customer_id', $culqi_customer['post_id'] );

			return true;
		}
			
			
		$country_code = sanitize_text_field( $post_data['country_code'] );

		$args_customer = [
			'email'		=> $order->get_billing_email(),
			'metadata'	=> [ 'user_id' => get_current_user_id() ],
		];

		$billing_first_name 	= $order->get_billing_first_name();
		$billing_last_name 		= $order->get_billing_last_name();
		$billing_phone 			= $order->get_billing_phone();
		$billing_address_1 		= $order->get_billing_address_1();
		$billing_city 			= $order->get_billing_city();
		$billing_country 		= $order->get_billing_country();

		if( ! empty( $billing_first_name ) )
			$args_customer['first_name'] = $billing_first_name;

		if( ! empty( $billing_last_name ) )
			$args_customer['last_name'] = $billing_last_name;

		if( ! empty( $billing_phone ) )
			$args_customer['phone_number'] = $billing_phone;

		if( ! empty( $billing_address_1 ) )
			$args_customer['address'] = $billing_address_1;

		if( ! empty( $billing_city ) )
			$args_customer['address_city'] = $billing_city;

		if( ! empty( $billing_country ) )
			$args_customer['country_code'] = $billing_country;
		else
			$args_customer['country_code'] = $country_code;

		$culqi_customer = FullCulqi_Customers::create(
			get_current_user_id(), $args_customer
		);

		// Error
		if( $culqi_customer['status'] == 'error' ) {
			
			$error = sprintf(
				esc_html__( 'Culqi Customer Error: %s', 'fullculqi' ),
				$culqi_customer['data']
			);
			$this->log->set_error( $error );

			return false;
		}


		$culqi_customer_id = $culqi_customer['data']['culqi_customer_id'];
		$post_customer_id = $culqi_customer['data']['post_customer_id'];

		// Log Notice
		$notice = sprintf(
			esc_html__( 'Culqi Customer Created: %s', 'fullculqi' ), $culqi_customer_id
		);
		$this->log->set_notice( $notice );

		// Update meta culqi id in wc order
		update_post_meta( $order->get_id(), 'culqi_customer_id', $culqi_customer_id );

		// Log
		$notice = sprintf(
			esc_html__( 'Post Customer Created: %s', 'fullculqi' ), $post_customer_id
		);
		$this->log->set_notice( $notice );

		// Update meta post in wc order
		update_post_meta( $order->get_id(), 'post_customer_id', $post_customer_id );

		return true;
	}


	/**
	 * Update Order
	 * @param  OBJECT $culqi_order
	 * @return mixed
	 */
	public function update( $culqi_order ) {

		if( ! isset( $culqi_order->metadata->order_id ) )
			return;

		$order_id = absint( $culqi_order->metadata->order_id );
		
		$order = new WC_Order( $order_id );

		if( ! $order )
			return;

		// Log
		$log = new FullCulqi_Logs( $order->get_id() );

		// Payment Settings
		$method = get_option( 'woocommerce_fullculqi_settings', [] );


		switch( $culqi_order->state ) {
			case 'paid' :

				$notice = sprintf(
					esc_html__( 'The CIP %s was paid', 'fullculqi' ),
					$cip_code
				);

				$order->add_order_note( $notice );
				$log->set_notice( $notice );

				// Status
				if( $method['status_success'] == 'wc-completed')
					$order->payment_complete();
				else {
					$order->update_status( $method_array['status_success'],
						sprintf(
							esc_html__( 'Status changed by FullCulqi (to %s)', 'fullculqi' ),
							$method['status_success']
						)
					);
				}

				break;

			case 'expired' :

				$error = sprintf(
					esc_html__( 'The CIP %s expired', 'fullculqi' ),
					$cip_code
				);

				$log->set_error( $error );
				$order->update_status( 'cancelled', $error );

				break;

			case 'deleted' :

				$error = sprintf(
					esc_html__( 'The CIP %s was deleted', 'fullculqi' ),
					$cip_code
				);
				
				$log->set_error( $error );
				$order->update_status( 'cancelled', $error );

				break;
		}

		return true;
	}

	/**
	 * Notice Currency
	 * @return html
	 */
	public function notice_currency() {
		fullculqi_get_template( 'layouts/notice_currency.php', [], FULLCULQI_WC_DIR );
	}

}

new FullCulqi_WC();