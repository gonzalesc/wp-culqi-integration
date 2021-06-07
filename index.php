<?php
/*
Plugin Name: Culqi Full Integration 
Plugin URI:https://wordpress.org/plugins/wp-culqi-integration
Description: Culqi is a Payment method to Peru. This plugin is a full integration with the Culqi API.
Version: 2.0.5
Author: Lets Go Dev
Author URI: https://www.letsgodev.com/
Developer: Alexander Gonzales
Developer URI: https://vcard.gonzalesc.org/
Text Domain: culqi, woocommerce, method payment
Requires at least: 5.6
Tested up to: 5.7.2
Stable tag: 5.6
Requires PHP: 5.6
WC requires at least: 4.9.0
WC tested up to: 5.3.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'FULLCULQI_FILE' , __FILE__ );
define( 'FULLCULQI_DIR' , plugin_dir_path(__FILE__) );
define( 'FULLCULQI_URL' , plugin_dir_url(__FILE__) );
define( 'FULLCULQI_BASE' , plugin_basename( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require_once FULLCULQI_DIR . 'includes/class-fullculqi.php';


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fullculqi-activator.php
 */
function fullculqi_activate() {
	require_once FULLCULQI_DIR . 'includes/class-fullculqi-activator.php';
	fullculqi_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fullculqi-deactivator.php
 */
//function culqi_deactivate() {
//	require_once FULLCULQI_DIR . 'includes/class-fullculqi-deactivator.php';
//	fullculqi_Deactivator::deactivate();
//}


register_activation_hook( __FILE__, 'fullculqi_activate' );
//register_deactivation_hook( __FILE__, 'fullculqi_deactivate' );

/**
 * Store the plugin global
 */
global $fullculqi;

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */

function fullculqi() {
	return FullCulqi::instance();
}

$fullculqi = fullculqi();
?>