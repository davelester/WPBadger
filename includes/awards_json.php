<?php global $post;
header('Content-Type: application/json'); 
$email = get_post_meta($post->ID, 'wpbadger-award-email-address', true);
$chosen_badge_id = get_post_meta($post->ID, 'wpbadger-award-choose-badge', true);
$salt = get_post_meta( $post->ID, 'wpbadger-award-salt', true );
$title = get_the_title($chosen_badge_id);
$version = get_post_meta( $chosen_badge_id, 'wpbadger-badge-version', true );
$issued_on = get_the_date('Y-m-d');
$evidence = get_permalink();

// Create a query to retrieve badge data
$badge_query = new WP_Query( array(
	'post_status' => 'publish',
	'post_type' => 'badge',
	'post_title' => $title,
	'meta_query' => array(
		array(
			'key' => 'wpbadger-badge-version',
			'value' => $version,
			'compare' => '=',
			'type' => 'CHAR'
			)
		)
	)
);

$badge_query->the_post();
?>
{
  "recipient": "sha256$<?php echo hash("sha256", ($email . $salt)); ?>",
  "salt": "<?php echo esc_js( $salt ); ?>",
  "evidence": "<?php echo esc_js( $evidence ); ?>",
  "issued_on": "<?php echo esc_js( $issued_on ); ?>",
  "badge": {
    "version": "<?php echo esc_js( $version ); ?>",
    "name": "<?php echo esc_js( get_the_title() ); ?>",
    "image": "<?php $post_thumbnail_id = get_post_thumbnail_id(); echo esc_js( wp_get_attachment_url( $post_thumbnail_id ) ); ?>",
    "description": "<?php echo esc_js( get_the_content() ); ?>",
    "criteria": "<?php echo esc_js( get_permalink() ); ?>",
    "issuer": {
      "origin": "<?php $data = parse_url(get_bloginfo('siteurl')); echo esc_js( 'http://' . $data['host'] ); ?>",
      "name": "<?php echo esc_js( get_option('wpbadger_issuer_name') ); ?>",
      "org": "<?php echo esc_js( get_option('wpbadger_issuer_org') ); ?>",
      "contact": "<?php echo esc_js( get_option('wpbadger_issuer_contact') ); ?>"
    }
  }
}

<?php
// Reset Post Data
wp_reset_postdata();
?>
