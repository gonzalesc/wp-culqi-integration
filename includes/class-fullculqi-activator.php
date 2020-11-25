<?php

/**
 * Activator plugin
 */
class fullculqi_Activator {
	
	static public function activate() {

		$settings = fullculqi_get_settings();

		if( !isset($settings['commerce']) || empty($settings['commerce']) ||
			!isset($settings['public_key']) || empty($settings['public_key']) ||
			!isset($settings['secret_key']) || empty($settings['secret_key'])
		) {
			set_transient( 'fullculqi_activator', true, 30 );
		}

		// Permissions
		self::set_capabilities();

		// Refresh Permalinks
		flush_rewrite_rules();
	}


	/**
	 * Set Permission to Admin
	 */
	public static function set_capabilities() {
		$administrator = get_role( 'administrator' );
	
		$admin_caps = apply_filters( 'fullculqi/charges/set_capabilities', [
			//'delete_charges',
			//'delete_others_charges',
			//'delete_private_charges',
			//'delete_published_charges',
			'edit_charges',
			//'edit_others_charges',
			//'edit_private_charges',
			'edit_published_charges',
			'publish_charges',
			//'read_private_charges',
			
			'edit_orders',
			'edit_published_orders',
			'publish_orders',
		] );
	
		foreach( $admin_caps as $cap )
			$administrator->add_cap( $cap );
	}
}
?>