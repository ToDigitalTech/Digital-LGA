<?php
/**
 * Custom roles and capabilities management.
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class DLGA_Roles {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'maybe_update_roles' ) );
    }

    /**
     * Update roles if version changed.
     */
    public static function maybe_update_roles() {
        $current = get_option( 'dlga_roles_version', '0' );
        if ( version_compare( $current, DIGITAL_LGA_VERSION, '<' ) ) {
            self::add_capabilities();
            update_option( 'dlga_roles_version', DIGITAL_LGA_VERSION );
        }
    }

    /**
     * Add custom capabilities to roles.
     */
    private static function add_capabilities() {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $caps = array(
                'manage_dlga',
                'manage_dlga_settings',
                'manage_dlga_tenders',
                'manage_dlga_committee',
                'manage_dlga_accountability',
                'approve_dlga_users',
                'approve_dlga_milestones',
                'manage_dlga_blacklist',
                'view_dlga_reports',
            );
            foreach ( $caps as $cap ) {
                $admin->add_cap( $cap );
            }
        }

        $reviewer = get_role( 'dlga_reviewer' );
        if ( $reviewer ) {
            $reviewer->add_cap( 'manage_dlga_tenders' );
            $reviewer->add_cap( 'approve_dlga_users' );
            $reviewer->add_cap( 'view_dlga_reports' );
        }

        $vetter = get_role( 'dlga_vetter' );
        if ( $vetter ) {
            $vetter->add_cap( 'manage_dlga_tenders' );
            $vetter->add_cap( 'view_dlga_reports' );
        }

        $accountability = get_role( 'dlga_accountability' );
        if ( $accountability ) {
            $accountability->add_cap( 'manage_dlga_accountability' );
            $accountability->add_cap( 'approve_dlga_milestones' );
            $accountability->add_cap( 'view_dlga_reports' );
        }
    }

    /**
     * Check if a user has a DLGA role.
     *
     * @param int|WP_User $user User ID or object.
     * @return string|false The DLGA role slug, or false.
     */
    public static function get_dlga_role( $user = null ) {
        if ( null === $user ) {
            $user = wp_get_current_user();
        } elseif ( is_int( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( ! $user || ! $user->exists() ) {
            return false;
        }

        $dlga_roles = array(
            'dlga_business',
            'dlga_citizen',
            'dlga_civil_servant',
            'dlga_reviewer',
            'dlga_vetter',
            'dlga_accountability',
        );

        foreach ( $dlga_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                return $role;
            }
        }

        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return 'administrator';
        }

        return false;
    }

    /**
     * Get human-readable role name.
     *
     * @param string $role Role slug.
     * @return string
     */
    public static function get_role_label( $role ) {
        $labels = array(
            'dlga_business'       => __( 'Business', 'digital-lga' ),
            'dlga_citizen'        => __( 'Citizen', 'digital-lga' ),
            'dlga_civil_servant'  => __( 'Civil Servant', 'digital-lga' ),
            'dlga_reviewer'       => __( 'Committee Reviewer', 'digital-lga' ),
            'dlga_vetter'         => __( 'Committee Vetter', 'digital-lga' ),
            'dlga_accountability' => __( 'Accountability Team', 'digital-lga' ),
            'administrator'       => __( 'Administrator', 'digital-lga' ),
        );

        return isset( $labels[ $role ] ) ? $labels[ $role ] : $role;
    }
}
