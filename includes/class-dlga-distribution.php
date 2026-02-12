<?php
/**
 * Monthly distribution to civil servants.
 *
 * Handles calculating and distributing personnel pool funds.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Distribution {

    public static function init() {
        add_action( 'dlga_monthly_distribution', array( __CLASS__, 'run_monthly_distribution' ) );
        add_action( 'init', array( __CLASS__, 'schedule_cron' ) );
    }

    /**
     * Schedule monthly distribution cron.
     */
    public static function schedule_cron() {
        if ( ! wp_next_scheduled( 'dlga_monthly_distribution' ) ) {
            wp_schedule_event( strtotime( 'first day of next month midnight' ), 'monthly', 'dlga_monthly_distribution' );
        }
    }

    /**
     * Run monthly distribution of personnel pool.
     */
    public static function run_monthly_distribution() {
        global $wpdb;

        $month = gmdate( 'Y-m', strtotime( 'last month' ) );

        // Check if already distributed this month
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dlga_distributions WHERE distribution_month = %s",
            $month
        ) );

        if ( $existing > 0 ) {
            return;
        }

        // Get personnel pool balance
        $pool = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}dlga_fund_pools WHERE pool_type = 'personnel'"
        );

        if ( ! $pool || $pool->total_balance <= 0 ) {
            return;
        }

        $available = floatval( $pool->total_balance );

        // Get eligible civil servants (verified, pool-access service types)
        $eligible = DLGA_Civil_Servant::get_pool_eligible();

        if ( empty( $eligible ) ) {
            return;
        }

        $per_person = round( $available / count( $eligible ), 2 );

        // Get payroll count for the month
        $payroll_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT business_id) FROM {$wpdb->prefix}dlga_payrolls
             WHERE pay_period = %s AND payment_status = 'completed'",
            $month
        ) );

        foreach ( $eligible as $servant ) {
            $service_type = get_user_meta( $servant->ID, 'dlga_service_type', true );

            $wpdb->insert(
                "{$wpdb->prefix}dlga_distributions",
                array(
                    'civil_servant_id'     => $servant->ID,
                    'service_type'         => $service_type,
                    'amount'               => $per_person,
                    'distribution_month'   => $month,
                    'source_payroll_count' => $payroll_count,
                    'payment_status'       => 'pending',
                    'created_at'           => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%f', '%s', '%d', '%s', '%s' )
            );

            // Notify civil servant
            wp_mail(
                $servant->user_email,
                sprintf( __( '[Digital LGA] Monthly Distribution - %s', 'digital-lga' ), $month ),
                sprintf(
                    __( "Dear %s,\n\nYour monthly distribution of %s is ready.\n\nThis was funded by %d businesses contributing to the community.\n\nThank you for your service.", 'digital-lga' ),
                    $servant->display_name,
                    DLGA_Settings::format_amount( $per_person ),
                    $payroll_count
                )
            );
        }

        // Update pool balance
        $total_distributed = $per_person * count( $eligible );
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance - %f,
                 monthly_distributed = %f,
                 last_distribution_date = %s
             WHERE pool_type = 'personnel'",
            $total_distributed,
            $total_distributed,
            current_time( 'Y-m-d' )
        ) );
    }

    /**
     * Get distributions for a civil servant.
     *
     * @param int $user_id Civil servant user ID.
     * @return array
     */
    public static function get_for_user( $user_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dlga_distributions
             WHERE civil_servant_id = %d ORDER BY distribution_month DESC",
            $user_id
        ) );
    }

    /**
     * Get distribution summary for a month.
     *
     * @param string $month Month in Y-m format.
     * @return object|null
     */
    public static function get_month_summary( $month = '' ) {
        global $wpdb;

        if ( empty( $month ) ) {
            $month = gmdate( 'Y-m' );
        }

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(*) as recipient_count,
                COALESCE(SUM(amount), 0) as total_distributed,
                COALESCE(AVG(amount), 0) as avg_per_person
             FROM {$wpdb->prefix}dlga_distributions
             WHERE distribution_month = %s",
            $month
        ) );
    }
}
