<?php
/**
 * Bid submission form template.
 *
 * Displays tender summary and bid form for businesses to submit
 * proposals including cost, timeline, technical details, and documents.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$tender         = get_post( $tender_id );
$budget         = get_post_meta( $tender_id, '_dlga_budget', true );
$location       = get_post_meta( $tender_id, '_dlga_location', true );
$timeline       = get_post_meta( $tender_id, '_dlga_timeline', true );
$deadline       = get_post_meta( $tender_id, '_dlga_bidding_deadline', true );
$status_labels  = DLGA_Tender::get_status_labels();
$tender_status  = get_post_meta( $tender_id, '_dlga_tender_status', true );
$status_label   = isset( $status_labels[ $tender_status ] ) ? $status_labels[ $tender_status ] : $tender_status;

$tender_categories = get_the_terms( $tender_id, 'dlga_project_category' );
?>
<div class="dlga-tenders dlga-submit-bid">

    <!-- Success Notice -->
    <?php if ( isset( $_GET['bid_submitted'] ) && '1' === $_GET['bid_submitted'] ) : ?>
        <div class="dlga-notice dlga-notice-success">
            <?php esc_html_e( 'Your bid has been submitted successfully! The committee will review it during the vetting period.', 'digital-lga' ); ?>
        </div>
    <?php endif; ?>

    <!-- Tender Info Header -->
    <div class="dlga-card dlga-tender-info-header">
        <h2>
            <?php printf(
                esc_html__( 'Submit Bid: %s', 'digital-lga' ),
                esc_html( $tender->post_title )
            ); ?>
        </h2>

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
                        <th><?php esc_html_e( 'Expected Timeline', 'digital-lga' ); ?></th>
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
                    <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                    <td>
                        <span class="dlga-badge dlga-badge-<?php echo esc_attr( $tender_status ); ?>">
                            <?php echo esc_html( $status_label ); ?>
                        </span>
                    </td>
                </tr>
                <?php if ( $tender_categories && ! is_wp_error( $tender_categories ) ) :
                    $cat_names = wp_list_pluck( $tender_categories, 'name' );
                ?>
                    <tr>
                        <th><?php esc_html_e( 'Category', 'digital-lga' ); ?></th>
                        <td><?php echo esc_html( implode( ', ', $cat_names ) ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="<?php echo esc_url( get_permalink( $tender_id ) ); ?>" class="dlga-btn dlga-btn-small">
            <?php esc_html_e( 'View Full Tender Details', 'digital-lga' ); ?>
        </a>
    </div>

    <!-- Bid Form -->
    <div class="dlga-card dlga-bid-form-wrap">
        <h3><?php esc_html_e( 'Your Bid', 'digital-lga' ); ?></h3>

        <form method="post" enctype="multipart/form-data" class="dlga-form">
            <?php wp_nonce_field( 'dlga_submit_bid', 'dlga_bid_nonce' ); ?>
            <input type="hidden" name="tender_id" value="<?php echo esc_attr( $tender_id ); ?>">

            <!-- Financial -->
            <fieldset>
                <legend><?php esc_html_e( 'Financial Proposal', 'digital-lga' ); ?></legend>

                <div class="dlga-form-row">
                    <div class="dlga-form-group">
                        <label for="proposed_cost">
                            <?php
                            printf(
                                /* translators: %s: formatted maximum budget */
                                esc_html__( 'Proposed Cost (max: %s) *', 'digital-lga' ),
                                esc_html( DLGA_Settings::format_amount( $budget ) )
                            );
                            ?>
                        </label>
                        <input type="number" name="proposed_cost" id="proposed_cost"
                               min="0" max="<?php echo esc_attr( floatval( $budget ) ); ?>"
                               step="0.01" required
                               placeholder="<?php esc_attr_e( 'Enter your proposed cost', 'digital-lga' ); ?>">
                        <small class="dlga-form-help">
                            <?php esc_html_e( 'Your proposed cost must not exceed the allocated budget.', 'digital-lga' ); ?>
                        </small>
                    </div>
                    <div class="dlga-form-group">
                        <label for="proposed_timeline"><?php esc_html_e( 'Proposed Timeline *', 'digital-lga' ); ?></label>
                        <input type="text" name="proposed_timeline" id="proposed_timeline" required
                               placeholder="<?php esc_attr_e( 'e.g., 3 months, 12 weeks', 'digital-lga' ); ?>">
                        <small class="dlga-form-help">
                            <?php esc_html_e( 'Specify the estimated time to complete the project.', 'digital-lga' ); ?>
                        </small>
                    </div>
                </div>
            </fieldset>

            <!-- Technical Proposal -->
            <fieldset>
                <legend><?php esc_html_e( 'Technical Proposal', 'digital-lga' ); ?></legend>

                <div class="dlga-form-group">
                    <label for="technical_proposal"><?php esc_html_e( 'Technical Approach *', 'digital-lga' ); ?></label>
                    <textarea name="technical_proposal" id="technical_proposal" rows="8" required
                              placeholder="<?php esc_attr_e( 'Describe your technical approach, methodology, materials, and execution plan...', 'digital-lga' ); ?>"></textarea>
                    <small class="dlga-form-help">
                        <?php esc_html_e( 'Provide a detailed description of how you plan to execute this project.', 'digital-lga' ); ?>
                    </small>
                </div>

                <div class="dlga-form-group">
                    <label for="team_details"><?php esc_html_e( 'Team Details *', 'digital-lga' ); ?></label>
                    <textarea name="team_details" id="team_details" rows="6" required
                              placeholder="<?php esc_attr_e( 'Describe key team members, their qualifications, and roles in this project...', 'digital-lga' ); ?>"></textarea>
                    <small class="dlga-form-help">
                        <?php esc_html_e( 'Include information about the team that will work on this project.', 'digital-lga' ); ?>
                    </small>
                </div>
            </fieldset>

            <!-- Documents -->
            <fieldset>
                <legend><?php esc_html_e( 'Supporting Documents', 'digital-lga' ); ?></legend>

                <div class="dlga-form-row">
                    <div class="dlga-form-group">
                        <label for="company_license"><?php esc_html_e( 'Company License / Certificate *', 'digital-lga' ); ?></label>
                        <input type="file" name="company_license" id="company_license" required
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="dlga-form-help">
                            <?php esc_html_e( 'Upload your company license or relevant professional certificate. Accepted formats: PDF, JPG, PNG, DOC.', 'digital-lga' ); ?>
                        </small>
                    </div>
                    <div class="dlga-form-group">
                        <label for="portfolio"><?php esc_html_e( 'Portfolio / Past Work (up to 10 files)', 'digital-lga' ); ?></label>
                        <input type="file" name="portfolio[]" id="portfolio" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="dlga-form-help">
                            <?php esc_html_e( 'Upload documents or images showcasing previous similar projects.', 'digital-lga' ); ?>
                        </small>
                    </div>
                </div>
            </fieldset>

            <div class="dlga-form-submit">
                <button type="submit" class="dlga-btn dlga-btn-primary">
                    <?php esc_html_e( 'Submit Bid', 'digital-lga' ); ?>
                </button>
                <a href="<?php echo esc_url( get_permalink( $tender_id ) ); ?>" class="dlga-btn dlga-btn-secondary">
                    <?php esc_html_e( 'Cancel', 'digital-lga' ); ?>
                </a>
            </div>
        </form>
    </div>

</div>
