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
			'not_found' =>  __('No books found'),
			'not_found_in_trash' => __('No awards found in Trash'),
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
		add_rewrite_tag( '%%reject%%', '([1]{1,})' );
	}

	/**
	 * Generates custom rewrite rules
	 *
	 * @since 1.2
	 */
	function generate_rewrite_rules( $wp_rewrite ) {
		$rules = array(
			/**
			 * Top level
			 */

			// Create
			'awards/([^/]+)/accept/?$' =>
				'index.php?post_type=' . $this->get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&accept=1',



		);

		// Merge bbPress rules with existing
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
	
	<p>
	<select name="wpbadger_badge_id" id="wpbadger_badge_id">
	
	<?php $query = new WP_Query( array( 'post_type' => 'badge' ) );
	
	while ( $query->have_posts() ) : $query->the_post();
		echo '<li>';
		echo "<option id='wpbadger-award-choose-badge' value='";
		echo get_post_meta( $object->ID, 'wpbadger-badge-choose-badge', true );
		echo "'>";
		echo  the_title() . " (Version X)</option>";
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
	$chosen_badge_meta_key = 'wpbadger-badge-version';
	$chosen_badge_meta_value = get_post_meta( $post_id, $meta_key, true );

	$email_new_meta_value = $_POST['wpbadger-award-email-address'];
	$email_meta_key = 'wpbadger-award-email-address';
	$email_meta_value = get_post_meta( $post_id, $meta_key, true );

	if ( $chosen_badge_new_meta_value && '' == $chosen_badge_meta_value ) {
		add_post_meta( $post_id, $chosen_badge_meta_key, $chosen_badge_new_meta_value, true );
	} elseif ( $chosen_badge_new_meta_value && $chosen_badge_new_meta_value != $chosen_badge_meta_value ) {
		update_post_meta( $post_id, $chosen_badge_meta_key, $chosen_badge_new_meta_value );
	} elseif ( '' == $chosen_badge_new_meta_value && $chosen_badge_meta_value ) {
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

	if (is_single() ){
		// Get query information
		$accept = get_query_var( 'accept' );

		// Check if post type 'Awards'
		if ( 'award' == get_post_type() ) {
			$template_file = dirname(__FILE__) . '/awards_template.php';
			return $template_file;
		}
	}

	return $template;
}

//add_action( 'publish_post', 'wpbadger_award_send_email' );
add_action( 'save_post', 'wpbadger_award_send_email' );
//add_action( 'update_post', 'wpbadger_award_send_email' );

function wpbadger_award_send_email( $post_id ) {
	//verify post is not a revision
	if ( !wp_is_post_revision( $post_id ) && (get_post_type( $post_id) == 'award')) {

		$post_title = get_the_title( $post_id );
		$post_url = get_permalink( $post_id );
		$subject = 'Congratulations! You\'ve been awarded a badge!';

		$message = "Winner Winner chicken dinner! Please visit the link to redeem your badge.\n\n";
		$message .= "<a href='". $post_url. "'>" .$post_title. "</a>\n\n";

		wp_mail( 'davelester@gmail.com', $subject, $message );
		
	}
}
?>