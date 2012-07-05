<?php
/**
 * @package WPBadger
 */
/*
Plugin Name: WPBadger
Plugin URI: https://github.com/davelester/WPBadger
Description: A lightweight badge issuing platform built using WordPress.
Version: 0.0.5
Author: Dave Lester
Author URI: http://www.davelester.org
*/

add_action('admin_menu', 'wpbadger_admin_menu');
register_activation_hook(__FILE__,'wpbadger_install');

require_once( dirname(__FILE__) . '/includes/badges.php' );
require_once( dirname(__FILE__) . '/includes/awards.php' );

global $wpbadger_db_version;
$wpbadger_db_version = "0.0.5";

function wpbadger_install()
{
	global $wpbadger_db_version;

	add_option("wpbadger_db_version", $wpbadger_db_version);
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

			$badge = $_REQUEST['wpbadger_award_choose_badge'];
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
					'post_type' => 'award'
				);

				$post_id = wp_insert_post( $post, $wp_error );

				update_post_meta($post_id, 'wpbadger-award-email-address', $email);
				update_post_meta($post_id, 'wpbadger-award-choose-badge', $badge);
				update_post_meta($post_id, 'wpbadger-award-expires', $expires);
			}
			
			echo "<div id='message' class='updated'><p>Badges were awarded successfully. You can view a list of <a href='" . get_bloginfo('url') . "/wp-admin/edit.php?post_type=award'>all awards</a>.</p></div>";
		} else {
			echo "<div id='message' class='updated'><p>Badge award was unsuccessful. It is necessary to specify a badge and email address.</p></div>";
		}
	}
?>

	<form method="POST" action="" name="wpbadger_config">

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
					echo "<option name='wpbadger_award_choose_badge'". $selected . ">";
					echo $title_version . "</option>";
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
		<td><textarea name="wpbadger_config_award_email_text" id="wpbadger_config_award_email_text" rows="4" cols="30"></textarea></td>
		</tr>
    </table>

    <p class="submit">
    <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>

<?php
}