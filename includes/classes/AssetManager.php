<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

defined('ABSPATH') || exit;

final class AssetManager
{
    public const STYLE_VERSION  = NB_ACCOUNTS_VERSION;
    public const SCRIPT_VERSION = NB_ACCOUNTS_VERSION;

    /**
     * Register all plugin assets.
     */
    public static function register_assets(): void
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        $styles = [
            'nb-accounts-design-system' => ['design-system.css', []],
            'nb-accounts-components'    => ['components.css', ['nb-accounts-design-system']],
            'nb-accounts-frontend'      => ['frontend.css', ['nb-accounts-design-system', 'nb-accounts-components']],
            'nb-accounts-auth'          => ['auth.css', ['nb-accounts-design-system', 'nb-accounts-components']],
            'nb-accounts-dashboard'     => ['dashboard.css', ['nb-accounts-design-system', 'nb-accounts-components']],
            'nb-accounts-notifications' => ['notifications.css', ['nb-accounts-design-system', 'nb-accounts-components']],
            'nb-accounts-profile'       => ['profile.css', ['nb-accounts-design-system', 'nb-accounts-components']],
            'nb-accounts-admin'         => ['admin.css', []],
            'nb-accounts-style'         => ['style.css', ['nb-accounts-design-system']],
        ];

        foreach ($styles as $handle => [$file, $deps]) {
            wp_register_style(
                $handle,
                self::asset_url(self::minified_name('css/' . $file)),
                $deps,
                self::STYLE_VERSION
            );
        }

        $scripts = [
            'nb-accounts-utils'         => ['utils.js', ['jquery']],
            'nb-accounts-frontend'      => ['frontend.js', ['jquery', 'nb-accounts-utils']],
            'nb-accounts-auth'          => ['auth.js', ['jquery']],
            'nb-accounts-dashboard'     => ['dashboard.js', ['jquery', 'nb-accounts-frontend']],
            'nb-accounts-modal'         => ['modal.js', ['jquery']],
            'nb-accounts-notifications' => ['notifications.js', ['jquery']],
            'nb-accounts-profile'       => ['profile.js', ['jquery']],
            'nb-accounts-rest'          => ['rest.js', ['jquery']],
            'nb-accounts-settings'      => ['settings.js', ['jquery', 'wp-color-picker']],
            'nb-accounts-tables'        => ['tables.js', ['jquery']],
            'nb-accounts-validation'    => ['validation.js', ['jquery']],
            'nb-accounts-media'         => ['media.js', ['jquery']],
            'nb-accounts-admin'         => ['admin.js', ['jquery']],
        ];

        foreach ($scripts as $handle => [$file, $deps]) {
            wp_register_script(
                $handle,
                self::asset_url(self::minified_name('js/' . $file)),
                $deps,
                self::SCRIPT_VERSION,
                true
            );

            wp_script_add_data($handle, 'defer', true);
        }
    }

    /**
     * Enqueue frontend assets only where needed.
     */
    public static function enqueue_frontend_assets(): void
    {
        if (is_admin()) {
            return;
        }

        if (! self::is_accounts_frontend_context()) {
            return;
        }

        self::register_assets();

        wp_enqueue_style('nb-accounts-design-system');
        wp_enqueue_style('nb-accounts-components');
        wp_enqueue_style('nb-accounts-frontend');
        wp_enqueue_style('nb-accounts-style');

        wp_enqueue_script('nb-accounts-utils');
        wp_enqueue_script('nb-accounts-frontend');
        wp_enqueue_script('nb-accounts-rest');
        wp_enqueue_script('nb-accounts-validation');

        self::localize_frontend_script();

        if (self::is_auth_page()) {
            wp_enqueue_style('nb-accounts-auth');
            wp_enqueue_script('nb-accounts-auth');
        }

        if (self::is_dashboard_page()) {
            wp_enqueue_style('nb-accounts-dashboard');
            wp_enqueue_script('nb-accounts-dashboard');

            if (current_user_can('nb_review_articles') || current_user_can('manage_options')) {
                wp_enqueue_script('nb-accounts-modal');
                wp_enqueue_script('nb-accounts-tables');
            }
        }

        if (self::is_profile_page()) {
            wp_enqueue_style('nb-accounts-profile');
            wp_enqueue_script('nb-accounts-profile');
            wp_enqueue_script('nb-accounts-media');
        }

        if (self::is_notifications_page()) {
            wp_enqueue_style('nb-accounts-notifications');
            wp_enqueue_script('nb-accounts-notifications');
        }
    }

    /**
     * Enqueue admin assets only on plugin screens.
     */
    public static function enqueue_admin_assets(
        string $hook = ''
    ): void {
        if (! is_admin() || $hook === '' || ! self::is_plugin_admin_screen($hook)) {
            return;
        }

        self::register_assets();

        wp_enqueue_style('nb-accounts-admin');
        wp_enqueue_script('nb-accounts-admin');

        wp_localize_script(
            'nb-accounts-admin',
            'nbaAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('nba_admin'),
            ]
        );

        if (strpos($hook, 'newsblenda-accounts-settings') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('nb-accounts-settings');

        wp_localize_script(
            'nb-accounts-settings',
            'nbaSettings',
            [
                'ajaxUrl'        => admin_url('admin-ajax.php'),
                'nonce'          => wp_create_nonce('nba_admin'),
                'testEmailNonce' => wp_create_nonce('nba_send_test_email'),
                'smtpTestNonce'  => wp_create_nonce('nba_smtp_test'),
                'resetNonce'     => wp_create_nonce('nba_reset_settings'),
                'i18n'           => [
                    'sending'      => __('Sending…', 'newsblenda-accounts'),
                    'testing'      => __('Testing…', 'newsblenda-accounts'),
                    'confirmReset' => __('Reset all settings in this tab to their default values? This cannot be undone.', 'newsblenda-accounts'),
                    'saved'        => __('Settings saved.', 'newsblenda-accounts'),
                ],
            ]
        );
    }

    /**
     * Determine whether the current request needs accounts assets.
     */
    private static function is_accounts_frontend_context(): bool
    {
        if (self::is_dashboard_page()) {
            return true;
        }

        if (is_page([
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'submit',
            'profile',
            'notifications',
            'earnings',
            'payouts',
        ])) {
            return true;
        }

        if (! is_singular()) {
            return false;
        }

        $post = get_post();

        if (! $post instanceof \WP_Post) {
            return false;
        }

        foreach ([
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
        ] as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Localize the primary frontend script once.
     */
    private static function localize_frontend_script(): void
    {
        wp_localize_script(
            'nb-accounts-frontend',
            'NBAccounts',
            [
                'ajax_url'     => admin_url('admin-ajax.php'),
                'home_url'     => home_url(),
                'rest_url'     => esc_url_raw(rest_url()),
                'nonce'        => wp_create_nonce('nb_accounts'),
                'editor_nonce' => wp_create_nonce('nb_editor_action'),
                'author_nonce' => wp_create_nonce('nb_author_action'),
                'logged_in'    => is_user_logged_in(),
                'i18n'         => [
                    'showPassword'     => __('Show password', 'newsblenda-accounts'),
                    'hidePassword'     => __('Hide password', 'newsblenda-accounts'),
                    'strengthWeak'     => __('Weak', 'newsblenda-accounts'),
                    'strengthMedium'   => __('Medium', 'newsblenda-accounts'),
                    'strengthStrong'   => __('Strong', 'newsblenda-accounts'),
                    'strengthVStrong'  => __('Very Strong', 'newsblenda-accounts'),
                    'passwordsMatch'   => __('Passwords match', 'newsblenda-accounts'),
                    'passwordsNoMatch' => __('Passwords do not match', 'newsblenda-accounts'),
                    'confirmApprove'   => __('Approve this article?', 'newsblenda-accounts'),
                    'confirmPublish'   => __('Publish this article now?', 'newsblenda-accounts'),
                    'confirmResubmit'  => __('Resubmit this article for review?', 'newsblenda-accounts'),
                    'confirmDelete'    => __('Permanently delete this draft? This cannot be undone.', 'newsblenda-accounts'),
                ],
            ]
        );
    }

    private static function is_auth_page(): bool
    {
        return is_page([
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
        ]);
    }

    private static function is_dashboard_page(): bool
    {
        return is_page(['dashboard', 'editor-dashboard']);
    }

    private static function is_notifications_page(): bool
    {
        return is_page('notifications');
    }

    private static function is_profile_page(): bool
    {
        return is_page('profile');
    }

    private static function is_plugin_admin_screen(
        string $hook
    ): bool {
        foreach ([
            'newsblenda-accounts',
            'nb-payouts',
            'nb-reports',
        ] as $screen) {
            if (strpos($hook, $screen) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function asset_url(
        string $relative_path
    ): string {
        return NB_ACCOUNTS_URL . 'assets/' . ltrim($relative_path, '/');
    }

    private static function minified_name(
        string $relative_path
    ): string {
        $minified = preg_replace('/\.(css|js)$/', '.min.$1', $relative_path);
        $absolute = NB_ACCOUNTS_PATH . 'assets/' . ltrim((string) $minified, '/');

        if (file_exists($absolute)) {
            return (string) $minified;
        }

        return $relative_path;
    }
}
