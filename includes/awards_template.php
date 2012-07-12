<?php 
global $post;

wp_enqueue_script('openbadges', 'http://beta.openbadges.org/issuer.js', array(), null);
wp_enqueue_script('jquery_ajax', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
get_header(); ?>

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
	
	//Function that issues the badge
	$('.backPackLink').click(function() {
		var assertionUrl = "<?php echo get_permalink(); ?>json/";
		OpenBadges.issue([''+assertionUrl+''], function(errors, successes) {					
			if (successes.length > 0) {
					$('.backPackLink').hide();
					$('.login-info').hide();
					$('#badgeSuccess').show();
					$.ajax({
  						url: '<?php echo get_permalink(); ?>accept/',
  						type: 'POST',
						success: function(data, textStatus) {
							window.location.href = '<?php echo get_permalink(); ?>';
						}
					});
				}
			});
		});
	});
</script>

<?php
$award_status = get_post_meta($post->ID, 'wpbadger-award-status', true);
if ($award_status == 'Awarded') { ?>
<h1>Congratulations! The <?php echo get_the_title(get_post_meta($post->ID, 'wpbadger-award-choose-badge', true)); ?> badge has been awarded</h1>

<p>Please choose to <a href="#" class="backPackLink">accept badge</a> or <a href="#">decline badge</a></p>

<?php } elseif ($award_status == 'Accepted') {?>
	<p>This award has already been accepted.</p>
<?php } elseif ($award_status == 'Declined') { ?>
	<p>This award has been declined by the user.</p>
<?php } ?>

	</div><!-- #content -->
</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
