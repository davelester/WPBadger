<?php
/**
 * Badge custom post type.
 *
 * @package wpbadger
 */

/**
 * Implements all the filters and actions needed to make the badge
 * custom post type work.
 */
class WPBadger_Badge_Schema
{
    /** Capability type to use when registering the custom post type. */
    private $post_capability_type;
    /** Name to use when registering the custom post type. */
    private $post_type_name;

    /**
     * Constructs the WPBadger Badge Schema instance. It registers all the hooks
     * needed to support the custom post type. This should only be called once.
     */
    function __construct()
    {
		add_action( 'init', array( $this, 'init' ) );

        add_action( 'load-post.php', array( $this, 'meta_boxes_setup' ) );
        add_action( 'load-post-new.php', array( $this, 'meta_boxes_setup' ) );

        add_filter( 'user_can_richedit', array( $this, 'disable_wysiwyg' ) );

        /* Filter the content of the badge post type in the display, so badge metadata
           including badge image are displayed on the page. */
        add_filter( 'the_content', array( $this, 'content_filter' ) );

        /* Filter the title of a badge post type in its display to include version */
        add_filter( 'the_title', array( $this, 'title_filter' ), 10, 3 );

        add_filter('manage_badge_posts_columns', array( $this, 'manage_posts_columns' ), 10);  
        add_action('manage_badge_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2);  
	}

    // Accessors and Mutators

    public function get_post_capability_type()
    {
        return $this->post_capability_type;
    }

    public function get_post_type_name()
    {
		return $this->post_type_name;
	}

    private function set_post_capability_type( $new_val = 'post' )
    {
        $this->post_capability_type = apply_filters( 'wpbadger_badge_post_capability_type', $new_val );
    }

    private function set_post_type_name( $new_val = 'badge' )
    {
		$this->post_type_name = apply_filters( 'wpbadger_badge_post_type_name', $new_val );
	}

    // General Filters and Actions

    /**
     * Initialize the custom post type. This registers what we need to
     * support the Badge type.
     */
    function init()
    {
        $this->set_post_type_name();
        $this->set_post_capability_type();

		$labels = array(
			'name'              => _x( 'Badges', 'post type general name', 'wpbadger' ),
			'singular_name'     => _x( 'Badge', 'post type singular name', 'wpbadger' ),
			'add_new'           => _x( 'Add New', 'badge', 'wpbadger' ),
			'add_new_item'      => __( 'Add New Badge', 'wpbadger' ),
			'edit_item'         => __( 'Edit Badge', 'wpbadger' ),
			'new_item'          => __( 'New Badge', 'wpbadger' ),
			'all_items'         => __( 'All Badges', 'wpbadger' ),
			'view_item'         => __( 'View Badge', 'wpbadger' ),
			'search_items'      => __( 'Search Badges', 'wpbadger' ),
			'not_found'         => __( 'No badges found', 'wpbadger' ),
			'not_found_in_trash' => __( 'No badges found in Trash', 'wpbadger' ),
			'parent_item_colon' => '',
			'menu_name'         => __( 'Badges', 'wpbadger' )
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'query_var'         => true,
			'rewrite' => array(
				'slug'          => 'badges',
				'with_front'    => false,
			),
			'capability_type'   => $this->get_post_capability_type(),
			'has_archive'       => true,
			'hierarchical'      => false,
			'supports'          => array( 'title', 'editor', 'thumbnail' )
		);

		register_post_type( $this->get_post_type_name(), $args );
	}
    
    // Loop Filters and Actions
    
    /**
     * Adds the badge image to the content when we are in The Loop.
     */
    function content_filter( $content )
    {
        if (get_post_type() == 'badge' && in_the_loop())
            return '<p>' . get_the_post_thumbnail( get_the_ID(), 'thumbnail', array( 'class' => 'alignright' ) ) . $content . '</p>';
        else
            return $content;
    }

    /**
     * Adds the badge version to the title when we are in The Loop.
     */
    function title_filter( $title )
    {
        if (get_post_type() == 'badge' && in_the_loop())
            return $title . ' (Version ' . get_post_meta( get_the_ID(), 'wpbadger-badge-version', true ) . ')';
        else
            return $title;
    }

    // Admin Filters and Actions

    /**
     * Disable the rich text editor for badges. We use the badge 'content'
     * as the description, and that doesn't support HTML.
     */
    function disable_wysiwyg( $default )
    {
        global $post;

        if ('badge' == get_post_type( $post ))
            return false;
        return $default;
    }

    /**
     * Modify the Feature Image metabox to be called the Badge Image.
     */
    function image_meta_box()
    {
        global $wp_meta_boxes;

        unset( $wp_meta_boxes[ 'post' ][ 'side' ][ 'core' ][ 'postimagediv' ] );
        add_meta_box(
            'postimagediv',
            esc_html__( 'Badge Image', 'wpbadger' ),
            'post_thumbnail_meta_box',
            'badge',
            'side',
            'low'
        );
    }

    /**
     * Add the badge version column to the table listing badges.
     */
    function manage_posts_columns( $defaults )
    {  
        $defaults[ 'badge_version' ] = 'Badge Version';
        return $defaults;  
    }  

    /**
     * Echo data for the badge version when displaying the table.
     */
    function manage_posts_custom_column( $column_name, $post_id )
    {  
        if ($column_name == 'badge_version')
            esc_html_e( get_post_meta( $post_id, 'wpbadger-badge-version', true ) );
    }

    /**
     * Display the Badge Version metabox.
     */
    function meta_box_version( $object, $box )
    {
        wp_nonce_field( basename( __FILE__ ), 'wpbadger_badge_nonce' );

        ?>
        <p>
            <input class="widefat" type="text" name="wpbadger-badge-version" id="wpbadger-badge-version" value="<?php esc_attr_e( get_post_meta( $object->ID, 'wpbadger-badge-version', true ) ); ?>" size="30" />
        </p>
        <?php
    }

    /**
     * Add the meta boxes to the badge post editor page.
     */
    function meta_boxes_add()
    {
        add_meta_box(
            'wpbadger-badge-version',		// Unique ID
            esc_html__( 'Badge Version', 'wpbadger' ),	// Title
            array( $this, 'meta_box_version' ),		// Callback function
            'badge',						// Admin page (or post type)
            'side',							// Context
            'default'						// Priority
        );
    }

    /**
     * Add the action hooks needed to support badge post editor metaboxes.
     */
    function meta_boxes_setup()
    {
        add_action( 'add_meta_boxes', array( $this, 'meta_boxes_add' ) );
        add_action( 'add_meta_boxes', array( $this, 'image_meta_box' ), 0 );

        add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
    }

    /**
     * Save the meta information for a badge post.
     */
    function save_post( $post_id, $post )
    {
        if ($post->post_type != $this->get_post_type_name())
            return $post_id;

        if (empty( $_POST ) || !wp_verify_nonce( $_POST[ basename( __FILE__ ) ], 'wpbadger_badge_nonce' ))
            return $post_id;

        $post_type = get_post_type_object( $post->post_type );
        if (!current_user_can( $post_type->cap->edit_post, $post_id ))
            return $post_id;

        $new_meta_value = $_POST['wpbadger-badge-version'];
        if (preg_match( '/^\d+$/', $new_meta_value )) {
            $new_meta_value .= '.0';
        } elseif (!preg_match( '/^\d+(\.\d+)+$/', $new_meta_value )) {
            $new_meta_value = '1.0';
        }

        $meta_key = 'wpbadger-badge-version';
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        if ($new_meta_value && '' == $meta_value)
            add_post_meta( $post_id, $meta_key, $new_meta_value, true );
        elseif ($new_meta_value && $new_meta_value != $meta_value)
            update_post_meta( $post_id, $meta_key, $new_meta_value );
        elseif ('' == $new_meta_value && $meta_value)
            delete_post_meta( $post_id, $meta_key, $meta_value );		
    }
}

new WPBadger_Badge_Schema();

