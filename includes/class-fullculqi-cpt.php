<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FullCulqi_Cpt {

	public function __construct() {
		add_action('init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type() {

		// Payments
		$labels_payment = [
			'name'					=> esc_html__('Culqi Payments', 'letsgo'),
			'menu_name'				=> esc_html__('Payments', 'letsgo'),
			'name_admin_bar'		=> esc_html__('Payments', 'letsgo'),
			'all_items'				=> esc_html__('Payments', 'letsgo'),
			'singular_name'			=> esc_html__('Payment', 'letsgo'),
			'add_new'				=> esc_html__('Add New Payment', 'letsgo'),
			'add_new_item'			=> esc_html__('Add New Payment','letsgo'),
			'edit_item'				=> esc_html__('Edit Payment','letsgo'),
			'new_item'				=> esc_html__('New Payment','letsgo'),
			'view_item'				=> esc_html__('View Payment','letsgo'),
			'search_items'			=> esc_html__('Search Payments','letsgo'),
			'not_found'				=> esc_html__('Nothing found','letsgo'),
			'not_found_in_trash'	=> esc_html__('Nothing found in Trash','letsgo'),
			'parent_item_colon'		=> ''	
		];
		 
		$args_payment = [
			'labels'				=> $labels_payment,
			'public'				=> false,
			'show_in_menu'			=> 'fullculqi_menu',
			'publicly_queryable'	=> false,
			'show_ui'				=> true,
			'query_var'				=> false,
			//'menu_icon'			=> plugins_url( 'images/icon_star.png' , esc_html__FILEesc_html__ ),
			'rewrite'				=> false,
			'hierarchical'			=> false,
			'menu_position'			=> 54.2,
			'supports'				=> false,
			'exclude_from_search'	=> true,
			'show_in_nav_menus'		=> false,
			'map_meta_cap'			=> true,
			'capability_type'		=> [ 'payment', 'payments' ],
			'capabilities'			=> [
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
			]
		];

		register_post_type('culqi_payments', apply_filters('fullculqi/register_post/payment', $args_payment));
		
		flush_rewrite_rules();
	}
}

new FullCulqi_Cpt();
?>