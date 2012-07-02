<?php global $post;
header('Content-Type: application/json'); 
$email = get_post_meta($post->ID, 'wpbadger-award-email-address', true);
$chosen_badge = get_post_meta($post->ID, 'wpbadger-award-choose-badge', true);
list($title, $version) = split(' \(', $chosen_badge, 2);
$version = substr($version,0,-1);
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
  "recipient": "sha256$<?php echo hash("sha256", ($email . "hashbrowns")); ?>",
  "salt": "hashbrowns",
  "evidence": "<?php echo $evidence; ?>",
  "issued_on": "<?php echo $issued_on; ?>",
  "badge": {
    "version": "<?php echo $version; ?>",
    "name": "<?php echo get_the_title(); ?>",
    "image": "<?php $post_thumbnail_id = get_post_thumbnail_id(); echo wp_get_attachment_url( $post_thumbnail_id ); ?>",
    "description": "<?php echo get_the_content(); ?>",
    "criteria": "<?php echo get_permalink(); ?>",
    "issuer": {
      "origin": "<?php echo get_bloginfo('siteurl')?>",
      "name": "<?php echo get_option('wpbadger_issuer_name'); ?>",
      "org": "<?php echo get_option('wpbadger_issuer_org'); ?>",
      "contact": "<?php echo get_option('wpbadger_issuer_contact'); ?>"
    }
  }
}

<?php
// Reset Post Data
wp_reset_postdata();
?>