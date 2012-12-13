<?php global $post;
header('Content-Type: application/json'); 

# Award data
$email = get_post_meta( $post->ID, 'wpbadger-award-email-address', true );
$salt = get_post_meta( $post->ID, 'wpbadger-award-salt', true );
$issued_on = get_the_date('Y-m-d');
$evidence = get_permalink();

# Badge data
$badge_id = get_post_meta( $post->ID, 'wpbadger-award-choose-badge', true );
$badge_title = get_the_title( $badge_id );
$badge_version = get_post_meta( $badge_id, 'wpbadger-badge-version', true );
$badge_desc = $wpbadger_badge_schema->get_post_description( $badge_id );
$badge_image_id = get_post_thumbnail_id( $badge_id );
$badge_image_url = wp_get_attachment_url( $badge_image_id );
$badge_url = get_permalink( $badge_id );

# Issuer data
$issuer_origin_parts = parse_url( get_site_url() );
$issuer_origin_url = 'http://' . $issuer_origin_parts[ 'host' ];
$issuer_name = get_option( 'wpbadger_issuer_name' );
$issuer_org = get_option( 'wpbadger_issuer_org' );
$issuer_contact = get_option( 'wpbadger_issuer_contact' );
if (empty( $issuer_contact ))
    $issuer_contact = get_bloginfo( 'admin_email' );

?>
{
  "recipient": "sha256$<?php echo hash( "sha256", ($email . $salt) ) ?>",
  "salt": "<?php echo esc_js( $salt ) ?>",
  "evidence": "<?php echo esc_js( $evidence ) ?>",
  "issued_on": "<?php echo esc_js( $issued_on ) ?>",
  "badge": {
    "version": "<?php echo esc_js( $badge_version ) ?>",
    "name": "<?php echo esc_js( $badge_title ) ?>",
    "image": "<?php echo esc_js( $badge_image_url ) ?>",
    "description": "<?php echo esc_js( $badge_desc ) ?>",
    "criteria": "<?php echo esc_js( $badge_url ) ?>",
    "issuer": {
      "origin": "<?php echo esc_js( $issuer_origin_url ) ?>",
      "name": "<?php echo esc_js( $issuer_name ) ?>",
      "org": "<?php echo esc_js( $issuer_org ) ?>",
      "contact": "<?php echo esc_js( $issuer_contact ) ?>"
    }
  }
}

<?php
