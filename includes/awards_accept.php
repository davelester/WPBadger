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