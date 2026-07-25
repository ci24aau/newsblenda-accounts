<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Security;

defined('ABSPATH') || exit;

class Security
{
    /**
     * Maximum failed login attempts.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds.
     */
    private const LOCKOUT_TIME = 900;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'init',
            [$this, 'disable_author_enumeration']
        );

        add_action(
            'init',
            [$this, 'prevent_direct_author_access']
        );

        add_filter(
            'xmlrpc_enabled',
            '__return_false'
        );

        add_filter(
            'rest_authentication_errors',
            [$this, 'rest_authentication']
        );

        add_action(
            'wp_login_failed',
            [$this, 'record_failed_login']
        );

        add_action(
            'wp_login',
            [$this, 'clear_failed_logins'],
            10,
            2
        );
    }

    /**
     * Disable author enumeration.
     */
    public function disable_author_enumeration(): void
    {
        if (is_admin()) {
            return;
        }

        if (! isset($_GET['author'])) {
            return;
        }

        wp_safe_redirect(home_url());

        exit;
    }

    /**
     * Prevent author archives.
     */
    public function prevent_direct_author_access(): void
    {
        if (is_admin()) {
            return;
        }

        if (! is_author()) {
            return;
        }

        wp_safe_redirect(home_url());

        exit;
    }

    /**
     * Protect REST endpoints.
     */
    public function rest_authentication($result)
    {
        if (! empty($result)) {
            return $result;
        }

        $route = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['REQUEST_URI'])
            )
            : '';

        if (strpos($route, '/wp-json/newsblenda/') === false) {
            return $result;
        }

        if (is_user_logged_in()) {
            return $result;
        }

        return new \WP_Error(
            'nb_rest_forbidden',
            __('Authentication required.', 'newsblenda-accounts'),
            [
                'status' => rest_authorization_required_code(),
            ]
        );
    }
    
        /**
     * Record failed login attempt.
     */
    public function record_failed_login(
        string $username
    ): void {

        $ip = self::ip();

        $key = 'nb_failed_login_' . md5($ip);

        $attempts = (int) get_transient($key);

        set_transient(
            $key,
            $attempts + 1,
            self::LOCKOUT_TIME
        );

    }

    /**
     * Clear failed login attempts.
     */
    public function clear_failed_logins(
        string $user_login,
        \WP_User $user
    ): void {

        delete_transient(
            'nb_failed_login_' . md5(self::ip())
        );

    }

    /**
     * Is client locked out?
     */
    public static function is_locked(): bool
    {
        $attempts = (int) get_transient(
            'nb_failed_login_' . md5(self::ip())
        );

        return $attempts >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Generate nonce.
     */
    public static function nonce(
        string $action
    ): string {

        return wp_create_nonce($action);

    }

    /**
     * Verify nonce.
     */
    public static function verify_nonce(
        string $nonce,
        string $action
    ): bool {

        return (bool) wp_verify_nonce(
            $nonce,
            $action
        );

    }

    /**
     * Validate uploaded file.
     */
    public static function validate_upload(
        array $file,
        array $allowed = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'pdf'
        ]
    ): bool {

        if (empty($file['name'])) {
            return false;
        }

        $extension = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        return in_array(
            $extension,
            $allowed,
            true
        );

    }

    /**
     * Get client IP.
     */
    public static function ip(): string
    {
        $keys = [

            'HTTP_CF_CONNECTING_IP',

            'HTTP_CLIENT_IP',

            'HTTP_X_FORWARDED_FOR',

            'REMOTE_ADDR',

        ];

        foreach ($keys as $key) {

            if (empty($_SERVER[$key])) {
                continue;
            }

            $ip = explode(
                ',',
                (string) $_SERVER[$key]
            );

            return sanitize_text_field(
                trim($ip[0])
            );

        }

        return '';
    }

    /**
     * Get user agent.
     */
    public static function user_agent(): string
    {
        return sanitize_text_field(
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }

    /**
     * Password strength.
     */
    public static function strong_password(
        string $password
    ): bool {

        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);

    }
    
        /**
     * Sanitize array recursively.
     */
    public static function sanitize_array(
        array $data
    ): array {

        foreach ($data as $key => $value) {

            if (is_array($value)) {

                $data[$key] = self::sanitize_array($value);

            } else {

                $data[$key] = sanitize_text_field(
                    wp_unslash((string) $value)
                );

            }

        }

        return $data;
    }

    /**
     * Sanitize request array.
     */
    public static function sanitize_request(
        array $request
    ): array {

        return self::sanitize_array($request);

    }

    /**
     * Validate redirect URL.
     */
    public static function safe_redirect(
        string $url,
        string $fallback = ''
    ): string {

        $url = wp_validate_redirect(
            $url,
            $fallback ?: home_url('/')
        );

        return esc_url_raw($url);

    }

    /**
     * Is administrator?
     */
    public static function is_admin_user(
        int $user_id = 0
    ): bool {

        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        return user_can(
            $user_id,
            'manage_options'
        );

    }

    /**
     * Current user can access plugin.
     */
    public static function can_access_dashboard(): bool
    {
        return current_user_can(
            'nb_access_dashboard'
        );
    }

    /**
     * Generate secure token.
     */
    public static function token(
        int $length = 64
    ): string {

        return wp_generate_password(
            $length,
            false,
            false
        );

    }

    /**
     * Hash a value.
     */
    public static function hash(
        string $value
    ): string {

        return wp_hash($value);

    }

    /**
     * Compare hashed values.
     */
    public static function verify_hash(
        string $value,
        string $hash
    ): bool {

        return hash_equals(
            wp_hash($value),
            $hash
        );

    }

    /**
     * Is HTTPS enabled?
     */
    public static function is_https(): bool
    {
        return is_ssl();
    }

    /**
     * Current request method.
     */
    public static function request_method(): string
    {
        return sanitize_text_field(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    /**
     * Is POST request?
     */
    public static function is_post(): bool
    {
        return self::request_method() === 'POST';
    }

    /**
     * Is GET request?
     */
    public static function is_get(): bool
    {
        return self::request_method() === 'GET';
    }

    /**
     * Generate CSP nonce.
     */
    public static function csp_nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Check whether debug mode is enabled.
     */
    public static function debug(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
}