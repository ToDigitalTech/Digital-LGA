<?php
/**
 * Civil servant dashboard template.
 *
 * Displays service info, verification status, monthly distributions,
 * payment history, and funding source details.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$user                = wp_get_current_user();
$service_type        = get_user_meta( $user->ID, 'dlga_service_type', true );
$badge_number        = get_user_meta( $user->ID, 'dlga_badge_number', true );
$station             = get_user_meta( $user->ID, 'dlga_station', true );
$verification_status = get_user_meta( $user->ID, 'dlga_verification_status', true );
$lga_name            = DLGA_Settings::get( 'dlga_lga_name', 'LGA' );

// Look up service type display name.
$service_types     = DLGA_Civil_Servant::get_service_types();
$service_type_name = $service_type;
foreach ( $service_types as $type ) {
    if ( $type['slug'] === $service_type ) {
        $service_type_name = $type['name'];
        break;
    }
}

// Get distributions for this user.
$distributions = DLGA_Distribution::get_for_user( $user->ID );

// Calculate total received.
$total_received = 0;
foreach ( $distributions as $dist ) {
    $total_received += floatval( $dist->amount );
}

// Get the most recent distribution for "current monthly amount".
$current_amount       = 0;
$current_source_count = 0;
if ( ! empty( $distributions ) ) {
    $current_amount       = floatval( $distributions[0]->amount );
    $current_source_count = intval( $distributions[0]->source_payroll_count );
}
?>
<div class="dlga-dashboard dlga-civil-servant-dashboard">
    <h2>
        <?php printf(
            esc_html__( 'Welcome, %s', 'digital-lga' ),
            esc_html( $user->display_name )
        ); ?>
    </h2>

    <!-- Service Information -->
    <div class="dlga-card dlga-service-info">
        <h3><?php esc_html_e( 'Service Information', 'digital-lga' ); ?></h3>
        <table class="dlga-table dlga-info-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Service Type', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $service_type_name ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Badge Number', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $badge_number ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Station', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $station ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Verification Status', 'digital-lga' ); ?></th>
                    <td>
                        <span class="dlga-badge dlga-badge-<?php echo esc_attr( $verification_status ); ?>">
                            <?php echo esc_html( ucfirst( $verification_status ) ); ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ( 'pending' === $verification_status ) : ?>
            <div class="dlga-notice dlga-notice-warning">
                <?php esc_html_e( 'Your account is pending verification. You will begin receiving distributions once verified.', 'digital-lga' ); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Distribution Amount -->
    <div class="dlga-card dlga-distribution-summary">
        <h3><?php esc_html_e( 'Monthly Distribution', 'digital-lga' ); ?></h3>
        <div class="dlga-stats-row">
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $current_amount ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Most Recent Distribution', 'digital-lga' ); ?></span>
            </div>
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $total_received ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Total Received', 'digital-lga' ); ?></span>
            </div>
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( count( $distributions ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Payments Received', 'digital-lga' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Funded By Businesses -->
    <div class="dlga-card dlga-funded-by">
        <h3><?php esc_html_e( 'Funded by Local Businesses', 'digital-lga' ); ?></h3>
        <?php if ( $current_source_count > 0 ) : ?>
            <p>
                <?php printf(
                    esc_html__( 'Your most recent distribution was funded by %d businesses contributing to the %s community fund through payroll processing.', 'digital-lga' ),
                    $current_source_count,
                    esc_html( $lga_name )
                ); ?>
            </p>
        <?php else : ?>
            <p>
                <?php printf(
                    esc_html__( 'Distributions are funded by businesses in %s that process payroll through the Digital LGA platform. A portion of each payroll contribution goes into the personnel pool, which is distributed monthly to verified civil servants.', 'digital-lga' ),
                    esc_html( $lga_name )
                ); ?>
            </p>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/dlga/transparency/' ) ); ?>" class="dlga-btn dlga-btn-secondary">
            <?php esc_html_e( 'View Transparency Dashboard', 'digital-lga' ); ?>
        </a>
    </div>

    <!-- Payment History Table -->
    <div class="dlga-card dlga-payment-history">
        <h3><?php esc_html_e( 'Payment History', 'digital-lga' ); ?></h3>

        <?php if ( ! empty( $distributions ) ) : ?>
            <table class="dlga-table dlga-distributions-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Month', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Funded by', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $distributions as $dist ) : ?>
                        <tr>
                            <td><?php echo esc_html( $dist->distribution_month ); ?></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $dist->amount ) ); ?></td>
                            <td>
                                <?php printf(
                                    esc_html__( '%d businesses', 'digital-lga' ),
                                    intval( $dist->source_payroll_count )
                                ); ?>
                            </td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $dist->payment_status ); ?>">
                                    <?php echo esc_html( ucfirst( $dist->payment_status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $dist->created_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No payment records yet. Distributions are processed monthly for verified civil servants.', 'digital-lga' ); ?></p>
        <?php endif; ?>
    </div>
</div>
