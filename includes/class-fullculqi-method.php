<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Gateway_FullCulqi extends WC_Payment_Gateway {

	public function __construct() {

		$this->id 					= 'fullculqi';
		$this->method_title			= __('Culqi Full Integration','letsgo');
		$this->method_description 	= __( 'Allows payments by Card Credit', 'letsgo' );
		$this->icon 				= FULLCULQI_PLUGIN_URL . 'public/assets/images/cards.png';
		
		// Define user set variables
		$this->has_fields	= false;
		$this->title		= $this->get_option( 'title' );
		$this->description	= $this->get_option( 'description' );
		$this->payment_type	= $this->get_option( 'payment_type', 'simple' );
		$this->payment_log	= $this->get_option( 'payment_log', false );
		$this->msg_fail		= $this->get_option( 'msg_fail' );
		$this->time_modal	= $this->get_option( 'time_modal', 0 );
		$this->settings		= fullculqi_get_settings();

		$this->supports = array(
				'products', 
				'subscriptions',
				'subscription_cancellation', 
				'subscription_suspension', 
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change'
			);


		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action('woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );
		add_action('woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );

		// JS and CSS
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}


	function enqueue_scripts() {
		if( is_checkout_pay_page() ) {

			global $wp;

			if( !isset($wp->query_vars['order-pay']) ) return;

			$pnames = array();
			$order_id = $wp->query_vars['order-pay'];
			$order = new WC_Order( $order_id );

			$settings = fullculqi_get_settings();
			
			foreach ($order->get_items() as $item ) {
				$product = $item->get_product();
				$pnames[] = $product->get_name();
			}

			//$js_checkout	= 'https://checkout.culqi.com/v2/';
			$js_checkout	= 'https://checkout.culqi.com/js/v3';
			$js_fullculqi	= FULLCULQI_PLUGIN_URL . 'public/assets/js/fullculqi.js';
			$js_waitme		= FULLCULQI_PLUGIN_URL . 'public/assets/js/waitMe.min.js';
			$css_waitme		= FULLCULQI_PLUGIN_URL . 'public/assets/css/waitMe.min.css';

			wp_enqueue_script('fullcheckout-js', $js_checkout, array('jquery'), false, true);
			wp_enqueue_script('fullculqi-js', $js_fullculqi, array('jquery', 'fullcheckout-js'), false, true);
			wp_enqueue_script('waitme-js', $js_waitme, array('jquery'), false, true);
			wp_enqueue_style('waitme-css', $css_waitme );

			wp_localize_script( 'fullculqi-js', 'fullculqi',
				array(
					'url_ajax'		=> admin_url('admin-ajax.php'),
					'url_success'	=> $order->get_checkout_order_received_url(),
					'public_key'	=> $settings['public_key'],
					'time_modal'	=> absint($this->time_modal*1000),
					'order_id'		=> $order_id,
					'commerce'		=> $settings['commerce'],
					'currency'		=> get_woocommerce_currency(),
					'description'	=> implode(',', $pnames),
					'loading_text'	=> __('Loading. Please wait.','letsgo'),
					'total'			=> $order->get_total()*100,
					'msg_fail'		=> $this->msg_fail,
					'msg_error'		=> __('There was some problem in the purchase process. Try again please','letsgo'),
					'wpnonce'		=> wp_create_nonce('fullculqi'),
				)
			);
		}
	}
	

	function init_form_fields() {

		$this->form_fields = apply_filters('fullculqi/method/form_fields', array(
								'enabled' => array(
									'title'		=> __( 'Enable/Disable', 'letsgo' ),
									'type'		=> 'checkbox',
									'label'		=> __( 'Enable Culqi', 'letsgo' ),
									'default'	=> 'yes',
								),
								'title' => array(
									'title'			=> __( 'Title', 'letsgo' ),
									'type'			=> 'text',
									'description'	=> __( 'This controls the title which the user sees during checkout.', 'letsgo' ),
									'desc_tip'		=> true,
								),
								'description' => array(
									'title'			=> __('Description', 'letsgo'),
									'description'	=> __('Brief description of the payment gateway. This message will be seen by the buyer','letsgo'),
									'class'			=> '',
									'type'			=> 'textarea',
									'desc_tip'		=> true,
								),
								'payment_type' => array(
									'title'			=> __('Payment Type','letsgo'),
									'type'			=> 'radio',
									'description'	=> __('You can choise how you want to work this method','letsgo'),
									'default'		=> 'simple',
									'desc_tip'		=> true,
									'options'		=> array(
										'simple'		=> __('Simple Payment','letsgo'),
									),
								),
								'status_success' => array(
									'title' => __('Success Status','letsgo'),
									'type' => 'select',
									'class'       => 'wc-enhanced-select',
									'description' => __('If the purchase is success, apply this status to the order','letsgo'),
									'default' => 'wc-processing',
									'desc_tip' => true,
									'options'  => wc_get_order_statuses(),
								),
								'payment_log' => array(
									'title'			=> __('Payments Log','letsgo'),
									'type'			=> 'checkbox',
									'description'	=> __('If you enable, a panel will appear bellow in the order detail with the log of the payment transactions.', 'letsgo'),
									'default'		=> true,
									'label'			=> __('Enabled Payments Log', 'letsgo'),
									'desc_tip'		=> false,
								),
								'msg_fail' => array(
									'title'			=> __('Failed Message', 'letsgo'),
									'description'	=> __('This is the message will be shown to the customer if there is a error in the payment','letsgo'),
									'class'			=> '',
									'type'			=> 'textarea',
									'desc_tip'		=> false,
									'default'		=> __('Im sorry! an error occurred making the payment. A email was sent to shop manager with your information.','letsgo'),
								),
								'time_modal' => array(
									'title'			=> __('Popup/Modal Time','letsgo'),
									'type'			=> 'text',
									'description'	=> __('If you want the modal window to appear after a while without clicking "buy", put the seconds here. (Warning: may it not work in Safari). If you do not want to, leave it at zero.','letsgo'),
									'default'		=> '0',
									'placeholder'	=> '0',
									'desc_tip'		=> false,
								),
							)
						);
	}


	function thankyou_page( $order_id ) {

		$order = new WC_Order( $order_id );
	}

	function receipt_page( $order_id ) {

		$order = new WC_Order( $order_id );	

		$args = array(
					'src_image'		=> $this->icon,
					'url_cancel'	=> esc_url( $order->get_cancel_order_url() ),
				);

		do_action('fullculqi/form-receipt/before', $order);

		wc_get_template('public/layouts/form-receipt.php', $args, false, FULLCULQI_PLUGIN_DIR );

		do_action('fullculqi/form-receipt/after', $order);
	}


	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'pending', __('Order pending confirmation','letsgo'));

		return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url(true),
				);
	}


	function generate_radio_html( $key, $data ) {

		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'radio',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);
		$data = wp_parse_args( $data, $defaults );
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					
					<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
						<label for="<?php echo esc_attr( $option_key ); ?>">
							<input type="radio" value="<?php echo esc_attr( $option_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $option_key ); ?>" <?php checked( $this->get_option( $key ), $option_key ); ?> /><?php echo esc_attr( $option_value ); ?>
						</label>
						<br />
					<?php endforeach; ?>

					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

}

?>