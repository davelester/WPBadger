<?php
global $post;
// Only update the post meta if the previous status was 'Awarded'
if (get_post_meta($post->ID, 'wpbadger-award-status', true) == 'Awarded') {
	update_post_meta($post->ID, 'wpbadger-award-status', 'Rejected');
}