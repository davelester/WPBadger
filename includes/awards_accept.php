<?php
global $post;
// Only update the post meta if the previous status was 'Awarded'
if (get_post_meta($post->ID, 'wpbadger-award-status', true) == 'Awarded') {
	update_post_meta($post->ID, 'wpbadger-award-status', 'Accepted');
}

// Include administrative plugin helpers
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// If WP Super Cache Plugin installed, delete cache files for award post
if (is_plugin_active('wp-super-cache/wp-cache.php')) {
   wp_cache_post_change($post->ID);
}

global $post;

wp_enqueue_script('openbadges', 'http://beta.openbadges.org/issuer.js', array(), null);
wp_enqueue_script('jquery_ajax', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
get_header(); 
?>

<div id="container">
	<div id="content" role="main">

<?php
if ($accept) { ?>
	<p>Your award has been successfully accepted and added to your backpack.</p>
<?php } ?>

	</div>
</div>

<?php get_sidebar();
get_footer(); 

// Prevent template filter from running
exit; ?>