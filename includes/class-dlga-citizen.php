<?php
/**
 * Citizen user functionality.
 *
 * Handles citizen registration, job idea submission, and project verification.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Citizen {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_types' ) );
        add_action( 'init', array( __CLASS__, 'handle_registration' ) );
        add_action( 'init', array( __CLASS__, 'handle_job_idea_submission' ) );
        add_action( 'init', array( __CLASS__, 'handle_verification_submission' ) );
        add_shortcode( 'dlga_register_citizen', array( __CLASS__, 'registration_form_shortcode' ) );
        add_shortcode( 'dlga_citizen_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
        add_shortcode( 'dlga_submit_job_idea', array( __CLASS__, 'job_idea_form_shortcode' ) );
    }

    /**
     * Register custom post types.
     */
    public static function register_post_types() {
        register_post_type( 'dlga_job_idea', array(
            'labels' => array(
                'name'               => __( 'Job Ideas', 'digital-lga' ),
                'singular_name'      => __( 'Job Idea', 'digital-lga' ),
                'add_new_item'       => __( 'Add New Job Idea', 'digital-lga' ),
                'edit_item'          => __( 'Edit Job Idea', 'digital-lga' ),
                'view_item'          => __( 'View Job Idea', 'digital-lga' ),
                'search_items'       => __( 'Search Job Ideas', 'digital-lga' ),
                'not_found'          => __( 'No job ideas found', 'digital-lga' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'supports'     => array( 'title', 'editor', 'author' ),
            'capability_type' => 'post',
        ) );
    }

    /**
     * Render citizen registration form.
     *
     * @return string
     */
    public static function registration_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already registered.', 'digital-lga' ) . '</p>';
        }

        if ( isset( $_GET['registered'] ) && '1' === $_GET['registered'] ) {
            return '<div class="dlga-notice dlga-notice-success">'
                . esc_html__( 'Registration successful! You can now log in.', 'digital-lga' )
                . '</div>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/registration/citizen.php';
        return ob_get_clean();
    }

    /**
     * Handle citizen registration.
     */
    public static function handle_registration() {
        if ( ! isset( $_POST['dlga_register_citizen_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_register_citizen_nonce'], 'dlga_register_citizen' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        $full_name = sanitize_text_field( $_POST['full_name'] );
        $email     = sanitize_email( $_POST['email'] );
        $password  = $_POST['password'];
        $phone     = sanitize_text_field( $_POST['phone'] );
        $lga       = sanitize_text_field( $_POST['lga_residence'] );
        $id_type   = sanitize_text_field( $_POST['id_type'] );
        $id_number = sanitize_text_field( $_POST['id_number'] );

        if ( empty( $full_name ) || empty( $email ) || empty( $password ) ) {
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
        $user->set_role( 'dlga_citizen' );

        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $full_name,
            'first_name'   => $full_name,
        ) );

        update_user_meta( $user_id, 'dlga_phone', $phone );
        update_user_meta( $user_id, 'dlga_lga', $lga );
        update_user_meta( $user_id, 'dlga_id_type', $id_type );
        update_user_meta( $user_id, 'dlga_id_number', $id_number );
        update_user_meta( $user_id, 'dlga_approval_status', 'approved' );

        // Handle ID photo upload
        if ( ! empty( $_FILES['id_photo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attach_id = media_handle_upload( 'id_photo', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                update_user_meta( $user_id, 'dlga_id_photo', $attach_id );
            }
        }

        wp_redirect( add_query_arg( 'registered', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Render citizen dashboard.
     *
     * @return string
     */
    public static function dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to access your dashboard.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_citizen', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            return '<p>' . esc_html__( 'This dashboard is for registered citizens only.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/dashboard/citizen-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Render job idea submission form.
     *
     * @return string
     */
    public static function job_idea_form_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to submit an infrastructure idea.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_citizen', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
            return '<p>' . esc_html__( 'Only registered citizens can submit ideas.', 'digital-lga' ) . '</p>';
        }

        if ( isset( $_GET['idea_submitted'] ) && '1' === $_GET['idea_submitted'] ) {
            return '<div class="dlga-notice dlga-notice-success">'
                . esc_html__( 'Your idea has been submitted! The committee will review it within 3 days.', 'digital-lga' )
                . '</div>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/citizen/submit-job-idea.php';
        return ob_get_clean();
    }

    /**
     * Handle job idea submission.
     */
    public static function handle_job_idea_submission() {
        if ( ! isset( $_POST['dlga_idea_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_idea_nonce'], 'dlga_submit_idea' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'digital-lga' ) );
        }

        $title          = sanitize_text_field( $_POST['idea_title'] );
        $description    = sanitize_textarea_field( $_POST['idea_description'] );
        $location       = sanitize_text_field( $_POST['idea_location'] );
        $category       = sanitize_text_field( $_POST['idea_category'] );
        $urgency        = sanitize_text_field( $_POST['urgency'] );
        $estimated_cost = floatval( $_POST['estimated_cost'] );

        if ( empty( $title ) || empty( $description ) || empty( $location ) ) {
            wp_die( esc_html__( 'Please fill in all required fields.', 'digital-lga' ) );
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $description,
            'post_type'    => 'dlga_job_idea',
            'post_status'  => 'pending',
            'post_author'  => get_current_user_id(),
        ) );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            wp_die( esc_html__( 'Failed to submit idea. Please try again.', 'digital-lga' ) );
        }

        update_post_meta( $post_id, '_dlga_location', $location );
        update_post_meta( $post_id, '_dlga_category', $category );
        update_post_meta( $post_id, '_dlga_urgency', $urgency );
        update_post_meta( $post_id, '_dlga_estimated_cost', $estimated_cost );
        update_post_meta( $post_id, '_dlga_status', 'pending_review' );

        // Handle photo uploads
        if ( ! empty( $_FILES['idea_photos'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $photo_ids = array();
            $files     = $_FILES['idea_photos'];

            for ( $i = 0; $i < count( $files['name'] ) && $i < 5; $i++ ) {
                if ( $files['error'][ $i ] === 0 ) {
                    $_FILES['idea_photo_single'] = array(
                        'name'     => $files['name'][ $i ],
                        'type'     => $files['type'][ $i ],
                        'tmp_name' => $files['tmp_name'][ $i ],
                        'error'    => $files['error'][ $i ],
                        'size'     => $files['size'][ $i ],
                    );

                    $attach_id = media_handle_upload( 'idea_photo_single', $post_id );
                    if ( ! is_wp_error( $attach_id ) ) {
                        $photo_ids[] = $attach_id;
                    }
                }
            }

            update_post_meta( $post_id, '_dlga_photos', $photo_ids );
        }

        // Notify committee
        $committee_users = get_users( array(
            'role__in' => array( 'dlga_reviewer', 'administrator' ),
            'fields'   => array( 'user_email' ),
        ) );

        foreach ( $committee_users as $cu ) {
            wp_mail(
                $cu->user_email,
                sprintf( __( '[Digital LGA] New Job Idea: %s', 'digital-lga' ), $title ),
                sprintf(
                    __( "A citizen has submitted a new infrastructure idea:\n\nTitle: %s\nLocation: %s\nUrgency: %s\n\nReview: %s", 'digital-lga' ),
                    $title,
                    $location,
                    $urgency,
                    admin_url( "post.php?post={$post_id}&action=edit" )
                )
            );
        }

        wp_redirect( add_query_arg( 'idea_submitted', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle citizen verification of a project.
     */
    public static function handle_verification_submission() {
        if ( ! isset( $_POST['dlga_verify_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_verify_nonce'], 'dlga_verify_project' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'digital-lga' ) );
        }

        global $wpdb;

        $tender_id = intval( $_POST['tender_id'] );
        $rating    = intval( $_POST['rating'] );
        $comment   = sanitize_textarea_field( $_POST['verification_comment'] );
        $type      = sanitize_text_field( $_POST['verification_type'] );
        $citizen_id = get_current_user_id();

        $rating = max( 1, min( 5, $rating ) );

        $photo_ids = array();
        if ( ! empty( $_FILES['verification_photos'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $files = $_FILES['verification_photos'];
            for ( $i = 0; $i < count( $files['name'] ) && $i < 3; $i++ ) {
                if ( $files['error'][ $i ] === 0 ) {
                    $_FILES['verify_photo_single'] = array(
                        'name'     => $files['name'][ $i ],
                        'type'     => $files['type'][ $i ],
                        'tmp_name' => $files['tmp_name'][ $i ],
                        'error'    => $files['error'][ $i ],
                        'size'     => $files['size'][ $i ],
                    );

                    $attach_id = media_handle_upload( 'verify_photo_single', $tender_id );
                    if ( ! is_wp_error( $attach_id ) ) {
                        $photo_ids[] = $attach_id;
                    }
                }
            }
        }

        $wpdb->insert(
            "{$wpdb->prefix}dlga_verifications",
            array(
                'tender_id'         => $tender_id,
                'citizen_id'        => $citizen_id,
                'verification_type' => $type,
                'rating'            => $rating,
                'comment'           => $comment,
                'photos'            => wp_json_encode( $photo_ids ),
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
        );

        wp_redirect( add_query_arg( 'verified', '1', wp_get_referer() ) );
        exit;
    }
}
