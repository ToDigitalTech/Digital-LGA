<?php
/**
 * Business dashboard template.
 *
 * Displays company info, worker management, payroll summary, and contribution totals.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$user         = wp_get_current_user();
$company_name = get_user_meta( $user->ID, 'dlga_company_name', true );
$cac_number   = get_user_meta( $user->ID, 'dlga_cac_number', true );
$approval     = get_user_meta( $user->ID, 'dlga_approval_status', true );
$industry     = get_user_meta( $user->ID, 'dlga_industry_type', true );
$workers      = DLGA_Business::get_workers( $user->ID );
$payroll_history = DLGA_Payroll::get_history( $user->ID );
$lga_name     = DLGA_Settings::get( 'dlga_lga_name', 'LGA' );

// Calculate contribution totals from completed payrolls.
$total_contributions = 0;
$total_paid          = 0;
foreach ( $payroll_history as $record ) {
    if ( 'completed' === $record->payment_status ) {
        $total_contributions += floatval( $record->total_contribution );
        $total_paid          += floatval( $record->total_business_pays );
    }
}
?>
<div class="dlga-dashboard dlga-business-dashboard">
    <h2><?php printf( esc_html__( 'Welcome, %s', 'digital-lga' ), esc_html( $company_name ) ); ?></h2>

    <?php if ( isset( $_GET['worker_added'] ) && '1' === $_GET['worker_added'] ) : ?>
        <div class="dlga-notice dlga-notice-success">
            <?php esc_html_e( 'Worker added successfully.', 'digital-lga' ); ?>
        </div>
    <?php endif; ?>

    <!-- Company Information -->
    <div class="dlga-card dlga-company-info">
        <h3><?php esc_html_e( 'Company Information', 'digital-lga' ); ?></h3>
        <table class="dlga-table dlga-info-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Company Name', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $company_name ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'CAC Number', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $cac_number ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Industry', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( ucfirst( $industry ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Account Status', 'digital-lga' ); ?></th>
                    <td>
                        <span class="dlga-badge dlga-badge-<?php echo esc_attr( $approval ); ?>">
                            <?php echo esc_html( ucfirst( $approval ) ); ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Contribution Totals -->
    <div class="dlga-card dlga-contribution-totals">
        <h3><?php esc_html_e( 'Contribution Summary', 'digital-lga' ); ?></h3>
        <div class="dlga-stats-row">
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $total_contributions ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Total Contributions to Community', 'digital-lga' ); ?></span>
            </div>
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( DLGA_Settings::format_amount( $total_paid ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Total Payroll Processed', 'digital-lga' ); ?></span>
            </div>
            <div class="dlga-stat">
                <span class="dlga-stat-value"><?php echo esc_html( count( $workers ) ); ?></span>
                <span class="dlga-stat-label"><?php esc_html_e( 'Registered Workers', 'digital-lga' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Worker Management -->
    <div class="dlga-card dlga-worker-management">
        <h3><?php esc_html_e( 'Worker Management', 'digital-lga' ); ?></h3>

        <?php if ( ! empty( $workers ) ) : ?>
            <table class="dlga-table dlga-workers-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Salary', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $workers as $worker ) : ?>
                        <tr>
                            <td><?php echo esc_html( $worker['name'] ); ?></td>
                            <td><?php echo esc_html( $worker['email'] ); ?></td>
                            <td><?php echo esc_html( isset( $worker['phone'] ) ? $worker['phone'] : '—' ); ?></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $worker['salary'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No workers added yet. Use the form below to add your first worker.', 'digital-lga' ); ?></p>
        <?php endif; ?>

        <!-- Add Worker Form -->
        <div class="dlga-add-worker-form">
            <h4><?php esc_html_e( 'Add New Worker', 'digital-lga' ); ?></h4>
            <form method="post" class="dlga-form">
                <?php wp_nonce_field( 'dlga_add_worker', 'dlga_add_worker_nonce' ); ?>

                <div class="dlga-form-row">
                    <div class="dlga-form-group">
                        <label for="worker_name"><?php esc_html_e( 'Full Name *', 'digital-lga' ); ?></label>
                        <input type="text" name="worker_name" id="worker_name" required>
                    </div>
                    <div class="dlga-form-group">
                        <label for="worker_email"><?php esc_html_e( 'Email Address *', 'digital-lga' ); ?></label>
                        <input type="email" name="worker_email" id="worker_email" required>
                    </div>
                </div>
                <div class="dlga-form-row">
                    <div class="dlga-form-group">
                        <label for="worker_phone"><?php esc_html_e( 'Phone Number', 'digital-lga' ); ?></label>
                        <input type="tel" name="worker_phone" id="worker_phone">
                    </div>
                    <div class="dlga-form-group">
                        <label for="worker_salary"><?php esc_html_e( 'Monthly Salary *', 'digital-lga' ); ?></label>
                        <input type="number" name="worker_salary" id="worker_salary" min="1" step="0.01" required>
                    </div>
                </div>

                <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Add Worker', 'digital-lga' ); ?></button>
            </form>
        </div>
    </div>

    <!-- Payroll Summary Link -->
    <div class="dlga-card dlga-payroll-link">
        <h3><?php esc_html_e( 'Process Payroll', 'digital-lga' ); ?></h3>
        <p>
            <?php printf(
                esc_html__( 'You have %d workers registered. Process this month\'s payroll to contribute to %s community funds.', 'digital-lga' ),
                count( $workers ),
                esc_html( $lga_name )
            ); ?>
        </p>
        <a href="<?php echo esc_url( home_url( '/dlga/process-payroll/' ) ); ?>" class="dlga-btn dlga-btn-primary">
            <?php esc_html_e( 'Go to Payroll', 'digital-lga' ); ?>
        </a>
    </div>

    <!-- Recent Payroll History -->
    <div class="dlga-card dlga-payroll-history">
        <h3><?php esc_html_e( 'Recent Payroll History', 'digital-lga' ); ?></h3>

        <?php if ( ! empty( $payroll_history ) ) : ?>
            <table class="dlga-table dlga-payroll-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Period', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Worker', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Gross Salary', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Contribution', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Total Paid', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $displayed = 0;
                    foreach ( $payroll_history as $record ) :
                        if ( $displayed >= 20 ) {
                            break;
                        }
                        $displayed++;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $record->pay_period ); ?></td>
                            <td>
                                <?php echo esc_html( $record->worker_name ); ?>
                                <br><small><?php echo esc_html( $record->worker_email ); ?></small>
                            </td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $record->gross_salary ) ); ?></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $record->total_contribution ) ); ?></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $record->total_business_pays ) ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $record->payment_status ); ?>">
                                    <?php echo esc_html( ucfirst( $record->payment_status ) ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No payroll records yet. Process your first payroll to see the history here.', 'digital-lga' ); ?></p>
        <?php endif; ?>
    </div>
</div>
