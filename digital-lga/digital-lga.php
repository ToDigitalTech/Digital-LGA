<?php
/**
 * Plugin Name: Digital LGA - Parallel Civic Infrastructure Platform
 * Plugin URI: https://github.com/ToDigitalTech/Digital-LGA
 * Description: Open-source platform enabling Local Government Areas to create transparent, accountable parallel civic infrastructure by collecting voluntary contributions and managing public projects via competitive tenders.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Digital LGA Contributors
 * Author URI: https://github.com/ToDigitalTech/Digital-LGA
 * License: Apache License 2.0
 * License URI: https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: digital-lga
 * Domain Path: /languages
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'DIGITAL_LGA_VERSION', '1.0.0' );
define( 'DIGITAL_LGA_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIGITAL_LGA_URL', plugin_dir_url( __FILE__ ) );
define( 'DIGITAL_LGA_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation.
 */
function activate_digital_lga() {
    require_once DIGITAL_LGA_PATH . 'includes/class-dlga-activator.php';
    DLGA_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_digital_lga' );

/**
 * Plugin deactivation.
 */
function deactivate_digital_lga() {
    require_once DIGITAL_LGA_PATH . 'includes/class-dlga-activator.php';
    DLGA_Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_digital_lga' );

/**
 * Load plugin files.
 */
require DIGITAL_LGA_PATH . 'includes/class-dlga-activator.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-roles.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-settings.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-business.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-citizen.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-civil-servant.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-payroll.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-distribution.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-tender.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-committee.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-woocommerce.php';
require DIGITAL_LGA_PATH . 'includes/class-dlga-transparency.php';

if ( is_admin() ) {
    require DIGITAL_LGA_PATH . 'admin/class-dlga-admin.php';
}

require DIGITAL_LGA_PATH . 'public/class-dlga-public.php';

/**
 * Initialize plugin after all plugins are loaded.
 */
function run_digital_lga() {
    // Check for WooCommerce
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'Digital LGA requires WooCommerce to be installed and activated.', 'digital-lga' );
            echo '</p></div>';
        } );
        return;
    }

    DLGA_Roles::init();
    DLGA_Settings::init();
    DLGA_Business::init();
    DLGA_Citizen::init();
    DLGA_Civil_Servant::init();
    DLGA_Payroll::init();
    DLGA_Distribution::init();
    DLGA_Tender::init();
    DLGA_Committee::init();
    DLGA_WooCommerce_Integration::init();
    DLGA_Transparency::init();

    if ( is_admin() ) {
        DLGA_Admin::init();
    }

    DLGA_Public::init();
}
add_action( 'plugins_loaded', 'run_digital_lga' );
