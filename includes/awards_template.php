<?php 
global $post;

wp_enqueue_script('openbadges', 'http://beta.openbadges.org/issuer.js', array(), null);
wp_enqueue_script('jquery_ajax', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
get_header(); 

// Pass query parameters differently based upon site permalink structure
if (get_option('permalink_structure') == '') {
	$query_separator = '&';
} else {
	$query_separator = '?';
}

?>

<div id="container">
	<div id="content" role="main">

<script>
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
		var assertionUrl = "<?php echo get_permalink() . $query_separator; ?>json=1";
		OpenBadges.issue([''+assertionUrl+''], function(errors, successes) {					
			if (successes.length > 0) {
					$('.backPackLink').hide();
					$('.login-info').hide();
					$('#badgeSuccess').show();
					$.ajax({
  						url: '<?php echo get_permalink() . $query_separator; ?>accept=1',
  						type: 'POST',
						success: function(data, textStatus) {
							window.location.href = '<?php echo get_permalink(); ?>';
						}
					});
				}
			});
		});
	
	// Function that rejects the badge
	$('.rejectBadge').click(function() {
		$.ajax({
			url: '<?php echo get_permalink() . $query_separator; ?>reject=1',
			type: 'POST',
			success: function(data, textStatus) {
				window.location.href = '<?php echo get_permalink(); ?>';
			}
		});
	});
});
</script>

<?php
$award_status = get_post_meta($post->ID, 'wpbadger-award-status', true);
if ($accept) { ?>
	<p>Your award has been successfully accepted and added to your backpack.</p>
<?php } else {
if ($award_status == 'Awarded') { ?>
<h1>Congratulations! The <?php echo get_the_title(get_post_meta($post->ID, 'wpbadger-award-choose-badge', true)); ?> badge has been awarded</h1>

<p>Please choose to <a href="#" class="backPackLink">accept badge</a> or <a href="#" class="rejectBadge">decline badge</a></p>

<?php } elseif ($award_status == 'Accepted') {?>
	<p>This award has already been claimed.</p><p>If you believe this was done in error, please contact the site administrator, <a href="mailto:<?php echo get_settings('admin_email');?>"><?php echo get_settings('admin_email');?></p>
<?php } elseif ($award_status == 'Rejected') { ?>
	<p>You have declined this badge.</p>
<?php } 

} ?>

	</div>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>