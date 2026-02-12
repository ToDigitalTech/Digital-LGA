<?php
/**
 * Citizen dashboard template.
 *
 * Displays welcome message, submitted ideas, links to submit new ideas,
 * transparency dashboard, and active tenders in the area.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$user     = wp_get_current_user();
$lga_name = DLGA_Settings::get( 'dlga_lga_name', 'LGA' );
$user_lga = get_user_meta( $user->ID, 'dlga_lga', true );

// Get ideas submitted by this citizen.
$my_ideas = get_posts( array(
    'post_type'      => 'dlga_job_idea',
    'post_status'    => array( 'publish', 'pending', 'draft' ),
    'author'         => $user->ID,
    'posts_per_page' => 20,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

// Get active tenders in the area.
$active_tenders = get_posts( array(
    'post_type'      => 'dlga_tender',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        array(
            'key'     => '_dlga_tender_status',
            'value'   => array( DLGA_Tender::STATUS_OPEN, DLGA_Tender::STATUS_IN_PROGRESS, DLGA_Tender::STATUS_AWARDED ),
            'compare' => 'IN',
        ),
    ),
) );
?>
<div class="dlga-dashboard dlga-citizen-dashboard">

    <!-- Welcome Message -->
    <div class="dlga-card dlga-welcome">
        <h2>
            <?php printf(
                esc_html__( 'Welcome, %s', 'digital-lga' ),
                esc_html( $user->display_name )
            ); ?>
        </h2>
        <p>
            <?php printf(
                esc_html__( 'Thank you for being an active citizen of %s. Your voice helps shape the community.', 'digital-lga' ),
                esc_html( $lga_name )
            ); ?>
        </p>
    </div>

    <!-- Quick Actions -->
    <div class="dlga-card dlga-quick-actions">
        <h3><?php esc_html_e( 'Quick Actions', 'digital-lga' ); ?></h3>
        <div class="dlga-actions-row">
            <a href="<?php echo esc_url( home_url( '/dlga/submit-job-idea/' ) ); ?>" class="dlga-btn dlga-btn-primary">
                <?php esc_html_e( 'Submit New Idea', 'digital-lga' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/dlga/transparency/' ) ); ?>" class="dlga-btn dlga-btn-secondary">
                <?php esc_html_e( 'Transparency Dashboard', 'digital-lga' ); ?>
            </a>
        </div>
    </div>

    <!-- My Submitted Ideas -->
    <div class="dlga-card dlga-my-ideas">
        <h3><?php esc_html_e( 'My Submitted Ideas', 'digital-lga' ); ?></h3>

        <?php if ( ! empty( $my_ideas ) ) : ?>
            <table class="dlga-table dlga-ideas-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Urgency', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $my_ideas as $idea ) :
                        $idea_status   = get_post_meta( $idea->ID, '_dlga_status', true );
                        $idea_location = get_post_meta( $idea->ID, '_dlga_location', true );
                        $idea_category = get_post_meta( $idea->ID, '_dlga_category', true );
                        $idea_urgency  = get_post_meta( $idea->ID, '_dlga_urgency', true );
                    ?>
                        <tr>
                            <td><?php echo esc_html( $idea->post_title ); ?></td>
                            <td><?php echo esc_html( $idea_location ); ?></td>
                            <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $idea_category ) ) ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $idea_urgency ); ?>">
                                    <?php echo esc_html( ucfirst( $idea_urgency ) ); ?>
                                </span>
                            </td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $idea_status ); ?>">
                                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $idea_status ) ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( get_the_date( '', $idea ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'You have not submitted any ideas yet.', 'digital-lga' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/dlga/submit-job-idea/' ) ); ?>" class="dlga-btn dlga-btn-primary">
                <?php esc_html_e( 'Submit Your First Idea', 'digital-lga' ); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Active Tenders in the Area -->
    <div class="dlga-card dlga-active-tenders">
        <h3>
            <?php printf(
                esc_html__( 'Active Tenders in %s', 'digital-lga' ),
                esc_html( $lga_name )
            ); ?>
        </h3>

        <?php if ( ! empty( $active_tenders ) ) : ?>
            <table class="dlga-table dlga-tenders-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Project', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Budget', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Verifications', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $status_labels = DLGA_Tender::get_status_labels();
                    foreach ( $active_tenders as $tender ) :
                        $tender_status  = get_post_meta( $tender->ID, '_dlga_tender_status', true );
                        $tender_budget  = get_post_meta( $tender->ID, '_dlga_budget', true );
                        $verify_count   = DLGA_Tender::get_verification_count( $tender->ID );
                        $status_label   = isset( $status_labels[ $tender_status ] ) ? $status_labels[ $tender_status ] : $tender_status;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $tender->post_title ); ?></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $tender_budget ) ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $tender_status ); ?>">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $verify_count ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_permalink( $tender->ID ) ); ?>" class="dlga-btn dlga-btn-small">
                                    <?php esc_html_e( 'View', 'digital-lga' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'There are no active tenders at this time.', 'digital-lga' ); ?></p>
        <?php endif; ?>
    </div>
</div>
