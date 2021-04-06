<?php
/**
 * Settings Class
 * @since  1.0.0
 * @package Includes / Admin / Settings
 */
class FullCulqi_Settings {

	/**
	 * Construct
	 */
	public function __construct() {

		// Script JS & CSS
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Menu
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Register Form
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}


	/**
	 * CSS & JS
	 * @return mixed
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if( isset( $screen->base ) && (
			$screen->base == 'culqi-integracion_page_fullculqi_addons' ||
			$screen->base == 'culqi-full-integration_page_fullculqi_addons'
		) ) {
			wp_enqueue_style(
				'fullculqi-css-addons',
				FULLCULQI_URL . 'resources/assets/css/admin-addons.css'
			);
		}

		if( isset( $screen->base ) &&
			( $screen->base == 'culqi-integracion_page_fullculqi_settings' ||
				$screen->base == 'culqi-full-integration_page_fullculqi_settings' ||
				$screen->base == 'dashboard_page_fullculqi-welcome' )
		) {
			wp_enqueue_script(
				'fullculqi-js-settings',
				FULLCULQI_URL . 'resources/assets/js/admin-settings.js',
				[ 'jquery' ], false, true
			);

			// Loading Gif
			$img_loading = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url( 'images/spinner.gif' )
			);

			// Success Icon
			$img_success = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url( 'images/yes.png' )
			);

			// Failure Icon
			$img_failure = sprintf(
				'<img src="%s" style="width: auto;" />',
				admin_url('images/no.png')
			);

			wp_localize_script( 'fullculqi-js-settings', 'fullculqi_vars',
				apply_filters('fullculqi/settings/localize', [
					'url_ajax'			=> admin_url( 'admin-ajax.php' ),
					'img_loading'		=> $img_loading,
					'img_success'		=> $img_success,
					'img_failure'		=> $img_failure,
					'delete_loading'	=> esc_html__( 'Deleting posts from %s.', 'fullculqi' ),
					'delete_error'		=> esc_html__( 'Error deleting a post', 'fullculqi' ),
					'delete_success'	=> esc_html__( '%s : Posts deleted.', 'fullculqi' ),
					'delete_cpts'		=> array_keys( fullculqi_get_cpts() ),
					'text_confirm'		=> sprintf( esc_html__(
						'If you continue, you will delete all the posts in %s', 'fullculqi'
						), implode( ',', fullculqi_get_cpts() )
					),
					'is_welcome'		=> $screen->base == 'dashboard_page_fullculqi-welcome' ? true : false,
					'nonce'				=> wp_create_nonce( 'fullculqi-wpnonce' ),
				] )
			);
		}
	}

	
	/**
	 * Add to menu
	 * @return mixed
	 */
	public function admin_menu() {

		add_menu_page(
			esc_html__( 'Culqi Full Integration', 'fullculqi' ),
			esc_html__( 'Culqi Full Integration', 'fullculqi' ),
			'manage_options',
			'fullculqi_menu',
			'', //function
			'dashicons-cart',
			54.1
		);

		do_action('fullculqi/settings/before_menu');

		add_submenu_page(
			'fullculqi_menu',
			esc_html__( 'Settings', 'fullculqi' ),
			esc_html__( 'Settings', 'fullculqi' ),
			'manage_options',
			'fullculqi_settings',
			[ $this, 'settings_page' ]
		);

		add_submenu_page(
			'fullculqi_menu',
			esc_html__( 'Webhooks', 'fullculqi' ),
			esc_html__( 'Webhooks', 'fullculqi' ),
			'manage_options',
			'fullculqi_webhooks',
			[ $this, 'webhooks_page' ]
		);

		do_action( 'fullculqi/settings/after_menu' );

		add_submenu_page(
			'fullculqi_menu',
			esc_html__( 'Add-ons', 'fullculqi' ),
			esc_html__( 'Add-ons', 'fullculqi' ),
			'manage_options',
			'fullculqi_addons',
			[ $this, 'addons_page' ]
		);
	}

	/**
	 * Addons Page
	 * @return mixed
	 */
	public function addons_page() {

		$args = [
			'banner_1'	=> FULLCULQI_URL . 'resources/assets/images/letsgo_1.png',
			'banner_2'	=> FULLCULQI_URL . 'resources/assets/images/letsgo_2.png',
			'banner_3'	=> FULLCULQI_URL . 'resources/assets/images/letsgo_3.png',
			'banner_4'	=> FULLCULQI_URL . 'resources/assets/images/letsgo_4.png',
			'icon_wc'	=> FULLCULQI_URL . 'resources/assets/images/icon_woo.png',
			'icon_wp'	=> FULLCULQI_URL . 'resources/assets/images/icon_wp.png',
			'has_subscribers'	=> class_exists('FullCulqi_Subscription'),
			'has_oneclick'		=> class_exists('FullCulqi_CardCredit'),
			'has_button'		=> class_exists('FullCulqi_Button'),
			'has_deferred'		=> class_exists('FullCulqi_DF'),
		];

		fullculqi_get_template( 'resources/layouts/admin/addons_page.php', $args );
	}


	/**
	 * Settings Page
	 * @return mixed
	 */
	public function settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(	esc_html__(
        		'You do not have sufficient permissions to access this page.',
				'fullculqi'
			) );
        }

        fullculqi_get_template( 'resources/layouts/admin/settings_page.php' );
	}


	public function webhooks_page() {

		$args = [
			'webhook_url'	=> site_url( 'fullculqi-api/webhooks' ),
			'webhook_list'	=> get_option( 'fullculqi_webhooks' ),
		];

		fullculqi_get_template(
			'resources/layouts/admin/webhooks-page.php', $args
		);
	}


	/**
	 * Register Settings
	 * @return mixed
	 */
	public function register_settings() {

		do_action( 'fullculqi/settings/before_fields' );
		
		register_setting(
			'fullculqi_group', // Option group
			'fullculqi_options', // Option name
			[ $this, 'settings_sanitize' ] // Sanitize
		);

		add_settings_section(
			'fullculqi_section', // ID
			false, // Title
			false, // Callback [ $this, 'print_section_info' ]
			'fullculqi_page' // Page
		);

		add_settings_field(
			'fullculqi_commerce', // ID
			esc_html__( 'Commerce name', 'fullculqi' ), // Commerce Name
			[ $this, 'input_commerce' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_pubkey', // ID
			esc_html__( 'Public Key', 'fullculqi' ), // Public Key
			[ $this, 'input_pubkey' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_seckey', // ID
			esc_html__( 'Secret Key', 'fullculqi' ), // Secret Key
			[ $this, 'input_seckey' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_logo', // ID
			esc_html__( 'Logo URL', 'fullculqi' ), // Logo
			[ $this, 'input_logo' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		add_settings_field(
			'fullculqi_button_clear', // ID
			esc_html__( 'Delete all the entities', 'fullculqi' ), // Simple Payment
			[ $this, 'input_delete_all' ], // Callback
			'fullculqi_page', // Page
			'fullculqi_section' // Section
		);

		do_action( 'fullculqi/settings/after_fields' );
	}


	/**
	 * Sanitize fields
	 * @param  array $inputs
	 * @return array
	 */
	public function settings_sanitize( $inputs = [] ) {

		$default = fullculqi_get_default();

		foreach( $default as $key => $value) {
			if( ! isset( $inputs[$key] ) || empty( $inputs[$key] ) )
				$settings[$key] = $default[$key];
			else
				$settings[$key] = is_array( $inputs[$key] ) ? array_map( 'sanitize_text_field', $inputs[$key] ) : sanitize_text_field( $inputs[$key] );
		}

		return $settings;
	}

	
	/**
	 * Input Commerce
	 * @return html
	 */
	public function input_commerce() {
		$settings = fullculqi_get_settings();

		fullculqi_get_template( 'resources/layouts/admin/settings/input_commerce.php', $settings );
	}

	/**
	 * Input Publick Key
	 * @return html
	 */
	public function input_pubkey() {
		$settings = fullculqi_get_settings();

		fullculqi_get_template( 'resources/layouts/admin/settings/input_pubkey.php', $settings );
	}

	/**
	 * Input Secret Key
	 * @return html
	 */
	public function input_seckey() {
		$settings = fullculqi_get_settings();

		fullculqi_get_template( 'resources/layouts/admin/settings/input_seckey.php', $settings );
	}

	/**
	 * Input URL logo
	 * @return html
	 */
	public function input_logo() {
		$settings = fullculqi_get_settings();

		fullculqi_get_template( 'resources/layouts/admin/settings/input_logo.php', $settings );
	}

	/**
	 * Input Button Delete All
	 * @return html
	 */
	public function input_delete_all() {
		fullculqi_get_template( 'resources/layouts/admin/settings/input_delete_all.php' );
	}

}

new FullCulqi_Settings();