<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Auth;

use Newsblenda\Accounts\Email\Mailer;

defined('ABSPATH') || exit;

class Password
{
    private const TOKEN_WINDOW = DAY_IN_SECONDS;
    private const REQUEST_HOURLY_MAX = 5;
    private const SUBMIT_HOURLY_MAX = 10;
    private const HONEYPOT_FIELD = 'nb_website';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'handle_forgot_password']);
        add_action('init', [$this, 'handle_reset_password']);

        add_action('wp_ajax_nopriv_nb_accounts_forgot_password', [$this, 'ajax_forgot_password']);
        add_action('wp_ajax_nopriv_nb_accounts_reset_password', [$this, 'ajax_reset_password']);
    }

    /**
     * Handle forgot password form.
     */
    public function handle_forgot_password(): void
    {
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
            (! isset($_POST['nbe_forgot_password']) && ! isset($_POST['nb_forgot_password']))
        ) {
            return;
        }

        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'nbe_nonce')) {
            $this->redirect_with_message('forgot-password', 'invalid-nonce');
        }

        if (! empty($_POST[self::HONEYPOT_FIELD])) {
            $this->redirect_with_message('forgot-password', 'sent');
        }

        $email = sanitize_email(wp_unslash($_POST['nbe_email'] ?? $_POST['email'] ?? ''));
        $result = $this->request_password_reset($email);

        $this->redirect_with_message('forgot-password', $result['status']);
    }

    /**
     * Handle reset password form.
     */
    public function handle_reset_password(): void
    {
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
            (! isset($_POST['nbe_reset_submit']) && ! isset($_POST['nb_reset_password']))
        ) {
            return;
        }

        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'nbe_nonce')) {
            $this->redirect_with_message('reset-password', 'invalid-nonce');
        }

        if (! empty($_POST[self::HONEYPOT_FIELD])) {
            $this->redirect_with_message('reset-password', 'error');
        }

        $user_id = absint(wp_unslash($_POST['nbe_user'] ?? $_POST['user'] ?? '0'));
        $token = sanitize_text_field(wp_unslash($_POST['nbe_token'] ?? $_POST['token'] ?? ''));
        $password = (string) ($_POST['nbe_password'] ?? $_POST['password'] ?? '');
        $confirm = (string) ($_POST['nbe_confirm_password'] ?? $_POST['confirm_password'] ?? '');

        $result = $this->reset_password_with_token(
            $user_id,
            $token,
            $password,
            $confirm
        );

        if ($result['status'] === 'success') {
            wp_safe_redirect(add_query_arg('reset', 'success', home_url('/login/')));
            exit;
        }

        $this->redirect_with_message('reset-password', $result['status']);
    }

    /**
     * AJAX forgot password handler.
     */
    public function ajax_forgot_password(): void
    {
        if (! check_ajax_referer('nb_accounts', 'nonce', false)) {
            wp_send_json_error(
                [
                    'message' => __('Security check failed. Please refresh and try again.', 'newsblenda-accounts'),
                ],
                403
            );
        }

        if (! empty($_POST[self::HONEYPOT_FIELD])) {
            wp_send_json_success(
                [
                    'status' => 'sent',
                    'message' => __('If an account exists with that email address, a password reset link has been sent.', 'newsblenda-accounts'),
                ]
            );
        }

        $email = sanitize_email(wp_unslash($_POST['nbe_email'] ?? $_POST['email'] ?? ''));
        $result = $this->request_password_reset($email);

        if ($result['status'] === 'error') {
            wp_send_json_error(
                [
                    'status' => $result['status'],
                    'message' => $result['message'],
                ],
                400
            );
        }

        wp_send_json_success(
            [
                'status' => $result['status'],
                'message' => $result['message'],
            ]
        );
    }

    /**
     * AJAX reset password handler.
     */
    public function ajax_reset_password(): void
    {
        if (! check_ajax_referer('nb_accounts', 'nonce', false)) {
            wp_send_json_error(
                [
                    'status' => 'invalid-nonce',
                    'message' => __('Security check failed. Please refresh and try again.', 'newsblenda-accounts'),
                ],
                403
            );
        }

        if (! empty($_POST[self::HONEYPOT_FIELD])) {
            wp_send_json_error(
                [
                    'status' => 'error',
                    'message' => __('Unable to reset your password. Please request a new reset link.', 'newsblenda-accounts'),
                ],
                400
            );
        }

        $user_id = absint(wp_unslash($_POST['nbe_user'] ?? $_POST['user'] ?? '0'));
        $token = sanitize_text_field(wp_unslash($_POST['nbe_token'] ?? $_POST['token'] ?? ''));
        $password = (string) ($_POST['nbe_password'] ?? $_POST['password'] ?? '');
        $confirm = (string) ($_POST['nbe_confirm_password'] ?? $_POST['confirm_password'] ?? '');

        $result = $this->reset_password_with_token($user_id, $token, $password, $confirm);

        if ($result['status'] === 'success') {
            wp_send_json_success(
                [
                    'status' => 'success',
                    'message' => __('Your password has been reset successfully. Redirecting to login…', 'newsblenda-accounts'),
                    'redirect' => add_query_arg('reset', 'success', home_url('/login/')),
                ]
            );
        }

        wp_send_json_error(
            [
                'status' => $result['status'],
                'message' => $result['message'],
            ],
            400
        );
    }

    /**
     * Request password reset link.
     *
     * @return array{status:string,message:string}
     */
    private function request_password_reset(string $email): array
    {
        $generic = [
            'status' => 'sent',
            'message' => __('If an account exists with that email address, a password reset link has been sent.', 'newsblenda-accounts'),
        ];

        if (! is_email($email)) {
            return [
                'status' => 'invalid-email',
                'message' => __('Please enter a valid email address.', 'newsblenda-accounts'),
            ];
        }

        $request_rate_key = 'nb_password_reset_rate_' . wp_hash(strtolower($email));
        $request_count = (int) get_transient($request_rate_key);

        if ($request_count >= self::REQUEST_HOURLY_MAX) {
            return [
                'status' => 'throttled',
                'message' => __('Too many reset requests. Please wait and try again later.', 'newsblenda-accounts'),
            ];
        }

        set_transient($request_rate_key, $request_count + 1, HOUR_IN_SECONDS);

        $user = get_user_by('email', $email);

        if (! $user instanceof \WP_User) {
            return $generic;
        }

        $token = $this->issue_reset_token((int) $user->ID, (string) $user->user_email);

        if ($token === '') {
            return [
                'status' => 'error',
                'message' => __('Unable to process your request right now. Please try again shortly.', 'newsblenda-accounts'),
            ];
        }

        $reset_url = add_query_arg(
            [
                'user' => (int) $user->ID,
                'token' => rawurlencode($token),
            ],
            home_url('/reset-password/')
        );

        if (! $this->send_reset_email($user, $reset_url)) {
            return [
                'status' => 'error',
                'message' => __('Unable to send reset email right now. Please try again shortly.', 'newsblenda-accounts'),
            ];
        }

        do_action('nb_accounts_password_reset_requested', $user);

        return $generic;
    }

    /**
     * Reset password using custom token.
     *
     * @return array{status:string,message:string}
     */
    private function reset_password_with_token(int $user_id, string $token, string $password, string $confirm): array
    {
        if ($password !== $confirm) {
            return [
                'status' => 'nomatch',
                'message' => __('Passwords do not match. Please try again.', 'newsblenda-accounts'),
            ];
        }

        if (! $this->password_is_strong($password)) {
            return [
                'status' => 'weak',
                'message' => __('Please choose a stronger password that meets all requirements.', 'newsblenda-accounts'),
            ];
        }

        $submit_rate_key = 'nb_password_reset_submit_' . wp_hash($user_id . '|' . $token . '|' . $this->client_ip());
        $submit_count = (int) get_transient($submit_rate_key);

        if ($submit_count >= self::SUBMIT_HOURLY_MAX) {
            return [
                'status' => 'submit-throttled',
                'message' => __('Too many reset attempts. Please request a new reset link.', 'newsblenda-accounts'),
            ];
        }

        set_transient($submit_rate_key, $submit_count + 1, HOUR_IN_SECONDS);

        $status = self::get_reset_token_status($user_id, $token);

        if ($status !== 'valid') {
            $messages = [
                'expired' => __('This reset link has expired. Please request a new password reset link.', 'newsblenda-accounts'),
                'consumed' => __('This reset link has already been used. Please request a new reset link.', 'newsblenda-accounts'),
                'invalid' => __('This reset link is invalid. Please request a new password reset link.', 'newsblenda-accounts'),
                'missing' => __('Missing reset token. Please request a new password reset link.', 'newsblenda-accounts'),
            ];

            return [
                'status' => $status,
                'message' => $messages[$status] ?? __('Unable to verify reset link. Please request a new one.', 'newsblenda-accounts'),
            ];
        }

        $token_id = self::match_active_token_id($user_id, $token);

        if ($token_id <= 0) {
            return [
                'status' => 'invalid',
                'message' => __('This reset link is invalid. Please request a new password reset link.', 'newsblenda-accounts'),
            ];
        }

        $user = get_userdata($user_id);

        if (! $user instanceof \WP_User) {
            return [
                'status' => 'invalid',
                'message' => __('Unable to find account for this reset link.', 'newsblenda-accounts'),
            ];
        }

        do_action('nb_accounts_before_password_changed', $user);

        wp_set_password($password, $user_id);

        if (class_exists('WP_Session_Tokens')) {
            $sessions = \WP_Session_Tokens::get_instance($user_id);
            $sessions->destroy_all();
        }

        update_user_meta($user_id, 'nb_last_password_change', current_time('mysql'));
        delete_user_meta($user_id, 'nb_force_password_reset');

        $this->consume_token($token_id);
        $this->consume_all_user_tokens($user_id);

        $this->send_reset_confirmation_email($user);

        do_action('nb_accounts_password_changed', $user);

        return [
            'status' => 'success',
            'message' => __('Password reset successful.', 'newsblenda-accounts'),
        ];
    }

    /**
     * Issue and persist a reset token.
     */
    private function issue_reset_token(int $user_id, string $email): string
    {
        $token = '';

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $exception) {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes(32, $strong);
                if ($bytes !== false && $strong) {
                    $token = bin2hex($bytes);
                }
            }
        }

        if ($token === '') {
            return '';
        }

        global $wpdb;

        $table = self::token_table();

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'email' => $email,
                'token_hash' => wp_hash_password($token),
                'expires_at' => gmdate('Y-m-d H:i:s', time() + self::TOKEN_WINDOW),
                'consumed_at' => null,
                'created_at' => current_time('mysql'),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($inserted === false) {
            return '';
        }

        return $token;
    }

    /**
     * Send password reset request email.
     */
    private function send_reset_email(\WP_User $user, string $reset_url): bool
    {
        $subject = __('Reset your Newsblenda password', 'newsblenda-accounts');
        $message = '';

        $template = NB_ACCOUNTS_PATH . 'templates/emails/password-reset.php';

        if (file_exists($template)) {
            ob_start();
            $user_name = (string) $user->display_name;
            $expiry_hours = (int) (self::TOKEN_WINDOW / HOUR_IN_SECONDS);
            include $template;
            $message = (string) ob_get_clean();
        }

        if ($message === '') {
            $message  = '<p>' . sprintf(esc_html__('Hello %s,', 'newsblenda-accounts'), esc_html($user->display_name)) . '</p>';
            $message .= '<p>' . esc_html__('A request was made to reset your password.', 'newsblenda-accounts') . '</p>';
            $message .= '<p><a href="' . esc_url($reset_url) . '">' . esc_html__('Reset Password', 'newsblenda-accounts') . '</a></p>';
            $message .= '<p>' . esc_html__('This reset link expires in 24 hours.', 'newsblenda-accounts') . '</p>';
        }

        return Mailer::send((string) $user->user_email, $subject, $message);
    }

    /**
     * Send reset completion email.
     */
    private function send_reset_confirmation_email(\WP_User $user): void
    {
        $subject = __('Your Newsblenda password was changed', 'newsblenda-accounts');
        $message  = '<p>' . sprintf(esc_html__('Hello %s,', 'newsblenda-accounts'), esc_html($user->display_name)) . '</p>';
        $message .= '<p>' . esc_html__('Your password has been changed successfully.', 'newsblenda-accounts') . '</p>';
        $message .= '<p>' . esc_html__('If you did not perform this action, please contact support immediately.', 'newsblenda-accounts') . '</p>';
        $message .= '<p><a href="' . esc_url(home_url('/login/')) . '">' . esc_html__('Sign in', 'newsblenda-accounts') . '</a></p>';

        Mailer::send((string) $user->user_email, $subject, $message);
    }

    /**
     * Get token status for reset page rendering.
     */
    public static function get_reset_token_status(int $user_id, string $token): string
    {
        if ($user_id <= 0 || $token === '') {
            return 'missing';
        }

        if (! self::valid_token_format($token)) {
            return 'invalid';
        }

        $record = self::find_token_record_by_token($user_id, $token);

        if (! is_array($record)) {
            return 'invalid';
        }

        if (! empty($record['consumed_at'])) {
            return 'consumed';
        }

        $expires = strtotime((string) $record['expires_at']);
        if ($expires === false || $expires < time()) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Match a token against active records and return token ID.
     */
    private static function match_active_token_id(int $user_id, string $token): int
    {
        $record = self::find_token_record_by_token($user_id, $token);

        if (! is_array($record)) {
            return 0;
        }

        if (! empty($record['consumed_at'])) {
            return 0;
        }

        $expires_at = strtotime((string) ($record['expires_at'] ?? ''));
        if ($expires_at === false || $expires_at < time()) {
            return 0;
        }

        return (int) ($record['id'] ?? 0);
    }

    /**
     * Consume a single token.
     */
    private function consume_token(int $token_id): void
    {
        global $wpdb;

        $wpdb->update(
            self::token_table(),
            [
                'consumed_at' => current_time('mysql'),
            ],
            [
                'id' => $token_id,
            ],
            [
                '%s',
            ],
            [
                '%d',
            ]
        );
    }

    /**
     * Consume all outstanding tokens for the user.
     */
    private function consume_all_user_tokens(int $user_id): void
    {
        global $wpdb;

        $table = self::token_table();

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET consumed_at = %s
                WHERE user_id = %d
                AND consumed_at IS NULL",
                current_time('mysql'),
                $user_id
            )
        );
    }

    /**
     * Find token record by comparing token hash.
     */
    private static function find_token_record_by_token(int $user_id, string $token): ?array
    {
        global $wpdb;

        $table = self::token_table();

        $records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, token_hash, expires_at, consumed_at
                FROM {$table}
                WHERE user_id = %d
                ORDER BY created_at DESC
                LIMIT 50",
                $user_id
            ),
            ARRAY_A
        );

        if (! is_array($records)) {
            return null;
        }

        foreach ($records as $record) {
            if (wp_check_password($token, (string) ($record['token_hash'] ?? ''))) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Password strength validation.
     */
    private function password_is_strong(string $password): bool
    {
        return (
            strlen($password) >= 8 &&
            preg_match('/[A-Z]/', $password) &&
            preg_match('/[a-z]/', $password) &&
            preg_match('/[0-9]/', $password) &&
            preg_match('/[^a-zA-Z0-9]/', $password)
        );
    }

    /**
     * Redirect helper.
     */
    private function redirect_with_message(string $page, string $status): void
    {
        wp_safe_redirect(
            add_query_arg(
                'status',
                $status,
                home_url('/' . $page . '/')
            )
        );

        exit;
    }

    /**
     * Get token table name.
     */
    private static function token_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'nb_password_reset_tokens';
    }

    /**
     * Validate token format.
     */
    private static function valid_token_format(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Password reset request timestamp.
     */
    public static function record_reset_request(int $user_id): void
    {
        update_user_meta(
            $user_id,
            'nb_last_password_reset_request',
            current_time('mysql')
        );
    }

    /**
     * Get last password reset request.
     */
    public static function last_reset_request(int $user_id): string
    {
        return (string) get_user_meta(
            $user_id,
            'nb_last_password_reset_request',
            true
        );
    }

    /**
     * Check whether a password reset can be requested.
     */
    public static function can_request_reset(int $user_id): bool
    {
        $last = self::last_reset_request($user_id);

        if ($last === '') {
            return true;
        }

        return (strtotime($last) + (5 * MINUTE_IN_SECONDS)) < time();
    }

    /**
     * Get client IP.
     */
    private function client_ip(): string
    {
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}
