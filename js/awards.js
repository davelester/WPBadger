jQuery(function ($) {
  // Some js originally based on Badge it Gadget Lite https://github.com/Codery/badge-it-gadget-lite/blob/master/digital-badges/get-my-badge.php

  $('.js-required').hide();

  if (/MSIE (\d+\.\d+);/.test( navigator.userAgent )){  //The Issuer API isn't supported on MSIE browsers
    $('#wpbadger-award-actions').hide();
    $('#wpbadger-award-browser-support').show();
  }

  // Function that issues the badge
  $('.acceptBadge').click( function (event) {
    var assertionUrl = WPBadger_Awards.assertion_url;

    event.preventDefault();
    OpenBadges.issue( [''+assertionUrl+''], function (errors, successes) {					
      if (errors.length > 0) {
        var $errorsdiv = $('#wpbadger-award-actions-errors');
        $.each( errors, function (idx, val) {
          $('<p></p>').text( "Reason: " + val.reason ).appendTo( $errorsdiv );
        } );
      }

      if (successes.length > 0) {
        $('#wpbadger-award-actions').hide();
        $.post(
          WPBadger_Awards.ajaxurl,
          {
            action: 'wpbadger_award_ajax',
            award_id: WPBadger_Awards.award_id,
            award_action: 'accept'
          },
          function (data, textStatus) {
            $('#wpbadger-award-actions-wrap').html( data );
          }
        );
      }
    });
  });

  // Function that rejects the badge
  $('.rejectBadge').click( function (event) {
    event.preventDefault();
    $.post(
      WPBadger_Awards.ajaxurl,
      {
        action: 'wpbadger_award_ajax',
        award_id: WPBadger_Awards.award_id,
        award_action: 'reject'
      },
      function (data, textStatus) {
        $('#wpbadger-award-actions-wrap').html( data );
      }
    );
  });
});