<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Core;

defined('ABSPATH') || exit;

class Router
{
    /**
     * Registered routes.
     */
    private array $routes = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_routes']);
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('template_redirect', [$this, 'load_template']);

        add_shortcode('nbe_login', [$this, 'render_shortcode']);
        add_shortcode('nb_login', [$this, 'render_shortcode']);
        add_shortcode('nbe_register', [$this, 'render_shortcode']);
        add_shortcode('nb_register', [$this, 'render_shortcode']);
        add_shortcode('nbe_dashboard', [$this, 'render_shortcode']);
        add_shortcode('nb_dashboard', [$this, 'render_shortcode']);
        add_shortcode('nbe_profile', [$this, 'render_shortcode']);
        add_shortcode('nb_profile', [$this, 'render_shortcode']);
        add_shortcode('nbe_notifications', [$this, 'render_shortcode']);
        add_shortcode('nb_notifications', [$this, 'render_shortcode']);
        add_shortcode('nbe_forgot_password', [$this, 'render_shortcode']);
        add_shortcode('nb_forgot_password', [$this, 'render_shortcode']);
        add_shortcode('nbe_reset_password', [$this, 'render_shortcode']);
        add_shortcode('nb_reset_password', [$this, 'render_shortcode']);
        add_shortcode('nbe_verify_email', [$this, 'render_shortcode']);
        add_shortcode('nb_verify_email', [$this, 'render_shortcode']);
        add_shortcode('nbe_submit', [$this, 'render_shortcode']);
        add_shortcode('nb_submit', [$this, 'render_shortcode']);
        add_shortcode('nbe_earnings', [$this, 'render_shortcode']);
        add_shortcode('nb_earnings', [$this, 'render_shortcode']);

        $this->routes = [
            'login'            => 'templates/auth/login.php',
            'register'         => 'templates/auth/register.php',
            'forgot-password'  => 'templates/auth/forgot-password.php',
            'reset-password'   => 'templates/auth/reset-password.php',
            'verify-email'     => 'templates/auth/verify-email.php',
            'logout'           => null,
            'dashboard'        => 'templates/dashboard/dashboard.php',
            'profile'          => 'templates/profile/profile.php',
            'notifications'    => 'templates/notifications/notifications.php',
            'earnings'         => 'templates/earnings/index.php',
            'submit'           => 'templates/dashboard/submit.php',
            'editor-dashboard' => 'templates/dashboard/editor.php',
        ];
    }

    /**
     * Register query variable.
     */
    public function query_vars(array $vars): array
    {
        $vars[] = 'nb_account_page';

        return $vars;
    }

    /**
     * Register rewrite rules.
     */
    public function register_routes(): void
    {
        foreach (array_keys($this->routes) as $page) {
            add_rewrite_rule(
                '^' . preg_quote($page, '/') . '/?$',
                'index.php?nb_account_page=' . $page,
                'top'
            );
        }
    }

    /**
     * Render a registered shortcode page.
     */
    public function render_shortcode(array $atts = [], ?string $content = null, string $tag = ''): string
    {
        $page = str_replace(['nbe_', 'nb_'], '', $tag);
        $page = str_replace('_', '-', $page);
        $page = trim($page, '-');

        if (! isset($this->routes[$page]) && $page === 'verify-email') {
            $page = 'verify-email';
        }

        if (! isset($this->routes[$page])) {
            return '';
        }

        ob_start();

        $template = $this->locate_template($this->routes[$page]);

        if ($template) {
            include $template;
        }

        return (string) ob_get_clean();
    }

    /**
     * Load plugin template.
     */
    public function load_template(): void
    {
        $page = get_query_var('nb_account_page');

        if (empty($page)) {
            return;
        }

        if (! isset($this->routes[$page])) {
            $this->not_found();
        }

        if ($page === 'logout') {
            wp_logout();
            wp_safe_redirect(home_url('/login/'));
            exit;
        }

        if (
            in_array(
                $page,
                [
                    'dashboard',
                    'profile',
                    'notifications',
                    'earnings',
                    'submit',
                    'editor-dashboard',
                ],
                true
            )
        ) {
            if (! is_user_logged_in()) {
                wp_safe_redirect(home_url('/login/'));
                exit;
            }
        }

        if ($page === 'verify-email' && isset($_GET['user'], $_GET['token'])) {
            $user_id = absint(wp_unslash($_GET['user']));
            $token = sanitize_text_field(wp_unslash($_GET['token']));

            if ($user_id > 0 && $token !== '') {
                $status = \Newsblenda\Accounts\Auth\Register::verify_email_status(
                    $user_id,
                    $token
                );
                wp_safe_redirect(add_query_arg('status', $status, home_url('/verify-email/')));
                exit;
            }
        }

        $template = $this->locate_template($this->routes[$page]);

        if (! $template) {
            wp_die(esc_html__('Template not found.', 'newsblenda-accounts'));
        }

        status_header(200);
        nocache_headers();

        global $wp_query;
        if ($wp_query) {
            $wp_query->is_404 = false;
        }

        do_action('nb_accounts_before_template', $page, $template);

        get_header();
        include $template;
        get_footer();

        do_action('nb_accounts_after_template', $page, $template);

        exit;
    }

    /**
     * Locate template.
     */
    private function locate_template(string $template): ?string
    {
        $theme = locate_template([
            'newsblenda-accounts/' . ltrim($template, '/'),
            $template,
        ]);

        if (! empty($theme)) {
            return $theme;
        }

        $plugin = NB_ACCOUNTS_PATH . $template;

        if (file_exists($plugin)) {
            return $plugin;
        }

        return null;
    }

    /**
     * Display a 404 page.
     */
    private function not_found(): void
    {
        global $wp_query;

        $wp_query->set_404();

        status_header(404);
        nocache_headers();
        include get_404_template();

        exit;
    }

    /**
     * Get plugin template path.
     */
    public static function template(string $file): string
    {
        return NB_ACCOUNTS_PATH . 'templates/' . ltrim($file, '/');
    }

    /**
     * Get plugin asset URL.
     */
    public static function asset(string $file): string {

        return NB_ACCOUNTS_URL .
            'assets/' .
            ltrim($file, '/');

    }

    /**
     * Get plugin URL.
     */
    public static function url(
        string $path = ''
    ): string {

        return NB_ACCOUNTS_URL .
            ltrim($path, '/');

    }

    /**
     * Get plugin path.
     */
    public static function path(
        string $path = ''
    ): string {

        return NB_ACCOUNTS_PATH .
            ltrim($path, '/');

    }

    /**
     * Check whether a route exists.
     */
    public function has_route(
        string $route
    ): bool {

        return isset($this->routes[$route]);

    }

    /**
     * Get all registered routes.
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Register a custom route.
     */
    public function add_route(
        string $route,
        ?string $template
    ): void {

        $this->routes[$route] = $template;

    }

    /**
     * Remove a registered route.
     */
    public function remove_route(
        string $route
    ): void {

        unset($this->routes[$route]);

    }

    /**
     * Current route.
     */
    public static function current_route(): string
    {
        return (string) get_query_var(
            'nb_account_page'
        );
    }

    /**
     * Is current route.
     */
    public static function is(
        string $route
    ): bool {

        return self::current_route() === $route;

    }

    /**
     * Route URL.
     */
    public static function route_url(
        string $route
    ): string {

        return home_url(
            '/' . trim($route, '/') . '/'
        );

    }

    /**
     * Redirect to a plugin route.
     */
    public static function redirect(
        string $route
    ): void {

        wp_safe_redirect(
            self::route_url($route)
        );

        exit;

    }

    /**
     * Is this a Newsblenda Accounts route?
     */
    public static function is_accounts_route(): bool
    {
        return self::current_route() !== '';
    }

    /**
     * Flush rewrite rules.
     */
    public static function flush(): void
    {
        flush_rewrite_rules();
    }
}