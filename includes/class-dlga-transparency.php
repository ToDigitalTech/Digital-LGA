<?php
/**
 * Transparency dashboard.
 *
 * Public-facing dashboard showing all platform data.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Transparency {

    public static function init() {
        add_shortcode( 'dlga_transparency', array( __CLASS__, 'dashboard_shortcode' ) );
    }

    /**
     * Render the transparency dashboard.
     *
     * @return string
     */
    public static function dashboard_shortcode() {
        $data = self::get_dashboard_data();

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/transparency.php';
        return ob_get_clean();
    }

    /**
     * Get all dashboard data.
     *
     * @return array
     */
    public static function get_dashboard_data() {
        global $wpdb;

        $current_month = gmdate( 'Y-m' );

        // Fund pools
        $pools = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dlga_fund_pools",
            OBJECT_K
        );

        // This month's collection
        $monthly_collected = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(total_contribution), 0) FROM {$wpdb->prefix}dlga_payrolls
             WHERE pay_period = %s AND payment_status = 'completed'",
            $current_month
        ) );

        // Worker count
        $worker_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT worker_email) FROM {$wpdb->prefix}dlga_payrolls
             WHERE pay_period = %s AND payment_status = 'completed'",
            $current_month
        ) );

        // Business count
        $business_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT business_id) FROM {$wpdb->prefix}dlga_payrolls
             WHERE pay_period = %s AND payment_status = 'completed'",
            $current_month
        ) );

        // Civil servants count
        $civil_servants = DLGA_Civil_Servant::count_verified();

        // Active projects
        $active_projects = get_posts( array(
            'post_type'      => 'dlga_tender',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_dlga_tender_status',
                    'value'   => array( DLGA_Tender::STATUS_AWARDED, DLGA_Tender::STATUS_IN_PROGRESS ),
                    'compare' => 'IN',
                ),
            ),
        ) );

        // Completed projects (this month)
        $completed_this_month = get_posts( array(
            'post_type'      => 'dlga_tender',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_dlga_tender_status',
                    'value' => DLGA_Tender::STATUS_COMPLETED,
                ),
                array(
                    'key'     => '_dlga_completed_date',
                    'value'   => array( $current_month . '-01', $current_month . '-31' ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        ) );

        // All completed projects
        $all_completed = get_posts( array(
            'post_type'      => 'dlga_tender',
            'posts_per_page' => 20,
            'meta_query'     => array(
                array(
                    'key'   => '_dlga_tender_status',
                    'value' => DLGA_Tender::STATUS_COMPLETED,
                ),
            ),
            'orderby'  => 'meta_value',
            'meta_key' => '_dlga_completed_date',
            'order'    => 'DESC',
        ) );

        // Top companies
        $top_companies = $wpdb->get_results(
            "SELECT cs.*, u.display_name,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = cs.company_id AND meta_key = 'dlga_company_name') as company_name
             FROM {$wpdb->prefix}dlga_company_stats cs
             JOIN {$wpdb->users} u ON cs.company_id = u.ID
             WHERE cs.completed_projects > 0
             ORDER BY cs.average_rating DESC, cs.completed_projects DESC
             LIMIT 10"
        );

        // All-time totals
        $all_time_collected = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(total_contribution), 0) FROM {$wpdb->prefix}dlga_payrolls
             WHERE payment_status = 'completed'"
        );

        $all_time_projects = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_tender_status' AND meta_value = %s",
            DLGA_Tender::STATUS_COMPLETED
        ) );

        // Monthly history (last 12 months)
        $monthly_history = $wpdb->get_results(
            "SELECT pay_period,
                    SUM(total_contribution) as total,
                    COUNT(DISTINCT business_id) as businesses,
                    COUNT(DISTINCT worker_email) as workers
             FROM {$wpdb->prefix}dlga_payrolls
             WHERE payment_status = 'completed'
             GROUP BY pay_period
             ORDER BY pay_period DESC
             LIMIT 12"
        );

        // Distribution summary by service type
        $dist_summary = $wpdb->get_results( $wpdb->prepare(
            "SELECT service_type, COUNT(*) as count, SUM(amount) as total
             FROM {$wpdb->prefix}dlga_distributions
             WHERE distribution_month = %s
             GROUP BY service_type",
            gmdate( 'Y-m', strtotime( 'last month' ) )
        ) );

        return array(
            'pools'                => $pools,
            'monthly_collected'    => $monthly_collected,
            'worker_count'         => $worker_count,
            'business_count'       => $business_count,
            'civil_servants'       => $civil_servants,
            'active_projects'      => $active_projects,
            'completed_this_month' => $completed_this_month,
            'all_completed'        => $all_completed,
            'top_companies'        => $top_companies,
            'all_time_collected'   => $all_time_collected,
            'all_time_projects'    => $all_time_projects,
            'monthly_history'      => $monthly_history,
            'dist_summary'         => $dist_summary,
            'current_month'        => $current_month,
            'lga_name'             => DLGA_Settings::get( 'dlga_lga_name', 'LGA' ),
        );
    }
}
