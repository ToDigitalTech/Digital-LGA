<?php
/**
 * Committee management.
 *
 * Handles committee dashboard, bid scoring, tender awarding, and accountability.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Committee {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_idea_review' ) );
        add_action( 'init', array( __CLASS__, 'handle_bid_score' ) );
        add_action( 'init', array( __CLASS__, 'handle_award_tender' ) );
        add_action( 'init', array( __CLASS__, 'handle_milestone_approval' ) );
        add_action( 'init', array( __CLASS__, 'handle_blacklist' ) );
        add_action( 'init', array( __CLASS__, 'handle_user_approval' ) );
    }

    /**
     * Handle job idea review by committee.
     */
    public static function handle_idea_review() {
        if ( ! isset( $_POST['dlga_review_idea_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_review_idea_nonce'], 'dlga_review_idea' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'manage_dlga_tenders' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $idea_id = intval( $_POST['idea_id'] );
        $action  = sanitize_text_field( $_POST['review_action'] );

        if ( 'approve' === $action ) {
            $budget   = floatval( $_POST['tender_budget'] );
            $timeline = sanitize_text_field( $_POST['tender_timeline'] );
            $desc     = sanitize_textarea_field( $_POST['tender_description'] );
            $category = sanitize_text_field( $_POST['tender_category'] );
            $location = get_post_meta( $idea_id, '_dlga_location', true );

            $idea = get_post( $idea_id );

            // Create tender from idea
            $tender_id = wp_insert_post( array(
                'post_title'   => $idea->post_title,
                'post_content' => ! empty( $desc ) ? $desc : $idea->post_content,
                'post_type'    => 'dlga_tender',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ) );

            if ( ! is_wp_error( $tender_id ) ) {
                update_post_meta( $tender_id, '_dlga_tender_status', DLGA_Tender::STATUS_OPEN );
                update_post_meta( $tender_id, '_dlga_budget', $budget );
                update_post_meta( $tender_id, '_dlga_timeline', $timeline );
                update_post_meta( $tender_id, '_dlga_location', $location );
                update_post_meta( $tender_id, '_dlga_original_idea', $idea_id );
                update_post_meta( $tender_id, '_dlga_bidding_closes', gmdate( 'Y-m-d', strtotime( '+' . intval( DLGA_Settings::get( 'dlga_bidding_days', 7 ) ) . ' days' ) ) );

                if ( ! empty( $category ) ) {
                    wp_set_object_terms( $tender_id, $category, 'dlga_project_category' );
                }

                // Copy photos from idea
                $photos = get_post_meta( $idea_id, '_dlga_photos', true );
                if ( ! empty( $photos ) ) {
                    update_post_meta( $tender_id, '_dlga_photos', $photos );
                }

                // Update idea status
                update_post_meta( $idea_id, '_dlga_status', 'approved' );
                update_post_meta( $idea_id, '_dlga_tender_id', $tender_id );
                wp_update_post( array( 'ID' => $idea_id, 'post_status' => 'publish' ) );
            }
        } elseif ( 'reject' === $action ) {
            $reason = sanitize_textarea_field( $_POST['reject_reason'] );
            update_post_meta( $idea_id, '_dlga_status', 'rejected' );
            update_post_meta( $idea_id, '_dlga_reject_reason', $reason );

            // Notify citizen
            $idea   = get_post( $idea_id );
            $author = get_userdata( $idea->post_author );
            if ( $author ) {
                wp_mail(
                    $author->user_email,
                    sprintf( __( '[Digital LGA] Idea Update: %s', 'digital-lga' ), $idea->post_title ),
                    sprintf(
                        __( "Your idea \"%s\" was not approved at this time.\n\nReason: %s\n\nYou can submit revised ideas anytime.", 'digital-lga' ),
                        $idea->post_title,
                        $reason
                    )
                );
            }
        }

        wp_redirect( add_query_arg( 'idea_reviewed', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle bid scoring.
     */
    public static function handle_bid_score() {
        if ( ! isset( $_POST['dlga_score_bid_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_score_bid_nonce'], 'dlga_score_bid' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'manage_dlga_tenders' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $bid_id      = intval( $_POST['bid_id'] );
        $price_score = min( 40, max( 0, intval( $_POST['price_score'] ) ) );
        $time_score  = min( 20, max( 0, intval( $_POST['timeline_score'] ) ) );
        $perf_score  = min( 30, max( 0, intval( $_POST['performance_score'] ) ) );
        $tech_score  = min( 10, max( 0, intval( $_POST['technical_score'] ) ) );
        $total       = $price_score + $time_score + $perf_score + $tech_score;

        update_post_meta( $bid_id, '_dlga_price_score', $price_score );
        update_post_meta( $bid_id, '_dlga_timeline_score', $time_score );
        update_post_meta( $bid_id, '_dlga_performance_score', $perf_score );
        update_post_meta( $bid_id, '_dlga_technical_score', $tech_score );
        update_post_meta( $bid_id, '_dlga_bid_score', $total );
        update_post_meta( $bid_id, '_dlga_scored_by', get_current_user_id() );

        wp_redirect( add_query_arg( 'bid_scored', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle awarding a tender to a company.
     */
    public static function handle_award_tender() {
        if ( ! isset( $_POST['dlga_award_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_award_nonce'], 'dlga_award_tender' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'manage_dlga_tenders' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $tender_id  = intval( $_POST['tender_id'] );
        $bid_id     = intval( $_POST['winning_bid_id'] );
        $bid        = get_post( $bid_id );
        $company_id = $bid->post_author;
        $amount     = floatval( get_post_meta( $bid_id, '_dlga_proposed_cost', true ) );

        // Update tender status
        update_post_meta( $tender_id, '_dlga_tender_status', DLGA_Tender::STATUS_AWARDED );
        update_post_meta( $tender_id, '_dlga_winning_bid', $bid_id );
        update_post_meta( $tender_id, '_dlga_winning_company', $company_id );
        update_post_meta( $tender_id, '_dlga_awarded_amount', $amount );
        update_post_meta( $tender_id, '_dlga_awarded_date', current_time( 'mysql' ) );

        // Update bid status
        update_post_meta( $bid_id, '_dlga_bid_status', 'won' );

        // Mark other bids as lost
        $other_bids = get_posts( array(
            'post_type'      => 'dlga_bid',
            'posts_per_page' => -1,
            'post__not_in'   => array( $bid_id ),
            'meta_query'     => array(
                array( 'key' => '_dlga_tender_id', 'value' => $tender_id ),
            ),
        ) );

        foreach ( $other_bids as $ob ) {
            update_post_meta( $ob->ID, '_dlga_bid_status', 'lost' );
        }

        // Create milestones
        DLGA_Tender::create_default_milestones( $tender_id, $amount );

        // Create escrow
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}dlga_escrow",
            array(
                'tender_id'        => $tender_id,
                'company_id'       => $company_id,
                'total_amount'     => $amount,
                'amount_paid'      => 0,
                'amount_remaining' => $amount,
                'status'           => 'active',
                'created_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%f', '%f', '%f', '%s', '%s' )
        );

        // Deduct from infrastructure pool
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance - %f
             WHERE pool_type = 'infrastructure'",
            $amount
        ) );

        // Notify winner
        $company = get_userdata( $company_id );
        if ( $company ) {
            wp_mail(
                $company->user_email,
                sprintf( __( '[Digital LGA] Tender Awarded: %s', 'digital-lga' ), get_the_title( $tender_id ) ),
                sprintf(
                    __( "Congratulations! Your bid for \"%s\" has been selected.\n\nAwarded Amount: %s\n\nPlease log in to begin the project.", 'digital-lga' ),
                    get_the_title( $tender_id ),
                    DLGA_Settings::format_amount( $amount )
                )
            );
        }

        wp_redirect( add_query_arg( 'tender_awarded', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle milestone approval by accountability team.
     */
    public static function handle_milestone_approval() {
        if ( ! isset( $_POST['dlga_milestone_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_milestone_nonce'], 'dlga_approve_milestone' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'approve_dlga_milestones' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        global $wpdb;

        $milestone_id = intval( $_POST['milestone_id'] );
        $action       = sanitize_text_field( $_POST['milestone_action'] );
        $report       = sanitize_textarea_field( $_POST['inspection_report'] );

        $milestone = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dlga_milestones WHERE id = %d",
            $milestone_id
        ) );

        if ( ! $milestone ) {
            wp_die( esc_html__( 'Milestone not found.', 'digital-lga' ) );
        }

        if ( 'approve' === $action ) {
            $wpdb->update(
                "{$wpdb->prefix}dlga_milestones",
                array(
                    'status'            => 'approved',
                    'inspection_report' => $report,
                    'inspector_id'      => get_current_user_id(),
                    'approved_by'       => get_current_user_id(),
                    'approved_at'       => current_time( 'mysql' ),
                ),
                array( 'id' => $milestone_id ),
                array( '%s', '%s', '%d', '%d', '%s' ),
                array( '%d' )
            );

            // Update escrow
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}dlga_escrow
                 SET amount_paid = amount_paid + %f,
                     amount_remaining = amount_remaining - %f
                 WHERE tender_id = %d",
                $milestone->amount,
                $milestone->amount,
                $milestone->tender_id
            ) );

            // Unlock next milestone
            $next = $milestone->milestone_number + 1;
            $wpdb->update(
                "{$wpdb->prefix}dlga_milestones",
                array( 'status' => 'pending' ),
                array(
                    'tender_id'        => $milestone->tender_id,
                    'milestone_number' => $next,
                    'status'           => 'locked',
                ),
                array( '%s' ),
                array( '%d', '%d', '%s' )
            );

            // Check if all milestones completed
            $remaining = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dlga_milestones
                 WHERE tender_id = %d AND status != 'approved'",
                $milestone->tender_id
            ) );

            if ( 0 === (int) $remaining ) {
                update_post_meta( $milestone->tender_id, '_dlga_tender_status', DLGA_Tender::STATUS_COMPLETED );
                update_post_meta( $milestone->tender_id, '_dlga_completed_date', current_time( 'mysql' ) );

                $wpdb->update(
                    "{$wpdb->prefix}dlga_escrow",
                    array( 'status' => 'completed', 'completed_at' => current_time( 'mysql' ) ),
                    array( 'tender_id' => $milestone->tender_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );

                // Update company stats
                self::update_company_stats( $milestone->tender_id );
            }

        } elseif ( 'reject' === $action ) {
            $wpdb->update(
                "{$wpdb->prefix}dlga_milestones",
                array(
                    'status'            => 'rejected',
                    'inspection_report' => $report,
                    'inspector_id'      => get_current_user_id(),
                ),
                array( 'id' => $milestone_id ),
                array( '%s', '%s', '%d' ),
                array( '%d' )
            );
        }

        wp_redirect( add_query_arg( 'milestone_updated', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle blacklisting a company.
     */
    public static function handle_blacklist() {
        if ( ! isset( $_POST['dlga_blacklist_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_blacklist_nonce'], 'dlga_blacklist_company' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'manage_dlga_blacklist' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        global $wpdb;

        $company_id = intval( $_POST['company_id'] );
        $reason     = sanitize_textarea_field( $_POST['blacklist_reason'] );
        $tender_id  = intval( $_POST['related_tender_id'] );

        $wpdb->replace(
            "{$wpdb->prefix}dlga_blacklist",
            array(
                'company_id'     => $company_id,
                'reason'         => $reason,
                'tender_id'      => $tender_id > 0 ? $tender_id : null,
                'blacklisted_by' => get_current_user_id(),
                'blacklisted_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%d', '%d', '%s' )
        );

        // If tender related, refund remaining escrow
        if ( $tender_id > 0 ) {
            $escrow = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dlga_escrow WHERE tender_id = %d AND status = 'active'",
                $tender_id
            ) );

            if ( $escrow && $escrow->amount_remaining > 0 ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}dlga_fund_pools
                     SET total_balance = total_balance + %f
                     WHERE pool_type = 'infrastructure'",
                    $escrow->amount_remaining
                ) );

                $wpdb->update(
                    "{$wpdb->prefix}dlga_escrow",
                    array( 'status' => 'refunded', 'completed_at' => current_time( 'mysql' ) ),
                    array( 'tender_id' => $tender_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }

            update_post_meta( $tender_id, '_dlga_tender_status', DLGA_Tender::STATUS_FAILED );
        }

        wp_redirect( add_query_arg( 'blacklisted', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Handle user approval.
     */
    public static function handle_user_approval() {
        if ( ! isset( $_POST['dlga_approve_user_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['dlga_approve_user_nonce'], 'dlga_approve_user' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'digital-lga' ) );
        }

        if ( ! current_user_can( 'approve_dlga_users' ) && ! current_user_can( 'administrator' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        $user_id = intval( $_POST['user_id'] );
        $action  = sanitize_text_field( $_POST['approval_action'] );

        if ( 'approve' === $action ) {
            update_user_meta( $user_id, 'dlga_approval_status', 'approved' );
            update_user_meta( $user_id, 'dlga_verification_status', 'verified' );

            $user = get_userdata( $user_id );
            if ( $user ) {
                wp_mail(
                    $user->user_email,
                    __( '[Digital LGA] Account Approved', 'digital-lga' ),
                    sprintf(
                        __( "Dear %s,\n\nYour Digital LGA account has been approved. You can now log in and use the platform.\n\nThank you.", 'digital-lga' ),
                        $user->display_name
                    )
                );
            }
        } elseif ( 'reject' === $action ) {
            $reason = sanitize_textarea_field( $_POST['reject_reason'] );
            update_user_meta( $user_id, 'dlga_approval_status', 'rejected' );
            update_user_meta( $user_id, 'dlga_reject_reason', $reason );
        }

        wp_redirect( add_query_arg( 'user_updated', '1', wp_get_referer() ) );
        exit;
    }

    /**
     * Update company performance stats after project completion.
     *
     * @param int $tender_id Tender post ID.
     */
    public static function update_company_stats( $tender_id ) {
        global $wpdb;

        $company_id = intval( get_post_meta( $tender_id, '_dlga_winning_company', true ) );
        if ( ! $company_id ) {
            return;
        }

        $completed = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_winning_company' AND meta_value = %d
             AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dlga_tender_status' AND meta_value = %s)",
            $company_id,
            DLGA_Tender::STATUS_COMPLETED
        ) );

        $in_progress = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_winning_company' AND meta_value = %d
             AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dlga_tender_status' AND meta_value IN (%s, %s))",
            $company_id,
            DLGA_Tender::STATUS_AWARDED,
            DLGA_Tender::STATUS_IN_PROGRESS
        ) );

        $failed = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_winning_company' AND meta_value = %d
             AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dlga_tender_status' AND meta_value = %s)",
            $company_id,
            DLGA_Tender::STATUS_FAILED
        ) );

        $total = $completed + $in_progress + $failed;

        // Average rating from verifications on this company's tenders
        $avg_rating = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(v.rating)
             FROM {$wpdb->prefix}dlga_verifications v
             JOIN {$wpdb->postmeta} pm ON v.tender_id = pm.post_id
             WHERE pm.meta_key = '_dlga_winning_company' AND pm.meta_value = %d
             AND v.rating IS NOT NULL",
            $company_id
        ) );

        $total_verifications = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}dlga_verifications v
             JOIN {$wpdb->postmeta} pm ON v.tender_id = pm.post_id
             WHERE pm.meta_key = '_dlga_winning_company' AND pm.meta_value = %d",
            $company_id
        ) );

        $wpdb->replace(
            "{$wpdb->prefix}dlga_company_stats",
            array(
                'company_id'           => $company_id,
                'total_projects'       => $total,
                'completed_projects'   => $completed,
                'in_progress_projects' => $in_progress,
                'failed_projects'      => $failed,
                'average_rating'       => $avg_rating ? round( $avg_rating, 2 ) : null,
                'total_verifications'  => $total_verifications,
                'on_time_delivery_rate' => $total > 0 ? round( ( $completed / $total ) * 100, 2 ) : null,
                'last_updated'         => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%f', '%s' )
        );
    }

    /**
     * Get pending user approvals.
     *
     * @return array
     */
    public static function get_pending_approvals() {
        return get_users( array(
            'meta_query' => array(
                array(
                    'key'   => 'dlga_approval_status',
                    'value' => 'pending',
                ),
            ),
        ) );
    }

    /**
     * Get blacklisted companies.
     *
     * @return array
     */
    public static function get_blacklist() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT b.*, u.display_name as company_display_name
             FROM {$wpdb->prefix}dlga_blacklist b
             JOIN {$wpdb->users} u ON b.company_id = u.ID
             ORDER BY b.blacklisted_at DESC"
        );
    }
}
