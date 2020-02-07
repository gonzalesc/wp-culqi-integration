<?php

class FullCulqi {

	/**
	 * Plugin Instance
	 */
	protected static $_instance = null;

	/**
	 * Settings Instance
	 */
	protected $settings;
	
	/**
	 * Admin Instance
	 */
	protected $admin;

	/**
	 * Payment Instance
	 */
	protected $payment;

	/**
	 * Checkout Instance
	 */
	protected $checkout;

	/**
	 * Ajax Instance
	 */
	protected $ajax;

	/**
	 * License Instance
	 */
	protected $license;

	/**
	 * Ensures only one instance is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'letsgo' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'letsgo' ), '2.1' );
	}


	function __construct() {

		$this->load_dependencies();
		$this->set_locale();
		$this->set_objects();

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 0 );
	}

	private function load_dependencies() {

		require_once FULLCULQI_PLUGIN_DIR . 'vendor/autoload.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/functions.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-i18n.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-cpt.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-provider.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-define.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-logs.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-checkout.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-ajax.php';
		require_once FULLCULQI_PLUGIN_DIR . 'includes/class-fullculqi-wc.php';
		require_once FULLCULQI_PLUGIN_DIR . 'public/class-fullculqi-integrator.php';
		
		if( is_admin() ) {
			require_once FULLCULQI_PLUGIN_DIR . 'admin/class-fullculqi-settings.php';
			require_once FULLCULQI_PLUGIN_DIR . 'admin/class-fullculqi-entities.php';
			require_once FULLCULQI_PLUGIN_DIR . 'admin/class-fullculqi-payments.php';
			require_once FULLCULQI_PLUGIN_DIR . 'admin/class-fullculqi-admin.php';
		}
	}


	public function notice_woo() {
		echo '<div class="error"><p>' . sprintf( esc_html__( 'Woocommerce Culqi Full Integration plugin depends on the last version of %s to work!', 'letsgo' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
	}

	public function notice_currency() {
		echo '<div class="error"><p>' . esc_html__( 'Woocommerce Culqi Full Integration plugin needs the currency in the commerce be PEN or USD to work!', 'letsgo' ) . '</p></div>';
	}

	public function plugins_loaded() {
		global $culqi, $culqi_token;

		$settings = fullculqi_get_settings();

		if( isset($settings['secret_key']) && !empty($settings['secret_key']) ) {
			$GLOBALS['culqi'] = new Culqi\Culqi( [ 'api_key' => $settings['secret_key'] ] );
		}

		if( isset($settings['public_key']) && !empty($settings['public_key']) ) {
			$GLOBALS['culqi_token'] = new Culqi\Culqi( [ 'api_key' => $settings['public_key'] ] );
		}

		if( $settings['woo_payment'] == 'yes' ) {
			
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				add_action( 'admin_notices', [ $this, 'notice_woo'] );
				return;
			}

			if ( !in_array(get_woocommerce_currency(), array_keys(fullculqi_get_currencies()) )) {
				add_action( 'admin_notices', [ $this, 'notice_currency'] );
				return;
			}

			/********************
				METHOD PAYMENT
			*********************/
			new FullCulqi_Define();
		}
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the ShipArea_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 */
	private function set_locale() {

		$plugin_i18n = new FullCulqi_i18n();
		$plugin_i18n->set_domain( 'letsgo' );

		add_action( 'plugins_loaded', [ $plugin_i18n, 'load_plugin_textdomain' ] );
	}

	/**
	 * Set all global objects
	 */
	private function set_objects() {
		$this->ajax = new FullCulqi_Ajax();

		if( !is_admin() )
			$this->wc = new FullCulqi_WC();

		if( is_admin() ) {
			$this->settings		= new FullCulqi_settings();
			$this->payment 		= new FullCulqi_Payments();
			$this->admin		= new FullCulqi_Admin();
		}
	}
}

?>