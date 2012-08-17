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

add_action('admin_menu', 'wpbadger_admin_menu');
add_action('openbadges_shortcode', 'wpbadger_shortcode');
register_activation_hook(__FILE__,'wpbadger_activate');
register_deactivation_hook(__FILE__,'wpbadger_deactivate');

require_once( dirname(__FILE__) . '/includes/badges.php' );
require_once( dirname(__FILE__) . '/includes/awards.php' );

global $wpbadger_db_version;
$wpbadger_db_version = "0.6.2";

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

function wpbadger_admin_menu()
{
	add_submenu_page('options-general.php','Configure WPBadger Plugin','WPBadger Config','manage_options','wpbadger_configure_plugin','wpbadger_configure_plugin');
	add_submenu_page('edit.php?post_type=award','WPBadger | Bulk Award Badges','Bulk Award Badges','manage_options','wpbadger_bulk_award_badges','wpbadger_bulk_award_badges');
}

function wpbadger_bulk_award_badges()
{
wpbadger_admin_header('Manage Awarded Badges');
?>

<h2>Award Badges in Bulk</h2>

<?php	
	global $wpdb;

	if ($_POST['save']) {
		if ($_REQUEST['wpbadger_award_choose_badge'] && $_REQUEST['wpbadger_award_email_address']) {

			$badge_id = $_REQUEST['wpbadger_award_choose_badge'];
			$email_addresses = $_REQUEST['wpbadger_award_email_address'];
			$evidence = $_REQUEST['wpbadger_award_evidence'];
			$expires = $_REQUEST['wpbadger_award_expires'];

			$email_addresses = split(',', $email_addresses);
	
			foreach ($email_addresses as $email) {
				$email = trim($email);

				// Insert a new post for each award
				$post = array(
					'post_content' => $evidence,
					'post_status' => 'publish',
					'post_type' => 'award',
					'post_title' => 'Badge Awarded: ' . get_the_title($badge_id),
					'post_name' => wpbadger_award_generate_slug()
				);

				$post_id = wp_insert_post( $post, $wp_error );

				update_post_meta($post_id, 'wpbadger-award-email-address', $email);
				update_post_meta($post_id, 'wpbadger-award-choose-badge', $badge_id);
				update_post_meta($post_id, 'wpbadger-award-expires', $expires);
				update_post_meta($post_id, 'wpbadger-award-status','Awarded');
				
				// Send award email
				wpbadger_award_send_email($post_id);
			}
			
			echo "<div id='message' class='updated'><p>Badges were awarded successfully. You can view a list of <a href='" . get_bloginfo('url') . "/wp-admin/edit.php?post_type=award'>all awards</a>.</p></div>";
		} else {
			echo "<div id='message' class='updated'><p>Badge award was unsuccessful. It is necessary to specify a badge and email address.</p></div>";
		}
	}
?>

	<form method="POST" action="" name="wpbadger_bulk_award_badges">

	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">Choose Badge</th>
	        <td>
				<?php $choose_badge_meta = get_post_meta( $object->ID, 'wpbadger_award_choose_badge', true );?>

				<p>
				<select name="wpbadger_award_choose_badge" id="wpbadger_award_choose_badge">

				<?php 	
				$query = new WP_Query( array( 'post_type' => 'badge' ) );

				while ( $query->have_posts() ) : $query->the_post();
					$title_version = the_title(null, null, false) . " (" . get_post_meta(get_the_ID(), 'wpbadger-badge-version', true) . ")";

					if ($choose_badge_meta == $title_version) { 
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo "<option name='wpbadger-award-choose-badge' value='" . get_the_ID() . "'". $selected . ">";					echo $title_version . "</option>";
				endwhile;
				?>

				</select>
	        </td>
	        </tr>
			
	        <tr valign="top">
			
	        <th scope="row">Email Address (separated by commas)</th>
	        <td><textarea name="wpbadger_award_email_address" id="wpbadger_award_email_address" rows="4" cols="30"></textarea></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Evidence</th>
	        <td><textarea name="wpbadger_award_evidence" id="wpbadger_award_evidence" rows="4" cols="30"></textarea></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Expiration Date (optional field, YY-MM-DD format)</th>
	        <td><input type="text" name="wpbadger_award_expires" id="title" /></td>
	        </tr>
	    </table>

	    <p class="submit">
	    <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
	    </p>

	</form>
	</div>

<?php
}

function wpbadger_admin_header($tab)
{?>
<div class="wrap">
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
	wpbadger_admin_header('Configure Plugin');
?>
<h2>WPBadger Configuration</h2>

<?php
if ($_POST['save']) {

	if ($_REQUEST['wpbadger_issuer_name']) {
		update_option('wpbadger_issuer_name', $_REQUEST['wpbadger_issuer_name']);
		$success = TRUE;
	}

	if ($_REQUEST['wpbadger_issuer_org']) {
		update_option('wpbadger_issuer_org', $_REQUEST['wpbadger_issuer_org']);
		$success = TRUE;
	}
	
	if ($_REQUEST['wpbadger_issuer_contact']) {
		update_option('wpbadger_issuer_contact', $_REQUEST['wpbadger_issuer_contact']);
		$success = TRUE;
	}
	
	if ($_REQUEST['wpbadger_config_award_email_text']) {
		update_option('wpbadger_config_award_email_text', $_REQUEST['wpbadger_config_award_email_text']);
		$success = TRUE;
	}

	if ($success) {
		echo "<div id='message' class='updated'><p>Options successfully updated</p></div>";
	}
}
?>

<form method="POST" action="" name="wpbadger_config">

    <table class="form-table">

        <tr valign="top">
        <th scope="row">Issuing Agent Name</th>
        <td><input type="text" name="wpbadger_issuer_name" value="<?php echo get_option('wpbadger_issuer_name'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Issuing Organization</th>
        <td><input type="text" name="wpbadger_issuer_org" value="<?php echo get_option('wpbadger_issuer_org'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Contact Email Address</th>
        <td><input type="text" name="wpbadger_issuer_contact" value="<?php echo get_option('wpbadger_issuer_contact'); ?>" /></td>
        </tr>

		<tr valign="top">
		<th scope="row">Badge Award Email Text</th>
		<td><textarea name="wpbadger_config_award_email_text" id="wpbadger_config_award_email_text" rows="4" cols="30"><?php echo get_option('wpbadger_config_award_email_text'); ?></textarea></td>
		</tr>
    </table>

    <p class="submit">
    <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
    </p>

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