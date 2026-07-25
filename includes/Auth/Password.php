<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Auth;

use Newsblenda\Accounts\Email\Mailer;

defined('ABSPATH') || exit;

class Password
{
    /**
     * Mailer instance.
     */
    private Mailer $mailer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->mailer = new Mailer();

        add_action(
            'init',
            [$this, 'handle_forgot_password']
        );

        add_action(
            'init',
            [$this, 'handle_reset_password']
        );
    }

    /**
     * Handle forgot password form.
     */
    public function handle_forgot_password(): void
    {
        if (
            (empty($_POST['nbe_forgot_password']) && empty($_POST['nb_forgot_password'])) ||
            ! isset($_POST['_wpnonce'])
        ) {
            return;
        }

        if (
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['_wpnonce'])
                ),
                'nbe_nonce'
            )
        ) {

            wp_die(
                esc_html__(
                    'Security check failed.',
                    'newsblenda-accounts'
                )
            );

        }

        $email = sanitize_email(
            wp_unslash($_POST['nbe_email'] ?? $_POST['email'] ?? '')
        );

        if (empty($email)) {

            $this->redirect_with_message(
                'forgot-password',
                'empty'
            );

        }

        $user = get_user_by(
            'email',
            $email
        );

        /*
         * Never reveal whether an account exists.
         */
        if (! $user) {

            $this->redirect_with_message(
                'forgot-password',
                'sent'
            );

        }

        do_action(
            'nb_accounts_before_password_reset_request',
            $user
        );

        $key = get_password_reset_key($user);

        if (is_wp_error($key)) {

            $this->redirect_with_message(
                'forgot-password',
                'error'
            );

        }

        $reset_url = add_query_arg(
            [
                'key'   => rawurlencode($key),
                'login' => rawurlencode($user->user_login),
            ],
            home_url('/reset-password/')
        );

        $subject = __(
            'Reset your Newsblenda password',
            'newsblenda-accounts'
        );

        $message  = '<p>';
        $message .= sprintf(
            esc_html__(
                'Hello %s,',
                'newsblenda-accounts'
            ),
            esc_html($user->display_name)
        );
        $message .= '</p>';

        $message .= '<p>';
        $message .= esc_html__(
            'We received a request to reset your password.',
            'newsblenda-accounts'
        );
        $message .= '</p>';

        $message .= '<p>';
        $message .= sprintf(
            '<a href="%s">%s</a>',
            esc_url($reset_url),
            esc_html__(
                'Click here to reset your password',
                'newsblenda-accounts'
            )
        );
        $message .= '</p>';

        $message .= '<p>';
        $message .= esc_html__(
            'If you did not request this password reset, you can safely ignore this email.',
            'newsblenda-accounts'
        );
        $message .= '</p>';

        $this->mailer->send(
            $user->user_email,
            $subject,
            $message
        );

        $this->log_activity(
            $user->ID,
            'password_reset_requested'
        );

        do_action(
            'nb_accounts_password_reset_requested',
            $user
        );

        $this->redirect_with_message(
            'forgot-password',
            'sent'
        );
    }
    
    
        /**
     * Handle password reset form.
     */
    public function handle_reset_password(): void
    {
        if (
            (empty($_POST['nbe_reset_submit']) && empty($_POST['nb_reset_password'])) ||
            ! isset($_POST['_wpnonce'])
        ) {
            return;
        }

        if (
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['_wpnonce'])
                ),
                'nbe_nonce'
            )
        ) {

            wp_die(
                esc_html__(
                    'Security check failed.',
                    'newsblenda-accounts'
                )
            );

        }

        $login = sanitize_text_field(
            wp_unslash($_POST['nbe_login'] ?? $_POST['login'] ?? '')
        );

        $key = sanitize_text_field(
            wp_unslash($_POST['nbe_key'] ?? $_POST['key'] ?? '')
        );

        $password = (string) (
            $_POST['nbe_password'] ?? $_POST['password'] ?? ''
        );

        $confirm = (string) (
            $_POST['nbe_confirm_password'] ?? $_POST['confirm_password'] ?? ''
        );

        if ($password !== $confirm) {

            $this->redirect_with_message(
                'reset-password',
                'nomatch'
            );

        }

        if (! $this->password_is_strong($password)) {

            $this->redirect_with_message(
                'reset-password',
                'weak'
            );

        }

        $user = check_password_reset_key(
            $key,
            $login
        );

        if (is_wp_error($user)) {

            $this->redirect_with_message(
                'reset-password',
                'invalid'
            );

        }

        do_action(
            'nb_accounts_before_password_changed',
            $user
        );

        reset_password(
            $user,
            $password
        );

        update_user_meta(
            $user->ID,
            'nb_last_password_change',
            current_time('mysql')
        );

        delete_user_meta(
            $user->ID,
            'nb_force_password_reset'
        );

        $this->log_activity(
            $user->ID,
            'password_reset_completed'
        );

        do_action(
            'nb_accounts_password_changed',
            $user
        );

        $this->redirect_with_message(
            'login',
            'changed'
        );
    }

    /**
     * Password strength validation.
     */
    private function password_is_strong(
        string $password
    ): bool {

        if (strlen($password) < 8) {
            return false;
        }

        if (! preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (! preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (! preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Redirect helper.
     */
    private function redirect_with_message(
        string $page,
        string $status
    ): void {

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
     * Store activity.
     */
    private function log_activity(
        int $user_id,
        string $action
    ): void {

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
                'user_agent' => substr(
                    sanitize_text_field(
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ),
                    0,
                    255
                ),
                'created_at' => current_time('mysql'),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Generate a password reset URL.
     */
    public static function reset_url(
        string $key,
        string $login
    ): string {

        return add_query_arg(
            [
                'key'   => rawurlencode($key),
                'login' => rawurlencode($login),
            ],
            home_url('/reset-password/')
        );

    }

    /**
     * Check whether a password meets the minimum requirements.
     */
    public static function validate_password(
        string $password
    ): bool {

        return
            strlen($password) >= 8 &&
            preg_match('/[A-Z]/', $password) &&
            preg_match('/[a-z]/', $password) &&
            preg_match('/[0-9]/', $password);

    }

    /**
     * Force a password reset on next login.
     */
    public static function force_reset(
        int $user_id
    ): void {

        update_user_meta(
            $user_id,
            'nb_force_password_reset',
            1
        );

    }

    /**
     * Does the user require a password reset?
     */
    public static function requires_reset(
        int $user_id
    ): bool {

        return (bool) get_user_meta(
            $user_id,
            'nb_force_password_reset',
            true
        );

    }

    /**
     * Get last password change timestamp.
     */
    public static function last_changed(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_last_password_change',
            true
        );

    }

    /**
     * Password reset request timestamp.
     */
    public static function record_reset_request(
        int $user_id
    ): void {

        update_user_meta(
            $user_id,
            'nb_last_password_reset_request',
            current_time('mysql')
        );

    }

    /**
     * Get last password reset request.
     */
    public static function last_reset_request(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_last_password_reset_request',
            true
        );

    }

    /**
     * Check whether a password reset can be requested.
     * Prevents repeated requests within 5 minutes.
     */
    public static function can_request_reset(
        int $user_id
    ): bool {

        $last = self::last_reset_request($user_id);

        if (empty($last)) {
            return true;
        }

        return (
            strtotime($last) + (5 * MINUTE_IN_SECONDS)
        ) < time();

    }
}