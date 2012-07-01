<?php global $post;
header('Content-Type: application/json'); 
$email = get_post_meta($post->ID, "wpbadger-award-email-address", true);
$chosen_badge = get_post_meta($post->ID, "wpbadger-award-choose-badge", true);
echo $chosen_badge; ?>
{
  "recipient": "sha256$<?php echo hash("sha256", $email); ?>",
  "salt": "<?php echo wp_salt('auth'); ?>",
  "evidence": "/badges/html5-basic/bimmy",
  "expires": "2013-06-01",
  "issued_on": "2011-06-01",
  "badge": {
    "version": "0.5.0",
    "name": "HTML5 Fundamental",
    "image": "/img/html5-basic.png",
    "description": "Knows the difference between a <section> and an <article>",
    "criteria": "/badges/html5-basic",
    "issuer": {
      "origin": "<?php echo get_bloginfo('siteurl')?>",
      "name": "<?php echo get_option('wpbadger_issuer_name'); ?>",
      "org": "<?php echo get_option('wpbadger_issuer_org'); ?>",
      "contact": "<?php echo get_option('wpbadger_issuer_contact'); ?>"
    }
  }
}