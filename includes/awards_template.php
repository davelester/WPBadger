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
					if (errors.length > 0 ) {
						$('#errMsg').text('Error Message: '+ errors.toSource());
						$('#badge-error').show();	
						var data = 'ERROR, <?php echo $badges_array[$badgeId]['name']; ?>, <?php echo $recipient_name; ?>, ' +  errors.toSource();
						$.ajax({
    					url: 'record-issued-badges.php',
    					type: 'POST',
    					data: { data: data }
						});
					}
					
					if (successes.length > 0) {
							$('.backPackLink').hide();
							$('.login-info').hide();
							$('#badgeSuccess').show();
							var data = 'SUCCESS, <?php echo $badges_array[$badgeId]['name']; ?>, <?php echo $recipient_name; ?>';
							$.ajax({
    						url: 'record-issued-badges.php',
    						type: 'POST',
    						data: { data: data }
							});
						}	
					});    
				});
	});
</script>

<h1>Congratulations! The <?php echo get_the_title(get_post_meta($post->ID, 'wpbadger-award-choose-badge', true)); ?> badge has been awarded</h1>

<p>Please choose to <a href="#" class="backPackLink">accept badge</a> or <a href="#">decline badge</a></p>

	</div><!-- #content -->
</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
