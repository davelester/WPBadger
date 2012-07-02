<?php

class WPbadger_Award_Schema {
	private $post_type_name;

	function __construct() {
		$this->set_post_type_name();

		add_action( 'init', array( &$this, 'register_post_type' ) );

		// Add rewrite rules
		add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );
	}

	public function get_post_type_name() {
		return $this->post_type_name;
	}

	private function set_post_type_name() {
		$this->post_type_name = apply_filters( 'wpbadger_award_post_type_name', 'award' );
	}

	function register_post_type() {
		$labels = array(
			'name' => _x('Awards', 'post type general name'),
			'singular_name' => _x('Award', 'post type singular name'),
			'add_new' => _x('Add New', 'award'),
			'add_new_item' => __('Add New Award'),
			'edit_item' => __('Edit Award'),
			'new_item' => __('New Award'),
			'all_items' => __('All Award'),
			'view_item' => __('View Award'),
			'search_items' => __('Search Awards'),
			'not_found' =>  __('No awards found'),
			'not_found_in_trash' => __('No award found in Trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Awards'
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'query_var' => true,
			'rewrite'      => array(
				'slug'       => 'awards',
				'with_front' => false
			),
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'supports' => array( 'editor' )
		);

		register_post_type( $this->get_post_type_name(), $args );

		$this->add_rewrite_tags();
	}

	/**
	 * Add rewrite tags
	 *
	 * @since 1.2
	 */
	function add_rewrite_tags() {
		add_rewrite_tag( '%%accept%%', '([1]{1,})' );
		add_rewrite_tag( '%%json%%', '([1]{1,})' );
		add_rewrite_tag( '%%reject%%', '([1]{1,})' );
	}

	/**
	 * Generates custom rewrite rules
	 *
	 * @since 1.2
	 */
	function generate_rewrite_rules( $wp_rewrite ) {
		$rules = array(
			// Create rewrite rules for each action
			'awards/([^/]+)/?$' =>
				'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ),
			'awards/([^/]+)/accept/?$' =>
				'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&accept=1',
			'awards/([^/]+)/json/?$' =>
				'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&json=1',
			'awards/([^/]+)/reject/?$' =>
				'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&reject=1',
		);

		// Merge new rewrite rules with existing
		$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );

		return $wp_rewrite;
	}
}
new WPbadger_Award_Schema();

add_action( 'load-post.php', 'wpbadger_awards_meta_boxes_setup' );
add_action( 'load-post-new.php', 'wpbadger_awards_meta_boxes_setup' );

function wpbadger_awards_meta_boxes_setup() {
	add_action( 'add_meta_boxes', 'wpbadger_add_award_meta_boxes' );
	add_action( 'save_post', 'wpbadger_save_award_meta', 10, 2 );
}

// Create metaboxes for post editor
function wpbadger_add_award_meta_boxes() {

	add_meta_box(
		'wpbadger-award-choose-badge',		// Unique ID
		esc_html__( 'Choose Badge', 'example' ),	// Title
		'wpbadger_award_choose_badge_meta_box',		// Callback function
		'award',						// Admin page (or post type)
		'side',							// Context
		'default'						// Priority
	);
	
	add_meta_box(
		'wpbadger-award-email-address',		// Unique ID
		esc_html__( 'Email Address', 'example' ),	// Title
		'wpbadger_award_email_address_meta_box',		// Callback function
		'award',						// Admin page (or post type)
		'side',							// Context
		'default'						// Priority
	);
}

// Display metaboxes
function wpbadger_award_choose_badge_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'wpbadger_award_nonce' ); ?>
	
	<?php $choose_badge_meta = get_post_meta( $object->ID, 'wpbadger-award-choose-badge', true );?>
	
	<p>
	<select name="wpbadger-award-choose-badge" id="wpbadger-award-choose-badge">
	
	<?php 	
	$query = new WP_Query( array( 'post_type' => 'badge' ) );
	
	while ( $query->have_posts() ) : $query->the_post();
		$title_version = the_title(null, null, false) . " (" . get_post_meta(get_the_ID(), 'wpbadger-badge-version', true) . ")";

		if ($choose_badge_meta == $title_version) { 
			$selected = " selected";
		} else {
			$selected = "";
		}
		echo "<option name='wpbadger-award-choose-badge'". $selected . ">";
		echo $title_version . "</option>";
	endwhile;
	?>
	
	</select>
	</p>
<?php }

function wpbadger_award_email_address_meta_box( $object, $box ) { ?>

	<p>
		<input class="widefat" type="text" name="wpbadger-award-email-address" id="wpbadger-award-email-address" value="<?php echo esc_attr( get_post_meta( $object->ID, 'wpbadger-award-email-address', true ) ); ?>" size="30" />
	</p>
<?php }

function wpbadger_save_award_meta( $post_id, $post ) {

	if ( !isset( $_POST['wpbadger_award_nonce'] ) || !wp_verify_nonce( $_POST['wpbadger_award_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	$post_type = get_post_type_object( $post->post_type );

	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	$chosen_badge_new_meta_value = $_POST['wpbadger-award-choose-badge'];
	$chosen_badge_meta_key = 'wpbadger-award-choose-badge';
	$chosen_badge_meta_value = get_post_meta( $post_id, $chosen_badge_meta_key, true );

	$email_new_meta_value = $_POST['wpbadger-award-email-address'];
	$email_meta_key = 'wpbadger-award-email-address';
	$email_meta_value = get_post_meta( $post_id, $email_meta_key, true );

	if ( $chosen_badge_new_meta_value ) {
		update_post_meta( $post_id, $chosen_badge_meta_key, $chosen_badge_new_meta_value );
	} elseif ( '' == $chosen_badge_new_meta_value ) {
		delete_post_meta( $post_id, $chosen_badge_meta_key, $chosen_badge_meta_value );
	}
	
	if ( $email_new_meta_value && '' == $email_meta_value ) {
		add_post_meta( $post_id, $email_meta_key, $email_new_meta_value, true );
	} elseif ( $email_new_meta_value && $email_new_meta_value != $email_meta_value ) {
		update_post_meta( $post_id, $email_meta_key, $email_new_meta_value );
	} elseif ( '' == $email_new_meta_value && $email_meta_value ) {
		delete_post_meta( $post_id, $email_meta_key, $email_meta_value );	
	}
}

add_filter( 'template_include', 'wpbadger_award_template_check' );

function wpbadger_award_template_check() {
	global $template;

	// Get query information
	$accept = get_query_var( 'accept' );
	$json = get_query_var( 'json' );
	$reject = get_query_var( 'reject' );

	// Check if post type 'Awards'
	if ( 'award' == get_post_type() ) {
		
		if ($json) {
			$template_file = dirname(__FILE__) . '/awards_json.php';
			return $template_file;			
		} elseif ($accept) {
		} elseif ($reject) {			
		} else {
			$template_file = dirname(__FILE__) . '/awards_template.php';
			return $template_file;
		}
	}

	return $template;
}

add_action( 'wp_insert_post', 'wpbadger_award_send_email' );

function wpbadger_award_send_email( $post_id ) {
	// Verify that post has been published, and is an award
	if (('award' == get_post_type($post_id)) && ('publish' == get_post_status ($post_id))) {
		$email_address = get_post_meta($post_id, 'wpbadger-award-email-address', true);
		$badge = split(' (', get_post_meta($post_id, 'wpbadger-award-choose-badge', true));
		
		$post_title = get_the_title( $post_id );
		$post_url = get_permalink( $post_id );
		$subject = "Congratulations! You\'ve been awarded the \"$badge\" badge!";

		$message = "Winner Winner chicken dinner! Please visit the link to redeem your badge.\n\n";
		$message .= $post_url . "\n\n";

		wp_mail( $email_address, $subject, $message );

	}
}

add_filter( 'user_can_richedit', 'wpbadger_disable_wysiwyg_for_awards' );

function wpbadger_disable_wysiwyg_for_awards( $default ) {
    global $post;
    if ( 'award' == get_post_type( $post ) )
        return false;
    return $default;
}-

// Runs before saving a new post, and filters the post title
add_filter('title_save_pre', 'wpbadger_award_save_title');

function wpbadger_award_save_title($my_post_title) {
	if ($_POST['post_type'] == 'award') {
		$new_title = "Badge Awarded: " . $_POST['wpbadger-award-choose-badge'];
	}
	return $new_title;
}

// Runs before saving a new post, and filters the post slug
add_filter('name_save_pre', 'wpbadger_award_save_slug');

function wpbadger_award_save_slug($my_post_slug) {
	if ($_POST['post_type'] == 'award') {
		$new_slug = rand(100000000000000, 999999999999999);
		$my_post_slug = $new_slug;		
	}
	return $my_post_slug;
}
?>