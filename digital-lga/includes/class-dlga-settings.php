<?php
/**
 * Admin settings management.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Settings {

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Register all settings.
     */
    public static function register_settings() {
        $fields = array(
            'dlga_lga_name',
            'dlga_state',
            'dlga_contribution_rate',
            'dlga_worker_split',
            'dlga_business_split',
            'dlga_platform_fee',
            'dlga_personnel_pool',
            'dlga_infrastructure_pool',
            'dlga_emergency_pool',
            'dlga_payment_gateway',
            'dlga_payment_public_key',
            'dlga_payment_secret_key',
            'dlga_currency',
            'dlga_currency_symbol',
            'dlga_bidding_days',
            'dlga_vetting_days',
        );

        foreach ( $fields as $field ) {
            register_setting( 'dlga_settings_group', $field, array(
                'sanitize_callback' => array( __CLASS__, 'sanitize_setting' ),
            ) );
        }
    }

    /**
     * Sanitize a setting value.
     *
     * @param mixed $value The value to sanitize.
     * @return mixed
     */
    public static function sanitize_setting( $value ) {
        if ( is_numeric( $value ) ) {
            return floatval( $value );
        }
        return sanitize_text_field( $value );
    }

    /**
     * Get a setting value with fallback.
     *
     * @param string $key     Option key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get( $key, $default = '' ) {
        return get_option( $key, $default );
    }

    /**
     * Get contribution rate as decimal.
     *
     * @return float
     */
    public static function get_contribution_rate() {
        return floatval( self::get( 'dlga_contribution_rate', 10 ) ) / 100;
    }

    /**
     * Get worker split as decimal (percentage of total contribution).
     *
     * @return float
     */
    public static function get_worker_split() {
        return floatval( self::get( 'dlga_worker_split', 50 ) ) / 100;
    }

    /**
     * Get business split as decimal.
     *
     * @return float
     */
    public static function get_business_split() {
        return 1 - self::get_worker_split();
    }

    /**
     * Get platform fee as decimal.
     *
     * @return float
     */
    public static function get_platform_fee() {
        return floatval( self::get( 'dlga_platform_fee', 5 ) ) / 100;
    }

    /**
     * Get pool allocation percentages.
     *
     * @return array
     */
    public static function get_pool_allocations() {
        return array(
            'personnel'      => floatval( self::get( 'dlga_personnel_pool', 30 ) ) / 100,
            'infrastructure' => floatval( self::get( 'dlga_infrastructure_pool', 60 ) ) / 100,
            'emergency'      => floatval( self::get( 'dlga_emergency_pool', 10 ) ) / 100,
        );
    }

    /**
     * Get currency symbol.
     *
     * @return string
     */
    public static function get_currency_symbol() {
        return self::get( 'dlga_currency_symbol', "\xE2\x82\xA6" );
    }

    /**
     * Format amount with currency.
     *
     * @param float $amount The amount.
     * @return string
     */
    public static function format_amount( $amount ) {
        return self::get_currency_symbol() . number_format( floatval( $amount ), 2 );
    }

    /**
     * Calculate payroll breakdown.
     *
     * @param float $gross_salary The gross salary amount.
     * @return array
     */
    public static function calculate_payroll( $gross_salary ) {
        $rate              = self::get_contribution_rate();
        $worker_split      = self::get_worker_split();
        $business_split    = self::get_business_split();
        $fee_rate          = self::get_platform_fee();

        $total_contribution    = $gross_salary * $rate;
        $worker_contribution   = $total_contribution * $worker_split;
        $business_contribution = $total_contribution * $business_split;
        $net_to_worker         = $gross_salary - $worker_contribution;

        $subtotal     = $gross_salary + $business_contribution;
        $platform_fee = $subtotal * $fee_rate;
        $total_due    = $subtotal + $platform_fee;

        return array(
            'gross_salary'          => round( $gross_salary, 2 ),
            'worker_contribution'   => round( $worker_contribution, 2 ),
            'business_contribution' => round( $business_contribution, 2 ),
            'total_contribution'    => round( $total_contribution, 2 ),
            'net_to_worker'         => round( $net_to_worker, 2 ),
            'platform_fee'          => round( $platform_fee, 2 ),
            'total_business_pays'   => round( $total_due, 2 ),
        );
    }
}
