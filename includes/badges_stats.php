<?php

add_action( 'wp_ajax_wpbadger_get_awards_stats', 'wpbadger_badge_get_awards_stats' );
add_action( 'admin_menu', 'wpbadger_badges_stats_admin_menu' );

function wpbadger_badge_get_awards_stats()
{
    $badge_id = (int)$_REQUEST[ 'badge_id' ];
    $query = new WP_Query( array(
        'post_type'     => 'award',
        'post_status'   => 'publish',
        'meta_query' => array(
            array(
                'key'   => 'wpbadger-award-choose-badge',
                'value' => $badge_id
            )
        )
    ) );

    $stats = array(
        'Count'     => 0,
        'Awarded'   => 0,
        'Accepted'  => 0,
        'Rejected'  => 0
    );
    while ($query->next_post())
    {
        $award_status = get_post_meta( $query->post->ID, 'wpbadger-award-status', true );

        $stats[ 'Count' ]++;
        $stats[ $award_status ]++;
    }

    header( 'Content-type: application/json' );
?>
{
    BadgeID: <?php echo $badge_id ?>,
    Count: <?php echo $stats[ 'Count' ] ?>,
    Awarded: <?php echo $stats[ 'Awarded' ] ?>,
    Accepted: <?php echo $stats[ 'Accepted' ] ?>,
    Rejected: <?php echo $stats[ 'Rejected' ] ?>
}
<?php
    die();
}

if (!class_exists( 'WP_List_Table' ))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBadger_Badges_Stats_List_Table extends WP_List_Table
{
    function __construct()
    {
        parent::__construct( array(
            'singular'  => 'Badge',
            'plural'    => 'Badges',
            'ajax'      => false
        ) );
    }

    function column_default( $item, $column_name )
    {
        return $item[ $column_name ];
    }

    function column_title( $item )
    {
        $actions = array(
            'edit'      => '<a href="' . get_edit_post_link( $item[ 'ID' ] ) . '">Edit</a>',
            'view'      => '<a href="' . get_post_permalink( $item[ 'ID' ] ) . '">View</a>'
        );

        return $item[ 'title' ] . ' ' . $this->row_actions( $actions );
    }

    function get_columns()
    {
        return array(
            'title'     => 'Title',
            'Count'     => 'Awarded',
            'Accepted'  => 'Accepted',
            'Rejected'  => 'Rejected'
        );
    }

    function prepare_items()
    {
        $columns    = $this->get_columns();
        $hidden     = array();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $stats = array();

        # First, run the query that gets all the published badges
        $query = new WP_Query( array(
            'post_type'     => 'badge',
            'post_status'   => 'publish'
        ) );
        while ($query->next_post())
        {
            $stats[ $query->post->ID ] = array(
                'ID'        => $query->post->ID,
                'title'     => $query->post->post_title,
                'Count'     => 0,
                'Awarded'   => 0,
                'Accepted'  => 0,
                'Rejected'  => 0
            );
        }

        # Now, loop over all the published awards and increment the counts in
        # the stats array
        $query = new WP_Query( array(
            'post_type'     => 'award',
            'post_status'   => 'publish'
        ) );
        while ($query->next_post())
        {
            $badge_id = (int)get_post_meta( $query->post->ID, 'wpbadger-award-choose-badge', true );
            $award_status = get_post_meta( $query->post->ID, 'wpbadger-award-status', true );

            if (!isset( $stats[ $badge_id ] ) || !isset( $stats[ $badge_id ][ $award_status ] ))
                continue;

            $stats[ $badge_id ][ 'Count' ]++;
            $stats[ $badge_id ][ $award_status ]++;
        }

        $this->items = $stats;
        $this->set_pagination_args( array(
            'total_items'   => count( $stats ),
            'per_page'      => count( $stats ),
            'total_pages'   => 1
        ) );
    }
}

function wpbadger_badges_stats_admin_menu()
{
    $badge_type = get_post_type_object( 'badge' );

    add_submenu_page(
        'edit.php?post_type=badge',
        'WPBadger | Badge Statistics',
        'Badge Statistics',
        $badge_type->cap->edit_posts,
        'wpbadger_badges_stats_page',
        'wpbadger_badges_stats_page'
    );
}

function wpbadger_badges_stats_page()
{
    $table = new WPBadger_Badges_Stats_List_Table();
    $table->prepare_items();

?>
    <div class="wrap">
        <div id="icon-edit" class="icon32"><br /></div>
        <h2>Badge Statistics</h2>

        <?php $table->display() ?>
    </div>
<?php
}

