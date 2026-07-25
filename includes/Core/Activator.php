<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Core;

defined('ABSPATH') || exit;

class Activator
{
    /**
     * Activate plugin.
     */
    public static function activate(): void
    {
        self::create_roles();

        self::create_database();

        self::create_options();

        self::create_pages();

        self::create_directories();

        self::schedule_events();

        update_option(
            'nb_accounts_version',
            NB_ACCOUNTS_VERSION
        );

        update_option(
            'nb_accounts_installed',
            current_time('mysql')
        );

        update_option(
            'nb_accounts_activation_redirect',
            1
        );

        do_action(
            'nb_accounts_activated'
        );

        flush_rewrite_rules();
    }

    /**
     * Create Newsblenda roles.
     */
    private static function create_roles(): void
    {
        if (
            class_exists(
                '\Newsblenda\Accounts\Roles\Roles'
            )
        ) {

            \Newsblenda\Accounts\Roles\Roles::create_roles();

        }
    }

    /**
     * Install database.
     */
    private static function create_database(): void
    {
        if (
            class_exists(
                '\Newsblenda\Accounts\Database\Database'
            )
        ) {

            \Newsblenda\Accounts\Database\Database::install();

        }
    }

    /**
     * Default options.
     */
    private static function create_options(): void
    {
        $defaults = [

            /*
            |--------------------------------------------------------------------------
            | Registration
            |--------------------------------------------------------------------------
            */

            'allow_author_registration'     => 1,
            'allow_subscriber_registration' => 1,
            'require_email_verification'    => 1,
            'require_admin_approval'        => 1,

            /*
            |--------------------------------------------------------------------------
            | Routes
            |--------------------------------------------------------------------------
            */

            'login_slug'            => 'login',
            'register_slug'         => 'register',
            'dashboard_slug'        => 'dashboard',
            'profile_slug'          => 'profile',
            'forgot_password_slug'  => 'forgot-password',
            'reset_password_slug'   => 'reset-password',
            'verify_email_slug'     => 'verify-email',
            'notifications_slug'    => 'notifications',
            'earnings_slug'         => 'earnings',
            'submit_slug'           => 'submit',

            /*
            |--------------------------------------------------------------------------
            | Security
            |--------------------------------------------------------------------------
            */

            'max_login_attempts' => 5,
            'lockout_minutes'    => 30,

            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */

            'sender_name'  => get_bloginfo('name'),
            'sender_email' => get_option('admin_email'),

        ];

        if (
            get_option('nb_accounts_settings') === false
        ) {

            add_option(
                'nb_accounts_settings',
                $defaults
            );

        }
    }
    
    
        /**
     * Create required frontend pages.
     */
    private static function create_pages(): void
    {
        $pages = [

            'login' => [
                'title'   => 'Login',
                'content' => '[nbe_login]',
            ],

            'register' => [
                'title'   => 'Register',
                'content' => '[nbe_register]',
            ],

            'dashboard' => [
                'title'   => 'Dashboard',
                'content' => '[nbe_dashboard]',
            ],

            'profile' => [
                'title'   => 'Profile',
                'content' => '[nbe_profile]',
            ],

            'forgot-password' => [
                'title'   => 'Forgot Password',
                'content' => '[nbe_forgot_password]',
            ],

            'reset-password' => [
                'title'   => 'Reset Password',
                'content' => '[nbe_reset_password]',
            ],

            'verify-email' => [
                'title'   => 'Verify Email',
                'content' => '[nbe_verify_email]',
            ],

            'notifications' => [
                'title'   => 'Notifications',
                'content' => '[nbe_notifications]',
            ],

            'earnings' => [
                'title'   => 'Earnings',
                'content' => '[nb_earnings]',
            ],

            'submit' => [
                'title'   => 'Submit Article',
                'content' => '[nb_submit]',
            ],

        ];

        foreach ($pages as $slug => $page) {

            if (get_page_by_path($slug)) {
                continue;
            }

            wp_insert_post(
                [
                    'post_title'   => $page['title'],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $page['content'],
                ]
            );

        }
    }

    /**
     * Create upload directories.
     */
    private static function create_directories(): void
    {
        $upload = wp_upload_dir();

        $directories = [

            $upload['basedir'] . '/newsblenda',

            $upload['basedir'] . '/newsblenda/avatars',

            $upload['basedir'] . '/newsblenda/documents',

            $upload['basedir'] . '/newsblenda/temp',

            $upload['basedir'] . '/newsblenda/logs',

        ];

        foreach ($directories as $directory) {

            wp_mkdir_p($directory);

            $index = trailingslashit($directory) . 'index.php';

            if (! file_exists($index)) {

                file_put_contents(
                    $index,
                    "<?php\n// Silence is golden."
                );

            }

        }
    }

    /**
     * Schedule cron events.
     */
    private static function schedule_events(): void
    {
        if (
            ! wp_next_scheduled(
                'nb_accounts_daily_event'
            )
        ) {

            wp_schedule_event(
                time(),
                'daily',
                'nb_accounts_daily_event'
            );

        }

        if (
            ! wp_next_scheduled(
                'nb_accounts_hourly_event'
            )
        ) {

            wp_schedule_event(
                time(),
                'hourly',
                'nb_accounts_hourly_event'
            );

        }

        if (
            ! wp_next_scheduled(
                'nb_accounts_five_minutes'
            )
        ) {

            wp_schedule_event(
                time(),
                'hourly',
                'nb_accounts_five_minutes'
            );

        }
    }
    
        /**
     * Check whether the plugin has been installed.
     */
    public static function installed(): bool
    {
        return (bool) get_option(
            'nb_accounts_installed',
            false
        );
    }

    /**
     * Get installed version.
     */
    public static function installed_version(): string
    {
        return (string) get_option(
            'nb_accounts_version',
            ''
        );
    }

    /**
     * Determine whether an upgrade is required.
     */
    public static function needs_upgrade(): bool
    {
        return version_compare(
            self::installed_version(),
            NB_ACCOUNTS_VERSION,
            '<'
        );
    }

    /**
     * Mark activation redirect complete.
     */
    public static function clear_activation_redirect(): void
    {
        delete_option(
            'nb_accounts_activation_redirect'
        );
    }

    /**
     * Should redirect after activation?
     */
    public static function should_redirect(): bool
    {
        return (bool) get_option(
            'nb_accounts_activation_redirect',
            false
        );
    }

    /**
     * Get plugin settings.
     */
    public static function settings(): array
    {
        return (array) get_option(
            'nb_accounts_settings',
            []
        );
    }

    /**
     * Update plugin settings.
     */
    public static function update_settings(
        array $settings
    ): bool {

        return update_option(
            'nb_accounts_settings',
            $settings
        );

    }

    /**
     * Plugin upload directory.
     */
    public static function upload_directory(): string
    {
        $upload = wp_upload_dir();

        return trailingslashit(
            $upload['basedir']
        ) . 'newsblenda/';
    }

    /**
     * Plugin upload URL.
     */
    public static function upload_url(): string
    {
        $upload = wp_upload_dir();

        return trailingslashit(
            $upload['baseurl']
        ) . 'newsblenda/';
    }

    /**
     * Ensure upload directories exist.
     */
    public static function verify_directories(): void
    {
        self::create_directories();
    }

    /**
     * Ensure scheduled events exist.
     */
    public static function verify_schedule(): void
    {
        self::schedule_events();
    }

    /**
     * Ensure required pages exist.
     */
    public static function verify_pages(): void
    {
        self::create_pages();
    }

    /**
     * Ensure required roles exist.
     */
    public static function verify_roles(): void
    {
        self::create_roles();
    }

    /**
     * Ensure database tables exist.
     */
    public static function verify_database(): void
    {
        self::create_database();
    }
}