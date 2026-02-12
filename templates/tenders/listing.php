<?php
/**
 * Tender listing template.
 *
 * Displays filterable, paginated list of tenders with status, budget,
 * location, bid count, and links to individual tender pages.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$status_labels    = DLGA_Tender::get_status_labels();
$categories       = get_terms( array(
    'taxonomy'   => 'dlga_project_category',
    'hide_empty' => false,
) );
$current_status   = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$current_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
?>
<div class="dlga-tenders dlga-tender-listing">

    <!-- Filters -->
    <div class="dlga-card dlga-tender-filters">
        <h3><?php esc_html_e( 'Filter Tenders', 'digital-lga' ); ?></h3>
        <form method="get" class="dlga-form dlga-filter-form">
            <div class="dlga-form-row">
                <div class="dlga-form-group">
                    <label for="dlga-filter-status"><?php esc_html_e( 'Status', 'digital-lga' ); ?></label>
                    <select name="status" id="dlga-filter-status">
                        <option value=""><?php esc_html_e( 'All Statuses', 'digital-lga' ); ?></option>
                        <?php foreach ( $status_labels as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dlga-form-group">
                    <label for="dlga-filter-category"><?php esc_html_e( 'Category', 'digital-lga' ); ?></label>
                    <select name="category" id="dlga-filter-category">
                        <option value=""><?php esc_html_e( 'All Categories', 'digital-lga' ); ?></option>
                        <?php if ( ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $current_category, $cat->slug ); ?>>
                                    <?php echo esc_html( $cat->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="dlga-form-group dlga-form-group-submit">
                    <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Filter', 'digital-lga' ); ?></button>
                    <?php if ( ! empty( $current_status ) || ! empty( $current_category ) ) : ?>
                        <a href="<?php echo esc_url( remove_query_arg( array( 'status', 'category' ) ) ); ?>" class="dlga-btn dlga-btn-secondary">
                            <?php esc_html_e( 'Clear Filters', 'digital-lga' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="dlga-tender-results-summary">
        <p>
            <?php
            printf(
                /* translators: %d: number of tenders found */
                esc_html( _n( '%d tender found', '%d tenders found', $query->found_posts, 'digital-lga' ) ),
                intval( $query->found_posts )
            );
            ?>
        </p>
    </div>

    <!-- Tender List -->
    <?php if ( $query->have_posts() ) : ?>
        <div class="dlga-card dlga-tender-list">
            <table class="dlga-table dlga-tenders-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Project', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Budget', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Bids', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $tender_id     = get_the_ID();
                        $budget        = get_post_meta( $tender_id, '_dlga_budget', true );
                        $location      = get_post_meta( $tender_id, '_dlga_location', true );
                        $tender_status = get_post_meta( $tender_id, '_dlga_tender_status', true );
                        $bids          = DLGA_Tender::get_bids( $tender_id );
                        $bid_count     = count( $bids );
                        $status_label  = isset( $status_labels[ $tender_status ] ) ? $status_labels[ $tender_status ] : $tender_status;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( get_the_title() ); ?></strong>
                                <?php
                                $tender_categories = get_the_terms( $tender_id, 'dlga_project_category' );
                                if ( $tender_categories && ! is_wp_error( $tender_categories ) ) :
                                    $cat_names = wp_list_pluck( $tender_categories, 'name' );
                                ?>
                                    <br>
                                    <small class="dlga-tender-category">
                                        <?php echo esc_html( implode( ', ', $cat_names ) ); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?></td>
                            <td><?php echo esc_html( $location ); ?></td>
                            <td>
                                <span class="dlga-badge dlga-badge-<?php echo esc_attr( $tender_status ); ?>">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $bid_count ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_permalink( $tender_id ) ); ?>" class="dlga-btn dlga-btn-small">
                                    <?php esc_html_e( 'View', 'digital-lga' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ( $query->max_num_pages > 1 ) : ?>
            <div class="dlga-pagination">
                <?php
                echo wp_kses_post( paginate_links( array(
                    'total'   => $query->max_num_pages,
                    'current' => max( 1, get_query_var( 'paged' ) ),
                    'format'  => '?paged=%#%',
                    'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'digital-lga' ),
                    'next_text' => esc_html__( 'Next', 'digital-lga' ) . ' &raquo;',
                ) ) );
                ?>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

    <?php else : ?>
        <div class="dlga-card dlga-no-results">
            <p><?php esc_html_e( 'No tenders found matching your criteria.', 'digital-lga' ); ?></p>
        </div>
    <?php endif; ?>

</div>
