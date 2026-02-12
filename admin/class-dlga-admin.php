<?php
/**
 * Admin interface.
 *
 * Registers admin menus, settings pages, and committee dashboards.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Register admin menus.
     */
    public static function add_menus() {
        // Main menu
        add_menu_page(
            __( 'Digital LGA', 'digital-lga' ),
            __( 'Digital LGA', 'digital-lga' ),
            'read',
            'digital-lga-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-building',
            30
        );

        // Settings (admin only)
        add_submenu_page(
            'digital-lga-settings',
            __( 'Settings', 'digital-lga' ),
            __( 'Settings', 'digital-lga' ),
            'manage_options',
            'digital-lga-settings',
            array( __CLASS__, 'settings_page' )
        );

        // Service Types
        add_submenu_page(
            'digital-lga-settings',
            __( 'Service Types', 'digital-lga' ),
            __( 'Service Types', 'digital-lga' ),
            'manage_options',
            'dlga-service-types',
            array( __CLASS__, 'service_types_page' )
        );

        // User Approvals
        add_submenu_page(
            'digital-lga-settings',
            __( 'User Approvals', 'digital-lga' ),
            __( 'User Approvals', 'digital-lga' ),
            'approve_dlga_users',
            'dlga-approvals',
            array( __CLASS__, 'approvals_page' )
        );

        // Job Ideas
        add_submenu_page(
            'digital-lga-settings',
            __( 'Job Ideas', 'digital-lga' ),
            __( 'Job Ideas', 'digital-lga' ),
            'manage_dlga_tenders',
            'dlga-job-ideas',
            array( __CLASS__, 'job_ideas_page' )
        );

        // Tenders
        add_submenu_page(
            'digital-lga-settings',
            __( 'Tenders', 'digital-lga' ),
            __( 'Tenders', 'digital-lga' ),
            'manage_dlga_tenders',
            'dlga-tenders',
            array( __CLASS__, 'tenders_page' )
        );

        // Vetting Dashboard
        add_submenu_page(
            'digital-lga-settings',
            __( 'Vetting Dashboard', 'digital-lga' ),
            __( 'Vetting', 'digital-lga' ),
            'manage_dlga_tenders',
            'dlga-vetting',
            array( __CLASS__, 'vetting_page' )
        );

        // Accountability Dashboard
        add_submenu_page(
            'digital-lga-settings',
            __( 'Accountability', 'digital-lga' ),
            __( 'Accountability', 'digital-lga' ),
            'manage_dlga_accountability',
            'dlga-accountability',
            array( __CLASS__, 'accountability_page' )
        );

        // Committee Dashboard
        add_submenu_page(
            'digital-lga-settings',
            __( 'Committee', 'digital-lga' ),
            __( 'Committee', 'digital-lga' ),
            'manage_options',
            'dlga-committee',
            array( __CLASS__, 'committee_page' )
        );

        // Fund Pools
        add_submenu_page(
            'digital-lga-settings',
            __( 'Fund Pools', 'digital-lga' ),
            __( 'Fund Pools', 'digital-lga' ),
            'view_dlga_reports',
            'dlga-fund-pools',
            array( __CLASS__, 'fund_pools_page' )
        );

        // Blacklist
        add_submenu_page(
            'digital-lga-settings',
            __( 'Blacklist', 'digital-lga' ),
            __( 'Blacklist', 'digital-lga' ),
            'manage_dlga_blacklist',
            'dlga-blacklist',
            array( __CLASS__, 'blacklist_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'dlga' ) === false && strpos( $hook, 'digital-lga' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'dlga-admin',
            DIGITAL_LGA_URL . 'admin/css/dlga-admin.css',
            array(),
            DIGITAL_LGA_VERSION
        );

        wp_enqueue_script(
            'dlga-admin',
            DIGITAL_LGA_URL . 'admin/js/dlga-admin.js',
            array( 'jquery' ),
            DIGITAL_LGA_VERSION,
            true
        );
    }

    /**
     * Settings page.
     */
    public static function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Digital LGA Settings', 'digital-lga' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'dlga_settings_group' ); ?>

                <h2><?php esc_html_e( 'LGA Information', 'digital-lga' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'LGA Name', 'digital-lga' ); ?></th>
                        <td>
                            <input type="text" name="dlga_lga_name"
                                   value="<?php echo esc_attr( get_option( 'dlga_lga_name', 'Ikeja' ) ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'State', 'digital-lga' ); ?></th>
                        <td>
                            <input type="text" name="dlga_state"
                                   value="<?php echo esc_attr( get_option( 'dlga_state', 'Lagos' ) ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Contribution Settings', 'digital-lga' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Total Contribution Rate (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_contribution_rate"
                                   value="<?php echo esc_attr( get_option( 'dlga_contribution_rate', 10 ) ); ?>"
                                   min="1" max="50" step="0.1">%
                            <p class="description"><?php esc_html_e( 'Percentage of salary contributed (1% to 50%)', 'digital-lga' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Worker Contribution Split (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_worker_split" id="dlga_worker_split"
                                   value="<?php echo esc_attr( get_option( 'dlga_worker_split', 50 ) ); ?>"
                                   min="0" max="100" step="1">%
                            <p class="description"><?php esc_html_e( 'Percentage of contribution paid by worker (0% = business pays all)', 'digital-lga' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Business Contribution Split (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_business_split" id="dlga_business_split"
                                   value="<?php echo esc_attr( get_option( 'dlga_business_split', 50 ) ); ?>"
                                   min="0" max="100" step="1" readonly>%
                            <p class="description"><?php esc_html_e( 'Auto-calculated (100% - Worker Split)', 'digital-lga' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Platform Fee (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_platform_fee"
                                   value="<?php echo esc_attr( get_option( 'dlga_platform_fee', 5 ) ); ?>"
                                   min="0" max="10" step="0.1">%
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Pool Allocation', 'digital-lga' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Personnel Pool (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_personnel_pool" class="dlga-pool-input"
                                   value="<?php echo esc_attr( get_option( 'dlga_personnel_pool', 30 ) ); ?>"
                                   min="0" max="100" step="1">%
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Infrastructure Pool (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_infrastructure_pool" class="dlga-pool-input"
                                   value="<?php echo esc_attr( get_option( 'dlga_infrastructure_pool', 60 ) ); ?>"
                                   min="0" max="100" step="1">%
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Emergency Reserve (%)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_emergency_pool" class="dlga-pool-input"
                                   value="<?php echo esc_attr( get_option( 'dlga_emergency_pool', 10 ) ); ?>"
                                   min="0" max="100" step="1">%
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <p class="description">
                                <strong><?php esc_html_e( 'Total must equal 100%', 'digital-lga' ); ?></strong> -
                                <?php esc_html_e( 'Current total:', 'digital-lga' ); ?>
                                <span id="dlga-pool-total">100</span>%
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Payment Gateway', 'digital-lga' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Payment Provider', 'digital-lga' ); ?></th>
                        <td>
                            <select name="dlga_payment_gateway">
                                <option value="paystack" <?php selected( get_option( 'dlga_payment_gateway' ), 'paystack' ); ?>>Paystack</option>
                                <option value="flutterwave" <?php selected( get_option( 'dlga_payment_gateway' ), 'flutterwave' ); ?>>Flutterwave</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'API Public Key', 'digital-lga' ); ?></th>
                        <td>
                            <input type="text" name="dlga_payment_public_key"
                                   value="<?php echo esc_attr( get_option( 'dlga_payment_public_key' ) ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'API Secret Key', 'digital-lga' ); ?></th>
                        <td>
                            <input type="password" name="dlga_payment_secret_key"
                                   value="<?php echo esc_attr( get_option( 'dlga_payment_secret_key' ) ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Tender Settings', 'digital-lga' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Bidding Period (days)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_bidding_days"
                                   value="<?php echo esc_attr( get_option( 'dlga_bidding_days', 7 ) ); ?>"
                                   min="1" max="30">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Vetting Period (days)', 'digital-lga' ); ?></th>
                        <td>
                            <input type="number" name="dlga_vetting_days"
                                   value="<?php echo esc_attr( get_option( 'dlga_vetting_days', 7 ) ); ?>"
                                   min="1" max="30">
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Settings', 'digital-lga' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Service types management page.
     */
    public static function service_types_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'digital-lga' ) );
        }

        // Handle adding new service type
        if ( isset( $_POST['dlga_add_service_type_nonce'] ) && wp_verify_nonce( $_POST['dlga_add_service_type_nonce'], 'dlga_add_service_type' ) ) {
            $name        = sanitize_text_field( $_POST['service_name'] );
            $pool_access = isset( $_POST['pool_access'] ) ? 1 : 0;
            $description = sanitize_textarea_field( $_POST['service_description'] );

            if ( ! empty( $name ) ) {
                $post_id = wp_insert_post( array(
                    'post_title'  => $name,
                    'post_type'   => 'dlga_service_type',
                    'post_status' => 'publish',
                ) );

                if ( ! is_wp_error( $post_id ) ) {
                    update_post_meta( $post_id, '_dlga_pool_access', $pool_access );
                    update_post_meta( $post_id, '_dlga_description', $description );
                }
            }
        }

        $service_types = get_posts( array(
            'post_type'      => 'dlga_service_type',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Service Types', 'digital-lga' ); ?></h1>

            <h2><?php esc_html_e( 'Add New Service Type', 'digital-lga' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'dlga_add_service_type', 'dlga_add_service_type_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Service Name', 'digital-lga' ); ?></th>
                        <td><input type="text" name="service_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Pool Access', 'digital-lga' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="pool_access" value="1">
                                <?php esc_html_e( 'Members receive share of Personnel Pool', 'digital-lga' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'digital-lga' ); ?></th>
                        <td><textarea name="service_description" rows="3" class="large-text"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Service Type', 'digital-lga' ) ); ?>
            </form>

            <h2><?php esc_html_e( 'Existing Service Types', 'digital-lga' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service Name', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Pool Access', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Registered Members', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $service_types ) ) : ?>
                        <tr><td colspan="4"><?php esc_html_e( 'No service types configured. Default types will be used.', 'digital-lga' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $service_types as $st ) :
                            $pool = get_post_meta( $st->ID, '_dlga_pool_access', true );
                            $count = DLGA_Civil_Servant::count_verified( $st->post_name );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $st->post_title ); ?></strong></td>
                            <td><?php echo $pool ? esc_html__( 'Yes', 'digital-lga' ) : esc_html__( 'No', 'digital-lga' ); ?></td>
                            <td><?php echo esc_html( $count ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $st->ID ) ); ?>"><?php esc_html_e( 'Edit', 'digital-lga' ); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * User approvals page.
     */
    public static function approvals_page() {
        $pending = DLGA_Committee::get_pending_approvals();
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'User Approvals', 'digital-lga' ); ?></h1>

            <?php if ( empty( $pending ) ) : ?>
                <p><?php esc_html_e( 'No pending approvals.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Role', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Details', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'digital-lga' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $pending as $user ) :
                            $role = DLGA_Roles::get_dlga_role( $user->ID );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $user->display_name ); ?></td>
                            <td><?php echo esc_html( $user->user_email ); ?></td>
                            <td><?php echo esc_html( DLGA_Roles::get_role_label( $role ) ); ?></td>
                            <td>
                                <?php if ( 'dlga_business' === $role ) : ?>
                                    <?php echo esc_html( get_user_meta( $user->ID, 'dlga_company_name', true ) ); ?>
                                    (CAC: <?php echo esc_html( get_user_meta( $user->ID, 'dlga_cac_number', true ) ); ?>)
                                <?php elseif ( 'dlga_civil_servant' === $role ) : ?>
                                    <?php echo esc_html( get_user_meta( $user->ID, 'dlga_service_type', true ) ); ?>
                                    (Badge: <?php echo esc_html( get_user_meta( $user->ID, 'dlga_badge_number', true ) ); ?>)
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'dlga_approve_user', 'dlga_approve_user_nonce' ); ?>
                                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
                                    <input type="hidden" name="approval_action" value="approve">
                                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Approve', 'digital-lga' ); ?></button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'dlga_approve_user', 'dlga_approve_user_nonce' ); ?>
                                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
                                    <input type="hidden" name="approval_action" value="reject">
                                    <input type="text" name="reject_reason" placeholder="<?php esc_attr_e( 'Reason...', 'digital-lga' ); ?>" style="width:150px;">
                                    <button type="submit" class="button"><?php esc_html_e( 'Reject', 'digital-lga' ); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Job ideas management page.
     */
    public static function job_ideas_page() {
        $ideas = get_posts( array(
            'post_type'      => 'dlga_job_idea',
            'post_status'    => array( 'pending', 'publish' ),
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $categories = get_terms( array(
            'taxonomy'   => 'dlga_project_category',
            'hide_empty' => false,
        ) );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Citizen Job Ideas', 'digital-lga' ); ?></h1>

            <?php if ( empty( $ideas ) ) : ?>
                <p><?php esc_html_e( 'No job ideas submitted yet.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <?php foreach ( $ideas as $idea ) :
                    $status   = get_post_meta( $idea->ID, '_dlga_status', true );
                    $location = get_post_meta( $idea->ID, '_dlga_location', true );
                    $urgency  = get_post_meta( $idea->ID, '_dlga_urgency', true );
                    $category = get_post_meta( $idea->ID, '_dlga_category', true );
                    $author   = get_userdata( $idea->post_author );
                ?>
                <div class="dlga-admin-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-bottom:20px;">
                    <h3><?php echo esc_html( $idea->post_title ); ?>
                        <span class="dlga-badge dlga-badge-<?php echo esc_attr( $urgency ); ?>"><?php echo esc_html( ucfirst( $urgency ) ); ?></span>
                        <span class="dlga-badge"><?php echo esc_html( $status ); ?></span>
                    </h3>
                    <p><strong><?php esc_html_e( 'Location:', 'digital-lga' ); ?></strong> <?php echo esc_html( $location ); ?></p>
                    <p><strong><?php esc_html_e( 'Submitted by:', 'digital-lga' ); ?></strong> <?php echo $author ? esc_html( $author->display_name ) : esc_html__( 'Unknown', 'digital-lga' ); ?></p>
                    <p><?php echo esc_html( $idea->post_content ); ?></p>

                    <?php if ( 'pending_review' === $status ) : ?>
                        <hr>
                        <h4><?php esc_html_e( 'Review This Idea', 'digital-lga' ); ?></h4>
                        <form method="post">
                            <?php wp_nonce_field( 'dlga_review_idea', 'dlga_review_idea_nonce' ); ?>
                            <input type="hidden" name="idea_id" value="<?php echo esc_attr( $idea->ID ); ?>">

                            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                                <div style="flex:1;min-width:300px;">
                                    <h5><?php esc_html_e( 'Approve & Create Tender', 'digital-lga' ); ?></h5>
                                    <p>
                                        <label><?php esc_html_e( 'Budget:', 'digital-lga' ); ?></label><br>
                                        <input type="number" name="tender_budget" step="1000" min="0" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 5000000', 'digital-lga' ); ?>">
                                    </p>
                                    <p>
                                        <label><?php esc_html_e( 'Timeline:', 'digital-lga' ); ?></label><br>
                                        <input type="text" name="tender_timeline" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 30 days', 'digital-lga' ); ?>">
                                    </p>
                                    <p>
                                        <label><?php esc_html_e( 'Category:', 'digital-lga' ); ?></label><br>
                                        <select name="tender_category" style="width:100%;">
                                            <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                                            <?php if ( ! is_wp_error( $categories ) ) : ?>
                                                <?php foreach ( $categories as $cat ) : ?>
                                                    <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </p>
                                    <p>
                                        <label><?php esc_html_e( 'Tender Description:', 'digital-lga' ); ?></label><br>
                                        <textarea name="tender_description" rows="4" style="width:100%;" placeholder="<?php esc_attr_e( 'Leave blank to use citizen description', 'digital-lga' ); ?>"></textarea>
                                    </p>
                                    <button type="submit" name="review_action" value="approve" class="button button-primary">
                                        <?php esc_html_e( 'Approve & Create Tender', 'digital-lga' ); ?>
                                    </button>
                                </div>

                                <div style="flex:0 0 300px;">
                                    <h5><?php esc_html_e( 'Reject', 'digital-lga' ); ?></h5>
                                    <p>
                                        <label><?php esc_html_e( 'Reason:', 'digital-lga' ); ?></label><br>
                                        <textarea name="reject_reason" rows="4" style="width:100%;"></textarea>
                                    </p>
                                    <button type="submit" name="review_action" value="reject" class="button">
                                        <?php esc_html_e( 'Reject Idea', 'digital-lga' ); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Tenders management page.
     */
    public static function tenders_page() {
        $tenders = get_posts( array(
            'post_type'      => 'dlga_tender',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $statuses = DLGA_Tender::get_status_labels();
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Tenders', 'digital-lga' ); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Tender', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Budget', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Bids', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $tenders ) ) : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No tenders yet.', 'digital-lga' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $tenders as $tender ) :
                            $status = get_post_meta( $tender->ID, '_dlga_tender_status', true );
                            $budget = get_post_meta( $tender->ID, '_dlga_budget', true );
                            $bids   = DLGA_Tender::get_bids( $tender->ID );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $tender->post_title ); ?></strong></td>
                            <td><?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?></td>
                            <td><?php echo isset( $statuses[ $status ] ) ? esc_html( $statuses[ $status ] ) : esc_html( $status ); ?></td>
                            <td><?php echo count( $bids ); ?></td>
                            <td><?php echo esc_html( get_the_date( '', $tender ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $tender->ID ) ); ?>"><?php esc_html_e( 'Edit', 'digital-lga' ); ?></a> |
                                <a href="<?php echo esc_url( get_permalink( $tender->ID ) ); ?>"><?php esc_html_e( 'View', 'digital-lga' ); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Vetting dashboard page.
     */
    public static function vetting_page() {
        $tenders = get_posts( array(
            'post_type'      => 'dlga_tender',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_dlga_tender_status',
                    'value'   => array( DLGA_Tender::STATUS_OPEN, DLGA_Tender::STATUS_VETTING ),
                    'compare' => 'IN',
                ),
            ),
        ) );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Vetting Dashboard', 'digital-lga' ); ?></h1>

            <?php if ( empty( $tenders ) ) : ?>
                <p><?php esc_html_e( 'No tenders in vetting phase.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <?php foreach ( $tenders as $tender ) :
                    $status = get_post_meta( $tender->ID, '_dlga_tender_status', true );
                    $budget = get_post_meta( $tender->ID, '_dlga_budget', true );
                    $bids   = DLGA_Tender::get_bids( $tender->ID );
                ?>
                <div class="dlga-admin-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-bottom:20px;">
                    <h3><?php echo esc_html( $tender->post_title ); ?> - <?php echo esc_html( DLGA_Settings::format_amount( $budget ) ); ?></h3>

                    <?php if ( empty( $bids ) ) : ?>
                        <p><?php esc_html_e( 'No bids received yet.', 'digital-lga' ); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                                    <th><?php esc_html_e( 'Proposed Cost', 'digital-lga' ); ?></th>
                                    <th><?php esc_html_e( 'Timeline', 'digital-lga' ); ?></th>
                                    <th><?php esc_html_e( 'Score', 'digital-lga' ); ?></th>
                                    <th><?php esc_html_e( 'Actions', 'digital-lga' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $bids as $bid ) :
                                    $company     = get_userdata( $bid->post_author );
                                    $cost        = get_post_meta( $bid->ID, '_dlga_proposed_cost', true );
                                    $timeline    = get_post_meta( $bid->ID, '_dlga_proposed_timeline', true );
                                    $score       = get_post_meta( $bid->ID, '_dlga_bid_score', true );
                                    $company_name = $company ? get_user_meta( $company->ID, 'dlga_company_name', true ) : 'Unknown';
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $company_name ); ?></td>
                                    <td><?php echo esc_html( DLGA_Settings::format_amount( $cost ) ); ?></td>
                                    <td><?php echo esc_html( $timeline ); ?></td>
                                    <td><?php echo esc_html( $score ); ?>/100</td>
                                    <td>
                                        <details>
                                            <summary><?php esc_html_e( 'Score Bid', 'digital-lga' ); ?></summary>
                                            <form method="post" style="margin-top:10px;">
                                                <?php wp_nonce_field( 'dlga_score_bid', 'dlga_score_bid_nonce' ); ?>
                                                <input type="hidden" name="bid_id" value="<?php echo esc_attr( $bid->ID ); ?>">
                                                <p><?php esc_html_e( 'Price (0-40):', 'digital-lga' ); ?> <input type="number" name="price_score" min="0" max="40" value="0" style="width:60px;"></p>
                                                <p><?php esc_html_e( 'Timeline (0-20):', 'digital-lga' ); ?> <input type="number" name="timeline_score" min="0" max="20" value="0" style="width:60px;"></p>
                                                <p><?php esc_html_e( 'Past Performance (0-30):', 'digital-lga' ); ?> <input type="number" name="performance_score" min="0" max="30" value="0" style="width:60px;"></p>
                                                <p><?php esc_html_e( 'Technical (0-10):', 'digital-lga' ); ?> <input type="number" name="technical_score" min="0" max="10" value="0" style="width:60px;"></p>
                                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Submit Score', 'digital-lga' ); ?></button>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ( DLGA_Tender::STATUS_VETTING === $status ) : ?>
                            <form method="post" style="margin-top:15px;">
                                <?php wp_nonce_field( 'dlga_award_tender', 'dlga_award_nonce' ); ?>
                                <input type="hidden" name="tender_id" value="<?php echo esc_attr( $tender->ID ); ?>">
                                <label><?php esc_html_e( 'Select Winner:', 'digital-lga' ); ?></label>
                                <select name="winning_bid_id">
                                    <?php foreach ( $bids as $bid ) :
                                        $company = get_userdata( $bid->post_author );
                                        $company_name = $company ? get_user_meta( $company->ID, 'dlga_company_name', true ) : 'Unknown';
                                        $score = get_post_meta( $bid->ID, '_dlga_bid_score', true );
                                    ?>
                                        <option value="<?php echo esc_attr( $bid->ID ); ?>">
                                            <?php echo esc_html( sprintf( '%s (Score: %s/100)', $company_name, $score ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Award Tender', 'digital-lga' ); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Accountability dashboard page.
     */
    public static function accountability_page() {
        global $wpdb;

        $active_milestones = $wpdb->get_results(
            "SELECT m.*, p.post_title as tender_title
             FROM {$wpdb->prefix}dlga_milestones m
             JOIN {$wpdb->posts} p ON m.tender_id = p.ID
             WHERE m.status IN ('pending', 'rejected')
             ORDER BY m.created_at ASC"
        );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Accountability Dashboard', 'digital-lga' ); ?></h1>

            <h2><?php esc_html_e( 'Milestones Requiring Inspection', 'digital-lga' ); ?></h2>

            <?php if ( empty( $active_milestones ) ) : ?>
                <p><?php esc_html_e( 'No milestones pending inspection.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <?php foreach ( $active_milestones as $ms ) : ?>
                <div class="dlga-admin-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin-bottom:15px;">
                    <h3>
                        <?php echo esc_html( $ms->tender_title ); ?> -
                        <?php echo esc_html( $ms->title ); ?>
                        (<?php echo esc_html( DLGA_Settings::format_amount( $ms->amount ) ); ?>)
                    </h3>
                    <p>
                        <strong><?php esc_html_e( 'Status:', 'digital-lga' ); ?></strong> <?php echo esc_html( ucfirst( $ms->status ) ); ?> |
                        <strong><?php esc_html_e( 'Milestone:', 'digital-lga' ); ?></strong> #<?php echo esc_html( $ms->milestone_number ); ?> (<?php echo esc_html( $ms->percentage ); ?>%)
                    </p>

                    <form method="post">
                        <?php wp_nonce_field( 'dlga_approve_milestone', 'dlga_milestone_nonce' ); ?>
                        <input type="hidden" name="milestone_id" value="<?php echo esc_attr( $ms->id ); ?>">

                        <p>
                            <label><?php esc_html_e( 'Inspection Report:', 'digital-lga' ); ?></label><br>
                            <textarea name="inspection_report" rows="4" style="width:100%;" placeholder="<?php esc_attr_e( 'Describe your inspection findings...', 'digital-lga' ); ?>"></textarea>
                        </p>

                        <button type="submit" name="milestone_action" value="approve" class="button button-primary">
                            <?php esc_html_e( 'Approve & Release Payment', 'digital-lga' ); ?>
                        </button>
                        <button type="submit" name="milestone_action" value="reject" class="button">
                            <?php esc_html_e( 'Reject - Issues Found', 'digital-lga' ); ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Committee page.
     */
    public static function committee_page() {
        global $wpdb;

        $committee_members = get_users( array(
            'role__in' => array( 'dlga_reviewer', 'dlga_vetter', 'dlga_accountability' ),
        ) );

        $pending_ideas = wp_count_posts( 'dlga_job_idea' );
        $pending_count = isset( $pending_ideas->pending ) ? $pending_ideas->pending : 0;

        $active_tenders = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_tender_status' AND meta_value IN ('open_bidding', 'vetting')"
        );

        $active_projects = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_dlga_tender_status' AND meta_value IN ('awarded', 'in_progress')"
        );

        $pools = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dlga_fund_pools",
            OBJECT_K
        );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Committee Dashboard', 'digital-lga' ); ?></h1>

            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:30px;">
                <div style="background:#fff;border:1px solid #ccd0d4;padding:20px;flex:1;min-width:200px;text-align:center;">
                    <h2 style="margin:0;font-size:2em;"><?php echo esc_html( $pending_count ); ?></h2>
                    <p><?php esc_html_e( 'Pending Ideas', 'digital-lga' ); ?></p>
                </div>
                <div style="background:#fff;border:1px solid #ccd0d4;padding:20px;flex:1;min-width:200px;text-align:center;">
                    <h2 style="margin:0;font-size:2em;"><?php echo esc_html( $active_tenders ); ?></h2>
                    <p><?php esc_html_e( 'Active Tenders', 'digital-lga' ); ?></p>
                </div>
                <div style="background:#fff;border:1px solid #ccd0d4;padding:20px;flex:1;min-width:200px;text-align:center;">
                    <h2 style="margin:0;font-size:2em;"><?php echo esc_html( $active_projects ); ?></h2>
                    <p><?php esc_html_e( 'Projects In Progress', 'digital-lga' ); ?></p>
                </div>
            </div>

            <h2><?php esc_html_e( 'Fund Pool Balances', 'digital-lga' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Pool', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Balance', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Monthly Collected', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Monthly Distributed', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $pools ) : foreach ( $pools as $key => $pool ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( ucfirst( $pool->pool_type ) ); ?></strong></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->total_balance ) ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->monthly_collected ) ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->monthly_distributed ) ); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Committee Members', 'digital-lga' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Role', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $committee_members as $member ) :
                        $role = DLGA_Roles::get_dlga_role( $member->ID );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $member->display_name ); ?></td>
                        <td><?php echo esc_html( DLGA_Roles::get_role_label( $role ) ); ?></td>
                        <td><?php echo esc_html( $member->user_email ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Fund pools page.
     */
    public static function fund_pools_page() {
        global $wpdb;

        $pools = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dlga_fund_pools"
        );

        $recent_payrolls = $wpdb->get_results(
            "SELECT p.*, u.display_name as business_name
             FROM {$wpdb->prefix}dlga_payrolls p
             JOIN {$wpdb->users} u ON p.business_id = u.ID
             ORDER BY p.created_at DESC LIMIT 20"
        );
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Fund Pools', 'digital-lga' ); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Pool', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Total Balance', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Monthly Collected', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Monthly Distributed', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Last Distribution', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $pools as $pool ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( ucfirst( $pool->pool_type ) ); ?></strong></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->total_balance ) ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->monthly_collected ) ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pool->monthly_distributed ) ); ?></td>
                        <td><?php echo esc_html( $pool->last_distribution_date ? $pool->last_distribution_date : '-' ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Recent Payroll Transactions', 'digital-lga' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Business', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Worker', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Gross', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Contribution', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'digital-lga' ); ?></th>
                        <th><?php esc_html_e( 'Period', 'digital-lga' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_payrolls as $pr ) : ?>
                    <tr>
                        <td><?php echo esc_html( $pr->business_name ); ?></td>
                        <td><?php echo esc_html( $pr->worker_name ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pr->gross_salary ) ); ?></td>
                        <td><?php echo esc_html( DLGA_Settings::format_amount( $pr->total_contribution ) ); ?></td>
                        <td><?php echo esc_html( ucfirst( $pr->payment_status ) ); ?></td>
                        <td><?php echo esc_html( $pr->pay_period ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Blacklist management page.
     */
    public static function blacklist_page() {
        $blacklist = DLGA_Committee::get_blacklist();
        ?>
        <div class="wrap dlga-admin-wrap">
            <h1><?php esc_html_e( 'Blacklisted Companies', 'digital-lga' ); ?></h1>

            <?php if ( empty( $blacklist ) ) : ?>
                <p><?php esc_html_e( 'No blacklisted companies.', 'digital-lga' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Reason', 'digital-lga' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'digital-lga' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $blacklist as $entry ) : ?>
                        <tr>
                            <td><?php echo esc_html( $entry->company_display_name ); ?></td>
                            <td><?php echo esc_html( $entry->reason ); ?></td>
                            <td><?php echo esc_html( $entry->blacklisted_at ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Blacklist a Company', 'digital-lga' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'dlga_blacklist_company', 'dlga_blacklist_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Company', 'digital-lga' ); ?></th>
                        <td>
                            <select name="company_id">
                                <?php
                                $businesses = get_users( array( 'role' => 'dlga_business' ) );
                                foreach ( $businesses as $biz ) :
                                    $name = get_user_meta( $biz->ID, 'dlga_company_name', true );
                                ?>
                                    <option value="<?php echo esc_attr( $biz->ID ); ?>"><?php echo esc_html( $name ? $name : $biz->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Reason', 'digital-lga' ); ?></th>
                        <td><textarea name="blacklist_reason" rows="3" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Related Tender ID', 'digital-lga' ); ?></th>
                        <td><input type="number" name="related_tender_id" value="0"></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Blacklist Company', 'digital-lga' ) ); ?>
            </form>
        </div>
        <?php
    }
}
