<?php
/**
 * Transparency dashboard template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$lga_name = esc_html( $data['lga_name'] );
$currency = DLGA_Settings::get_currency_symbol();
$pools    = $data['pools'];
?>
<div class="dlga-transparency">
    <div class="dlga-transparency-header">
        <h1><?php printf( esc_html__( 'Digital LGA - %s', 'digital-lga' ), $lga_name ); ?></h1>
        <p><?php esc_html_e( 'Real-time transparency for our community funds', 'digital-lga' ); ?></p>
    </div>

    <!-- Overview Stats -->
    <section class="dlga-section">
        <h2><?php printf(
            esc_html__( 'This Month (%s)', 'digital-lga' ),
            esc_html( date_i18n( 'F Y', strtotime( $data['current_month'] . '-01' ) ) )
        ); ?></h2>

        <div class="dlga-stats-grid">
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $data['monthly_collected'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Total Collected', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['worker_count'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Workers Contributing', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['business_count'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Businesses Enrolled', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['civil_servants'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Civil Servants Supported', 'digital-lga' ); ?></div>
            </div>
        </div>
    </section>

    <!-- Fund Distribution -->
    <section class="dlga-section">
        <h2><?php esc_html_e( 'Fund Distribution', 'digital-lga' ); ?></h2>

        <div class="dlga-pools-grid">
            <?php if ( isset( $pools['personnel'] ) ) : ?>
            <div class="dlga-pool-card dlga-pool-personnel">
                <h3><?php printf(
                    esc_html__( 'Personnel Pool (%s%%)', 'digital-lga' ),
                    esc_html( DLGA_Settings::get( 'dlga_personnel_pool', 30 ) )
                ); ?></h3>
                <div class="dlga-pool-balance"><?php echo esc_html( DLGA_Settings::format_amount( $pools['personnel']->total_balance ) ); ?></div>

                <?php if ( ! empty( $data['dist_summary'] ) ) : ?>
                    <ul>
                    <?php foreach ( $data['dist_summary'] as $ds ) : ?>
                        <li>
                            <?php echo esc_html( ucwords( str_replace( '_', ' ', $ds->service_type ) ) ); ?>:
                            <?php echo esc_html( $ds->count ); ?> <?php esc_html_e( 'members', 'digital-lga' ); ?> =
                            <?php echo esc_html( DLGA_Settings::format_amount( $ds->total ) ); ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ( isset( $pools['infrastructure'] ) ) : ?>
            <div class="dlga-pool-card dlga-pool-infrastructure">
                <h3><?php printf(
                    esc_html__( 'Infrastructure Pool (%s%%)', 'digital-lga' ),
                    esc_html( DLGA_Settings::get( 'dlga_infrastructure_pool', 60 ) )
                ); ?></h3>
                <div class="dlga-pool-balance"><?php echo esc_html( DLGA_Settings::format_amount( $pools['infrastructure']->total_balance ) ); ?></div>
                <p>
                    <?php printf(
                        esc_html__( 'Active Projects: %d', 'digital-lga' ),
                        count( $data['active_projects'] )
                    ); ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ( isset( $pools['emergency'] ) ) : ?>
            <div class="dlga-pool-card dlga-pool-emergency">
                <h3><?php printf(
                    esc_html__( 'Emergency Reserve (%s%%)', 'digital-lga' ),
                    esc_html( DLGA_Settings::get( 'dlga_emergency_pool', 10 ) )
                ); ?></h3>
                <div class="dlga-pool-balance"><?php echo esc_html( DLGA_Settings::format_amount( $pools['emergency']->total_balance ) ); ?></div>
                <p><?php esc_html_e( 'Accumulated for crisis management', 'digital-lga' ); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Active Projects -->
    <?php if ( ! empty( $data['active_projects'] ) ) : ?>
    <section class="dlga-section">
        <h2><?php printf( esc_html__( 'Projects In Progress (%d)', 'digital-lga' ), count( $data['active_projects'] ) ); ?></h2>

        <div class="dlga-projects-grid">
            <?php foreach ( $data['active_projects'] as $project ) :
                $budget   = get_post_meta( $project->ID, '_dlga_budget', true );
                $status   = get_post_meta( $project->ID, '_dlga_tender_status', true );
                $company_id = get_post_meta( $project->ID, '_dlga_winning_company', true );
                $company_name = $company_id ? get_user_meta( $company_id, 'dlga_company_name', true ) : '';
                $verifications = DLGA_Tender::get_verification_count( $project->ID );
                $milestones = DLGA_Tender::get_milestones( $project->ID );
                $completed_ms = 0;
                $total_ms = count( $milestones );
                foreach ( $milestones as $ms ) {
                    if ( 'approved' === $ms->status ) {
                        $completed_ms++;
                    }
                }
                $progress = $total_ms > 0 ? round( ( $completed_ms / $total_ms ) * 100 ) : 0;
            ?>
            <div class="dlga-project-card">
                <h3><a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
                <div class="dlga-progress-bar">
                    <div class="dlga-progress-fill" style="width:<?php echo esc_attr( $progress ); ?>%">
                        <span><?php echo esc_html( $progress ); ?>%</span>
                    </div>
                </div>
                <p>
                    <?php printf( esc_html__( 'Budget: %s', 'digital-lga' ), esc_html( DLGA_Settings::format_amount( $budget ) ) ); ?>
                    <?php if ( $company_name ) : ?>
                        | <?php printf( esc_html__( 'Company: %s', 'digital-lga' ), esc_html( $company_name ) ); ?>
                    <?php endif; ?>
                </p>
                <p>
                    <?php printf( esc_html__( '%d citizen verifications', 'digital-lga' ), $verifications ); ?>
                </p>
                <a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>" class="dlga-btn dlga-btn-secondary">
                    <?php esc_html_e( 'View Live Progress', 'digital-lga' ); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Completed Projects -->
    <?php if ( ! empty( $data['completed_this_month'] ) ) : ?>
    <section class="dlga-section">
        <h2><?php printf( esc_html__( 'Completed This Month (%d)', 'digital-lga' ), count( $data['completed_this_month'] ) ); ?></h2>

        <div class="dlga-projects-grid">
            <?php foreach ( $data['completed_this_month'] as $project ) :
                $budget  = get_post_meta( $project->ID, '_dlga_budget', true );
                $company_id = get_post_meta( $project->ID, '_dlga_winning_company', true );
                $company_name = $company_id ? get_user_meta( $company_id, 'dlga_company_name', true ) : '';
                $rating  = DLGA_Tender::get_average_rating( $project->ID );
                $verifications = DLGA_Tender::get_verification_count( $project->ID );
            ?>
            <div class="dlga-project-card dlga-project-completed">
                <h3><a href="<?php echo esc_url( get_permalink( $project->ID ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
                <p>
                    <?php printf( esc_html__( 'Budget: %s', 'digital-lga' ), esc_html( DLGA_Settings::format_amount( $budget ) ) ); ?>
                    <?php if ( $company_name ) : ?>
                        | <?php echo esc_html( $company_name ); ?>
                    <?php endif; ?>
                </p>
                <p>
                    <?php if ( $rating > 0 ) : ?>
                        <?php printf( esc_html__( 'Rating: %s/5', 'digital-lga' ), esc_html( number_format( $rating, 1 ) ) ); ?> |
                    <?php endif; ?>
                    <?php printf( esc_html__( '%d verifications', 'digital-lga' ), $verifications ); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Top Companies -->
    <?php if ( ! empty( $data['top_companies'] ) ) : ?>
    <section class="dlga-section">
        <h2><?php esc_html_e( 'Top Performing Companies', 'digital-lga' ); ?></h2>

        <table class="dlga-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Projects', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Success Rate', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Avg Rating', 'digital-lga' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $data['top_companies'] as $co ) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url( add_query_arg( 'company_id', $co->company_id, home_url( '/dlga/company/' ) ) ); ?>">
                            <?php echo esc_html( $co->company_name ? $co->company_name : $co->display_name ); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html( $co->completed_projects ); ?></td>
                    <td><?php echo esc_html( $co->on_time_delivery_rate ? $co->on_time_delivery_rate . '%' : '-' ); ?></td>
                    <td><?php echo esc_html( $co->average_rating ? number_format( $co->average_rating, 1 ) . '/5' : '-' ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- Monthly History -->
    <?php if ( ! empty( $data['monthly_history'] ) ) : ?>
    <section class="dlga-section">
        <h2><?php esc_html_e( 'Monthly Collection History', 'digital-lga' ); ?></h2>

        <table class="dlga-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Month', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Total Collected', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Businesses', 'digital-lga' ); ?></th>
                    <th><?php esc_html_e( 'Workers', 'digital-lga' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $data['monthly_history'] as $mh ) : ?>
                <tr>
                    <td><?php echo esc_html( date_i18n( 'F Y', strtotime( $mh->pay_period . '-01' ) ) ); ?></td>
                    <td><?php echo esc_html( DLGA_Settings::format_amount( $mh->total ) ); ?></td>
                    <td><?php echo esc_html( number_format( $mh->businesses ) ); ?></td>
                    <td><?php echo esc_html( number_format( $mh->workers ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- All-Time Stats -->
    <section class="dlga-section">
        <h2><?php esc_html_e( 'All-Time Statistics', 'digital-lga' ); ?></h2>

        <div class="dlga-stats-grid">
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $data['all_time_collected'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Total Collected Since Launch', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['all_time_projects'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Projects Completed', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['civil_servants'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Civil Servants Supported', 'digital-lga' ); ?></div>
            </div>
            <div class="dlga-stat-card">
                <div class="dlga-stat-value"><?php echo esc_html( number_format( $data['business_count'] ) ); ?></div>
                <div class="dlga-stat-label"><?php esc_html_e( 'Businesses Participating', 'digital-lga' ); ?></div>
            </div>
        </div>
    </section>
</div>
