<?php
/**
 * Payroll processing.
 *
 * Handles payroll calculation, WooCommerce order creation, and fund pool allocation.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Payroll {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_payroll_submission' ) );
        add_shortcode( 'dlga_process_payroll', array( __CLASS__, 'payroll_form_shortcode' ) );
    }

    /**
     * Render payroll processing form.
     *
     * @return string
     */
    public static function payroll_form_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to process payroll.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            return '<p>' . esc_html__( 'Only registered businesses can process payroll.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="dlga-payroll-form">
            <h2><?php esc_html_e( 'Process Payroll', 'digital-lga' ); ?></h2>

            <div class="dlga-payroll-settings-info">
                <h3><?php esc_html_e( 'Current Rates', 'digital-lga' ); ?></h3>
                <ul>
                    <li><?php printf(
                        esc_html__( 'Contribution Rate: %s%%', 'digital-lga' ),
                        esc_html( DLGA_Settings::get( 'dlga_contribution_rate', 10 ) )
                    ); ?></li>
                    <li><?php printf(
                        esc_html__( 'Worker Pays: %s%% of contribution', 'digital-lga' ),
                        esc_html( DLGA_Settings::get( 'dlga_worker_split', 50 ) )
                    ); ?></li>
                    <li><?php printf(
                        esc_html__( 'Business Pays: %s%% of contribution', 'digital-lga' ),
                        esc_html( 100 - floatval( DLGA_Settings::get( 'dlga_worker_split', 50 ) ) )
                    ); ?></li>
                    <li><?php printf(
                        esc_html__( 'Platform Fee: %s%%', 'digital-lga' ),
                        esc_html( DLGA_Settings::get( 'dlga_platform_fee', 5 ) )
                    ); ?></li>
                </ul>
            </div>

            <?php
            $workers = DLGA_Business::get_workers( get_current_user_id() );
            if ( empty( $workers ) ) :
            ?>
                <p><?php esc_html_e( 'You have no workers added. Please add workers from your dashboard first.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <form method="post" id="dlga-payroll-form">
                    <?php wp_nonce_field( 'dlga_process_payroll', 'dlga_payroll_nonce' ); ?>
                    <input type="hidden" name="pay_period" value="<?php echo esc_attr( gmdate( 'Y-m' ) ); ?>">

                    <table class="dlga-table dlga-payroll-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="dlga-select-all"></th>
                                <th><?php esc_html_e( 'Worker', 'digital-lga' ); ?></th>
                                <th><?php esc_html_e( 'Gross Salary', 'digital-lga' ); ?></th>
                                <th><?php esc_html_e( 'Worker Deduction', 'digital-lga' ); ?></th>
                                <th><?php esc_html_e( 'Net to Worker', 'digital-lga' ); ?></th>
                                <th><?php esc_html_e( 'Business Contribution', 'digital-lga' ); ?></th>
                                <th><?php esc_html_e( 'Total Due', 'digital-lga' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $workers as $index => $worker ) :
                                $calc = DLGA_Settings::calculate_payroll( $worker['salary'] );
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_workers[]"
                                           value="<?php echo esc_attr( $index ); ?>" checked>
                                </td>
                                <td>
                                    <?php echo esc_html( $worker['name'] ); ?>
                                    <br><small><?php echo esc_html( $worker['email'] ); ?></small>
                                </td>
                                <td><?php echo esc_html( DLGA_Settings::format_amount( $calc['gross_salary'] ) ); ?></td>
                                <td><?php echo esc_html( DLGA_Settings::format_amount( $calc['worker_contribution'] ) ); ?></td>
                                <td><?php echo esc_html( DLGA_Settings::format_amount( $calc['net_to_worker'] ) ); ?></td>
                                <td><?php echo esc_html( DLGA_Settings::format_amount( $calc['business_contribution'] ) ); ?></td>
                                <td><strong><?php echo esc_html( DLGA_Settings::format_amount( $calc['total_business_pays'] ) ); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="dlga-payroll-summary">
                        <h3><?php esc_html_e( 'Payroll Summary', 'digital-lga' ); ?></h3>
                        <p><?php printf(
                            esc_html__( 'Pay Period: %s', 'digital-lga' ),
                            esc_html( gmdate( 'F Y' ) )
                        ); ?></p>
                    </div>

                    <button type="submit" class="dlga-btn dlga-btn-primary">
                        <?php esc_html_e( 'Process Payroll & Pay', 'digital-lga' ); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle payroll form submission.
     */
    public static function handle_payroll_submission() {
        if ( ! isset( $_POST['dlga_payroll_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_payroll_nonce'], 'dlga_process_payroll' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'digital-lga' ) );
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $selected   = isset( $_POST['selected_workers'] ) ? array_map( 'intval', $_POST['selected_workers'] ) : array();
        $pay_period = sanitize_text_field( $_POST['pay_period'] );
        $workers    = DLGA_Business::get_workers( $user->ID );

        if ( empty( $selected ) || empty( $workers ) ) {
            wp_die( esc_html__( 'No workers selected.', 'digital-lga' ) );
        }

        global $wpdb;
        $total_due          = 0;
        $total_contribution = 0;
        $payroll_records    = array();

        foreach ( $selected as $index ) {
            if ( ! isset( $workers[ $index ] ) ) {
                continue;
            }

            $worker = $workers[ $index ];
            $calc   = DLGA_Settings::calculate_payroll( $worker['salary'] );

            $wpdb->insert(
                "{$wpdb->prefix}dlga_payrolls",
                array(
                    'business_id'           => $user->ID,
                    'worker_name'           => $worker['name'],
                    'worker_email'          => $worker['email'],
                    'worker_phone'          => isset( $worker['phone'] ) ? $worker['phone'] : '',
                    'gross_salary'          => $calc['gross_salary'],
                    'worker_contribution'   => $calc['worker_contribution'],
                    'business_contribution' => $calc['business_contribution'],
                    'total_contribution'    => $calc['total_contribution'],
                    'platform_fee'          => $calc['platform_fee'],
                    'net_to_worker'         => $calc['net_to_worker'],
                    'total_business_pays'   => $calc['total_business_pays'],
                    'payment_status'        => 'pending',
                    'pay_period'            => $pay_period,
                    'created_at'            => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s' )
            );

            $payroll_records[] = array(
                'id'     => $wpdb->insert_id,
                'worker' => $worker,
                'calc'   => $calc,
            );

            $total_due          += $calc['total_business_pays'];
            $total_contribution += $calc['total_contribution'];
        }

        // Create WooCommerce order
        if ( class_exists( 'WC_Order' ) && ! empty( $payroll_records ) ) {
            $order = wc_create_order( array(
                'customer_id' => $user->ID,
                'status'      => 'pending',
            ) );

            if ( ! is_wp_error( $order ) ) {
                // Add fee items for breakdown
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( sprintf(
                    __( 'Payroll - %s (%d workers)', 'digital-lga' ),
                    gmdate( 'F Y', strtotime( $pay_period . '-01' ) ),
                    count( $payroll_records )
                ) );
                $fee->set_amount( $total_due );
                $fee->set_total( $total_due );
                $order->add_item( $fee );

                $order->set_total( $total_due );
                $order->update_meta_data( '_dlga_payroll_records', wp_json_encode( wp_list_pluck( $payroll_records, 'id' ) ) );
                $order->update_meta_data( '_dlga_total_contribution', $total_contribution );
                $order->update_meta_data( '_dlga_pay_period', $pay_period );
                $order->save();

                // Update payroll records with order ID
                foreach ( $payroll_records as $record ) {
                    $wpdb->update(
                        "{$wpdb->prefix}dlga_payrolls",
                        array( 'wc_order_id' => $order->get_id() ),
                        array( 'id' => $record['id'] ),
                        array( '%d' ),
                        array( '%d' )
                    );
                }

                // Redirect to WooCommerce checkout
                wp_redirect( $order->get_checkout_payment_url() );
                exit;
            }
        }

        wp_redirect( add_query_arg( 'payroll_error', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Process payroll completion after payment.
     *
     * Called when WooCommerce order is marked as completed.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function process_completed_payroll( $order_id ) {
        global $wpdb;

        $order              = wc_get_order( $order_id );
        $total_contribution = floatval( $order->get_meta( '_dlga_total_contribution' ) );

        if ( $total_contribution <= 0 ) {
            return;
        }

        // Update payroll records
        $wpdb->update(
            "{$wpdb->prefix}dlga_payrolls",
            array(
                'payment_status' => 'completed',
                'processed_at'   => current_time( 'mysql' ),
            ),
            array( 'wc_order_id' => $order_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        // Allocate to fund pools
        $pools = DLGA_Settings::get_pool_allocations();

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance + %f,
                 monthly_collected = monthly_collected + %f
             WHERE pool_type = 'personnel'",
            $total_contribution * $pools['personnel'],
            $total_contribution * $pools['personnel']
        ) );

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance + %f,
                 monthly_collected = monthly_collected + %f
             WHERE pool_type = 'infrastructure'",
            $total_contribution * $pools['infrastructure'],
            $total_contribution * $pools['infrastructure']
        ) );

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance + %f,
                 monthly_collected = monthly_collected + %f
             WHERE pool_type = 'emergency'",
            $total_contribution * $pools['emergency'],
            $total_contribution * $pools['emergency']
        ) );
    }

    /**
     * Get payroll history for a business.
     *
     * @param int    $business_id User ID.
     * @param string $pay_period  Optional period filter.
     * @return array
     */
    public static function get_history( $business_id, $pay_period = '' ) {
        global $wpdb;

        $where = $wpdb->prepare( "WHERE business_id = %d", $business_id );
        if ( ! empty( $pay_period ) ) {
            $where .= $wpdb->prepare( " AND pay_period = %s", $pay_period );
        }

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dlga_payrolls {$where} ORDER BY created_at DESC"
        );
    }

    /**
     * Get total collected for a period.
     *
     * @param string $pay_period Period in Y-m format.
     * @return float
     */
    public static function get_period_total( $pay_period = '' ) {
        global $wpdb;

        if ( empty( $pay_period ) ) {
            $pay_period = gmdate( 'Y-m' );
        }

        return (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(total_contribution), 0)
             FROM {$wpdb->prefix}dlga_payrolls
             WHERE pay_period = %s AND payment_status = 'completed'",
            $pay_period
        ) );
    }
}
