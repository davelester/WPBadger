<?php header('Content-Type: application/json'); ?>
{
  "recipient": "sha256$2ad891a61112bb953171416acc9cfe2484d59a45a3ed574a1ca93b47d07629fe",
  "salt": "hashbrowns",
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