<?php
/**
 * Plugin activation and deactivation.
 *
 * Creates custom database tables and flushes rewrite rules.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::create_roles();
        self::set_default_options();
        self::register_post_types();
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Fund Pools
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_fund_pools (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            pool_type VARCHAR(50) NOT NULL,
            total_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
            monthly_collected DECIMAL(15,2) NOT NULL DEFAULT 0,
            monthly_distributed DECIMAL(15,2) NOT NULL DEFAULT 0,
            last_distribution_date DATE NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pool_type (pool_type)
        ) $charset_collate;";

        // Payroll Transactions
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_payrolls (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            business_id BIGINT UNSIGNED NOT NULL,
            worker_name VARCHAR(200) NOT NULL,
            worker_email VARCHAR(200) NOT NULL,
            worker_phone VARCHAR(50) DEFAULT '',
            gross_salary DECIMAL(15,2) NOT NULL,
            worker_contribution DECIMAL(15,2) NOT NULL,
            business_contribution DECIMAL(15,2) NOT NULL,
            total_contribution DECIMAL(15,2) NOT NULL,
            platform_fee DECIMAL(15,2) NOT NULL,
            net_to_worker DECIMAL(15,2) NOT NULL,
            total_business_pays DECIMAL(15,2) NOT NULL,
            wc_order_id BIGINT UNSIGNED NULL,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            pay_period VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY business_id (business_id),
            KEY payment_status (payment_status),
            KEY pay_period (pay_period)
        ) $charset_collate;";

        // Personnel Distributions
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_distributions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            civil_servant_id BIGINT UNSIGNED NOT NULL,
            service_type VARCHAR(100) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            distribution_month VARCHAR(20) NOT NULL,
            source_payroll_count INT NOT NULL DEFAULT 0,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY civil_servant_id (civil_servant_id),
            KEY distribution_month (distribution_month),
            KEY payment_status (payment_status)
        ) $charset_collate;";

        // Tender Milestones
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_milestones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tender_id BIGINT UNSIGNED NOT NULL,
            milestone_number INT NOT NULL,
            title VARCHAR(200) NOT NULL DEFAULT '',
            percentage INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'locked',
            proof_of_work TEXT NULL,
            inspection_report TEXT NULL,
            inspector_id BIGINT UNSIGNED NULL,
            approved_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY tender_id (tender_id),
            KEY status (status)
        ) $charset_collate;";

        // Escrow
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_escrow (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tender_id BIGINT UNSIGNED NOT NULL,
            company_id BIGINT UNSIGNED NOT NULL,
            total_amount DECIMAL(15,2) NOT NULL,
            amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0,
            amount_remaining DECIMAL(15,2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tender_id (tender_id)
        ) $charset_collate;";

        // Citizen Verifications
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_verifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tender_id BIGINT UNSIGNED NOT NULL,
            citizen_id BIGINT UNSIGNED NOT NULL,
            verification_type VARCHAR(50) NOT NULL,
            rating INT NULL,
            comment TEXT NULL,
            photos TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tender_id (tender_id),
            KEY citizen_id (citizen_id)
        ) $charset_collate;";

        // Company Performance Stats
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_company_stats (
            company_id BIGINT UNSIGNED NOT NULL,
            total_projects INT NOT NULL DEFAULT 0,
            completed_projects INT NOT NULL DEFAULT 0,
            in_progress_projects INT NOT NULL DEFAULT 0,
            failed_projects INT NOT NULL DEFAULT 0,
            average_rating DECIMAL(3,2) NULL,
            total_verifications INT NOT NULL DEFAULT 0,
            on_time_delivery_rate DECIMAL(5,2) NULL,
            last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (company_id)
        ) $charset_collate;";

        // Blacklist
        $sql[] = "CREATE TABLE {$wpdb->prefix}dlga_blacklist (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id BIGINT UNSIGNED NOT NULL,
            reason TEXT NOT NULL,
            tender_id BIGINT UNSIGNED NULL,
            blacklisted_by BIGINT UNSIGNED NOT NULL,
            blacklisted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY company_id (company_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        // Insert default fund pools
        $wpdb->replace(
            "{$wpdb->prefix}dlga_fund_pools",
            array( 'pool_type' => 'personnel', 'total_balance' => 0, 'created_at' => current_time( 'mysql' ) ),
            array( '%s', '%f', '%s' )
        );
        $wpdb->replace(
            "{$wpdb->prefix}dlga_fund_pools",
            array( 'pool_type' => 'infrastructure', 'total_balance' => 0, 'created_at' => current_time( 'mysql' ) ),
            array( '%s', '%f', '%s' )
        );
        $wpdb->replace(
            "{$wpdb->prefix}dlga_fund_pools",
            array( 'pool_type' => 'emergency', 'total_balance' => 0, 'created_at' => current_time( 'mysql' ) ),
            array( '%s', '%f', '%s' )
        );

        update_option( 'dlga_db_version', DIGITAL_LGA_VERSION );
    }

    /**
     * Create custom user roles.
     */
    private static function create_roles() {
        // Business role
        add_role( 'dlga_business', __( 'DLGA Business', 'digital-lga' ), array(
            'read'         => true,
            'upload_files' => true,
        ) );

        // Citizen role
        add_role( 'dlga_citizen', __( 'DLGA Citizen', 'digital-lga' ), array(
            'read'         => true,
            'upload_files' => true,
        ) );

        // Civil Servant role
        add_role( 'dlga_civil_servant', __( 'DLGA Civil Servant', 'digital-lga' ), array(
            'read'         => true,
            'upload_files' => true,
        ) );

        // Committee Reviewer
        add_role( 'dlga_reviewer', __( 'DLGA Reviewer', 'digital-lga' ), array(
            'read'                => true,
            'upload_files'        => true,
            'edit_posts'          => true,
            'edit_published_posts' => true,
            'publish_posts'       => true,
        ) );

        // Committee Vetter
        add_role( 'dlga_vetter', __( 'DLGA Vetter', 'digital-lga' ), array(
            'read'                => true,
            'upload_files'        => true,
            'edit_posts'          => true,
            'edit_published_posts' => true,
        ) );

        // Accountability Team
        add_role( 'dlga_accountability', __( 'DLGA Accountability', 'digital-lga' ), array(
            'read'                => true,
            'upload_files'        => true,
            'edit_posts'          => true,
            'edit_published_posts' => true,
        ) );
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = array(
            'dlga_lga_name'            => 'Ikeja',
            'dlga_state'               => 'Lagos',
            'dlga_contribution_rate'   => 10,
            'dlga_worker_split'        => 50,
            'dlga_business_split'      => 50,
            'dlga_platform_fee'        => 5,
            'dlga_personnel_pool'      => 30,
            'dlga_infrastructure_pool' => 60,
            'dlga_emergency_pool'      => 10,
            'dlga_payment_gateway'     => 'paystack',
            'dlga_currency'            => 'NGN',
            'dlga_currency_symbol'     => "\xE2\x82\xA6",
            'dlga_bidding_days'        => 7,
            'dlga_vetting_days'        => 7,
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Register custom post types for rewrite rules.
     */
    private static function register_post_types() {
        // Trigger post type registration
        if ( class_exists( 'DLGA_Tender' ) ) {
            DLGA_Tender::register_post_types();
        }
        if ( class_exists( 'DLGA_Citizen' ) ) {
            DLGA_Citizen::register_post_types();
        }
    }
}
