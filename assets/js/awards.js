jQuery(document).ready(function($) {
	// Some js originally based on Badge it Gadget Lite https://github.com/Codery/badge-it-gadget-lite/blob/master/digital-badges/get-my-badge.php

	$('.js-required').hide();

	if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){  //The Issuer API isn't supported on MSIE browsers
		$('.backPackLink').hide();
		$('.login-info').hide();
		$('.browserSupport').show();
	}

	// Function that issues the badge
	$('.backPackLink').click(function() {
		var assertionUrl = '" . get_permalink() . $query_separator . "json=1';
		OpenBadges.issue([''+assertionUrl+''], function(errors, successes) {
			if (successes.length > 0) {
				$('.backPackLink').hide();
				$('.login-info').hide();
				$('#badgeSuccess').show();
				$.ajax({
					url: WPBadger_Awards.accept_url,
					type: 'POST',
					success: function(data, textStatus) {
						window.location.href = WPBadger_Awards.redirect_url;
					}
				});
			}
		});
	});

	// Function that rejects the badge
	// @todo Why do we do this with AJAX?
	$('.rejectBadge').click(function() {
		$.ajax({
			url: WPBadger_Awards.reject_url,
			type: 'POST',
			success: function(data, textStatus) {
				window.location.href = WPBadger_Awards.redirect_url;
			}
		});
	});
},(jQuery));
