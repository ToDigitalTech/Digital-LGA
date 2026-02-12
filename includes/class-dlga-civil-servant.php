<?php
/**
 * Civil servant user functionality.
 *
 * Handles civil servant registration, service type management, and dashboard.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Civil_Servant {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_types' ) );
        add_action( 'init', array( __CLASS__, 'handle_registration' ) );
        add_shortcode( 'dlga_register_civil_servant', array( __CLASS__, 'registration_form_shortcode' ) );
        add_shortcode( 'dlga_civil_servant_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
    }

    /**
     * Register service type post type.
     */
    public static function register_post_types() {
        register_post_type( 'dlga_service_type', array(
            'labels' => array(
                'name'               => __( 'Service Types', 'digital-lga' ),
                'singular_name'      => __( 'Service Type', 'digital-lga' ),
                'add_new_item'       => __( 'Add New Service Type', 'digital-lga' ),
                'edit_item'          => __( 'Edit Service Type', 'digital-lga' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'supports'     => array( 'title' ),
            'capability_type' => 'post',
        ) );
    }

    /**
     * Render civil servant registration form.
     *
     * @return string
     */
    public static function registration_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already registered.', 'digital-lga' ) . '</p>';
        }

        if ( isset( $_GET['registered'] ) && '1' === $_GET['registered'] ) {
            return '<div class="dlga-notice dlga-notice-success">'
                . esc_html__( 'Registration submitted! Your account is pending verification.', 'digital-lga' )
                . '</div>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/registration/civil-servant.php';
        return ob_get_clean();
    }

    /**
     * Handle civil servant registration.
     */
    public static function handle_registration() {
        if ( ! isset( $_POST['dlga_register_civil_servant_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_register_civil_servant_nonce'], 'dlga_register_civil_servant' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        $full_name    = sanitize_text_field( $_POST['full_name'] );
        $email        = sanitize_email( $_POST['email'] );
        $password     = $_POST['password'];
        $phone        = sanitize_text_field( $_POST['phone'] );
        $lga          = sanitize_text_field( $_POST['lga_service'] );
        $service_type = sanitize_text_field( $_POST['service_type'] );
        $badge_number = sanitize_text_field( $_POST['badge_number'] );
        $station      = sanitize_text_field( $_POST['station'] );
        $years        = intval( $_POST['years_of_service'] );
        $bank_name    = sanitize_text_field( $_POST['bank_name'] );
        $account_num  = sanitize_text_field( $_POST['account_number'] );
        $account_name = sanitize_text_field( $_POST['account_name'] );

        if ( empty( $full_name ) || empty( $email ) || empty( $password ) || empty( $service_type ) || empty( $badge_number ) ) {
            wp_die( esc_html__( 'Please fill in all required fields.', 'digital-lga' ) );
        }

        if ( email_exists( $email ) ) {
            wp_die( esc_html__( 'This email is already registered.', 'digital-lga' ) );
        }

        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_die( $user_id->get_error_message() );
        }

        $user = new WP_User( $user_id );
        $user->set_role( 'dlga_civil_servant' );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $full_name,
            'first_name'   => $full_name,
        ) );

        update_user_meta( $user_id, 'dlga_phone', $phone );
        update_user_meta( $user_id, 'dlga_lga', $lga );
        update_user_meta( $user_id, 'dlga_service_type', $service_type );
        update_user_meta( $user_id, 'dlga_badge_number', $badge_number );
        update_user_meta( $user_id, 'dlga_station', $station );
        update_user_meta( $user_id, 'dlga_years_of_service', $years );
        update_user_meta( $user_id, 'dlga_bank_name', $bank_name );
        update_user_meta( $user_id, 'dlga_account_number', $account_num );
        update_user_meta( $user_id, 'dlga_account_name', $account_name );
        update_user_meta( $user_id, 'dlga_verification_status', 'pending' );
        update_user_meta( $user_id, 'dlga_approval_status', 'pending' );

        // Handle file uploads
        if ( ! empty( $_FILES['government_id']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attach_id = media_handle_upload( 'government_id', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_government_id_doc', $attach_id );
            }
        }

        if ( ! empty( $_FILES['badge_photo']['name'] ) ) {
            $attach_id = media_handle_upload( 'badge_photo', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_badge_photo', $attach_id );
            }
        }

        if ( ! empty( $_FILES['employment_proof']['name'] ) ) {
            $attach_id = media_handle_upload( 'employment_proof', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_employment_proof', $attach_id );
            }
        }

        // Notify admin
        $admin_email = get_option( 'admin_email' );
        wp_mail(
            $admin_email,
            sprintf( __( 'New Civil Servant Registration: %s', 'digital-lga' ), $full_name ),
            sprintf(
                __( "A new civil servant has registered:\n\nName: %s\nService: %s\nBadge: %s\nStation: %s\n\nPlease verify and approve.", 'digital-lga' ),
                $full_name,
                $service_type,
                $badge_number,
                $station
            )
        );

        wp_redirect( add_query_arg( 'registered', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Render civil servant dashboard.
     *
     * @return string
     */
    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to access your dashboard.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_civil_servant', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            return '<p>' . esc_html__( 'This dashboard is for registered civil servants only.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/dashboard/civil-servant-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Get enabled service types.
     *
     * @param bool $pool_only Only return types with pool access.
     * @return array
     */
    public static function get_service_types( $pool_only = false ) {
        $args = array(
            'post_type'      => 'dlga_service_type',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( $pool_only ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_dlga_pool_access',
                    'value' => '1',
                ),
            );
        }

        $posts = get_posts( $args );
        $types = array();

        foreach ( $posts as $post ) {
            $types[] = array(
                'id'          => $post->ID,
                'name'        => $post->post_title,
                'slug'        => $post->post_name,
                'pool_access' => (bool) get_post_meta( $post->ID, '_dlga_pool_access', true ),
                'description' => get_post_meta( $post->ID, '_dlga_description', true ),
            );
        }

        // Fallback defaults if no service types configured
        if ( empty( $types ) ) {
            $types = self::get_default_service_types();
        }

        return $types;
    }

    /**
     * Get default service types.
     *
     * @return array
     */
    public static function get_default_service_types() {
        return array(
            array( 'id' => 0, 'name' => __( 'Police Officer', 'digital-lga' ), 'slug' => 'police_officer', 'pool_access' => true ),
            array( 'id' => 0, 'name' => __( 'Fire Service Officer', 'digital-lga' ), 'slug' => 'fire_service', 'pool_access' => true ),
            array( 'id' => 0, 'name' => __( 'Sanitation Worker', 'digital-lga' ), 'slug' => 'sanitation_worker', 'pool_access' => true ),
            array( 'id' => 0, 'name' => __( 'Platform Committee', 'digital-lga' ), 'slug' => 'platform_committee', 'pool_access' => true ),
            array( 'id' => 0, 'name' => __( 'Hospital Doctor', 'digital-lga' ), 'slug' => 'hospital_doctor', 'pool_access' => false ),
            array( 'id' => 0, 'name' => __( 'Teacher', 'digital-lga' ), 'slug' => 'teacher', 'pool_access' => false ),
        );
    }

    /**
     * Count verified civil servants, optionally by service type.
     *
     * @param string|null $service_type Optional service type filter.
     * @return int
     */
    public static function count_verified( $service_type = null ) {
        $args = array(
            'role'       => 'dlga_civil_servant',
            'meta_query' => array(
                array(
                    'key'   => 'dlga_verification_status',
                    'value' => 'verified',
                ),
            ),
            'count_total' => true,
            'fields'      => 'ID',
        );

        if ( $service_type ) {
            $args['meta_query'][] = array(
                'key'   => 'dlga_service_type',
                'value' => $service_type,
            );
        }

        $query = new WP_User_Query( $args );
        return $query->get_total();
    }

    /**
     * Get verified civil servants eligible for pool distribution.
     *
     * @return array
     */
    public static function get_pool_eligible() {
        $pool_types = self::get_service_types( true );
        $type_slugs = wp_list_pluck( $pool_types, 'slug' );

        if ( empty( $type_slugs ) ) {
            // Use names as slugs for default types
            $type_slugs = array( 'police_officer', 'fire_service', 'sanitation_worker', 'platform_committee' );
        }

        $args = array(
            'role'       => 'dlga_civil_servant',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'   => 'dlga_verification_status',
                    'value' => 'verified',
                ),
                array(
                    'key'     => 'dlga_service_type',
                    'value'   => $type_slugs,
                    'compare' => 'IN',
                ),
            ),
        );

        $query = new WP_User_Query( $args );
        return $query->get_results();
    }
}
