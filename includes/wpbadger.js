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
	
	// Function that rejects the badge
	$('.rejectBadge').click(function() {
		$.ajax({
			url: '<?php echo get_permalink(); ?>reject/',
			type: 'POST',
			success: function(data, textStatus) {
				window.location.href = '<?php echo get_permalink(); ?>';
			}
		});
	});
});
