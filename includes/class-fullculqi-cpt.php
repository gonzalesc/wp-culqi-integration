<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FullCulqi_Cpt {

	public function __construct() {
		add_action('init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type() {

		// Payments
		$labels_payment = array(
			'name'					=> __('Culqi Payments', 'letsgo'),
			'menu_name'				=> __('Payments', 'letsgo'),
			'name_admin_bar'		=> __('Payments', 'letsgo'),
			'all_items'				=> __('Payments', 'letsgo'),
			'singular_name'			=> __('Payment', 'letsgo'),
			'add_new'				=> __('Add New Payment', 'letsgo'),
			'add_new_item'			=> __('Add New Payment','letsgo'),
			'edit_item'				=> __('Edit Payment','letsgo'),
			'new_item'				=> __('New Payment','letsgo'),
			'view_item'				=> __('View Payment','letsgo'),
			'search_items'			=> __('Search Payments','letsgo'),
			'not_found'				=> __('Nothing found','letsgo'),
			'not_found_in_trash'	=> __('Nothing found in Trash','letsgo'),
			'parent_item_colon'		=> ''	
		);
		 
		$args_payment = array(
			'labels'				=> $labels_payment,
			'public'				=> false,
			'show_in_menu'			=> 'fullculqi_menu',
			'publicly_queryable'	=> false,
			'show_ui'				=> true,
			'query_var'				=> false,
			//'menu_icon'			=> plugins_url( 'images/icon_star.png' , __FILE__ ),
			'rewrite'				=> false,
			'hierarchical'			=> false,
			'menu_position'			=> 54.2,
			'supports'				=> false,
			'exclude_from_search'	=> true,
			'show_in_nav_menus'		=> false,
			'map_meta_cap'			=> true,
			'capability_type'		=> array('payment','payments'),
			'capabilities'			=> array(
										'edit_post'		=> 'edit_payment',
										'read_post'		=> 'read_payment',
										'delete_post'	=> 'delete_payment',

										'edit_posts'			=> 'edit_payments',
										'edit_others_posts'		=> 'edit_others_payments',
										'publish_posts'			=> 'publish_payments',
										'read_private_posts'	=> 'read_private_payments',	

										'read'						=> 'read',
										'delete_posts'				=> 'delete_payments',
										'delete_private_posts'		=> 'delete_private_payments',
										'delete_published_posts'	=> 'delete_published_payments',
										'delete_others_posts'		=> 'delete_others_payments',
										'edit_private_posts'		=> 'edit_private_payments',
										'edit_published_posts'		=> 'edit_published_payments',
										//'create_posts'			=> 'edit_payments',
										'create_posts'				=> 'do_not_allow',
								)
		);

		register_post_type('culqi_payments', apply_filters('fullculqi/register_post/payment', $args_payment));
		
		flush_rewrite_rules();
	}
}

new FullCulqi_Cpt();
?>