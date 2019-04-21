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

		$administrator = get_role( 'administrator' );
	
		$admin_caps = array(
				//'delete_payments',
				//'delete_others_payments',
				//'delete_private_payments',
				//'delete_published_payments',
				'edit_payments',
				//'edit_others_payments',
				//'edit_private_payments',
				'edit_published_payments',
				'publish_payments',
				//'read_private_payments',

				'edit_customers',
				'edit_published_customers',
				'publish_customers',

				'delete_plans',
				'delete_published_plans',
				'edit_plans',
				'edit_others_plans',
				'edit_published_plans',
				'publish_plans',

				'edit_others_subscribers',
				'edit_subscribers',
				'edit_published_subscribers',
				'publish_subscribers',
			);

		$admin_caps = apply_filters('fullculqi/capabilities', $admin_caps);
	
		foreach( $admin_caps as $cap ) {
			$administrator->add_cap( $cap );
		}
	}
}
?>