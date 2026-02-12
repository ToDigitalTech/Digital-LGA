<?php
/**
 * Company profile template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$user         = get_userdata( $company_id );
$company_name = get_user_meta( $company_id, 'dlga_company_name', true );
$cac          = get_user_meta( $company_id, 'dlga_cac_number', true );
$industry     = get_user_meta( $company_id, 'dlga_industry_type', true );
$is_public    = get_user_meta( $company_id, 'dlga_is_public_sector', true );
$is_blacklisted = DLGA_Business::is_blacklisted( $company_id );

global $wpdb;

$stats = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dlga_company_stats WHERE company_id = %d",
    $company_id
) );

// Get completed projects
$completed_projects = get_posts( array(
    'post_type'      => 'dlga_tender',
    'posts_per_page' => 20,
    'meta_query'     => array(
        'relation' => 'AND',
        array( 'key' => '_dlga_winning_company', 'value' => $company_id ),
        array( 'key' => '_dlga_tender_status', 'value' => DLGA_Tender::STATUS_COMPLETED ),
    ),
) );

// Get active projects
$active_projects = get_posts( array(
    'post_type'      => 'dlga_tender',
    'posts_per_page' => 10,
    'meta_query'     => array(
        'relation' => 'AND',
        array( 'key' => '_dlga_winning_company', 'value' => $company_id ),
        array(
            'key'     => '_dlga_tender_status',
            'value'   => array( DLGA_Tender::STATUS_AWARDED, DLGA_Tender::STATUS_IN_PROGRESS ),
            'compare' => 'IN',
        ),
    ),
) );
?>
<div class="dlga-company-profile">
    <div class="dlga-card">
        <h1><?php echo esc_html( $company_name ? $company_name : $user->display_name ); ?></h1>

        <?php if ( $is_blacklisted ) : ?>
            <div class="dlga-badge dlga-badge-danger"><?php esc_html_e( 'BLACKLISTED', 'digital-lga' ); ?></div>
        <?php endif; ?>

        <?php if ( $is_public ) : ?>
            <span class="dlga-badge dlga-badge-success"><?php esc_html_e( 'Public Sector Business', 'digital-lga' ); ?></span>
        <?php endif; ?>

        <p>
            <?php printf( esc_html__( 'Member since: %s', 'digital-lga' ), esc_html( date_i18n( 'F Y', strtotime( $user->user_registered ) ) ) ); ?>
            <?php if ( $cac ) : ?>
                | <?php printf( esc_html__( 'CAC: %s', 'digital-lga' ), esc_html( $cac ) ); ?>
            <?php endif; ?>
            <?php if ( $industry ) : ?>
                | <?php echo esc_html( ucfirst( $industry ) ); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Performance Metrics -->
    <?php if ( $stats ) : ?>
    <div class="dlga-card">
        <h2><?php esc_html_e( 'Performance Metrics', 'digital-lga' ); ?></h2>
        <div class="dlga-stats-grid">
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->total_projects ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Total Projects', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->completed_projects ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Completed', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->in_progress_projects ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'In Progress', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->average_rating ? number_format( $stats->average_rating, 1 ) . '/5' : '-' ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Avg Rating', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->on_time_delivery_rate ? $stats->on_time_delivery_rate . '%' : '-' ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Success Rate', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( $stats->total_verifications ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Citizen Verifications', 'digital-lga' ); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Completed Projects -->
    <?php if ( ! empty( $completed_projects ) ) : ?>
    <div class="dlga-card">
        <h2><?php esc_html_e( 'Completed Projects', 'digital-lga' ); ?></h2>
        <div class="dlga-projects-grid">
            <?php foreach ( $completed_projects as $project ) :
                $budget  = get_post_meta( $project->ID, '_dlga_budget', true );
                $rating  = DLGA_Tender::get_average_rating( $project->ID );
                $verif   = DLGA_Tender::get_verification_count( $project->ID );
            ?>
            <div class="dlga-project-card dlga-project-completed">
                <h3><a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
                <p>
                    <?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?> |
                    <?php echo esc_html( get_the_date( '', $project ) ); ?>
                </p>
                <p>
                    <?php if ( $rating > 0 ) : ?>
                        <?php printf( esc_html__( 'Rating: %s/5', 'digital-lga' ), esc_html( number_format( $rating, 1 ) ) ); ?> |
                    <?php endif; ?>
                    <?php printf( esc_html__( '%d verifications', 'digital-lga' ), $verif ); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Projects -->
    <?php if ( ! empty( $active_projects ) ) : ?>
    <div class="dlga-card">
        <h2><?php esc_html_e( 'Current Projects', 'digital-lga' ); ?></h2>
        <?php foreach ( $active_projects as $project ) :
            $budget = get_post_meta( $project->ID, '_dlga_budget', true );
            $milestones = DLGA_Tender::get_milestones( $project->ID );
            $completed_ms = 0;
            foreach ( $milestones as $ms ) {
                if ( 'approved' === $ms->status ) {
                    $completed_ms++;
                }
            }
            $progress = count( $milestones ) > 0 ? round( ( $completed_ms / count( $milestones ) ) * 100 ) : 0;
        ?>
        <div class="dlga-project-card">
            <h3><a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
            <div class="dlga-progress-bar">
                <div class="dlga-progress-fill" style="width:<?php echo esc_attr( $progress ); ?>%">
                    <span><?php echo esc_html( $progress ); ?>%</span>
                </div>
            </div>
            <p><?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
