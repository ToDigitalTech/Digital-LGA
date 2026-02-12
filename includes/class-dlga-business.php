<?php
/**
 * Business user functionality.
 *
 * Handles business registration, worker management, and dashboard.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Business {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_registration' ) );
        add_action( 'init', array( __CLASS__, 'handle_add_worker' ) );
        add_shortcode( 'dlga_register_business', array( __CLASS__, 'registration_form_shortcode' ) );
        add_shortcode( 'dlga_business_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
    }

    /**
     * Render business registration form.
     *
     * @return string
     */
    public static function registration_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already registered. Visit your dashboard.', 'digital-lga' ) . '</p>';
        }

        if ( isset( $_GET['registered'] ) && '1' === $_GET['registered'] ) {
            return '<div class="dlga-notice dlga-notice-success">'
                . esc_html__( 'Registration submitted successfully! Your account is pending admin approval.', 'digital-lga' )
                . '</div>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/registration/business.php';
        return ob_get_clean();
    }

    /**
     * Handle business registration form submission.
     */
    public static function handle_registration() {
        if ( ! isset( $_POST['dlga_register_business_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_register_business_nonce'], 'dlga_register_business' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        $email        = sanitize_email( $_POST['email'] );
        $password     = $_POST['password'];
        $company_name = sanitize_text_field( $_POST['company_name'] );
        $cac_number   = sanitize_text_field( $_POST['cac_number'] );
        $tax_id       = sanitize_text_field( $_POST['tax_id'] );
        $industry     = sanitize_text_field( $_POST['industry_type'] );
        $address      = sanitize_textarea_field( $_POST['company_address'] );
        $phone        = sanitize_text_field( $_POST['contact_phone'] );
        $is_public    = isset( $_POST['is_public_sector'] ) ? 1 : 0;
        $bank_name    = sanitize_text_field( $_POST['bank_name'] );
        $account_num  = sanitize_text_field( $_POST['account_number'] );
        $account_name = sanitize_text_field( $_POST['account_name'] );

        // Validate required fields
        if ( empty( $email ) || empty( $password ) || empty( $company_name ) || empty( $cac_number ) ) {
            wp_die( esc_html__( 'Please fill in all required fields.', 'digital-lga' ) );
        }

        if ( email_exists( $email ) ) {
            wp_die( esc_html__( 'This email is already registered.', 'digital-lga' ) );
        }

        // Create user
        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_die( $user_id->get_error_message() );
        }

        $user = new WP_User( $user_id );
        $user->set_role( 'dlga_business' );

        // Store meta
        update_user_meta( $user_id, 'dlga_company_name', $company_name );
        update_user_meta( $user_id, 'dlga_cac_number', $cac_number );
        update_user_meta( $user_id, 'dlga_tax_id', $tax_id );
        update_user_meta( $user_id, 'dlga_industry_type', $industry );
        update_user_meta( $user_id, 'dlga_company_address', $address );
        update_user_meta( $user_id, 'dlga_contact_phone', $phone );
        update_user_meta( $user_id, 'dlga_is_public_sector', $is_public );
        update_user_meta( $user_id, 'dlga_bank_name', $bank_name );
        update_user_meta( $user_id, 'dlga_account_number', $account_num );
        update_user_meta( $user_id, 'dlga_account_name', $account_name );
        update_user_meta( $user_id, 'dlga_approval_status', 'pending' );
        update_user_meta( $user_id, 'first_name', $company_name );

        // Handle file uploads
        if ( ! empty( $_FILES['cac_certificate']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attach_id = media_handle_upload( 'cac_certificate', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_cac_certificate', $attach_id );
            }
        }

        if ( ! empty( $_FILES['tax_clearance']['name'] ) ) {
            $attach_id = media_handle_upload( 'tax_clearance', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_tax_clearance', $attach_id );
            }
        }

        // Notify admin
        $admin_email = get_option( 'admin_email' );
        wp_mail(
            $admin_email,
            sprintf( __( 'New Business Registration: %s', 'digital-lga' ), $company_name ),
            sprintf(
                __( "A new business has registered on Digital LGA:\n\nCompany: %s\nCAC: %s\nEmail: %s\n\nPlease review and approve in the admin panel.", 'digital-lga' ),
                $company_name,
                $cac_number,
                $email
            )
        );

        wp_redirect( add_query_arg( 'registered', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Render business dashboard.
     *
     * @return string
     */
    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to access your dashboard.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            return '<p>' . esc_html__( 'This dashboard is for registered businesses only.', 'digital-lga' ) . '</p>';
        }

        $approval = get_user_meta( $user->ID, 'dlga_approval_status', true );
        if ( 'approved' !== $approval && ! current_user_can( 'administrator' ) ) {
            return '<div class="dlga-notice dlga-notice-warning">'
                . esc_html__( 'Your account is pending admin approval. You will be notified when approved.', 'digital-lga' )
                . '</div>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/dashboard/business-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Handle adding a worker to a business.
     */
    public static function handle_add_worker() {
        if ( ! isset( $_POST['dlga_add_worker_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_add_worker_nonce'], 'dlga_add_worker' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'digital-lga' ) );
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $worker_name   = sanitize_text_field( $_POST['worker_name'] );
        $worker_email  = sanitize_email( $_POST['worker_email'] );
        $worker_phone  = sanitize_text_field( $_POST['worker_phone'] );
        $worker_salary = floatval( $_POST['worker_salary'] );

        if ( empty( $worker_name ) || empty( $worker_email ) || $worker_salary <= 0 ) {
            wp_die( esc_html__( 'Please fill in all required fields.', 'digital-lga' ) );
        }

        $workers = get_user_meta( $user->ID, 'dlga_workers', true );
        if ( ! is_array( $workers ) ) {
            $workers = array();
        }

        $workers[] = array(
            'name'   => $worker_name,
            'email'  => $worker_email,
            'phone'  => $worker_phone,
            'salary' => $worker_salary,
        );

        update_user_meta( $user->ID, 'dlga_workers', $workers );

        wp_redirect( add_query_arg( 'worker_added', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Get workers for a business.
     *
     * @param int $business_id User ID.
     * @return array
     */
    public static function get_workers( $business_id ) {
        $workers = get_user_meta( $business_id, 'dlga_workers', true );
        return is_array( $workers ) ? $workers : array();
    }

    /**
     * Check if a company is blacklisted.
     *
     * @param int $company_id User ID.
     * @return bool
     */
    public static function is_blacklisted( $company_id ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dlga_blacklist WHERE company_id = %d",
            $company_id
        ) );
    }
}
