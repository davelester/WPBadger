jQuery(function ($) {
  var $descriptiondiv = $('#wpbadger-badge-descriptiondiv');
  if ($descriptiondiv.length > 0) {
    $descriptiondiv.insertBefore('#postdivrich');
    wptitlehint('wpbadger-badge-description');
  }
});

