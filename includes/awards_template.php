<?php 
global $post;

wp_enqueue_script('openbadges', 'http://beta.openbadges.org/issuer.js', array(), null);
wp_enqueue_script('jquery_ajax', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
wp_enqueue_script('wpbadger', plugins_url() . '/WPBadger/includes/wpbadger.js');
get_header(); ?>

<div id="container">
	<div id="content" role="main">

<?php
$award_status = get_post_meta($post->ID, 'wpbadger-award-status', true);
if ($award_status == 'Awarded') { ?>
<h1>Congratulations! The <?php echo get_the_title(get_post_meta($post->ID, 'wpbadger-award-choose-badge', true)); ?> badge has been awarded</h1>

<p>Please choose to <a href="#" class="backPackLink">accept badge</a> or <a href="#" class="rejectBadge">decline badge</a></p>

<?php } elseif ($award_status == 'Accepted') {?>
	<p>Your award has been successfully accepted and added to your backpack.</p>
<?php } elseif ($award_status == 'Rejected') { ?>
	<p>You have declined this badge.</p>
<?php } ?>

	</div>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>