<?php
/**
 * Public bids vetting template.
 *
 * Displays all bids for a tender during the vetting period, allowing
 * community members to review proposals and leave comments.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$tender         = get_post( $tender_id );
$budget         = get_post_meta( $tender_id, '_dlga_budget', true );
$tender_status  = get_post_meta( $tender_id, '_dlga_tender_status', true );
$status_labels  = DLGA_Tender::get_status_labels();
$status_label   = isset( $status_labels[ $tender_status ] ) ? $status_labels[ $tender_status ] : $tender_status;
$bids           = DLGA_Tender::get_bids( $tender_id );
$vetting_days   = DLGA_Settings::get( 'dlga_vetting_days', 7 );
?>
<div class="dlga-tenders dlga-bids-vetting">

    <!-- Comment Success Notice -->
    <?php if ( isset( $_GET['comment_posted'] ) && '1' === $_GET['comment_posted'] ) : ?>
        <div class="dlga-notice dlga-notice-success">
            <?php esc_html_e( 'Your comment has been submitted.', 'digital-lga' ); ?>
        </div>
    <?php endif; ?>

    <!-- Vetting Header -->
    <div class="dlga-card dlga-vetting-header">
        <h2>
            <?php printf(
                esc_html__( 'Public Bid Vetting: %s', 'digital-lga' ),
                esc_html( $tender->post_title )
            ); ?>
        </h2>
        <div class="dlga-vetting-meta">
            <span class="dlga-badge dlga-badge-<?php echo esc_attr( $tender_status ); ?> dlga-badge-large">
                <?php echo esc_html( $status_label ); ?>
            </span>
            <span class="dlga-vetting-budget">
                <strong><?php esc_html_e( 'Budget:', 'digital-lga' ); ?></strong>
                <?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?>
            </span>
        </div>
        <p class="dlga-vetting-description">
            <?php
            printf(
                /* translators: %d: number of days for vetting period */
                esc_html__( 'This tender is in the public vetting period (%d days). Community members are encouraged to review the bids below and provide feedback to promote transparency in the selection process.', 'digital-lga' ),
                intval( $vetting_days )
            );
            ?>
        </p>
        <a href="<?php echo esc_url( get_permalink( $tender_id ) ); ?>" class="dlga-btn dlga-btn-small">
            <?php esc_html_e( 'Back to Tender Details', 'digital-lga' ); ?>
        </a>
    </div>

    <!-- Bids -->
    <?php if ( ! empty( $bids ) ) : ?>
        <div class="dlga-vetting-bids-count">
            <p>
                <?php
                printf(
                    /* translators: %d: number of bids */
                    esc_html( _n( '%d bid submitted', '%d bids submitted', count( $bids ), 'digital-lga' ) ),
                    count( $bids )
                );
                ?>
            </p>
        </div>

        <?php foreach ( $bids as $index => $bid ) :
            $bid_cost       = get_post_meta( $bid->ID, '_dlga_proposed_cost', true );
            $bid_timeline   = get_post_meta( $bid->ID, '_dlga_proposed_timeline', true );
            $team_details   = get_post_meta( $bid->ID, '_dlga_team_details', true );
            $portfolio_ids  = get_post_meta( $bid->ID, '_dlga_portfolio', true );
            $license_id     = get_post_meta( $bid->ID, '_dlga_company_license', true );
            $company_name   = get_user_meta( $bid->post_author, 'dlga_company_name', true );
            $company_id     = $bid->post_author;

            // Get bid comments.
            $bid_comments = get_comments( array(
                'post_id' => $bid->ID,
                'status'  => 'approve',
                'orderby' => 'comment_date',
                'order'   => 'ASC',
            ) );
        ?>
            <div class="dlga-card dlga-vetting-bid" id="bid-<?php echo esc_attr( $bid->ID ); ?>">
                <!-- Bid Header -->
                <div class="dlga-vetting-bid-header">
                    <h3>
                        <?php
                        printf(
                            /* translators: 1: bid number, 2: company name */
                            esc_html__( 'Bid #%1$d - %2$s', 'digital-lga' ),
                            intval( $index + 1 ),
                            esc_html( $company_name )
                        );
                        ?>
                    </h3>
                    <span class="dlga-vetting-bid-date">
                        <?php printf(
                            esc_html__( 'Submitted: %s', 'digital-lga' ),
                            esc_html( get_the_date( '', $bid ) )
                        ); ?>
                    </span>
                </div>

                <!-- Bid Summary -->
                <table class="dlga-table dlga-info-table dlga-vetting-bid-summary">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                            <td><?php echo esc_html( $company_name ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Proposed Cost', 'digital-lga' ); ?></th>
                            <td>
                                <?php echo esc_html( DLGA_Settings::format_amount( $bid_cost ) ); ?>
                                <?php
                                $savings = floatval( $budget ) - floatval( $bid_cost );
                                if ( $savings > 0 ) :
                                ?>
                                    <span class="dlga-bid-savings">
                                        (<?php
                                        printf(
                                            /* translators: %s: savings amount */
                                            esc_html__( '%s under budget', 'digital-lga' ),
                                            esc_html( DLGA_Settings::format_amount( $savings ) )
                                        );
                                        ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Proposed Timeline', 'digital-lga' ); ?></th>
                            <td><?php echo esc_html( $bid_timeline ); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Technical Proposal -->
                <div class="dlga-vetting-bid-proposal">
                    <h4><?php esc_html_e( 'Technical Proposal', 'digital-lga' ); ?></h4>
                    <div class="dlga-content">
                        <?php echo wp_kses_post( wpautop( $bid->post_content ) ); ?>
                    </div>
                </div>

                <!-- Team Details -->
                <?php if ( ! empty( $team_details ) ) : ?>
                    <div class="dlga-vetting-bid-team">
                        <h4><?php esc_html_e( 'Team Details', 'digital-lga' ); ?></h4>
                        <div class="dlga-content">
                            <?php echo wp_kses_post( wpautop( $team_details ) ); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Portfolio -->
                <?php if ( ! empty( $portfolio_ids ) && is_array( $portfolio_ids ) ) : ?>
                    <div class="dlga-vetting-bid-portfolio">
                        <h4><?php esc_html_e( 'Portfolio / Past Work', 'digital-lga' ); ?></h4>
                        <div class="dlga-portfolio-files">
                            <?php foreach ( $portfolio_ids as $file_id ) :
                                $file_url  = wp_get_attachment_url( $file_id );
                                $file_name = get_the_title( $file_id );
                                $file_type = get_post_mime_type( $file_id );

                                if ( ! $file_url ) {
                                    continue;
                                }

                                // Display images inline, other files as download links.
                                if ( strpos( $file_type, 'image/' ) === 0 ) :
                            ?>
                                    <div class="dlga-portfolio-image">
                                        <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer">
                                            <img src="<?php echo esc_url( wp_get_attachment_image_url( $file_id, 'medium' ) ); ?>"
                                                 alt="<?php echo esc_attr( $file_name ); ?>"
                                                 class="dlga-portfolio-img">
                                        </a>
                                    </div>
                                <?php else : ?>
                                    <div class="dlga-portfolio-file">
                                        <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer" class="dlga-btn dlga-btn-small">
                                            <?php echo esc_html( $file_name ); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="dlga-vetting-bid-comments">
                    <h4>
                        <?php
                        printf(
                            /* translators: %d: number of comments */
                            esc_html__( 'Community Comments (%d)', 'digital-lga' ),
                            count( $bid_comments )
                        );
                        ?>
                    </h4>

                    <?php if ( ! empty( $bid_comments ) ) : ?>
                        <div class="dlga-comments-list">
                            <?php foreach ( $bid_comments as $comment ) : ?>
                                <div class="dlga-comment-item">
                                    <div class="dlga-comment-header">
                                        <strong><?php echo esc_html( $comment->comment_author ); ?></strong>
                                        <span class="dlga-comment-date">
                                            <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $comment->comment_date ) ) ); ?>
                                        </span>
                                    </div>
                                    <div class="dlga-comment-content">
                                        <?php echo esc_html( $comment->comment_content ); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="dlga-no-comments"><?php esc_html_e( 'No comments yet. Be the first to share your thoughts on this bid.', 'digital-lga' ); ?></p>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <?php if ( is_user_logged_in() ) : ?>
                        <div class="dlga-comment-form-wrap">
                            <form method="post" action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" class="dlga-form dlga-comment-form">
                                <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $bid->ID ); ?>">
                                <?php
                                // WordPress comment nonce.
                                comment_id_fields( $bid->ID );
                                ?>

                                <div class="dlga-form-group">
                                    <label for="comment-<?php echo esc_attr( $bid->ID ); ?>">
                                        <?php esc_html_e( 'Your Comment', 'digital-lga' ); ?>
                                    </label>
                                    <textarea name="comment" id="comment-<?php echo esc_attr( $bid->ID ); ?>" rows="3" required
                                              placeholder="<?php esc_attr_e( 'Share your thoughts on this bid...', 'digital-lga' ); ?>"></textarea>
                                </div>

                                <button type="submit" class="dlga-btn dlga-btn-small">
                                    <?php esc_html_e( 'Post Comment', 'digital-lga' ); ?>
                                </button>
                            </form>
                        </div>
                    <?php else : ?>
                        <p class="dlga-login-to-comment">
                            <a href="<?php echo esc_url( wp_login_url( add_query_arg( 'tender_id', $tender_id, home_url( '/dlga/bids-vetting/' ) ) ) ); ?>">
                                <?php esc_html_e( 'Log in to leave a comment.', 'digital-lga' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php else : ?>
        <div class="dlga-card dlga-no-bids">
            <p><?php esc_html_e( 'No bids have been submitted for this tender yet.', 'digital-lga' ); ?></p>
        </div>
    <?php endif; ?>

</div>
