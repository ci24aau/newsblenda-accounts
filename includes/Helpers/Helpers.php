<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Helpers;

defined('ABSPATH') || exit;

class Helpers
{
    /**
     * Current logged in user.
     */
    public static function current_user(): \WP_User
    {
        return wp_get_current_user();
    }

    /**
     * Current user ID.
     */
    public static function user_id(): int
    {
        return get_current_user_id();
    }

    /**
     * Is logged in?
     */
    public static function is_logged_in(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Redirect.
     */
    public static function redirect(string $url): void
    {
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Require login.
     */
    public static function require_login(): void
    {
        if (! self::is_logged_in()) {

            self::redirect(
                home_url('/login/')
            );

        }
    }

    /**
     * Current role.
     */
    public static function role(
        int $user_id = 0
    ): string {

        if (! $user_id) {
            $user_id = self::user_id();
        }

        $user = get_userdata($user_id);

        if (! $user) {
            return '';
        }

        return $user->roles[0] ?? '';
    }

    /**
     * Account status.
     */
    public static function account_status(
        int $user_id = 0
    ): string {

        if (! $user_id) {
            $user_id = self::user_id();
        }

        return (string) get_user_meta(
            $user_id,
            'nb_account_status',
            true
        );
    }

    /**
     * Account type.
     */
    public static function account_type(
        int $user_id = 0
    ): string {

        if (! $user_id) {
            $user_id = self::user_id();
        }

        return (string) get_user_meta(
            $user_id,
            'nb_account_type',
            true
        );
    }

    /**
     * Email verified?
     */
    public static function email_verified(
        int $user_id = 0
    ): bool {

        if (! $user_id) {
            $user_id = self::user_id();
        }

        return (bool) get_user_meta(
            $user_id,
            'nb_email_verified',
            true
        );
    }

    /**
     * Plugin option.
     *
     * Reads from the new per-section settings first (via the legacy map in
     * SettingsManager); falls back to the old flat option for keys that are
     * not mapped (e.g. route slugs).
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function option(
        string $key,
        $default = null
    ) {

        if (class_exists('\Newsblenda\Accounts\Admin\SettingsManager')) {
            $value = \Newsblenda\Accounts\Admin\SettingsManager::legacy_get($key);

            if ($value !== null) {
                return $value;
            }
        }

        $settings = get_option('nb_accounts_settings', []);

        return $settings[$key] ?? $default;
    }
    
        /**
     * Update user meta.
     */
    public static function update_meta(
        int $user_id,
        string $key,
        $value
    ): bool {

        return (bool) update_user_meta(
            $user_id,
            $key,
            $value
        );

    }

    /**
     * Get user meta.
     */
    public static function meta(
        int $user_id,
        string $key,
        $default = ''
    ) {

        $value = get_user_meta(
            $user_id,
            $key,
            true
        );

        return $value === ''
            ? $default
            : $value;

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
     * Current URL.
     */
    public static function current_url(): string
    {
        global $wp;

        return home_url(
            add_query_arg(
                [],
                $wp->request
            )
        );
    }

    /**
     * Home URL.
     */
    public static function home(
        string $path = ''
    ): string {

        return home_url('/' . ltrim($path, '/'));

    }

    /**
     * Admin URL.
     */
    public static function admin(
        string $path = ''
    ): string {

        return admin_url($path);

    }

    /**
     * Plugin URL.
     */
    public static function plugin_url(
        string $path = ''
    ): string {

        return NB_ACCOUNTS_URL .
            ltrim($path, '/');

    }

    /**
     * Plugin path.
     */
    public static function plugin_path(
        string $path = ''
    ): string {

        return NB_ACCOUNTS_PATH .
            ltrim($path, '/');

    }

    /**
     * Clean text.
     */
    public static function clean(
        string $text
    ): string {

        return sanitize_text_field($text);

    }

    /**
     * Clean email.
     */
    public static function email(
        string $email
    ): string {

        return sanitize_email($email);

    }

    /**
     * Escape HTML.
     */
    public static function e(
        string $text
    ): string {

        return esc_html($text);

    }

    /**
     * Escape URL.
     */
    public static function url(
        string $url
    ): string {

        return esc_url($url);

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
     * Create nonce.
     */
    public static function nonce(
        string $action
    ): string {

        return wp_create_nonce($action);

    }
    
        /**
     * Check role.
     */
    public static function has_role(
        string $role,
        int $user_id = 0
    ): bool {

        return self::role($user_id) === $role;

    }

    /**
     * Is author.
     */
    public static function is_author(
        int $user_id = 0
    ): bool {

        return self::has_role(
            'nb_author',
            $user_id
        );

    }

    /**
     * Is pending author.
     */
    public static function is_pending_author(
        int $user_id = 0
    ): bool {

        return self::has_role(
            'nb_author_pending',
            $user_id
        );

    }

    /**
     * Is editor.
     */
    public static function is_editor(
        int $user_id = 0
    ): bool {

        return self::has_role(
            'nb_editor',
            $user_id
        );

    }

    /**
     * Is administrator.
     */
    public static function is_admin(
        int $user_id = 0
    ): bool {

        return self::has_role(
            'administrator',
            $user_id
        );

    }

    /**
     * Is restricted.
     */
    public static function is_restricted(
        int $user_id = 0
    ): bool {

        return self::account_status($user_id) === 'restricted';

    }

    /**
     * User can submit articles.
     */
    public static function can_submit(
        int $user_id = 0
    ): bool {

        if (! $user_id) {
            $user_id = self::user_id();
        }

        if (
            self::is_restricted($user_id)
        ) {
            return false;
        }

        return self::is_author($user_id);

    }

    /**
     * Format money.
     */
    public static function money(
        float $amount
    ): string {

        return '£' . number_format(
            $amount,
            2
        );

    }

    /**
     * Format date.
     */
    public static function date(
        string $date
    ): string {

        if (empty($date)) {
            return '';
        }

        return wp_date(
            get_option('date_format'),
            strtotime($date)
        );

    }

    /**
     * Format date & time.
     */
    public static function datetime(
        string $date
    ): string {

        if (empty($date)) {
            return '';
        }

        return wp_date(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($date)
        );

    }

    /**
     * Current timestamp.
     */
    public static function now(): string
    {
        return current_time('mysql');
    }

    /**
     * Is AJAX request.
     */
    public static function is_ajax(): bool
    {
        return wp_doing_ajax();
    }

    /**
     * Is REST request.
     */
    public static function is_rest(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * JSON response.
     */
    public static function json(
        array $data,
        int $status = 200
    ): void {

        wp_send_json(
            $data,
            $status
        );

    }
    
        /**
     * Render a plugin template.
     */
    public static function render(
        string $template,
        array $data = []
    ): void {

        $file = NB_ACCOUNTS_PATH .
            'templates/' .
            ltrim($template, '/');

        if (! file_exists($file)) {
            return;
        }

        if (! empty($data)) {
            extract($data, EXTR_SKIP);
        }

        include $file;
    }

    /**
     * Get request value.
     */
    public static function request(
        string $key,
        $default = null
    ) {

        if (! isset($_REQUEST[$key])) {
            return $default;
        }

        return is_array($_REQUEST[$key])
            ? map_deep(
                wp_unslash($_REQUEST[$key]),
                'sanitize_text_field'
            )
            : sanitize_text_field(
                wp_unslash($_REQUEST[$key])
            );

    }

    /**
     * Get POST value.
     */
    public static function post(
        string $key,
        $default = null
    ) {

        if (! isset($_POST[$key])) {
            return $default;
        }

        return is_array($_POST[$key])
            ? map_deep(
                wp_unslash($_POST[$key]),
                'sanitize_text_field'
            )
            : sanitize_text_field(
                wp_unslash($_POST[$key])
            );

    }

    /**
     * Get GET value.
     */
    public static function get(
        string $key,
        $default = null
    ) {

        if (! isset($_GET[$key])) {
            return $default;
        }

        return sanitize_text_field(
            wp_unslash($_GET[$key])
        );

    }

    /**
     * Validate email.
     */
    public static function valid_email(
        string $email
    ): bool {

        return (bool) is_email($email);

    }

    /**
     * Validate username.
     */
    public static function valid_username(
        string $username
    ): bool {

        return validate_username($username);

    }

    /**
     * Is empty.
     */
    public static function blank(
        $value
    ): bool {

        return empty($value);

    }

    /**
     * Generate UUID.
     */
    public static function uuid(): string
    {
        return wp_generate_uuid4();
    }

    /**
     * Get client IP address.
     */
    public static function ip(): string
    {
        return sanitize_text_field(
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }

    /**
     * Get current user agent.
     */
    public static function user_agent(): string
    {
        return sanitize_text_field(
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }

    /**
     * Create upload directory.
     */
    public static function create_directory(
        string $directory
    ): bool {

        return wp_mkdir_p($directory);

    }

    /**
     * File exists.
     */
    public static function file_exists(
        string $file
    ): bool {

        return file_exists($file);

    }

    /**
     * Plugin version.
     */
    public static function version(): string
    {
        return NB_ACCOUNTS_VERSION;
    }

    /**
     * Plugin name.
     */
    public static function plugin_name(): string
    {
        return 'Newsblenda Accounts';
    }

    /**
     * Current blog name.
     */
    public static function site_name(): string
    {
        return get_bloginfo('name');
    }

    /**
     * Is development mode enabled?
     */
    public static function debug(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
}