<?php
/**
 * Single tender view template.
 *
 * Displays full tender details including description, budget, timeline,
 * milestones, bids (during vetting), citizen verifications, and forms
 * for verification and progress updates.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$status_labels  = DLGA_Tender::get_status_labels();
$tender_status  = get_post_meta( $tender_id, '_dlga_tender_status', true );
$budget         = get_post_meta( $tender_id, '_dlga_budget', true );
$location       = get_post_meta( $tender_id, '_dlga_location', true );
$timeline       = get_post_meta( $tender_id, '_dlga_timeline', true );
$deadline       = get_post_meta( $tender_id, '_dlga_bidding_deadline', true );
$origin_idea_id = get_post_meta( $tender_id, '_dlga_origin_idea', true );
$winning_bid_id = get_post_meta( $tender_id, '_dlga_winning_bid', true );
$status_label   = isset( $status_labels[ $tender_status ] ) ? $status_labels[ $tender_status ] : $tender_status;

$bids           = DLGA_Tender::get_bids( $tender_id );
$milestones     = DLGA_Tender::get_milestones( $tender_id );
$verifications  = DLGA_Tender::get_verifications( $tender_id );
$avg_rating     = DLGA_Tender::get_average_rating( $tender_id );
$verify_count   = DLGA_Tender::get_verification_count( $tender_id );

$current_user   = wp_get_current_user();
$is_citizen     = is_user_logged_in() && in_array( 'dlga_citizen', (array) $current_user->roles, true );

// Determine if current user is the winning company.
$is_winning_company = false;
if ( $winning_bid_id && is_user_logged_in() ) {
    $winning_bid = get_post( $winning_bid_id );
    if ( $winning_bid && (int) $winning_bid->post_author === (int) $current_user->ID ) {
        $is_winning_company = true;
    }
}

// Statuses at which bids become visible.
$bids_visible_statuses = array(
    DLGA_Tender::STATUS_VETTING,
    DLGA_Tender::STATUS_AWARDED,
    DLGA_Tender::STATUS_IN_PROGRESS,
    DLGA_Tender::STATUS_COMPLETED,
    DLGA_Tender::STATUS_FAILED,
);

$tender_categories = get_the_terms( $tender_id, 'dlga_project_category' );
?>
<div class="dlga-tenders dlga-single-tender">

    <!-- Success Notices -->
    <?php if ( isset( $_GET['verified'] ) && '1' === $_GET['verified'] ) : ?>
        <div class="dlga-notice dlga-notice-success">
            <?php esc_html_e( 'Your verification has been submitted. Thank you for holding this project accountable!', 'digital-lga' ); ?>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['progress_updated'] ) && '1' === $_GET['progress_updated'] ) : ?>
        <div class="dlga-notice dlga-notice-success">
            <?php esc_html_e( 'Progress update submitted successfully.', 'digital-lga' ); ?>
        </div>
    <?php endif; ?>

    <!-- Tender Header -->
    <div class="dlga-card dlga-tender-header">
        <div class="dlga-tender-title-row">
            <h2><?php echo esc_html( $tender->post_title ); ?></h2>
            <span class="dlga-badge dlga-badge-<?php echo esc_attr( $tender_status ); ?> dlga-badge-large">
                <?php echo esc_html( $status_label ); ?>
            </span>
        </div>

        <?php if ( $tender_categories && ! is_wp_error( $tender_categories ) ) :
            $cat_names = wp_list_pluck( $tender_categories, 'name' );
        ?>
            <p class="dlga-tender-categories">
                <strong><?php esc_html_e( 'Category:', 'digital-lga' ); ?></strong>
                <?php echo esc_html( implode( ', ', $cat_names ) ); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Tender Details -->
    <div class="dlga-card dlga-tender-details">
        <h3><?php esc_html_e( 'Project Details', 'digital-lga' ); ?></h3>

        <table class="dlga-table dlga-info-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Budget', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Location', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $location ); ?></td>
                </tr>
                <?php if ( ! empty( $timeline ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Timeline', 'digital-lga' ); ?></th>
                        <td><?php echo esc_html( $timeline ); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $deadline ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Bidding Deadline', 'digital-lga' ); ?></th>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $deadline ) ) ); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Total Bids', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( count( $bids ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Citizen Verifications', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( $verify_count ); ?></td>
                </tr>
                <?php if ( $avg_rating > 0 ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Average Rating', 'digital-lga' ); ?></th>
                        <td>
                            <span class="dlga-rating-value"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></span>
                            <span class="dlga-rating-max"> / 5</span>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Published', 'digital-lga' ); ?></th>
                    <td><?php echo esc_html( get_the_date( '', $tender ) ); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ( ! empty( $origin_idea_id ) ) :
            $idea_post = get_post( $origin_idea_id );
            if ( $idea_post ) :
        ?>
            <div class="dlga-origin-idea">
                <p>
                    <strong><?php esc_html_e( 'Citizen Idea:', 'digital-lga' ); ?></strong>
                    <?php echo esc_html( $idea_post->post_title ); ?>
                    <?php
                    $idea_author = get_userdata( $idea_post->post_author );
                    if ( $idea_author ) :
                    ?>
                        <span class="dlga-idea-author">
                            &mdash; <?php printf( esc_html__( 'submitted by %s', 'digital-lga' ), esc_html( $idea_author->display_name ) ); ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        <?php
            endif;
        endif;
        ?>

        <!-- Bid Action -->
        <?php if ( DLGA_Tender::STATUS_OPEN === $tender_status ) : ?>
            <div class="dlga-tender-action">
                <a href="<?php echo esc_url( add_query_arg( 'tender_id', $tender_id, home_url( '/dlga/submit-bid/' ) ) ); ?>" class="dlga-btn dlga-btn-primary">
                    <?php esc_html_e( 'Submit a Bid', 'digital-lga' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Description -->
    <div class="dlga-card dlga-tender-description">
        <h3><?php esc_html_e( 'Description', 'digital-lga' ); ?></h3>
        <div class="dlga-content">
            <?php echo wp_kses_post( apply_filters( 'the_content', $tender->post_content ) ); ?>
        </div>
    </div>

    <!-- Milestones & Progress -->
    <?php if ( ! empty( $milestones ) ) : ?>
        <div class="dlga-card dlga-tender-milestones">
            <h3><?php esc_html_e( 'Project Milestones', 'digital-lga' ); ?></h3>

            <?php
            // Calculate overall progress.
            $completed_pct = 0;
            foreach ( $milestones as $ms ) {
                if ( 'approved' === $ms->status ) {
                    $completed_pct += intval( $ms->percentage );
                }
            }
            ?>

            <!-- Progress Bar -->
            <div class="dlga-progress-bar-wrap">
                <div class="dlga-progress-bar">
                    <div class="dlga-progress-bar-fill" style="width: <?php echo esc_attr( $completed_pct ); ?>%;">
                        <span class="dlga-progress-bar-text"><?php echo esc_html( $completed_pct ); ?>%</span>
                    </div>
                </div>
                <p class="dlga-progress-label">
                    <?php printf( esc_html__( 'Overall Progress: %d%%', 'digital-lga' ), intval( $completed_pct ) ); ?>
                </p>
            </div>

            <!-- Milestone Table -->
            <table class="dlga-table dlga-milestones-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( '#', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Milestone', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Weight', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $milestones as $ms ) :
                        $ms_status_labels = array(
                            'locked'    => __( 'Locked', 'digital-lga' ),
                            'pending'   => __( 'Pending', 'digital-lga' ),
                            'submitted' => __( 'Submitted', 'digital-lga' ),
                            'approved'  => __( 'Approved', 'digital-lga' ),
                            'rejected'  => __( 'Rejected', 'digital-lga' ),
                        );
                        $ms_label = isset( $ms_status_labels[ $ms->status ] ) ? $ms_status_labels[ $ms->status ] : $ms->status;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $ms->milestone_number ); ?></td>
                            <td><?php echo esc_html( $ms->title ); ?></td>
                            <td><?php echo esc_html( $ms->percentage ); ?>%</td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $ms->amount ) ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $ms->status ); ?>">
                                    <?php echo esc_html( $ms_label ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Bids (visible during vetting and beyond) -->
    <?php if ( in_array( $tender_status, $bids_visible_statuses, true ) && ! empty( $bids ) ) : ?>
        <div class="dlga-card dlga-tender-bids">
            <h3>
                <?php printf( esc_html__( 'Bids (%d)', 'digital-lga' ), count( $bids ) ); ?>
            </h3>

            <?php if ( DLGA_Tender::STATUS_VETTING === $tender_status ) : ?>
                <p class="dlga-vetting-notice">
                    <?php esc_html_e( 'This tender is currently in the public vetting period. Community members can review and comment on bids.', 'digital-lga' ); ?>
                </p>
                <a href="<?php echo esc_url( add_query_arg( 'tender_id', $tender_id, home_url( '/dlga/bids-vetting/' ) ) ); ?>" class="dlga-btn dlga-btn-secondary">
                    <?php esc_html_e( 'View Full Vetting Page', 'digital-lga' ); ?>
                </a>
            <?php endif; ?>

            <table class="dlga-table dlga-bids-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Proposed Cost', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Timeline', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $bids as $bid ) :
                        $bid_cost     = get_post_meta( $bid->ID, '_dlga_proposed_cost', true );
                        $bid_timeline = get_post_meta( $bid->ID, '_dlga_proposed_timeline', true );
                        $bid_status   = get_post_meta( $bid->ID, '_dlga_bid_status', true );
                        $company_name = get_user_meta( $bid->post_author, 'dlga_company_name', true );
                        $is_winner    = ( $winning_bid_id && (int) $bid->ID === (int) $winning_bid_id );
                    ?>
                        <tr class="<?php echo $is_winner ? 'dlga-bid-winner' : ''; ?>">
                            <td>
                                <?php echo esc_html( $company_name ); ?>
                                <?php if ( $is_winner ) : ?>
                                    <span class="dlga-badge dlga-badge-awarded"><?php esc_html_e( 'Winner', 'digital-lga' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $bid_cost ) ); ?></td>
                            <td><?php echo esc_html( $bid_timeline ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $bid_status ); ?>">
                                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $bid_status ) ) ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Citizen Verifications -->
    <?php if ( ! empty( $verifications ) ) : ?>
        <div class="dlga-card dlga-tender-verifications">
            <h3>
                <?php printf( esc_html__( 'Citizen Verifications (%d)', 'digital-lga' ), count( $verifications ) ); ?>
            </h3>

            <div class="dlga-verifications-list">
                <?php foreach ( $verifications as $v ) : ?>
                    <div class="dlga-verification-item">
                        <div class="dlga-verification-header">
                            <strong><?php echo esc_html( $v->citizen_name ); ?></strong>
                            <span class="dlga-verification-date">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $v->created_at ) ) ); ?>
                            </span>
                        </div>
                        <div class="dlga-verification-meta">
                            <span class="dlga-badge dlga-badge-<?php echo esc_attr( $v->verification_type ); ?>">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $v->verification_type ) ) ); ?>
                            </span>
                            <?php if ( $v->rating ) : ?>
                                <span class="dlga-verification-rating">
                                    <?php
                                    printf(
                                        /* translators: %d: rating out of 5 */
                                        esc_html__( 'Rating: %d/5', 'digital-lga' ),
                                        intval( $v->rating )
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $v->comment ) ) : ?>
                            <div class="dlga-verification-comment">
                                <?php echo esc_html( $v->comment ); ?>
                            </div>
                        <?php endif; ?>
                        <?php
                        $photos = json_decode( $v->photos, true );
                        if ( ! empty( $photos ) && is_array( $photos ) ) :
                        ?>
                            <div class="dlga-verification-photos">
                                <?php foreach ( $photos as $photo_id ) :
                                    $photo_url = wp_get_attachment_image_url( $photo_id, 'medium' );
                                    if ( $photo_url ) :
                                ?>
                                    <img src="<?php echo esc_url( $photo_url ); ?>"
                                         alt="<?php esc_attr_e( 'Verification photo', 'digital-lga' ); ?>"
                                         class="dlga-verification-photo">
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Citizen Verification Form -->
    <?php if ( $is_citizen && in_array( $tender_status, array( DLGA_Tender::STATUS_IN_PROGRESS, DLGA_Tender::STATUS_COMPLETED ), true ) ) : ?>
        <div class="dlga-card dlga-tender-verify-form">
            <h3><?php esc_html_e( 'Submit Your Verification', 'digital-lga' ); ?></h3>
            <p><?php esc_html_e( 'As a citizen, you can verify the progress or completion of this project. Your feedback promotes transparency and accountability.', 'digital-lga' ); ?></p>

            <form method="post" enctype="multipart/form-data" class="dlga-form">
                <?php wp_nonce_field( 'dlga_verify_project', 'dlga_verify_nonce' ); ?>
                <input type="hidden" name="tender_id" value="<?php echo esc_attr( $tender_id ); ?>">

                <div class="dlga-form-row">
                    <div class="dlga-form-group">
                        <label for="verification_type"><?php esc_html_e( 'Verification Type *', 'digital-lga' ); ?></label>
                        <select name="verification_type" id="verification_type" required>
                            <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                            <option value="site_visit"><?php esc_html_e( 'Site Visit', 'digital-lga' ); ?></option>
                            <option value="progress_check"><?php esc_html_e( 'Progress Check', 'digital-lga' ); ?></option>
                            <option value="completion_review"><?php esc_html_e( 'Completion Review', 'digital-lga' ); ?></option>
                            <option value="quality_assessment"><?php esc_html_e( 'Quality Assessment', 'digital-lga' ); ?></option>
                        </select>
                    </div>
                    <div class="dlga-form-group">
                        <label for="rating"><?php esc_html_e( 'Rating (1-5) *', 'digital-lga' ); ?></label>
                        <select name="rating" id="rating" required>
                            <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                            <option value="1">1 - <?php esc_html_e( 'Poor', 'digital-lga' ); ?></option>
                            <option value="2">2 - <?php esc_html_e( 'Below Average', 'digital-lga' ); ?></option>
                            <option value="3">3 - <?php esc_html_e( 'Average', 'digital-lga' ); ?></option>
                            <option value="4">4 - <?php esc_html_e( 'Good', 'digital-lga' ); ?></option>
                            <option value="5">5 - <?php esc_html_e( 'Excellent', 'digital-lga' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="dlga-form-group">
                    <label for="verification_comment"><?php esc_html_e( 'Your Observations *', 'digital-lga' ); ?></label>
                    <textarea name="verification_comment" id="verification_comment" rows="5" required
                              placeholder="<?php esc_attr_e( 'Describe what you observed at the project site...', 'digital-lga' ); ?>"></textarea>
                </div>

                <div class="dlga-form-group">
                    <label for="verification_photos"><?php esc_html_e( 'Photos (up to 3)', 'digital-lga' ); ?></label>
                    <input type="file" name="verification_photos[]" id="verification_photos" accept="image/*" multiple>
                    <small class="dlga-form-help"><?php esc_html_e( 'Upload photos from the project site to support your verification.', 'digital-lga' ); ?></small>
                </div>

                <button type="submit" class="dlga-btn dlga-btn-primary">
                    <?php esc_html_e( 'Submit Verification', 'digital-lga' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Progress Update Form (winning company only) -->
    <?php if ( $is_winning_company && DLGA_Tender::STATUS_IN_PROGRESS === $tender_status ) : ?>
        <div class="dlga-card dlga-tender-progress-form">
            <h3><?php esc_html_e( 'Submit Progress Update', 'digital-lga' ); ?></h3>
            <p><?php esc_html_e( 'Keep the community informed about the progress of this project.', 'digital-lga' ); ?></p>

            <form method="post" enctype="multipart/form-data" class="dlga-form">
                <?php wp_nonce_field( 'dlga_progress_update', 'dlga_progress_nonce' ); ?>
                <input type="hidden" name="tender_id" value="<?php echo esc_attr( $tender_id ); ?>">

                <div class="dlga-form-group">
                    <label for="progress_update"><?php esc_html_e( 'Progress Update *', 'digital-lga' ); ?></label>
                    <textarea name="progress_update" id="progress_update" rows="6" required
                              placeholder="<?php esc_attr_e( 'Describe the current status, work completed, and next steps...', 'digital-lga' ); ?>"></textarea>
                </div>

                <div class="dlga-form-group">
                    <label for="progress_photos"><?php esc_html_e( 'Progress Photos (up to 5)', 'digital-lga' ); ?></label>
                    <input type="file" name="progress_photos[]" id="progress_photos" accept="image/*" multiple>
                    <small class="dlga-form-help"><?php esc_html_e( 'Upload photos showing project progress.', 'digital-lga' ); ?></small>
                </div>

                <button type="submit" class="dlga-btn dlga-btn-primary">
                    <?php esc_html_e( 'Submit Update', 'digital-lga' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Progress Updates History -->
    <?php
    $progress_updates = get_comments( array(
        'post_id' => $tender_id,
        'type'    => 'dlga_progress',
        'status'  => 'approve',
        'orderby' => 'comment_date',
        'order'   => 'DESC',
    ) );

    if ( ! empty( $progress_updates ) ) :
    ?>
        <div class="dlga-card dlga-tender-progress-history">
            <h3><?php esc_html_e( 'Progress Updates', 'digital-lga' ); ?></h3>

            <div class="dlga-progress-updates-list">
                <?php foreach ( $progress_updates as $update ) :
                    $update_author = get_userdata( $update->user_id );
                    $company_name  = $update_author ? get_user_meta( $update_author->ID, 'dlga_company_name', true ) : '';
                    $update_photos = get_comment_meta( $update->comment_ID, '_dlga_progress_photos', true );
                ?>
                    <div class="dlga-progress-update-item">
                        <div class="dlga-progress-update-header">
                            <strong>
                                <?php
                                if ( ! empty( $company_name ) ) {
                                    echo esc_html( $company_name );
                                } elseif ( $update_author ) {
                                    echo esc_html( $update_author->display_name );
                                }
                                ?>
                            </strong>
                            <span class="dlga-progress-update-date">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $update->comment_date ) ) ); ?>
                            </span>
                        </div>
                        <div class="dlga-progress-update-content">
                            <?php echo esc_html( $update->comment_content ); ?>
                        </div>
                        <?php if ( ! empty( $update_photos ) && is_array( $update_photos ) ) : ?>
                            <div class="dlga-progress-update-photos">
                                <?php foreach ( $update_photos as $photo_id ) :
                                    $photo_url = wp_get_attachment_image_url( $photo_id, 'medium' );
                                    if ( $photo_url ) :
                                ?>
                                    <img src="<?php echo esc_url( $photo_url ); ?>"
                                         alt="<?php esc_attr_e( 'Progress photo', 'digital-lga' ); ?>"
                                         class="dlga-progress-photo">
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
