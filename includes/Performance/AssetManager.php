<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Performance;

defined('ABSPATH') || exit;

/**
 * Conditional asset registration and enqueueing helpers.
 *
 * Assets are only enqueued on the pages that actually need them,
 * reducing unnecessary payload on every other page.
 */
class AssetManager
{
    /**
     * Asset version – shared with the plugin constant when available.
     */
    private static function version(): string
    {
        return defined('NB_ACCOUNTS_VERSION') ? NB_ACCOUNTS_VERSION : '1.0.0';
    }

    /**
     * Base URL for plugin assets.
     */
    private static function base_url(): string
    {
        return defined('NB_ACCOUNTS_URL') ? NB_ACCOUNTS_URL : '';
    }

    /**
     * Register all plugin CSS handles (without enqueueing).
     */
    public static function register_styles(): void
    {
        $url = self::base_url();
        $ver = self::version();

        wp_register_style(
            'nb-accounts-design-system',
            $url . 'assets/css/design-system.css',
            [],
            $ver
        );

        wp_register_style(
            'nb-accounts-components',
            $url . 'assets/css/components.css',
            ['nb-accounts-design-system'],
            $ver
        );

        wp_register_style(
            'nb-accounts-frontend',
            $url . 'assets/css/frontend.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            $ver
        );

        wp_register_style(
            'nb-accounts-dashboard',
            $url . 'assets/css/dashboard.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            $ver
        );

        wp_register_style(
            'nb-accounts-auth',
            $url . 'assets/css/auth.css',
            ['nb-accounts-design-system'],
            $ver
        );

        wp_register_style(
            'nb-accounts-admin',
            $url . 'assets/css/admin.css',
            ['nb-accounts-design-system'],
            $ver
        );
    }

    /**
     * Register all plugin JS handles (without enqueueing).
     */
    public static function register_scripts(): void
    {
        $url = self::base_url();
        $ver = self::version();

        wp_register_script(
            'nb-accounts-frontend',
            $url . 'assets/js/frontend.js',
            ['jquery'],
            $ver,
            true
        );

        wp_register_script(
            'nb-accounts-admin',
            $url . 'assets/js/admin.js',
            ['jquery'],
            $ver,
            true
        );
    }

    /**
     * Enqueue assets for auth pages (login, register, password reset, etc.).
     */
    public static function enqueue_auth_assets(): void
    {
        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-components');
        wp_enqueue_style('nb-accounts-auth');
        wp_enqueue_style('nb-accounts-frontend');
        wp_enqueue_script('nb-accounts-frontend');
    }

    /**
     * Enqueue assets for dashboard pages (author / editor).
     */
    public static function enqueue_dashboard_assets(): void
    {
        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-components');
        wp_enqueue_style('nb-accounts-frontend');
        wp_enqueue_style('nb-accounts-dashboard');
        wp_enqueue_script('nb-accounts-frontend');
    }

    /**
     * Enqueue assets for admin settings pages.
     */
    public static function enqueue_admin_assets(): void
    {
        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-admin');
        wp_enqueue_script('nb-accounts-admin');
    }
}
