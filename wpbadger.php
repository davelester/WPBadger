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

global $wpbadger_db_version;
$wpbadger_db_version = "0.0.5";

function wpbadger_install()
{
	global $wpdb;
	global $wpbadger_db_version;

	$badges_table_name = $wpdb->prefix . "wpbadger_badges";
	$awarded_badges_table_name = $wpdb->prefix . "wpbadger_awarded_badges";

	$badgesSql = "CREATE TABLE $badges_table_name (
	badge_id mediumint(9) NOT NULL AUTO_INCREMENT,
	version varchar(20) NOT NULL,
	name varchar(128) NOT NULL,
	image_path varchar(255) NOT NULL,
	description varchar(128) NOT NULL,
	criteria varchar(55) NOT NULL,
	UNIQUE KEY id (badge_id)
	);";
 
	$awardedBadgesSql = "CREATE TABLE $awarded_badges_table_name (
	award_id mediumint(9) NOT NULL AUTO_INCREMENT,
	email_address varchar(100) NOT NULL,
	recipient text NOT NULL,
	badge_id int(11) NOT NULL,
	issued_on datetime,
	expires datetime,
	evidence varchar(100),
	UNIQUE KEY id (award_id)
	);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($badgesSql);
  	dbDelta($awardedBadgesSql);

	add_option("wpbadger_db_version", $wpbadger_db_version);
}

function wpbadger_admin_menu()
{
	add_menu_page('WPBadger','WPBadger','level_8','wpbadger','wpbadger_manage','',98);
	if (wpbadger_configured()) {
		add_submenu_page('wpbadger','WPBadger | Badges','Manage Badges','manage_options','wpbadger_badges','wpbadger_manage_badges');
		add_submenu_page('wpbadger_badges', 'WPBadger | Add a Badge','Add Badge','manage_options','wpbadger_add_badge','wpbadger_add_badge');
		add_submenu_page('wpbadger','WPBadger | Awarded Badges','Manage Awarded Badges','manage_options','wpbadger_manage_awards','wpbadger_manage_awards');
		add_submenu_page('wpbadger_awards', 'WPBadger | Award a Badge','Award A Badge','manage_options','wpbadger_award_badge','wpbadger_award_badge');
		add_submenu_page('wpbadger','WPBadger | Configure Plugin','Configure Plugin','manage_options','wpbadger_configure_plugin','wpbadger_configure_plugin');
	}
}

function wpbadger_award_badge()
{
	global $wpdb;

	if ($_POST['save']) {
		if ($_REQUEST['wpbadger_badge_id'] && $_REQUEST['wpbadger_award_email_address']) {

			$badge_id = $_REQUEST['wpbadger_badge_id'];
			$email_address = $_REQUEST['wpbadger_award_email_address'];
			$evidence = $_REQUEST['wpbadger_award_evidence'];
			$expires = $_REQUEST['wpbadger_award_expires'];

			// Start off by accepting one email address at a time..
			// eventually expand this to include batch listings of emails, split and verify correct email address formatting

			// Generate the recipient using the WordPress salt. foo is a placeholder
			$recipient = 'foo';
			
			// Issued on should be retrieving using unix timestamp (or similar method)
			$issued_on = '';

			$awarded_badges_table_name = $wpdb->prefix . "wpbadger_awarded_badges";

			$wpdb->insert( $awarded_badges_table_name, array( 'badge_id' => $badge_id, 'email_address' => $email_address, 'recipient' => $recipient, 'issued_on' => $issued_on, 'expires' => $expires, 'evidence' => $evidence ) );
			echo "success!!!";
		} else {
			echo "Badge award was unsuccessful. It is necessary to specify a badge and email address.";
		}
	}

	wpbadger_admin_header('Manage Awarded Badges');
?>

	<h2>Award a Badge</h2>

	<form method="POST" action="" name="wpbadger_config">

	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">Choose Badge</th>
	        <td>
				<select name="wpbadger_badge_id" id="wpbadger_badge_id">
				<?php
					$badges_table_name = $wpdb->prefix . "wpbadger_badges";
					$badges = $wpdb->get_results("SELECT * FROM $badges_table_name");
				
					foreach ($badges as $badge) {
						echo "<option id='wpbadger_badge_id' value='$badge->badge_id'>$badge->name (Version $badge->version)</option>";
					}
				?>
				</select>        	
	        </td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Email Address</th>
	        <td><input type="text" name="wpbadger_award_email_address" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Evidence</th>
	        <td><textarea name="wpbadger_award_evidence" id="wpbadger_award_evidence" rows="4" cols="30"></textarea></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Expiration Date</th>
	        <td><input type="text" name="wpbadger_award_expires" /></td>
	        </tr>
	    </table>

	    <p class="submit">
	    <input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes') ?>" />
	    </p>

	</form>
	</div>

<?php
}

function wpbadger_add_badge()
{
	global $wpdb;

	if ($_POST['save']) {
		if ($_REQUEST['wpbadger_badge_name'] && $_REQUEST['wpbadger_badge_image_path'] && $_REQUEST['wpbadger_badge_version'] && $_REQUEST['wpbadger_badge_description'] && $_REQUEST['wpbadger_badge_criteria']) {

			$name = $_REQUEST['wpbadger_badge_name'];
			$version = $_REQUEST['wpbadger_badge_version'];
			$image_path = $_REQUEST['wpbadger_badge_image_path'];
			$description = $_REQUEST['wpbadger_badge_description'];
			$criteria = $_REQUEST['wpbadger_badge_criteria'];

			$badges_table_name = $wpdb->prefix . "wpbadger_badges";

			$wpdb->insert( $badges_table_name, array( 'name' => $name, 'version' => $version, 'image_path' => $image_path, 'description' => $description, 'criteria' => $criteria ) );
			echo "success!!!";
		} else {
			echo "All fields must be filled out in order to add a new badge.";
		}
		
		/* 	@todo: after badge is awarded, need to retrieve issue_id from mysql (or maybe it's set ahead of time using rand())
		   	and using that issue_id and the email address, the user is notified that a badge has been awarded */
		
	}
	wpbadger_admin_header('Manage Badges');
	?>
	
	<h2>Add a New Badge</h2>

	<form method="POST" action="" name="wpbadger_config">

	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">Badge Name</th>
	        <td><input type="text" name="wpbadger_badge_name" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Image Path</th>
	        <td><input type="text" name="wpbadger_badge_image_path" /></td>
	        </tr>
	
			<tr valign="top">
	        <th scope="row">Version</th>
	        <td><input type="text" name="wpbadger_badge_version" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Description</th>
	        <td><textarea name="wpbadger_badge_description" id="wpbadger_badge_description" rows="4" cols="30"></textarea></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Criteria</th>
	        <td><input type="text" name="wpbadger_badge_criteria" /></td>
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
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2 class="nav-tab-wrapper">
		<?php
		$pages = array(	array('wpbadger','WPBadger') );

		if ( wpbadger_configured() ) {
			$pages = array_merge( $pages, array(
				array('wpbadger_badges','Manage Badges'),
				array('wpbadger_manage_awards','Manage Awarded Badges'),
				array('wpbadger_configure_plugin','Configure Plugin')
			) );
		}

		foreach ($pages as $page) {
			// Mark the selected tab as active
			if ($tab == $page[1]) { 
				$activeClass = " nav-tab-active"; 
			} else {
				$activeClass = "";
			}
			echo "<a href='admin.php?page=$page[0]' class='nav-tab".$activeClass."'>$page[1]</a>";
		}
		?>
	</h2>
<?php
}

function wpbadger_manage()
{
	wpbadger_admin_header('WPBadger');
?>

	<h2>Dashboard</h2>
	
	<p>The utility of a dashboard may depend on the number of badges being issued from a WordPress installation, and how frequently badges are being given out.</p>
	
	<p>Have a suggestion for what could be included here? Go ahead and add it to the <a href="https://github.com/davelester/wpbadger/wiki">project's Github wiki</a>.</p>
<?php
}

function wpbadger_manage_badges()
{
	wpbadger_admin_header('Manage Badges');
	?>
	
	<div id="wpbadger-manage-badges">
		<?php
			global $wpdb;	
		
			$badges_table_name = $wpdb->prefix . "wpbadger_badges";
			$num_of_badges = $wpdb->get_var("SELECT COUNT(*) FROM $badges_table_name");
		?>
		<br />
		<p>There are <strong><?php echo $num_of_badges; ?></strong> badges that have been added. <a href="admin.php?page=wpbadger_add_badge">Add a new badge</a> to WPBadger!</p>
		<table class="wp-list-table widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope='col' id='title' class='manage-column column-title'  style="">Badge Name</th>
				<th scope='col' id='image-path' class='manage-column column-tags' style="">Image</th>
				<th scope='col' id='version' class='manage-column column-author'  style="">Version</th>
				<th scope='col' id='description' class='manage-column column-categories' style="">Description</th>
				<th scope='col' id='date' class='manage-column column-date'  style="">Criteria</th>
			</tr>
			</thead>

			<tbody id="the-list">
			<?php
			$badges_results = $wpdb->get_results("SELECT * FROM $badges_table_name");

			if (!$badges_results) {
				echo "look man, you've got no badges. sorry.";
			} else {
				foreach ($badges_results as $result)
				{?>

			<tr id="post-1" class="post-1 post type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" valign="top">
				<td class="name page-title column-title"><strong><a href=""><?php echo $result->name; ?></a></strong></td>			
				<td class="image-path column-tags"><?php echo $result->image_path; ?></td>
				<td class="version column-author"><?php echo $result->version; ?></td>
				<td class="description column-categories"><?php echo $result->description; ?></td>
				<td class="criteria column-date"><?php echo $result->criteria; ?></td>
			</tr>
<?php
				}
			}
?>
			</tbody>

			<tfoot>
			<tr>
				<th scope='col' class='manage-column column-title' style="">Badge Name</th>
				<th scope='col' class='manage-column column-tags' style="">Image</th>
				<th scope='col' class='manage-column column-author sortable desc' style="">Version</th>
				<th scope='col' class='manage-column column-categories' style="">Description</th>
				<th scope='col' class='manage-column column-date' style="">Criteria</th>
			</tr>
			</tfoot>
		</table>
	</div>
</div>
<?php
}

function wpbadger_accept_award_page()
{
	echo "You have been awarded the _____ badge! Choose to accept this badge and add it to your badge backpack, or decline.";
	// On this public page, create div to echo the badge criteria, as well as the PNG of the image
}

function wpbadger_json_assertion() {
	header('Content-Type: application/json');
	// Call database to build a JSON file given the award information of a specified ID. include error JSON if it fails.
}

function wpbadger_manage_awards()
{
	wpbadger_admin_header('Manage Awarded Badges');
	?>
		<div id="wpbadger-manage-badges">
			<?php
				global $wpdb;
				$awarded_badges_table_name = $wpdb->prefix . "wpbadger_awarded_badges";
				$num_of_awarded_badges = $wpdb->get_var("SELECT COUNT(*) FROM $awarded_badges_table_name");
			?>
			<br />
			<p><strong><?php echo $num_of_awarded_badges; ?></strong> badges have been awarded. <a href="admin.php?page=wpbadger_award_badge">Award new badges</a></p>
			
			<table class="wp-list-table widefat" cellspacing="0">
				<thead>
				<tr>
					<th scope='col' id='issued-on' class='manage-column column-title'  style="">Date Issued</th>
					<th scope='col' id='badge' class='manage-column column-tags' style="">Badge</th>
					<th scope='col' id='email-address' class='manage-column column-author'  style="">Email Address</th>
				</tr>
				</thead>

				<tbody id="the-list">
				<?php
				$awarded_badges_results = $wpdb->get_results("SELECT * FROM $awarded_badges_table_name");

				if (!$awarded_badges_results) {
					echo "look man, you've got no badges. sorry.";
				} else {
					foreach ($awarded_badges_results as $result)
					{
				?>

				<tr id="post-1" class="post-1 post type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" valign="top">
					<td class="issued-on"><a href=""><?php echo $result->issued_on; ?></a></td>			
					<td class="badge"><?php echo $result->badge_id; ?></td>
					<td class="email-address"><?php echo $result->email_address; ?></td>
				</tr>
<?php
					}
				}
?>
			</tbody>

			<tfoot>
			<tr>
				<th scope='col' class='manage-column column-issued-on' style="">Issued On</th>
				<th scope='col' class='manage-column column-badge' style="">Badge</th>
				<th scope='col' class='manage-column column-email-address' style="">Email Address</th>
			</tfoot>
		</table>
	</div>
</div>
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
if ($_POST['save']) {
	if ($_REQUEST['wpbadger_config_origin']) {
		update_option('wpbadger_config_origin', $_REQUEST['wpbadger_config_origin']);
		$success = TRUE;
	}
	if ($_REQUEST['wpbadger_config_name']) {
		update_option('wpbadger_config_name', $_REQUEST['wpbadger_config_name']);
		$success = TRUE;
	}
	
	if ($success) {
		echo "Options successfully updated";
	}
}

wpbadger_admin_header('Configure Plugin');
?>

<h2>Configuration</h2>

<form method="POST" action="" name="wpbadger_config">

    <table class="form-table">
        <tr valign="top">
        <th scope="row">Origin</th>
        <td><input type="text" name="wpbadger_config_origin" value="<?php echo get_option('wpbadger_config_origin'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Issuing Agent Name</th>
        <td><input type="text" name="wpbadger_config_name" value="<?php echo get_option('wpbadger_config_name'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Issuing Organization</th>
        <td><input type="text" name="wpbadger_config_org" value="<?php echo get_option('wpbadger_config_org'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Contact Email Address</th>
        <td><input type="text" name="wpbadger_config_contact" value="<?php echo get_option('wpbadger_config_contact'); ?>" /></td>
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