<?php
/**
 * Public-facing functionality.
 *
 * Registers pages, shortcodes, and enqueues frontend assets.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Public {

    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
        add_filter( 'template_include', array( __CLASS__, 'template_override' ) );
        add_shortcode( 'dlga_login', array( __CLASS__, 'login_form_shortcode' ) );
        add_action( 'wp_login_failed', array( __CLASS__, 'login_failed' ) );
    }

    /**
     * Enqueue public scripts and styles.
     */
    public static function enqueue_assets() {
        wp_enqueue_style(
            'dlga-public',
            DIGITAL_LGA_URL . 'public/css/dlga-public.css',
            array(),
            DIGITAL_LGA_VERSION
        );

        wp_enqueue_script(
            'dlga-public',
            DIGITAL_LGA_URL . 'public/js/dlga-public.js',
            array( 'jquery' ),
            DIGITAL_LGA_VERSION,
            true
        );

        wp_localize_script( 'dlga-public', 'dlga_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dlga_ajax_nonce' ),
        ) );
    }

    /**
     * Register rewrite rules for custom pages.
     */
    public static function register_rewrite_rules() {
        add_rewrite_rule( '^dlga/register-business/?$', 'index.php?dlga_page=register-business', 'top' );
        add_rewrite_rule( '^dlga/register-citizen/?$', 'index.php?dlga_page=register-citizen', 'top' );
        add_rewrite_rule( '^dlga/register-civil-servant/?$', 'index.php?dlga_page=register-civil-servant', 'top' );
        add_rewrite_rule( '^dlga/dashboard/?$', 'index.php?dlga_page=dashboard', 'top' );
        add_rewrite_rule( '^dlga/transparency/?$', 'index.php?dlga_page=transparency', 'top' );
        add_rewrite_rule( '^dlga/submit-job-idea/?$', 'index.php?dlga_page=submit-job-idea', 'top' );
        add_rewrite_rule( '^dlga/company/([^/]+)/?$', 'index.php?dlga_page=company-profile&dlga_company=$matches[1]', 'top' );

        add_rewrite_tag( '%dlga_page%', '([^&]+)' );
        add_rewrite_tag( '%dlga_company%', '([^&]+)' );
    }

    /**
     * Override template for custom DLGA pages.
     *
     * @param string $template Default template.
     * @return string
     */
    public static function template_override( $template ) {
        $dlga_page = get_query_var( 'dlga_page' );

        if ( empty( $dlga_page ) ) {
            return $template;
        }

        // Return the default template - the shortcodes handle the content
        return $template;
    }

    /**
     * Login form shortcode.
     *
     * @return string
     */
    public static function login_form_shortcode() {
        if ( is_user_logged_in() ) {
            $role = DLGA_Roles::get_dlga_role();

            $dashboard_links = array(
                'dlga_business'       => '<a href="' . esc_url( home_url( '/dlga/dashboard/' ) ) . '">' . esc_html__( 'Go to Business Dashboard', 'digital-lga' ) . '</a>',
                'dlga_citizen'        => '<a href="' . esc_url( home_url( '/dlga/dashboard/' ) ) . '">' . esc_html__( 'Go to Citizen Dashboard', 'digital-lga' ) . '</a>',
                'dlga_civil_servant'  => '<a href="' . esc_url( home_url( '/dlga/dashboard/' ) ) . '">' . esc_html__( 'Go to Dashboard', 'digital-lga' ) . '</a>',
                'administrator'       => '<a href="' . esc_url( admin_url( 'admin.php?page=digital-lga-settings' ) ) . '">' . esc_html__( 'Go to Admin Dashboard', 'digital-lga' ) . '</a>',
            );

            $link = isset( $dashboard_links[ $role ] ) ? $dashboard_links[ $role ] : '';

            return '<div class="dlga-notice dlga-notice-info">'
                . sprintf( esc_html__( 'You are logged in as %s.', 'digital-lga' ), esc_html( wp_get_current_user()->display_name ) )
                . ' ' . $link
                . ' | <a href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Logout', 'digital-lga' ) . '</a>'
                . '</div>';
        }

        ob_start();
        ?>
        <div class="dlga-login-form">
            <h2><?php esc_html_e( 'Login to Digital LGA', 'digital-lga' ); ?></h2>

            <?php if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) : ?>
                <div class="dlga-notice dlga-notice-error">
                    <?php esc_html_e( 'Invalid email or password. Please try again.', 'digital-lga' ); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( wp_login_url() ); ?>">
                <div class="dlga-form-group">
                    <label for="user_login"><?php esc_html_e( 'Email', 'digital-lga' ); ?></label>
                    <input type="email" name="log" id="user_login" required>
                </div>
                <div class="dlga-form-group">
                    <label for="user_pass"><?php esc_html_e( 'Password', 'digital-lga' ); ?></label>
                    <input type="password" name="pwd" id="user_pass" required>
                </div>
                <div class="dlga-form-group">
                    <label>
                        <input type="checkbox" name="rememberme" value="forever">
                        <?php esc_html_e( 'Remember Me', 'digital-lga' ); ?>
                    </label>
                </div>
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/dlga/dashboard/' ) ); ?>">
                <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Login', 'digital-lga' ); ?></button>
            </form>

            <div class="dlga-register-links">
                <p><?php esc_html_e( 'New here? Register as:', 'digital-lga' ); ?></p>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/dlga/register-business/' ) ); ?>"><?php esc_html_e( 'Business', 'digital-lga' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dlga/register-citizen/' ) ); ?>"><?php esc_html_e( 'Citizen', 'digital-lga' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dlga/register-civil-servant/' ) ); ?>"><?php esc_html_e( 'Civil Servant', 'digital-lga' ); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Redirect failed login back to frontend.
     *
     * @param string $username Username attempted.
     */
    public static function login_failed( $username ) {
        $referrer = wp_get_referer();
        if ( $referrer && strpos( $referrer, 'wp-login.php' ) === false ) {
            wp_redirect( add_query_arg( 'login', 'failed', $referrer ) );
            exit;
        }
    }
}
