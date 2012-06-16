<?php

class WPBadger_Badge_Schema {
	private $post_type_name;

	function __construct() {
		$this->set_post_type_name();

		add_action( 'init', array( &$this, 'register_post_type' ) );
	}

	public function get_post_type_name() {
		return $this->post_type_name;
	}

	private function set_post_type_name() {
		$this->post_type_name = apply_filters( 'wpbadger_badge_post_type_name', 'badge' );
	}

	function register_post_type() {
		$labels = array(
			'name' => _x('Badges', 'post type general name'),
			'singular_name' => _x('Badge', 'post type singular name'),
			'add_new' => _x('Add New', 'book'),
			'add_new_item' => __('Add New Badge'),
			'edit_item' => __('Edit Book'),
			'new_item' => __('New Book'),
			'all_items' => __('All Books'),
			'view_item' => __('View Book'),
			'search_items' => __('Search Books'),
			'not_found' =>  __('No books found'),
			'not_found_in_trash' => __('No books found in Trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Badges'
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'query_var' => true,
			'rewrite'      => array(
				'slug'       => 'badges',
				'with_front' => false,
			),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' )
		);

		register_post_type( $this->get_post_type_name(), $args );
	}
}
new WPBadger_Badge_Schema();

?>