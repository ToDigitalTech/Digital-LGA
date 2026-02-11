<?php
/**
 * Tender system.
 *
 * Manages infrastructure tenders, bids, and project lifecycle.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Tender {

    const STATUS_DRAFT          = 'draft';
    const STATUS_OPEN           = 'open_bidding';
    const STATUS_VETTING        = 'vetting';
    const STATUS_AWARDED        = 'awarded';
    const STATUS_IN_PROGRESS    = 'in_progress';
    const STATUS_COMPLETED      = 'completed';
    const STATUS_FAILED         = 'failed';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_types' ) );
        add_action( 'init', array( __CLASS__, 'handle_bid_submission' ) );
        add_action( 'init', array( __CLASS__, 'handle_progress_update' ) );
        add_shortcode( 'dlga_tenders', array( __CLASS__, 'listing_shortcode' ) );
        add_shortcode( 'dlga_single_tender', array( __CLASS__, 'single_tender_shortcode' ) );
        add_shortcode( 'dlga_submit_bid', array( __CLASS__, 'bid_form_shortcode' ) );
        add_shortcode( 'dlga_company_profile', array( __CLASS__, 'company_profile_shortcode' ) );
    }

    /**
     * Register custom post types for tenders and bids.
     */
    public static function register_post_types() {
        // Tender post type
        register_post_type( 'dlga_tender', array(
            'labels' => array(
                'name'               => __( 'Tenders', 'digital-lga' ),
                'singular_name'      => __( 'Tender', 'digital-lga' ),
                'add_new_item'       => __( 'Add New Tender', 'digital-lga' ),
                'edit_item'          => __( 'Edit Tender', 'digital-lga' ),
                'view_item'          => __( 'View Tender', 'digital-lga' ),
                'search_items'       => __( 'Search Tenders', 'digital-lga' ),
                'not_found'          => __( 'No tenders found', 'digital-lga' ),
            ),
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'dlga/tenders' ),
            'show_in_menu' => false,
            'supports'     => array( 'title', 'editor', 'author', 'thumbnail' ),
            'capability_type' => 'post',
        ) );

        // Bid post type
        register_post_type( 'dlga_bid', array(
            'labels' => array(
                'name'          => __( 'Bids', 'digital-lga' ),
                'singular_name' => __( 'Bid', 'digital-lga' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'supports'     => array( 'title', 'editor', 'author' ),
            'capability_type' => 'post',
        ) );

        // Project category taxonomy
        register_taxonomy( 'dlga_project_category', 'dlga_tender', array(
            'labels' => array(
                'name'          => __( 'Project Categories', 'digital-lga' ),
                'singular_name' => __( 'Project Category', 'digital-lga' ),
            ),
            'hierarchical' => true,
            'public'       => true,
            'rewrite'      => array( 'slug' => 'dlga/category' ),
        ) );

        // Insert default categories
        if ( ! get_option( 'dlga_default_categories_created' ) ) {
            $defaults = array(
                'Road Maintenance',
                'Street Lighting',
                'Drainage Systems',
                'Public Buildings',
                'Emergency Infrastructure',
                'Waste Management',
                'Public Spaces',
            );
            foreach ( $defaults as $cat ) {
                if ( ! term_exists( $cat, 'dlga_project_category' ) ) {
                    wp_insert_term( $cat, 'dlga_project_category' );
                }
            }
            update_option( 'dlga_default_categories_created', '1' );
        }
    }

    /**
     * Render tender listing.
     *
     * @return string
     */
    public static function listing_shortcode() {
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $category      = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';

        $args = array(
            'post_type'      => 'dlga_tender',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => max( 1, get_query_var( 'paged' ) ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( ! empty( $status_filter ) ) {
            $args['meta_query'][] = array(
                'key'   => '_dlga_tender_status',
                'value' => $status_filter,
            );
        }

        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'dlga_project_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $args );

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/tenders/listing.php';
        return ob_get_clean();
    }

    /**
     * Render single tender.
     *
     * @return string
     */
    public static function single_tender_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts );

        $tender_id = intval( $atts['id'] );
        if ( ! $tender_id && is_singular( 'dlga_tender' ) ) {
            $tender_id = get_the_ID();
        }

        if ( ! $tender_id ) {
            return '<p>' . esc_html__( 'Tender not found.', 'digital-lga' ) . '</p>';
        }

        $tender = get_post( $tender_id );
        if ( ! $tender || 'dlga_tender' !== $tender->post_type ) {
            return '<p>' . esc_html__( 'Tender not found.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/tenders/single-tender.php';
        return ob_get_clean();
    }

    /**
     * Render bid submission form.
     *
     * @return string
     */
    public static function bid_form_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'tender_id' => 0 ), $atts );

        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to submit a bid.', 'digital-lga' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) ) {
            return '<p>' . esc_html__( 'Only registered businesses can submit bids.', 'digital-lga' ) . '</p>';
        }

        $is_public = get_user_meta( $user->ID, 'dlga_is_public_sector', true );
        if ( ! $is_public ) {
            return '<p>' . esc_html__( 'Only Public Sector Businesses can submit bids.', 'digital-lga' ) . '</p>';
        }

        if ( DLGA_Business::is_blacklisted( $user->ID ) ) {
            return '<div class="dlga-notice dlga-notice-error">'
                . esc_html__( 'Your company has been blacklisted and cannot submit bids.', 'digital-lga' )
                . '</div>';
        }

        $tender_id = intval( $atts['tender_id'] );
        if ( ! $tender_id ) {
            $tender_id = isset( $_GET['tender_id'] ) ? intval( $_GET['tender_id'] ) : 0;
        }

        if ( ! $tender_id ) {
            return '<p>' . esc_html__( 'No tender specified.', 'digital-lga' ) . '</p>';
        }

        $status = get_post_meta( $tender_id, '_dlga_tender_status', true );
        if ( self::STATUS_OPEN !== $status ) {
            return '<p>' . esc_html__( 'This tender is not currently accepting bids.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/tenders/submit-bid.php';
        return ob_get_clean();
    }

    /**
     * Handle bid submission.
     */
    public static function handle_bid_submission() {
        if ( ! isset( $_POST['dlga_bid_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_bid_nonce'], 'dlga_submit_bid' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in.', 'digital-lga' ) );
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'dlga_business', (array) $user->roles, true ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $tender_id     = intval( $_POST['tender_id'] );
        $proposed_cost = floatval( $_POST['proposed_cost'] );
        $timeline      = sanitize_text_field( $_POST['proposed_timeline'] );
        $proposal      = sanitize_textarea_field( $_POST['technical_proposal'] );
        $team_details  = sanitize_textarea_field( $_POST['team_details'] );

        $budget = floatval( get_post_meta( $tender_id, '_dlga_budget', true ) );
        if ( $proposed_cost > $budget ) {
            wp_die( esc_html__( 'Proposed cost cannot exceed the allocated budget.', 'digital-lga' ) );
        }

        $company_name = get_user_meta( $user->ID, 'dlga_company_name', true );

        $bid_id = wp_insert_post( array(
            'post_title'   => sprintf( '%s - %s', $company_name, get_the_title( $tender_id ) ),
            'post_content' => $proposal,
            'post_type'    => 'dlga_bid',
            'post_status'  => 'publish',
            'post_author'  => $user->ID,
        ) );

        if ( is_wp_error( $bid_id ) ) {
            wp_die( esc_html__( 'Failed to submit bid.', 'digital-lga' ) );
        }

        update_post_meta( $bid_id, '_dlga_tender_id', $tender_id );
        update_post_meta( $bid_id, '_dlga_proposed_cost', $proposed_cost );
        update_post_meta( $bid_id, '_dlga_proposed_timeline', $timeline );
        update_post_meta( $bid_id, '_dlga_team_details', $team_details );
        update_post_meta( $bid_id, '_dlga_bid_status', 'submitted' );
        update_post_meta( $bid_id, '_dlga_bid_score', 0 );

        // Handle file uploads
        if ( ! empty( $_FILES['portfolio'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $file_ids = array();
            $files    = $_FILES['portfolio'];

            for ( $i = 0; $i < count( $files['name'] ) && $i < 10; $i++ ) {
                if ( $files['error'][ $i ] === 0 ) {
                    $_FILES['portfolio_single'] = array(
                        'name'     => $files['name'][ $i ],
                        'type'     => $files['type'][ $i ],
                        'tmp_name' => $files['tmp_name'][ $i ],
                        'error'    => $files['error'][ $i ],
                        'size'     => $files['size'][ $i ],
                    );

                    $attach_id = media_handle_upload( 'portfolio_single', $bid_id );
                    if ( ! is_wp_error( $attach_id ) ) {
                        $file_ids[] = $attach_id;
                    }
                }
            }

            update_post_meta( $bid_id, '_dlga_portfolio', $file_ids );
        }

        // Handle license upload
        if ( ! empty( $_FILES['company_license']['name'] ) ) {
            $attach_id = media_handle_upload( 'company_license', $bid_id );
            if ( ! is_wp_error( $attach_id ) ) {
                update_post_meta( $bid_id, '_dlga_company_license', $attach_id );
            }
        }

        // Notify committee
        $committee = get_users( array(
            'role__in' => array( 'dlga_vetter', 'dlga_reviewer', 'administrator' ),
            'fields'   => array( 'user_email' ),
        ) );

        foreach ( $committee as $member ) {
            wp_mail(
                $member->user_email,
                sprintf( __( '[Digital LGA] New Bid: %s', 'digital-lga' ), get_the_title( $tender_id ) ),
                sprintf(
                    __( "Company: %s\nProposed Cost: %s\nTimeline: %s\n\nReview: %s", 'digital-lga' ),
                    $company_name,
                    DLGA_Settings::format_amount( $proposed_cost ),
                    $timeline,
                    admin_url( "post.php?post={$bid_id}&action=edit" )
                )
            );
        }

        wp_redirect( add_query_arg( 'bid_submitted', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle company progress update.
     */
    public static function handle_progress_update() {
        if ( ! isset( $_POST['dlga_progress_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_progress_nonce'], 'dlga_progress_update' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        $tender_id = intval( $_POST['tender_id'] );
        $update    = sanitize_textarea_field( $_POST['progress_update'] );
        $user_id   = get_current_user_id();

        // Store as comment on the tender
        $comment_data = array(
            'comment_post_ID' => $tender_id,
            'comment_content' => $update,
            'user_id'         => $user_id,
            'comment_type'    => 'dlga_progress',
            'comment_approved' => 1,
        );

        $comment_id = wp_insert_comment( $comment_data );

        // Handle photos
        if ( ! empty( $_FILES['progress_photos'] ) && $comment_id ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $photo_ids = array();
            $files     = $_FILES['progress_photos'];

            for ( $i = 0; $i < count( $files['name'] ) && $i < 5; $i++ ) {
                if ( $files['error'][ $i ] === 0 ) {
                    $_FILES['progress_photo_single'] = array(
                        'name'     => $files['name'][ $i ],
                        'type'     => $files['type'][ $i ],
                        'tmp_name' => $files['tmp_name'][ $i ],
                        'error'    => $files['error'][ $i ],
                        'size'     => $files['size'][ $i ],
                    );

                    $attach_id = media_handle_upload( 'progress_photo_single', $tender_id );
                    if ( ! is_wp_error( $attach_id ) ) {
                        $photo_ids[] = $attach_id;
                    }
                }
            }

            update_comment_meta( $comment_id, '_dlga_progress_photos', $photo_ids );
        }

        wp_redirect( add_query_arg( 'progress_updated', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Get bids for a tender.
     *
     * @param int  $tender_id Tender post ID.
     * @param bool $public    Only return public (vetting phase) bids.
     * @return array
     */
    public static function get_bids( $tender_id, $public = false ) {
        $args = array(
            'post_type'      => 'dlga_bid',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_dlga_tender_id',
                    'value' => $tender_id,
                ),
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_dlga_proposed_cost',
            'order'   => 'ASC',
        );

        return get_posts( $args );
    }

    /**
     * Get tender milestones.
     *
     * @param int $tender_id Tender post ID.
     * @return array
     */
    public static function get_milestones( $tender_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dlga_milestones
             WHERE tender_id = %d ORDER BY milestone_number ASC",
            $tender_id
        ) );
    }

    /**
     * Create default milestones for an awarded tender.
     *
     * @param int   $tender_id Tender post ID.
     * @param float $amount    Total project amount.
     */
    public static function create_default_milestones( $tender_id, $amount ) {
        global $wpdb;

        $milestones = array(
            array( 'number' => 1, 'title' => __( 'Project Start', 'digital-lga' ), 'pct' => 30 ),
            array( 'number' => 2, 'title' => __( '50% Completion', 'digital-lga' ), 'pct' => 40 ),
            array( 'number' => 3, 'title' => __( 'Project Completion', 'digital-lga' ), 'pct' => 30 ),
        );

        foreach ( $milestones as $ms ) {
            $wpdb->insert(
                "{$wpdb->prefix}dlga_milestones",
                array(
                    'tender_id'        => $tender_id,
                    'milestone_number' => $ms['number'],
                    'title'            => $ms['title'],
                    'percentage'       => $ms['pct'],
                    'amount'           => round( $amount * $ms['pct'] / 100, 2 ),
                    'status'           => 1 === $ms['number'] ? 'pending' : 'locked',
                    'created_at'       => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%d', '%f', '%s', '%s' )
            );
        }
    }

    /**
     * Get verifications for a tender.
     *
     * @param int $tender_id Tender post ID.
     * @return array
     */
    public static function get_verifications( $tender_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT v.*, u.display_name as citizen_name
             FROM {$wpdb->prefix}dlga_verifications v
             JOIN {$wpdb->users} u ON v.citizen_id = u.ID
             WHERE v.tender_id = %d
             ORDER BY v.created_at DESC",
            $tender_id
        ) );
    }

    /**
     * Get verification count for a tender.
     *
     * @param int $tender_id Tender post ID.
     * @return int
     */
    public static function get_verification_count( $tender_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dlga_verifications WHERE tender_id = %d",
            $tender_id
        ) );
    }

    /**
     * Get average rating for a tender.
     *
     * @param int $tender_id Tender post ID.
     * @return float
     */
    public static function get_average_rating( $tender_id ) {
        global $wpdb;
        return (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(AVG(rating), 0) FROM {$wpdb->prefix}dlga_verifications
             WHERE tender_id = %d AND rating IS NOT NULL",
            $tender_id
        ) );
    }

    /**
     * Get all status labels.
     *
     * @return array
     */
    public static function get_status_labels() {
        return array(
            self::STATUS_DRAFT       => __( 'Draft', 'digital-lga' ),
            self::STATUS_OPEN        => __( 'Open for Bidding', 'digital-lga' ),
            self::STATUS_VETTING     => __( 'Vetting Period', 'digital-lga' ),
            self::STATUS_AWARDED     => __( 'Awarded', 'digital-lga' ),
            self::STATUS_IN_PROGRESS => __( 'In Progress', 'digital-lga' ),
            self::STATUS_COMPLETED   => __( 'Completed', 'digital-lga' ),
            self::STATUS_FAILED      => __( 'Failed', 'digital-lga' ),
        );
    }

    /**
     * Company profile shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function company_profile_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        $company_id = intval( $atts['id'] );

        if ( ! $company_id ) {
            $company_id = isset( $_GET['company_id'] ) ? intval( $_GET['company_id'] ) : 0;
        }

        if ( ! $company_id ) {
            return '<p>' . esc_html__( 'Company not found.', 'digital-lga' ) . '</p>';
        }

        $user = get_userdata( $company_id );
        if ( ! $user || ! in_array( 'dlga_business', (array) $user->roles, true ) ) {
            return '<p>' . esc_html__( 'Company not found.', 'digital-lga' ) . '</p>';
        }

        ob_start();
        include DIGITAL_LGA_PATH . 'templates/company-profile.php';
        return ob_get_clean();
    }
}
