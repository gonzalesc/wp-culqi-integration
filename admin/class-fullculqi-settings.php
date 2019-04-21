<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FullCulqi_Settings {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}


	public function enqueue_scripts() {
		$screen = get_current_screen();

		if( !isset($screen->base) || $screen->base != 'culqi-full-integration_page_fullculqi_settings' )
			return;

		wp_enqueue_script( 'fullculqi-js', FULLCULQI_PLUGIN_URL . 'admin/assets/js/fullculqi_admin.js', [ 'jquery' ], false, true );

		wp_localize_script( 'fullculqi-js', 'fullculqi',
			[
				'url_ajax'		=> admin_url('admin-ajax.php'),
				'url_loading'	=> admin_url('images/spinner.gif'),
				'url_success'	=> admin_url('images/yes.png'),
				'url_failure'	=> admin_url('images/no.png'),
				'text_loading'	=> __('Synchronizing. It may take several minutes.','letsgo'),
				'text_success'	=> __('Complete synchronization.','letsgo'),
				'nonce'			=> wp_create_nonce( 'fullculqi-wpnonce' ),
			]
		);
	}

	
	public function add_admin_menu() {

		add_menu_page(
				__('Culqi Full Integration','letsgo'),
				__('Culqi Full Integration','letsgo'),
				'manage_options',
				'fullculqi_menu',
				'', //function
				'dashicons-cart',
				54.1
			);

		add_submenu_page(
				'fullculqi_menu',
				__('Settings','letsgo'),
				__('Settings','letsgo'),
				'manage_options',
				'fullculqi_settings',
				[ $this, 'menu_settings' ]
			);
	}

	public function menu_settings() {
		include_once FULLCULQI_PLUGIN_DIR.'admin/layouts/settings_options.php';
	}


	public function register_settings() {

		$settings = fullculqi_get_settings();

		do_action('fullculqi/settings/before_fields', $settings, $this);
		
		register_setting(
			'fullculqi_group', // Option group
			'fullculqi_options', // Option name
			[ $this, 'settings_sanitize' ] // Sanitize
		);

		add_settings_section(
			'fullculqi_section', // ID
			__('Culqi Full Integration Settings','letsgo'), // Title
			false, // Callback [ $this, 'print_section_info' ]
			'fullculqi_page' // Page
		);

		add_settings_field(
			'fullculqi_commerce', // ID
			__('Commerce name','letsgo'), // Commerce Name
			[ $this, 'input_commerce' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_pubkey', // ID
			__('Public Key','letsgo'), // Public Key
			[ $this, 'input_pubkey' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_seckey', // ID
			__('Secret Key','letsgo'), // Secret Key
			[ $this, 'input_seckey' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_sync_payments', // ID
			__('Synchronize Payments','letsgo'), // Button
			[ $this, 'button_sync_payments' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		do_action('fullculqi/settings/sync_fields', $settings, $this);

		add_settings_field(
			'fullculqi_woo_payment', // ID
			__('Activate Payment Method in Woocommerce','letsgo'), // Simple Payment
			[ $this, 'input_woo_payment' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		do_action('fullculqi/settings/after_fields', $settings, $this);
	}


	public function settings_sanitize($inputs) {
		return $inputs;
	}

	public function print_section_info() {
		echo '<div class="fullculqi_section">'.__('Options','letsgo').'</div>';
	}

	public function input_commerce() {
		$settings = fullculqi_get_settings();

		echo '<label for="fullculqi_commerce">
				<input type="text" id="fullculqi_commerce" name="fullculqi_options[commerce]" value="'.$settings['commerce'].'"/>
			</label>';
	}

	public function input_pubkey() {
		$settings = fullculqi_get_settings();

		echo '<label for="fullculqi_pubkey">
				<input type="text" id="fullculqi_pubkey" name="fullculqi_options[public_key]" value="'.$settings['public_key'].'"/>
			</label>';
	}

	public function input_seckey() {
		$settings = fullculqi_get_settings();

		echo '<label for="fullculqi_seckey">
				<input type="text" id="fullculqi_seckey" name="fullculqi_options[secret_key]" value="'.$settings['secret_key'].'"/>
			</label>';
	}

	public function button_sync_payments() {
		echo '<label for="fullculqi_sync_payments">
				'.__('Last','letsgo').' <input type="number" id="fullculqi_sync_payments_records" step="1" id="" value="100" style="width:55px;" /> '.__('records','letsgo').'
				<button id="fullculqi_sync_payments" class="fullculqi_sync_button" data-action="payments">'.__('Synchronize Now','letsgo').'</button>
				<span id="fullculqi_sync_payments_loading"></span>
			</label>';
	}

	public function input_woo_payment() {
		$settings = fullculqi_get_settings();

		echo '<label for="fullculqi_woo_payment">
				<input type="checkbox" id="fullculqi_woo_payment" name="fullculqi_options[woo_payment]" value="yes" '.checked($settings['woo_payment'], 'yes', false).' />
				<p>'.__('If checked, the Culqi payment method will appear in Woocommerce.', 'letsgo').'</p>
			</label>';
	}

}
?>