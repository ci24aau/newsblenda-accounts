<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Auth;

use Newsblenda\Accounts\Email\Mailer;
use Newsblenda\Accounts\Helpers\Helpers;

defined('ABSPATH') || exit;

class Register
{
    /**
     * Mailer.
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
            [$this, 'process']
        );
    }

    /**
     * Process registration.
     */
    public function process(): void
    {
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
            (! isset($_POST['nbe_register_submit']) && empty($_POST['nb_register']))
        ) {
            return;
        }

        if (
            empty($_POST['_wpnonce']) ||
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

        $this->register_user();
    }

    /**
     * Register a new account.
     */
    private function register_user(): void
    {
        $username = sanitize_user(
            wp_unslash($_POST['nbe_username'] ?? $_POST['username'] ?? '')
        );

        $email = sanitize_email(
            wp_unslash($_POST['nbe_email'] ?? $_POST['email'] ?? '')
        );

        $password = (string) (
            $_POST['nbe_password'] ?? $_POST['password'] ?? ''
        );

        $confirm = (string) (
            $_POST['nbe_confirm_password'] ?? $_POST['confirm_password'] ?? ''
        );

        $full_name = sanitize_text_field(
            wp_unslash($_POST['nbe_full_name'] ?? $_POST['display_name'] ?? '')
        );

        $phone = sanitize_text_field(
            wp_unslash($_POST['nbe_phone'] ?? $_POST['nb_phone'] ?? '')
        );

        $country = sanitize_text_field(
            wp_unslash($_POST['nbe_country'] ?? $_POST['nb_country'] ?? '')
        );

        $state = sanitize_text_field(
            wp_unslash($_POST['nbe_state'] ?? $_POST['nb_state'] ?? '')
        );

        $niche = sanitize_text_field(
            wp_unslash($_POST['nbe_niche'] ?? $_POST['nb_niche'] ?? '')
        );

        $account = sanitize_text_field(
            wp_unslash(
                $_POST['account_type'] ?? 'subscriber'
            )
        );

        if (
            empty($username) ||
            empty($email) ||
            empty($password)
        ) {

            $this->error(
                'Please complete all required fields.'
            );

        }

        if (! is_email($email)) {

            $this->error(
                'Please enter a valid email address.'
            );

        }

        if ($password !== $confirm) {

            $this->error(
                'Passwords do not match.'
            );

        }

        if (! $this->password_is_strong($password)) {

            $this->error(
                'Please choose a stronger password.'
            );

        }

        if (username_exists($username)) {

            $this->error(
                'Username already exists.'
            );

        }

        if (email_exists($email)) {

            $this->error(
                'Email address already exists.'
            );

        }

        if (
            $account === 'author' &&
            ! Helpers::option(
                'allow_author_registration',
                1
            )
        ) {

            $this->error(
                'Author registration is currently disabled.'
            );

        }

        if (
            $account === 'subscriber' &&
            ! Helpers::option(
                'allow_subscriber_registration',
                1
            )
        ) {

            $this->error(
                'Reader registration is currently disabled.'
            );

        }

        do_action(
            'nb_accounts_before_register',
            $_POST
        );

        $role = 'subscriber';
        $status = 'approved';

        if ($account === 'author') {

            $role = 'nb_author_pending';
            $status = 'pending_approval';

        }

        $user_id = wp_insert_user(

            [
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'display_name' => $username,
                'role'         => $role,
            ]

        );

        if (is_wp_error($user_id)) {

            $this->error(
                $user_id->get_error_message()
            );

        }
        
        
                /*
        |--------------------------------------------------------------------------
        | User Meta
        |--------------------------------------------------------------------------
        */

        update_user_meta(
            $user_id,
            'nb_account_type',
            $account
        );

        if ($full_name !== '') {
            update_user_meta($user_id, 'first_name', $full_name);
        }

        if ($phone !== '') {
            update_user_meta($user_id, 'nb_phone', $phone);
        }

        if ($country !== '') {
            update_user_meta($user_id, 'nb_country', $country);
        }

        if ($state !== '') {
            update_user_meta($user_id, 'nb_state', $state);
        }

        if ($niche !== '') {
            update_user_meta($user_id, 'nb_niche', $niche);
        }

        update_user_meta(
            $user_id,
            'nb_account_status',
            $status
        );

        update_user_meta(
            $user_id,
            'nb_registered',
            current_time('mysql')
        );

        update_user_meta(
            $user_id,
            'nb_last_login',
            ''
        );

        update_user_meta(
            $user_id,
            'nb_login_attempts',
            0
        );

        update_user_meta(
            $user_id,
            'nb_submission_restricted',
            0
        );

        /*
        |--------------------------------------------------------------------------
        | Email Verification
        |--------------------------------------------------------------------------
        */

        $verification_required = (bool) Helpers::option(
            'require_email_verification',
            1
        );

        if ($verification_required) {

            update_user_meta(
                $user_id,
                'nb_email_verified',
                0
            );

            $token = wp_generate_password(
                32,
                false,
                false
            );

            update_user_meta(
                $user_id,
                'nb_email_verification_token',
                $token
            );

            $verification_url = add_query_arg(
                [
                    'user'  => $user_id,
                    'token' => $token,
                ],
                home_url('/verify-email/')
            );

            $subject = __(
                'Verify your email address',
                'newsblenda-accounts'
            );

            $message  = '<p>';
            $message .= sprintf(
                esc_html__(
                    'Hello %s,',
                    'newsblenda-accounts'
                ),
                esc_html($username)
            );
            $message .= '</p>';

            $message .= '<p>';
            $message .= esc_html__(
                'Thank you for registering your Newsblenda account.',
                'newsblenda-accounts'
            );
            $message .= '</p>';

            $message .= '<p>';
            $message .= sprintf(
                '<a href="%s">%s</a>',
                esc_url($verification_url),
                esc_html__(
                    'Verify Email Address',
                    'newsblenda-accounts'
                )
            );
            $message .= '</p>';

            $this->mailer->send(
                $email,
                $subject,
                $message
            );

        } else {

            update_user_meta(
                $user_id,
                'nb_email_verified',
                1
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Activity
        |--------------------------------------------------------------------------
        */

        $this->log_activity(
            $user_id,
            'registered'
        );

        do_action(
            'nb_accounts_after_register',
            $user_id
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        Helpers::redirect(

            add_query_arg(

                'registered',

                '1',

                home_url('/login/')

            )

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

        return true;
    }
    
    
        /**
     * Store registration activity.
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
     * Generate an email verification URL.
     */
    public static function verification_url(
        int $user_id,
        string $token
    ): string {

        return add_query_arg(
            [
                'user'  => $user_id,
                'token' => rawurlencode($token),
            ],
            home_url('/verify-email/')
        );

    }

    /**
     * Verify an email token.
     */
    public static function verify_email(
        int $user_id,
        string $token
    ): bool {

        $stored = (string) get_user_meta(
            $user_id,
            'nb_email_verification_token',
            true
        );

        if (
            empty($stored) ||
            ! hash_equals($stored, $token)
        ) {
            return false;
        }

        update_user_meta(
            $user_id,
            'nb_email_verified',
            1
        );

        delete_user_meta(
            $user_id,
            'nb_email_verification_token'
        );

        do_action(
            'nb_accounts_email_verified',
            $user_id
        );

        return true;
    }

    /**
     * Has the user verified their email?
     */
    public static function email_verified(
        int $user_id
    ): bool {

        return (bool) get_user_meta(
            $user_id,
            'nb_email_verified',
            true
        );

    }

    /**
     * Get account status.
     */
    public static function account_status(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_account_status',
            true
        );

    }

    /**
     * Stop registration with an error.
     */
    private function error(
        string $message
    ): void {

        wp_die(
            esc_html($message),
            esc_html__(
                'Registration Error',
                'newsblenda-accounts'
            ),
            [
                'response' => 400,
            ]
        );

    }
}