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
			'all_items' => __('All Awards'),
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
            'exclude_from_search' => true,
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
        'wpbadger-award-information',
        esc_html__( 'Award Information', 'example' ),
        'wpbadger_award_information_meta_box',
        'award',
        'side',
        'default'
    );
}

function wpbadger_award_information_meta_box( $object, $box ) {
	wp_nonce_field( basename( __FILE__ ), 'wpbadger_award_nonce' );

    $is_published = ('publish' == $object->post_status || 'private' == $object->post_status);
    $award_badge_id = get_post_meta($object->ID, 'wpbadger-award-choose-badge', true);
    $award_email = get_post_meta($object->ID, 'wpbadger-award-email-address', true);
    $award_status = get_post_meta($object->ID, 'wpbadger-award-status', true);
	
    ?>
    <div id="wpbadger-award-actions">
	<div class="wpbadger-award-section wpbadger-award-badge">
    <label for="wpbadger-award-choose-badge">Badge: </label>
    
    <?php 	
    if (!$is_published || current_user_can('manage_options')) {
        echo '<select name="wpbadger-award-choose-badge" id="wpbadger-award-choose-badge">';

        $query = new WP_Query( array( 'post_type' => 'badge' ) );
        while ( $query->have_posts() ) : $query->the_post();
            $badge_title_version = the_title(null, null, false) . " (" . get_post_meta(get_the_ID(), 'wpbadger-badge-version', true) . ")";

            // As we iterate through the list of badges, if the chosen badge has the same ID then mark it as selected
            if ($award_badge_id == get_the_ID()) { 
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            echo "<option name='wpbadger-award-choose-badge' value='" . get_the_ID() . "'". $selected . ">";
            echo $badge_title_version . "</option>";
        endwhile;
        wp_reset_postdata();

        echo '</select>';
    } else {
        $badge_title_version = get_the_title( $award_badge_id ) . " (" . get_post_meta($award_badge_id, 'wpbadger-badge-version', true) . ")";
        echo "<b>" . $badge_title_version . "</b>";
    }
	?>
	
	</div>
    <div class="wpbadger-award-section wpbadger-award-email-address">
        <label for="wpbadger-award-email-address">Email Address:</label><br />
    <?php
    if (!$is_published || current_user_can('manage_options')) {
        echo '<input type="text" name="wpbadger-award-email-address" id="wpbadger-award-email-address" value="' . esc_attr($award_email) . '" />';
    } else {
        echo '<b>' . $award_email . '</b>';
    }
    ?>

	</div>

    <?php if ($is_published) { ?>
        <div class="wpbadger-award-section wpbadger-award-status">
        Status: <b><?php echo esc_html($award_status) ?></b>
        </div>
    <?php }

    echo '</div>';
}

function wpbadger_save_award_meta( $post_id, $post ) {

	if ( !isset( $_POST['wpbadger_award_nonce'] ) || !wp_verify_nonce( $_POST['wpbadger_award_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	$post_type = get_post_type_object( $post->post_type );

	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	$meta_key = 'wpbadger-award-choose-badge';
	$new_value = $_POST['wpbadger-award-choose-badge'];
    $old_value = get_post_meta( $post_id, $meta_key, true );

    if ( $new_value && empty( $old_value ) ) {
        add_post_meta( $post_id, $meta_key, $new_value, true );
    } elseif ( current_user_can( 'manage_options' ) ) {
        if ( $new_value && $new_value != $old_value ) {
            update_post_meta( $post_id, $meta_key, $new_value );
        } elseif ( empty( $new_value ) ) {
            delete_post_meta( $post_id, $meta_key, $old_value );
        }
    }

	$meta_key = 'wpbadger-award-email-address';
	$new_value = $_POST['wpbadger-award-email-address'];
	$old_value = get_post_meta( $post_id, $meta_key, true );

	if ( $new_value && empty( $old_value ) ) {
        add_post_meta( $post_id, $meta_key, $new_value, true );
    } elseif ( current_user_can( 'manage_options' ) ) {
        if ( $new_value && $new_value != $old_value ) {
            update_post_meta( $post_id, $meta_key, $new_value );
        } elseif ( empty( $new_value ) ) {
            delete_post_meta( $post_id, $meta_key, $old_value );	
        }
    }

    if ( get_post_meta( $post_id, 'wpbadger-award-status', true ) == false ) {
        add_post_meta( $post_id, 'wpbadger-award-status', 'Awarded' );
    }

	// Add the salt only the first time, and do not update if already exists
	if ( get_post_meta( $post_id, 'wpbadger-award-salt', true ) == false ) {
	    $salt = substr( str_shuffle( str_repeat( "0123456789abcdefghijklmnopqrstuvwxyz", 8 ) ), 0, 8 );
		add_post_meta( $post_id, 'wpbadger-award-salt', $salt );
    }
}

add_filter( 'template_include', 'wpbadger_award_template_check' );

function wpbadger_award_template_check() {
	global $template;
	
	$json = get_query_var('json');

	if ( get_post_type() == 'award' && $json) {
		$template_file = dirname(__FILE__) . '/awards_json.php';
		return $template_file;
	}
	return $template;
}

add_filter( 'the_content', 'wpbadger_award_content_filter' );

function wpbadger_award_content_filter($content) {
	$accept = get_query_var( 'accept' );
	$reject = get_query_var( 'reject' );
	
	if (get_post_type() == 'award') {
		if ($accept) {
			// Only update the post meta if the previous status was 'Awarded'
			if (get_post_meta(get_the_ID(), 'wpbadger-award-status', true) == 'Awarded') {
				update_post_meta(get_the_ID(), 'wpbadger-award-status', 'Accepted');
			} else {
				return "<p>This award has already been claimed.</p><p>If you believe this was done in error, please contact the site administrator, <a href='mailto:" . get_settings('admin_email') . "'>" . get_settings('admin_email') . "</p>";
			}

			// Include administrative plugin helpers
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			// If WP Super Cache Plugin installed, delete cache files for award post
			if (is_plugin_active('wp-super-cache/wp-cache.php')) {
			   wp_cache_post_change(get_the_ID());
			}

			return "<p>Your award has been successfully accepted and added to your backpack.</p>";
		} elseif ($reject) {
			if (get_post_meta(get_the_ID(), 'wpbadger-award-status', true) == 'Awarded') {
				update_post_meta(get_the_ID(), 'wpbadger-award-status', 'Rejected');
			} else {
				return "<p>This badge has successfully been declined.</p>";
			}

			// Include administrative plugin helpers
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			// If WP Super Cache Plugin installed, delete cache files for award post
			if (is_plugin_active('wp-super-cache/wp-cache.php')) {
			   wp_cache_post_change(get_the_ID());
			}
			
			return "<p>You have successfully declined your badge.</p>";
		} else {
			$award_status = get_post_meta(get_the_ID(), 'wpbadger-award-status', true);
			if ($award_status == 'Awarded') {
				// Pass query parameters differently based upon site permalink structure
				if (get_option('permalink_structure') == '') {
					$query_separator = '&';
				} else {
					$query_separator = '?';
				}
				
				return "<script>
				$(document).ready(function() {
					// Some js originally based on Badge it Gadget Lite https://github.com/Codery/badge-it-gadget-lite/blob/master/digital-badges/get-my-badge.php

					$('.js-required').hide();

					if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){  //The Issuer API isn't supported on MSIE browsers
						$('.backPackLink').hide();
						$('.login-info').hide();
						$('.browserSupport').show();
					}

					// Function that issues the badge
					$('.backPackLink').click(function() {
						var assertionUrl = '" . get_permalink() . $query_separator . "json=1';
						OpenBadges.issue([''+assertionUrl+''], function(errors, successes) {					
							if (successes.length > 0) {
									$('.backPackLink').hide();
									$('.login-info').hide();
									$('#badgeSuccess').show();
									$.ajax({
				  						url: '" . get_permalink() . $query_separator . "accept=1',
				  						type: 'POST',
										success: function(data, textStatus) {
											window.location.href = '" . get_permalink() ."';
										}
									});
								}
							});
						});

					// Function that rejects the badge
					$('.rejectBadge').click(function() {
						$.ajax({
							url: '" . get_permalink() . $query_separator . "reject=1',
							type: 'POST',
							success: function(data, textStatus) {
								window.location.href = '" . get_permalink() . "';
							}
						});
					});
				});
				</script>
				
				<p>Congratulations! The " . get_the_title(get_post_meta($post->ID, 'wpbadger-award-choose-badge', true)) . " badge has been awarded.</p><p>Please choose to <a href='#' class='backPackLink'>accept</a> or <a href='#' class='rejectBadge'>decline</a> the badge.</p>";
			} elseif ($award_status == 'Rejected') {
				return "<p>This badge has been successfully declined.</p>";
			} else {
				return "<p>This badge was awarded for the following reason:</p><p><em> \"" . get_the_content() .  "\"</em></p>";
			}
		}
	} else {
		return $content;
	}
}


add_action('wp_enqueue_scripts', 'wpbadger_award_enqueue_scripts');

function wpbadger_award_enqueue_scripts() {
	if (get_post_type() == 'award') {
		wp_enqueue_script('openbadges', 'http://beta.openbadges.org/issuer.js', array(), null);
		wp_enqueue_script('jquery_ajax', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
	}
}

add_action( 'wp_insert_post', 'wpbadger_award_send_email' );

function wpbadger_award_send_email( $post_id ) {
	// Verify that post has been published, and is an award
	if ((get_post_type($post_id) == 'award') && (get_post_status($post_id) == 'publish') && (get_post_meta($post_id, 'wpbadger-award-status', true) == 'Awarded')) {
		$email_address = get_post_meta($post_id, 'wpbadger-award-email-address', true);
		$badge = get_the_title(get_post_meta($post_id, 'wpbadger-award-choose-badge', true));

		$post_title = get_the_title( $post_id );
		$post_url = get_permalink( $post_id );
		$subject = "Congratulations! You have been awarded the " . $badge . " badge!";

		if (get_option('wpbadger_config_award_email_text')) {
			$message = get_option('wpbadger_config_award_email_text') . "\n\n";
		} else {
			$message = "Congratulations, " . get_option('wpbadger_issuer_org') . " has awarded you a badge. Please visit the link below to redeem it.\n\n";			
		}
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
}

// Runs before saving a new post, and filters the post data
add_filter('wp_insert_post_data', 'wpbadger_award_save_title', '99', 2);

function wpbadger_award_save_title($data, $postarr) {
	if ($_POST['post_type'] == 'award') {
		$data['post_title'] = "Badge Awarded: " . get_the_title($_POST['wpbadger-award-choose-badge']);
	}
	return $data;
}

// Generate the award slug. Shared by interface to award single badges, as well as bulk
function wpbadger_award_generate_slug() {
	$slug = rand(100000000000000, 999999999999999);
	
	return $slug;
}

// Runs before saving a new post, and filters the post slug
add_filter('name_save_pre', 'wpbadger_award_save_slug');

function wpbadger_award_save_slug($my_post_slug) {
	if ($_POST['post_type'] == 'award') {
		$my_post_slug = wpbadger_award_generate_slug();		
	}
	return $my_post_slug;
}

add_filter('manage_award_posts_columns', 'wpbadger_columns_awards', 10);  
add_action('manage_award_posts_custom_column', 'wpbadger_columns_content_only_awards', 10, 2);  
  
function wpbadger_columns_awards	($defaults) {  
    $defaults['issued_to_email'] = 'Issued To Email';
	$defaults['badge_status'] = 'Badge Status';
    return $defaults;  
}  
function wpbadger_columns_content_only_awards($column_name, $post_id) {  
    if ($column_name == 'issued_to_email') {  
		echo get_post_meta($post_id, 'wpbadger-award-email-address', true);
    }
	if ($column_name == 'badge_status') {
		echo get_post_meta($post_id, 'wpbadger-award-status', true);
	}
}
?>
