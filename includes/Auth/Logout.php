<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Auth;

defined('ABSPATH') || exit;

class Logout
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'init',
            [$this, 'handle_logout']
        );

        add_action(
            'wp_logout',
            [$this, 'after_logout']
        );
    }

    /**
     * Handle frontend logout.
     */
    public function handle_logout(): void
    {
        if (! is_user_logged_in()) {
            return;
        }

        $request = trim(
            parse_url(
                $_SERVER['REQUEST_URI'] ?? '',
                PHP_URL_PATH
            ),
            '/'
        );

        if ($request !== 'logout') {
            return;
        }

        if (
            empty($_GET['_wpnonce']) ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'nb_logout'
            )
        ) {

            wp_die(
                esc_html__(
                    'Security check failed.',
                    'newsblenda-accounts'
                )
            );

        }

        $user_id = get_current_user_id();

        $this->log_activity(
            $user_id,
            'logout'
        );

        do_action(
            'nb_accounts_before_logout',
            $user_id
        );

        wp_logout();

        wp_safe_redirect(
            add_query_arg(
                'status',
                'logged-out',
                home_url('/login/')
            )
        );

        exit;
    }

    /**
     * Runs after WordPress logout.
     */
    public function after_logout(): void
    {
        do_action(
            'nb_accounts_after_logout'
        );
    }

    /**
     * Generate logout URL.
     */
    public static function get_logout_url(): string
    {
        return wp_nonce_url(
            home_url('/logout/'),
            'nb_logout'
        );
    }

    /**
     * Logout URL with redirect.
     */
    public static function get_logout_redirect_url(
        string $redirect
    ): string {

        return add_query_arg(
            'redirect_to',
            rawurlencode($redirect),
            self::get_logout_url()
        );

    }

    /**
     * Is logout request.
     */
    public static function is_logout_request(): bool
    {
        $request = trim(
            parse_url(
                $_SERVER['REQUEST_URI'] ?? '',
                PHP_URL_PATH
            ),
            '/'
        );

        return $request === 'logout';
    }

    /**
     * Store activity.
     */
    private function log_activity(
        int $user_id,
        string $action
    ): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nb_activity';

        if (
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table
                )
            ) !== $table
        ) {
            return;
        }

        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'action'     => $action,
                'ip_address' => sanitize_text_field(
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ),
                'created_at' => current_time('mysql'),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
    
    
        /**
     * Get current user's logout URL.
     */
    public static function current_user_logout_url(): string
    {
        if (! is_user_logged_in()) {
            return home_url('/login/');
        }

        return self::get_logout_url();
    }

    /**
     * Logout current user immediately.
     */
    public static function logout_current_user(): void
    {
        if (! is_user_logged_in()) {
            return;
        }

        wp_logout();

        wp_safe_redirect(
            add_query_arg(
                'status',
                'logged-out',
                home_url('/login/')
            )
        );

        exit;
    }

    /**
     * Check whether the current user can logout.
     */
    public static function can_logout(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Get logout status message.
     */
    public static function status_message(): string
    {
        if (
            isset($_GET['status']) &&
            sanitize_text_field(
                wp_unslash($_GET['status'])
            ) === 'logged-out'
        ) {

            return __(
                'You have been logged out successfully.',
                'newsblenda-accounts'
            );

        }

        return '';
    }

    /**
     * Has the user just logged out?
     */
    public static function has_logged_out(): bool
    {
        return (
            isset($_GET['status']) &&
            sanitize_text_field(
                wp_unslash($_GET['status'])
            ) === 'logged-out'
        );
    }

    /**
     * Last logout timestamp.
     */
    public static function update_logout_time(
        int $user_id
    ): void {

        update_user_meta(
            $user_id,
            'nb_last_logout',
            current_time('mysql')
        );

    }

    /**
     * Get last logout time.
     */
    public static function last_logout(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_last_logout',
            true
        );

    }

    /**
     * Remove user session data.
     */
    private function clear_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Cleanup before logout.
     */
    private function cleanup(
        int $user_id
    ): void {

        $this->update_logout_time($user_id);

        $this->clear_session();

        do_action(
            'nb_accounts_logout_cleanup',
            $user_id
        );

    }
}