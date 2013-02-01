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

        /* Filter the content of the badge post type in the display, so badge metadata
           including badge image are displayed on the page. */
        add_filter( 'the_content', array( $this, 'content_filter' ) );

        /* Filter the title of a badge post type in its display to include version */
        add_filter( 'the_title', array( $this, 'title_filter' ), 10, 3 );

        add_action( 'save_post', array( $this, 'save_post_validate' ), 99, 2 );
        add_filter( 'display_post_states', array( $this, 'display_post_states' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        add_filter( 'manage_badge_posts_columns', array( $this, 'manage_posts_columns' ), 10 );
        add_action( 'manage_badge_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );
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
            'supports'          => array( 'title', 'editor', 'thumbnail' ),
            'taxonomies'        => array( 'category' )
		);

		register_post_type( $this->get_post_type_name(), $args );

        # Actions and filters that depend on the post_type name, so can't run
        # until here
	}
    
    // Loop Filters and Actions
    
    /**
     * Adds the badge image to the content when we are in The Loop.
     */
    function content_filter( $content )
    {
        if (get_post_type() == $this->get_post_type_name() && in_the_loop())
            return '<p>' . get_the_post_thumbnail( get_the_ID(), 'thumbnail', array( 'class' => 'alignright' ) ) . $content . '</p>';
        else
            return $content;
    }

    /**
     * Adds the badge version to the title when we are in The Loop.
     */
    function title_filter( $title )
    {
        if (get_post_type() == $this->get_post_type_name() && in_the_loop())
            return $title . ' (Version ' . get_post_meta( get_the_ID(), 'wpbadger-badge-version', true ) . ')';
        else
            return $title;
    }

    // Admin Filters and Actions

    /**
     * Display admin notices about invalid posts.
     */
    function admin_notices()
    {
        global $pagenow, $post;

        if ($pagenow != 'post.php')
            return;
        if (empty( $post ) || ($post->post_type != $this->get_post_type_name()))
            return;
        if ($post->post_status != 'publish')
            return;

        $valid = $this->check_valid( $post->ID, $post );

        if (!$valid[ 'image' ])
            echo '<div class="error"><p>'.__("You must set a badge image that is a PNG file.", 'wpbadger').'</p></div>';
        if (!$valid[ 'description' ])
            echo '<div class="error"><p>'.__("You must enter a badge description.", 'wpbadger').'</p></div>';
        if (!$valid[ 'description-length' ])
            echo '<div class="error"><p>'.__("The description cannot be longer than 128 characters.", 'wpbadger').'</p></div>';
        if (!$valid[ 'criteria' ])
            echo '<div class="error"><p>'.__("You must enter the badge criteria.", 'wpbadger').'</p></div>';
    }

    /**
     * Checks that a badge post is valid. Returns an array with the parts checked, and
     * an overall results. Array keys:
     *
     * - image
     * - description
     * - description-length
     * - criteria
     * - status
     * - all
     *
     * @return array
     */
    function check_valid( $post_id, $post = null )
    {
        if (is_null( $post ))
            $post = get_post( $post_id );

        $rv = array(
            'image'         => false,
            'description'   => false,
            'description-length' => false,
            'criteria'      => false,
            'status'        => false
        );

        # Check for post image, and that it is a PNG
        $image_id = get_post_thumbnail_id( $post_id );
        if ($image_id > 0)
        {
            $image_file = get_attached_file( $image_id );
            if (!empty( $image_file ))
            {
                $image_ext = pathinfo( $image_file, PATHINFO_EXTENSION );
                if (strtolower( $image_ext ) == 'png')
                    $rv[ 'image' ] = true;
            }
        }

        # Check that the description is not empty.
        $desc = get_post_meta( $post_id, 'wpbadger-badge-description', true );
        if (!empty( $desc ))
            $rv[ 'description' ] = true;
        if (strlen( $desc ) <= 128)
            $rv[ 'description-length' ] = true;
        
        # Check that the criteria is not empty.
        $criteria = trim( strip_tags( $post->post_content ) );
        if (!empty( $criteria ))
            $rv[ 'criteria' ] = true;

        if ($post->post_status == 'publish')
            $rv[ 'status' ] = true;

        $rv[ 'all' ] = $rv[ 'image' ] && $rv[ 'description' ] && $rv[ 'description-length' ] && $rv[ 'criteria' ] && $rv[ 'status' ];

        return $rv;
    }

    /**
     * Add a simple description metabox. We can't place this where we want
     * directly in the page, so just dump it wherever and use JS to reposition it.
     *
     * Also, since we're going to re-enable the media buttons, add the label for the criteria
     * box.
     */
    function description_meta_box()
    {
        if (get_post_type() != $this->get_post_type_name())
            return;

        ?>
        <div id="wpbadger-badge-descriptiondiv"><div id="wpbadger-badge-descriptionwrap">
            <label class="screen-reader-text" id="wpbadger-badge-description-prompt-text" for="wpbadger-badge-description"><?php _e( "Enter description here", "wpbadger" ) ?></label>
            <input type="text" class="widefat" name="wpbadger-badge-description" id="wpbadger-badge-description" value="<?php esc_attr_e( get_post_meta( get_the_ID(), 'wpbadger-badge-description', true ) ) ?>" />
        </div></div>
        <?php
    }

    /**
     * If the badge is invalid, add it to the list of post states.
     */
    function display_post_states( $post_states )
    {
        if (get_post_type() != $this->get_post_type_name())
            return $post_states;

        if (get_post_status() == 'publish')
        {
            $valid = get_post_meta( get_the_ID(), 'wpbadger-badge-valid', true );
            if (!$valid)
                $post_states[ 'wpbadger-badge-state' ] = '<span class="wpbadger-badge-state-invalid">'.__( "Invalid", 'wpbadger' ).'</span>';
        }

        return $post_states;
    }

    /**
     * Get the badge description metadata. For legacy reasons, this will
     * try to use the post_content if the description metadata isn't present.
     */
    function get_post_description( $post_id, $post = null )
    {
        if (is_null( $post ))
            $post = get_post( $post_id );

        $desc = get_post_meta( $post_id, 'wpbadger-badge-description', true );
        if (empty( $desc ))
        {
            $desc = strip_tags( $post->post_content );
            $desc = str_replace( array( "\r", "\n" ), '', $desc );
        }

        return $desc;
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
            $this->get_post_type_name(),
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
            $this->get_post_type_name(),						// Admin page (or post type)
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
        add_action( 'edit_form_advanced', array( $this, 'description_meta_box' ) );

        add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
    }

    /**
     * Save the meta information for a badge post.
     */
    function save_post( $post_id, $post )
    {
        if ($post->post_type != $this->get_post_type_name())
            return $post_id;

        if (empty( $_POST ) || !wp_verify_nonce( $_POST[ 'wpbadger_badge_nonce' ], basename( __FILE__ ) ))
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

        $meta_key = 'wpbadger-badge-description';
        $meta_value = strip_tags( $_POST[ $meta_key ] );

        if (empty( $meta_value ))
            delete_post_meta( $post_id, $meta_key );
        else
            update_post_meta( $post_id, $meta_key, $meta_value );
    }

    /**
     * Validate the post metadata and mark it as valid or not.
     */
    function save_post_validate( $post_id, $post )
    {
        if ($post->post_type != $this->get_post_type_name())
            return;

        $valid = $this->check_valid( $post_id, $post );

        update_post_meta( $post_id, 'wpbadger-badge-valid', $valid[ 'all' ] );
    }
}

$GLOBALS[ 'wpbadger_badge_schema' ] = new WPBadger_Badge_Schema();

