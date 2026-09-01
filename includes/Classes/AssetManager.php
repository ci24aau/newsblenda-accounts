<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

defined('ABSPATH') || exit;

class AssetManager
{
    public const VERSION = NB_ACCOUNTS_VERSION;

    public static function register_assets(): void
    {
        wp_register_style(
            'nb-accounts-design-system',
            NB_ACCOUNTS_URL . 'assets/css/design-system.css',
            [],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-components',
            NB_ACCOUNTS_URL . 'assets/css/components.css',
            ['nb-accounts-design-system'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-frontend',
            NB_ACCOUNTS_URL . 'assets/css/frontend.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-auth',
            NB_ACCOUNTS_URL . 'assets/css/auth.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-dashboard',
            NB_ACCOUNTS_URL . 'assets/css/dashboard.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-profile',
            NB_ACCOUNTS_URL . 'assets/css/profile.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-notifications',
            NB_ACCOUNTS_URL . 'assets/css/notifications.css',
            ['nb-accounts-design-system', 'nb-accounts-components'],
            self::VERSION
        );

        wp_register_style(
            'nb-accounts-admin',
            NB_ACCOUNTS_URL . 'assets/css/admin.css',
            ['nb-accounts-design-system'],
            self::VERSION
        );

        wp_register_script(
            'nb-accounts-frontend',
            NB_ACCOUNTS_URL . 'assets/js/frontend.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_register_script(
            'nb-accounts-auth',
            NB_ACCOUNTS_URL . 'assets/js/auth.js',
            ['nb-accounts-frontend'],
            self::VERSION,
            true
        );

        wp_register_script(
            'nb-accounts-dashboard',
            NB_ACCOUNTS_URL . 'assets/js/dashboard.js',
            ['nb-accounts-frontend'],
            self::VERSION,
            true
        );

        wp_register_script(
            'nb-accounts-profile',
            NB_ACCOUNTS_URL . 'assets/js/profile.js',
            ['nb-accounts-frontend'],
            self::VERSION,
            true
        );

        wp_register_script(
            'nb-accounts-notifications',
            NB_ACCOUNTS_URL . 'assets/js/notifications.js',
            ['nb-accounts-frontend'],
            self::VERSION,
            true
        );

        wp_register_script(
            'nb-accounts-admin',
            NB_ACCOUNTS_URL . 'assets/js/admin.js',
            ['jquery'],
            self::VERSION,
            true
        );

        foreach (
            [
                'nb-accounts-frontend',
                'nb-accounts-auth',
                'nb-accounts-dashboard',
                'nb-accounts-profile',
                'nb-accounts-notifications',
                'nb-accounts-admin',
            ] as $handle
        ) {
            wp_script_add_data($handle, 'defer', true);
        }
    }

    public static function enqueue_frontend_assets(): void
    {
        if (! self::should_load_frontend_assets()) {
            return;
        }

        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-components');
        wp_enqueue_style('nb-accounts-frontend');
        wp_enqueue_script('nb-accounts-frontend');

        if (self::is_auth_context()) {
            wp_enqueue_style('nb-accounts-auth');
            wp_enqueue_script('nb-accounts-auth');
        }

        if (self::is_dashboard_context()) {
            wp_enqueue_style('nb-accounts-dashboard');
            wp_enqueue_script('nb-accounts-dashboard');
        }

        if (is_page(['profile', 'earnings', 'payouts'])) {
            wp_enqueue_style('nb-accounts-profile');
            wp_enqueue_script('nb-accounts-profile');
        }

        if (is_page('notifications')) {
            wp_enqueue_style('nb-accounts-notifications');
            wp_enqueue_script('nb-accounts-notifications');
        }

        wp_localize_script(
            'nb-accounts-frontend',
            'NBAccounts',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'home_url' => home_url(),
                'rest_url' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('nb_accounts'),
                'editor_nonce' => wp_create_nonce('nb_editor_action'),
                'author_nonce' => wp_create_nonce('nb_author_action'),
                'logged_in' => is_user_logged_in(),
                'i18n' => [
                    'showPassword' => __('Show password', 'newsblenda-accounts'),
                    'hidePassword' => __('Hide password', 'newsblenda-accounts'),
                    'strengthWeak' => __('Weak', 'newsblenda-accounts'),
                    'strengthMedium' => __('Medium', 'newsblenda-accounts'),
                    'strengthStrong' => __('Strong', 'newsblenda-accounts'),
                    'strengthVStrong' => __('Very Strong', 'newsblenda-accounts'),
                    'passwordsMatch' => __('Passwords match', 'newsblenda-accounts'),
                    'passwordsNoMatch' => __('Passwords do not match', 'newsblenda-accounts'),
                    'confirmApprove' => __('Approve this article?', 'newsblenda-accounts'),
                    'confirmPublish' => __('Publish this article now?', 'newsblenda-accounts'),
                    'confirmResubmit' => __('Resubmit this article for review?', 'newsblenda-accounts'),
                    'confirmDelete' => __('Permanently delete this draft? This cannot be undone.', 'newsblenda-accounts'),
                ],
            ]
        );
    }

    public static function enqueue_admin_assets(string $hook = ''): void
    {
        if (! self::should_load_admin_assets($hook)) {
            return;
        }

        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-admin');
        wp_enqueue_script('nb-accounts-admin');

        wp_localize_script(
            'nb-accounts-admin',
            'NBAccountsAdmin',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nb_accounts_admin'),
            ]
        );
    }

    private static function should_load_frontend_assets(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (self::is_dashboard_context() || self::is_auth_context()) {
            return true;
        }

        if (is_page(['submit', 'profile', 'notifications', 'earnings', 'payouts'])) {
            return true;
        }

        if (! is_singular()) {
            return false;
        }

        $post = get_post();

        if (! $post instanceof \WP_Post) {
            return false;
        }

        foreach (self::shortcodes() as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    private static function should_load_admin_assets(string $hook): bool
    {
        if (! is_admin() || $hook === '') {
            return false;
        }

        foreach (['newsblenda-accounts', 'nb-settings', 'nb-payouts', 'nb-reports'] as $screen) {
            if (strpos($hook, $screen) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function is_dashboard_context(): bool
    {
        return is_page(['dashboard', 'editor-dashboard']);
    }

    private static function is_auth_context(): bool
    {
        return is_page(['login', 'register', 'forgot-password', 'reset-password', 'verify-email']);
    }

    /**
     * @return array<int, string>
     */
    private static function shortcodes(): array
    {
        return [
            'nbe_dashboard',
            'nb_dashboard',
            'nbe_login',
            'nb_login',
            'nbe_register',
            'nb_register',
            'nbe_profile',
            'nb_profile',
            'nbe_notifications',
            'nb_notifications',
            'nbe_earnings',
            'nb_earnings',
            'nbe_submit',
            'nb_submit',
            'nbe_forgot_password',
            'nb_forgot_password',
            'nbe_reset_password',
            'nb_reset_password',
            'nbe_verify_email',
            'nb_verify_email',
        ];
    }
}
