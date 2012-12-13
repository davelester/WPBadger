<?php
/**
 * @package WPBadger
 */
/*
Plugin Name: WPBadger
Plugin URI: https://github.com/davelester/WPBadger
Description: A lightweight badge issuing platform built using WordPress.
Version: 0.6.2
Author: Dave Lester
Author URI: http://www.davelester.org
*/

add_action('admin_init', 'wpbadger_admin_init');
add_action('admin_head', 'wpbadger_admin_head');
add_action('admin_menu', 'wpbadger_admin_menu');
add_action('admin_notices', 'wpbadger_admin_notices');
add_action('openbadges_shortcode', 'wpbadger_shortcode');
register_activation_hook(__FILE__,'wpbadger_activate');
register_deactivation_hook(__FILE__,'wpbadger_deactivate');

add_action('wp_enqueue_scripts', 'wpbadger_enqueue_scripts');

require_once( dirname(__FILE__) . '/includes/badges.php' );
require_once( dirname(__FILE__) . '/includes/badges_stats.php' );
require_once( dirname(__FILE__) . '/includes/awards.php' );

global $wpbadger_db_version;
$wpbadger_db_version = "0.7.2";

function wpbadger_activate()
{
	// If the current theme does not support post thumbnails, exit install and flash warning
	if(!current_theme_supports('post-thumbnails')) {
		echo "Unable to install plugin, because current theme does not support post-thumbnails. You can fix this by adding the following line to your current theme's functions.php file: add_theme_support( 'post-thumbnails' );";
		exit;
	}

	global $wpbadger_db_version;

	add_option("wpbadger_db_version", $wpbadger_db_version);

	// Flush rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function wpbadger_deactivate()
{
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function wpbadger_admin_init()
{
    wp_register_style( 'wpbadger-admin-styles', plugins_url('css/admin-styles.css', __FILE__) );
    wp_register_script( 'wpbadger-admin-post', plugins_url('js/admin-post.js', __FILE__), array( 'post' ) );
}

function wpbadger_admin_head()
{
    global $pagenow, $wpbadger_badge_schema, $wpbadger_award_schema;

    if (get_post_type() != $wpbadger_badge_schema->get_post_type_name() &&
        get_post_type() != $wpbadger_award_schema->get_post_type_name())
        return;

    wp_enqueue_style( 'wpbadger-admin-styles' );

    if ($pagenow == 'post.php' || $pagenow == 'post-new.php')
        wp_enqueue_script( 'wpbadger-admin-post' );
}

function wpbadger_enqueue_scripts()
{
    wp_enqueue_style( 'wpbadger-styles', plugins_url('css/styles.css', __FILE__) );
}

function wpbadger_admin_menu()
{
    global $wpbadger_award_schema;

    $award_type = get_post_type_object('award');

	add_submenu_page('options-general.php','Configure WPBadger Plugin','WPBadger Config','manage_options','wpbadger_configure_plugin','wpbadger_configure_plugin');
    add_submenu_page(
        'edit.php?post_type=award',
        'WPBadger | Bulk Award Badges',
        'Bulk Award Badges',
        (get_option('wpbadger_bulk_awards_allow_all') ? $award_type->cap->edit_posts : 'manage_options'),
        'wpbadger_bulk_award_badges',
        array( $wpbadger_award_schema, 'bulk_award' )
    );
}

function wpbadger_admin_notices()
{
    global $wpbadger_db_version;

    if ((get_option( 'wpbadger_db_version' ) != $wpbadger_db_version) && ($_POST[ 'wpbadger_db_version' ] != $wpbadger_db_version))
    {
        ?>
        <div class="updated">
            <p>WPBadger has been updated! Please go to the <a href="<?php echo admin_url( 'options-general.php?page=wpbadger_configure_plugin' ) ?>">configuration page</a> and update the database.</p>
        </div>
        <?php
    }
}


function wpbadger_admin_header($tab)
{?>
<div class="wrap wpbadger-wrap">
<?php
}

// Checks two mandatory fields of configured. If options are empty or don't exist, return FALSE
function wpbadger_configured()
{
	if (get_option('wpbadger_config_origin') && get_option('wpbadger_config_name')) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function wpbadger_shortcode()
{
	// Query for badges with specific title
	// @todo: sort the badge version to make it first
	if ($badgename) {
		echo "bah";
		exit;
		$badge_query = new WP_Query(array('post_type' => 'badge', 'post_title' => $badge_name));

		while ( $badge_query->have_posts() ) : $badge_query->the_post();

			// Query for a user meta, check if email is user meta email
			$award_query = new WP_Query( array(
				'post_status' => 'publish',
				'post_type' => 'award',
				'meta_query' => array(
					array(
						'key' => 'wpbadger-award-email-address',
						'value' => $email,
						'compare' => '=',
						'type' => 'CHAR'
						),
					array(
						'key' => 'wpbadger-award-choose-badge',
						'value' => get_the_ID(),
						'compare' => '=',
						'type' => 'CHAR'
						)
					)
				)
			);

			// If award has been issued to specific email address, add to params
			if ($award_query) {
				array_push($options, $email);
			}
		endwhile;
	}
}

function wpbadger_configure_plugin()
{ 
    global $wpbadger_db_version;

	wpbadger_admin_header('Configure Plugin');
?>
<h2>WPBadger Configuration</h2>

<?php
if ($_POST['save']) {
    check_admin_referer( 'wpbadger_config' );

    if (!get_option( 'wpbadger_issuer_lock' ) || is_super_admin())
    {
        if ($_REQUEST['wpbadger_issuer_name']) {
            update_option('wpbadger_issuer_name', $_REQUEST['wpbadger_issuer_name']);
        }

        if ($_REQUEST['wpbadger_issuer_org']) {
            update_option('wpbadger_issuer_org', $_REQUEST['wpbadger_issuer_org']);
        }

        if (is_super_admin())
        {
            update_option('wpbadger_issuer_lock', (bool)$_REQUEST['wpbadger_issuer_lock']);
        }
    }

	if ($_REQUEST['wpbadger_issuer_contact']) {
		update_option('wpbadger_issuer_contact', $_REQUEST['wpbadger_issuer_contact']);
	}

    update_option('wpbadger_bulk_awards_allow_all', (bool)$_REQUEST['wpbadger_bulk_awards_allow_all']);

	if ($_REQUEST['wpbadger_config_award_email_text']) {
		update_option('wpbadger_config_award_email_text', $_REQUEST['wpbadger_config_award_email_text']);
	}

    echo "<div id='message' class='updated'><p>Options successfully updated</p></div>";
} elseif ($_POST['update_db']) {
    global $wpbadger_award_schema, $wpbadger_badge_schema;

    $query = new WP_Query( array( 'post_type' => $wpbadger_badge_schema->get_post_type_name(), 'nopaging' => true ) );
    while ($query->next_post())
    {
        # Migrate the post_content to the description metadata
        $desc = $wpbadger_badge_schema->get_post_description( $query->post->ID, $query->post );
        update_post_meta( $query->post->ID, 'wpbadger-badge-description', $desc );

        # Validate the post
        $wpbadger_badge_schema->save_post_validate( $query->post->ID, $query->post );
    }

    $query = new WP_Query( array( 'post_type' => $wpbadger_award_schema->get_post_type_name(), 'nopaging' => true ) );
    while ($query->next_post())
    {
        $wpbadger_award_schema->save_post_validate( $query->post->ID, $query->post );
        # We just have to assume here that if the award is published then
        # an email was sent
        if ($query->post->post_status == 'publish') 
            update_post_meta( $query->post->ID, 'wpbadger-award-email-sent', get_post_meta( $query->post->ID, 'wpbadger-award-email-address', true ) );
    }

    update_option( 'wpbadger_db_version', $wpbadger_db_version );

    echo "<div class='updated'><p>Database successfully updated</p></div>";
}

$issuer_disabled = (get_option('wpbadger_issuer_lock') && !is_super_admin()) ? 'disabled="disabled"' : '';
?>

<form method="POST" action="" name="wpbadger_config">
    <?php wp_nonce_field( 'wpbadger_config' ); ?>

    <table class="form-table">

        <tr valign="top">
        <th scope="row"><label for="wpbadger_issuer_name">Issuing Agent Name</label></th>
        <td><input type="text" id="wpbadger_issuer_name" name="wpbadger_issuer_name" class="regular-text" value="<?php echo esc_attr( get_option('wpbadger_issuer_name') ); ?>" <?php echo $issuer_disabled ?> /></td>
        </tr>

        <tr valign="top">
        <th scope="row"><label for="wpbadger_issuer_org">Issuing Organization</label></th>
        <td><input type="text" id="wpbadger_issuer_org" name="wpbadger_issuer_org" class="regular-text" value="<?php echo esc_attr( get_option('wpbadger_issuer_org') ); ?>" <?php echo $issuer_disabled ?> /></td>
        </tr>

        <?php
        if (is_super_admin()) {
            ?>

            <tr valign="top">
            <th scope="row"></th>
            <td><label><input type="checkbox" id="wpbadger_issuer_lock" name="wpbadger_issuer_lock" value="1" <?php echo get_option('wpbadger_issuer_lock') ? 'checked="checked"' : '' ?> /> Disable editting of issuer information for non-admins.</label></td>
            </tr>
            
            <?php
        }
        ?>

        <tr valign="top">
        <th scope="row"><label for="wpbadger_issuer_contact">Contact Email Address</label></th>
        <td><input type="text" id="wpbadger_issuer_contact" name="wpbadger_issuer_contact" class="regular-text" value="<?php echo esc_attr( get_option('wpbadger_issuer_contact') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row"></th>
        <td><label><input type="checkbox" name="wpbadger_bulk_awards_allow_all" id="wpbadger_bulk_awards_allow_all" value="1" <?php echo get_option('wpbadger_bulk_awards_allow_all') ? 'checked="checked"' : '' ?> /> Allow all users to bulk award badges.</label></td>
        </tr>

		<tr valign="top">
		<th scope="row"><label for="wpbadger_config_award_email_text">Badge Award Email Text</label></th>
		<td><textarea name="wpbadger_config_award_email_text" id="wpbadger_config_award_email_text" class="large-text" rows="5" cols="45"><?php echo esc_attr( get_option('wpbadger_config_award_email_text') ); ?></textarea></td>
		</tr>
    </table>

    <p class="submit">
    <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
    </p>

</form>

<form method="POST" action="" name="wpbadger_db_update">
<input type="hidden" name="wpbadger_db_version" value="<?php esc_attr_e( $wpbadger_db_version ) ?>" />
<input type="submit" name="update_db" value="<?php _e('Update Database') ?>" />
</form>
</div>

<?php
}

function wpbadger_disable_quickedit( $actions, $post ) {
    if( $post->post_type == 'badge' || 'award' ) {
        unset( $actions['inline hide-if-no-js'] );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'wpbadger_disable_quickedit', 10, 2 );

