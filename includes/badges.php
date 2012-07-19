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
			'add_new' => _x('Add New', 'badge'),
			'add_new_item' => __('Add New Badge'),
			'edit_item' => __('Edit Badge'),
			'new_item' => __('New Badge'),
			'all_items' => __('All Badges'),
			'view_item' => __('View Badge'),
			'search_items' => __('Search Badges'),
			'not_found' =>  __('No badges found'),
			'not_found_in_trash' => __('No badges found in Trash'),
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
			'supports' => array( 'title', 'editor', 'thumbnail' )
		);

		register_post_type( $this->get_post_type_name(), $args );
	}
}
new WPBadger_Badge_Schema();

add_action( 'load-post.php', 'wpbadger_badges_meta_boxes_setup' );
add_action( 'load-post-new.php', 'wpbadger_badges_meta_boxes_setup' );

function wpbadger_badges_meta_boxes_setup() {
	add_action( 'add_meta_boxes', 'wpbadger_add_badge_meta_boxes' );
	add_action( 'save_post', 'wpbadger_save_badge_meta', 10, 2 );
}

// Create metaboxes for post editor
function wpbadger_add_badge_meta_boxes() {

	add_meta_box(
		'wpbadger-badge-version',		// Unique ID
		esc_html__( 'Badge Version', 'example' ),	// Title
		'wpbadger_badges_meta_box',		// Callback function
		'badge',						// Admin page (or post type)
		'side',							// Context
		'default'						// Priority
	);
}

// Display metaboxes
function wpbadger_badges_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'wpbadger_badge_nonce' ); ?>

	<p>
		<input class="widefat" type="text" name="wpbadger-badge-version" id="wpbadger-badge-version" value="<?php echo esc_attr( get_post_meta( $object->ID, 'wpbadger-badge-version', true ) ); ?>" size="30" />
	</p>
<?php }

function wpbadger_save_badge_meta( $post_id, $post ) {
	
	$post_type = get_post_type_object( $post->post_type );

	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	$new_meta_value = $_POST['wpbadger-badge-version'];

	$meta_key = 'wpbadger-badge-version';
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	if ( $new_meta_value && '' == $meta_value ) {
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );
	} elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	} elseif ( '' == $new_meta_value && $meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );		
	}
}

add_action( 'add_meta_boxes', 'wpbadger_change_badge_image_meta_box', 0 );

function wpbadger_change_badge_image_meta_box() {
	global $wp_meta_boxes;

	unset( $wp_meta_boxes['post']['side']['core']['postimagediv'] );
	add_meta_box('postimagediv',
	__('Badge Image'),
	'post_thumbnail_meta_box',
	'badge',
	'side',
	'low');
}

add_filter( 'user_can_richedit', 'wpbadger_disable_wysiwyg_for_badges' );

function wpbadger_disable_wysiwyg_for_badges( $default ) {
    global $post;
    if ( 'badge' == get_post_type( $post ) )
        return false;
    return $default;
}

?>