<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Core;

defined('ABSPATH') || exit;

class Plugin
{
    /**
     * Singleton instance.
     */
    private static ?Plugin $instance = null;

    /**
     * Loader.
     */
    private Loader $loader;

    /**
     * Plugin booted.
     */
    private bool $booted = false;

    /**
     * Plugin instance.
     */
    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->loader = new Loader();

        $this->load_dependencies();

        $this->register_hooks();

        do_action(
            'nb_accounts_plugin_loaded'
        );
    }

    /**
     * Loader.
     */
    public function loader(): Loader
    {
        return $this->loader;
    }

    /**
     * Plugin version.
     */
    public function version(): string
    {
        return NB_ACCOUNTS_VERSION;
    }

    /**
     * Plugin path.
     */
    public function path(): string
    {
        return NB_ACCOUNTS_PATH;
    }

    /**
     * Plugin URL.
     */
    public function url(): string
    {
        return NB_ACCOUNTS_URL;
    }

    /**
     * Plugin basename.
     */
    public function basename(): string
    {
        return NB_ACCOUNTS_BASENAME;
    }

    /**
     * Load dependencies.
     */
    private function load_dependencies(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Core
        |--------------------------------------------------------------------------
        */

        new Router();

        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Database\Database();

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Roles\Roles();

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Admin\Admin();

        new \Newsblenda\Accounts\Admin\Settings();

        new \Newsblenda\Accounts\Admin\SettingsMigrator();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Auth\Auth();

        new \Newsblenda\Accounts\Auth\Login();

        new \Newsblenda\Accounts\Auth\Register();

        new \Newsblenda\Accounts\Auth\Logout();

        new \Newsblenda\Accounts\Auth\Password();

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Dashboard\Dashboard();

        /*
        |--------------------------------------------------------------------------
        | Workflow
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Workflow\WorkflowManager();

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Profile\Profile();

        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Security\Security();

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Notifications\Notifications();

        /*
        |--------------------------------------------------------------------------
        | REST
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\REST\Routes();

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Email\Mailer();
        
                /*
        |--------------------------------------------------------------------------
        | Activity
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Activity\Activity();

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Validation\Validator();

        if (class_exists(\Newsblenda\Accounts\Submission\Submission::class)) {
            new \Newsblenda\Accounts\Submission\Submission();
        }

        /*
        |--------------------------------------------------------------------------
        | Earnings
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Earnings\Earnings();

        /*
        |--------------------------------------------------------------------------
        | Payouts
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Payouts\Payouts();

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        new \Newsblenda\Accounts\Reports\Reports();
    }
    
        /**
     * Register WordPress hooks.
     */
    private function register_hooks(): void
    {
        $this->loader->add_action(
            'plugins_loaded',
            $this,
            'plugins_loaded'
        );

        $this->loader->add_action(
            'init',
            $this,
            'load_textdomain'
        );

        $this->loader->add_action(
            'init',
            $this,
            'check_upgrade'
        );

        $this->loader->add_action(
            'init',
            $this,
            'register_assets'
        );

        $this->loader->add_action(
            'wp_enqueue_scripts',
            $this,
            'frontend_assets'
        );

        $this->loader->add_action(
            'admin_enqueue_scripts',
            $this,
            'admin_assets'
        );

        $this->loader->add_action(
            'admin_init',
            $this,
            'activation_redirect'
        );
    }

    /**
     * Plugins loaded.
     */
    public function plugins_loaded(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        do_action(
            'nb_accounts_booted'
        );
    }

    /**
     * Load translations.
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'newsblenda-accounts',
            false,
            dirname(NB_ACCOUNTS_BASENAME) . '/languages'
        );
    }

    /**
     * Check for upgrades.
     */
    public function check_upgrade(): void
    {
        if (class_exists('\Newsblenda\Accounts\Migrations\Migrator')) {
            \Newsblenda\Accounts\Migrations\Migrator::run_migrations();
        }

        if (! Activator::needs_upgrade()) {
            return;
        }

        update_option(
            'nb_accounts_version',
            NB_ACCOUNTS_VERSION
        );

        do_action(
            'nb_accounts_upgraded'
        );
    }

    /**
     * Register assets.
     */
    public function register_assets(): void
    {
        if (class_exists('\Newsblenda\Accounts\Classes\AssetManager')) {
            \Newsblenda\Accounts\Classes\AssetManager::register_assets();
        }
    }

    /**
     * Redirect after activation.
     */
    public function activation_redirect(): void
    {
        if (! Activator::should_redirect()) {
            return;
        }

        Activator::clear_activation_redirect();

        wp_safe_redirect(
            admin_url(
                'admin.php?page=newsblenda-accounts'
            )
        );

        exit;
    }

    /**
     * Frontend assets.
     */
    public function frontend_assets(): void
    {
        if (class_exists('\Newsblenda\Accounts\Classes\AssetManager')) {
            \Newsblenda\Accounts\Classes\AssetManager::enqueue_frontend_assets();
        }
    }

    /**
     * Determine whether frontend assets should be loaded.
     */
    private function should_load_frontend_assets(): bool
    {
        if (is_admin()) {
            return false;
        }

        if ($this->is_dashboard_context()) {
            return true;
        }

        if (is_page(
            [
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
            ]
        )) {
            return true;
        }

        return $this->has_accounts_shortcode();
    }

    /**
     * Determine whether current request is a dashboard screen.
     */
    private function is_dashboard_context(): bool
    {
        return is_page(['dashboard', 'editor-dashboard']);
    }

    /**
     * Check whether current singular content has account shortcodes.
     */
    private function has_accounts_shortcode(): bool
    {
        if (! is_singular()) {
            return false;
        }

        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return false;
        }

        $shortcodes = [
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

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin assets.
     */
    public function admin_assets(string $hook = ''): void
    {
        if (class_exists('\Newsblenda\Accounts\Classes\AssetManager')) {
            \Newsblenda\Accounts\Classes\AssetManager::enqueue_admin_assets($hook);
        }
    }

    /**
     * Determine whether admin assets should be loaded on current screen.
     */
    private function should_load_admin_assets(string $hook): bool
    {
        if (! is_admin()) {
            return false;
        }

        if ($hook === '') {
            return false;
        }

        $screens = [
            'newsblenda-accounts',
            'nb-settings',
            'nb-payouts',
            'nb-reports',
        ];

        foreach ($screens as $screen) {
            if (strpos($hook, $screen) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Start the plugin.
     */
    public function run(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loader->run();

        $this->booted = true;

        do_action(
            'nb_accounts_running',
            $this
        );
    }

    /**
     * Determine whether the plugin has booted.
     */
    public function is_booted(): bool
    {
        return $this->booted;
    }

    /**
     * Determine whether the plugin is running.
     */
    public function is_running(): bool
    {
        return $this->booted;
    }

    /**
     * Get plugin information.
     */
    public function info(): array
    {
        return [

            'name' => 'Newsblenda Accounts',

            'version' => $this->version(),

            'path' => $this->path(),

            'url' => $this->url(),

            'basename' => $this->basename(),

            'booted' => $this->booted,

            'wordpress' => get_bloginfo('version'),

            'php' => PHP_VERSION,

        ];
    }

    /**
     * Plugin health.
     */
    public function health(): array
    {
        return [

            'database' => class_exists(
                '\Newsblenda\Accounts\Database\Database'
            ),

            'roles' => class_exists(
                '\Newsblenda\Accounts\Roles\Roles'
            ),

            'mailer' => class_exists(
                '\Newsblenda\Accounts\Email\Mailer'
            ),

            'router' => class_exists(
                '\Newsblenda\Accounts\Core\Router'
            ),

            'loader' => $this->loader instanceof Loader,

        ];
    }

    /**
     * Check whether every core component is available.
     */
    public function healthy(): bool
    {
        foreach ($this->health() as $status) {

            if (! $status) {
                return false;
            }

        }

        return true;
    }

    /**
     * Magic getter.
     *
     * @param string $property
     * @return mixed|null
     */
    public function __get(string $property)
    {
        if ($property === 'loader') {
            return $this->loader;
        }

        return null;
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void
    {
        throw new \Exception(
            'Cannot unserialize singleton.'
        );
    }
}