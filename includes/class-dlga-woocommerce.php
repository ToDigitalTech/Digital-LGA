<?php
/**
 * WooCommerce integration.
 *
 * Hooks into WooCommerce for payment processing and order management.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_WooCommerce_Integration {

    public static function init() {
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ) );
        add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'on_order_completed' ) );
        add_filter( 'woocommerce_my_account_my_orders_query', array( __CLASS__, 'filter_orders' ) );
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'thankyou_page' ) );
    }

    /**
     * Handle completed order - allocate funds to pools.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function on_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Check if already processed
        if ( $order->get_meta( '_dlga_processed' ) ) {
            return;
        }

        $total_contribution = floatval( $order->get_meta( '_dlga_total_contribution' ) );

        if ( $total_contribution > 0 ) {
            DLGA_Payroll::process_completed_payroll( $order_id );
            $order->update_meta_data( '_dlga_processed', '1' );
            $order->save();
        }

        // Handle escrow payments for tenders
        $escrow_tender = $order->get_meta( '_dlga_escrow_tender_id' );
        if ( $escrow_tender ) {
            self::process_escrow_payment( $order_id, intval( $escrow_tender ) );
        }
    }

    /**
     * Process escrow allocation for a tender.
     *
     * @param int $order_id  WooCommerce order ID.
     * @param int $tender_id Tender post ID.
     */
    private static function process_escrow_payment( $order_id, $tender_id ) {
        global $wpdb;

        $order      = wc_get_order( $order_id );
        $company_id = intval( $order->get_meta( '_dlga_escrow_company_id' ) );
        $amount     = floatval( $order->get_meta( '_dlga_escrow_amount' ) );

        if ( $amount <= 0 ) {
            return;
        }

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dlga_escrow WHERE tender_id = %d",
            $tender_id
        ) );

        if ( ! $existing ) {
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
        }

        // Deduct from infrastructure pool
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}dlga_fund_pools
             SET total_balance = total_balance - %f
             WHERE pool_type = 'infrastructure'",
            $amount
        ) );
    }

    /**
     * Display DLGA info on WooCommerce thank-you page.
     *
     * @param int $order_id Order ID.
     */
    public static function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $contribution = floatval( $order->get_meta( '_dlga_total_contribution' ) );
        if ( $contribution <= 0 ) {
            return;
        }

        $pay_period = $order->get_meta( '_dlga_pay_period' );
        $pools      = DLGA_Settings::get_pool_allocations();
        ?>
        <div class="dlga-thankyou-breakdown">
            <h2><?php esc_html_e( 'Contribution Breakdown', 'digital-lga' ); ?></h2>
            <p><?php esc_html_e( 'Thank you for contributing to your community! Here is how your contribution will be allocated:', 'digital-lga' ); ?></p>
            <table class="dlga-table">
                <tr>
                    <td><?php esc_html_e( 'Total Contribution', 'digital-lga' ); ?></td>
                    <td><strong><?php echo esc_html( DLGA_Settings::format_amount( $contribution ) ); ?></strong></td>
                </tr>
                <tr>
                    <td><?php printf( esc_html__( 'Personnel Pool (%s%%)', 'digital-lga' ), esc_html( $pools['personnel'] * 100 ) ); ?></td>
                    <td><?php echo esc_html( DLGA_Settings::format_amount( $contribution * $pools['personnel'] ) ); ?></td>
                </tr>
                <tr>
                    <td><?php printf( esc_html__( 'Infrastructure Pool (%s%%)', 'digital-lga' ), esc_html( $pools['infrastructure'] * 100 ) ); ?></td>
                    <td><?php echo esc_html( DLGA_Settings::format_amount( $contribution * $pools['infrastructure'] ) ); ?></td>
                </tr>
                <tr>
                    <td><?php printf( esc_html__( 'Emergency Reserve (%s%%)', 'digital-lga' ), esc_html( $pools['emergency'] * 100 ) ); ?></td>
                    <td><?php echo esc_html( DLGA_Settings::format_amount( $contribution * $pools['emergency'] ) ); ?></td>
                </tr>
            </table>
            <p><a href="<?php echo esc_url( home_url( '/dlga/transparency/' ) ); ?>"><?php esc_html_e( 'View full transparency dashboard', 'digital-lga' ); ?></a></p>
        </div>
        <?php
    }

    /**
     * Filter WooCommerce orders to only show DLGA-related orders.
     *
     * @param array $args Query args.
     * @return array
     */
    public static function filter_orders( $args ) {
        return $args;
    }

    /**
     * Create a WooCommerce order for escrow.
     *
     * @param int   $tender_id  Tender post ID.
     * @param int   $company_id Company user ID.
     * @param float $amount     Escrow amount.
     * @return int|false Order ID or false.
     */
    public static function create_escrow_order( $tender_id, $company_id, $amount ) {
        if ( ! class_exists( 'WC_Order' ) ) {
            return false;
        }

        $tender = get_post( $tender_id );
        if ( ! $tender ) {
            return false;
        }

        $order = wc_create_order( array(
            'customer_id' => $company_id,
            'status'      => 'pending',
        ) );

        if ( is_wp_error( $order ) ) {
            return false;
        }

        $fee = new WC_Order_Item_Fee();
        $fee->set_name( sprintf(
            __( 'Escrow - %s', 'digital-lga' ),
            $tender->post_title
        ) );
        $fee->set_amount( $amount );
        $fee->set_total( $amount );
        $order->add_item( $fee );

        $order->set_total( $amount );
        $order->update_meta_data( '_dlga_escrow_tender_id', $tender_id );
        $order->update_meta_data( '_dlga_escrow_company_id', $company_id );
        $order->update_meta_data( '_dlga_escrow_amount', $amount );
        $order->save();

        return $order->get_id();
    }
}
