<?php

namespace Newsblenda\Accounts\Auth;

use Newsblenda\Accounts\Helpers\Helpers;

defined('ABSPATH') || exit;

class Login
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'init',
            [$this, 'process']
        );
    }

    /**
     * Process login form.
     */
    public function process(): void
    {
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || (! isset($_POST['nbe_login_submit']) && ! isset($_POST['nb_login']))
        ) {
            return;
        }

        if (
            !isset($_POST['_wpnonce'])
            || !wp_verify_nonce($_POST['_wpnonce'], 'nbe_nonce')
        ) {
            wp_die(
                esc_html__('Security check failed.', 'newsblenda-accounts')
            );
        }

        $this->authenticate();
    }
    
    
    /**
 * Check whether the user is temporarily locked out.
 */
private function is_locked_out(string $username): bool
{
    $user = get_user_by('login', $username);

    if (! $user) {
        return false;
    }

    $expires = (int) get_user_meta(
        $user->ID,
        'nb_lockout_expires',
        true
    );

    return $expires > time();
}

/**
 * Record failed login attempt.
 */
private function record_failed_attempt(string $username): void
{
    $user = get_user_by('login', $username);

    if (! $user) {
        return;
    }

    $attempts = (int) get_user_meta(
        $user->ID,
        'nb_login_attempts',
        true
    );

    $attempts++;

    update_user_meta(
        $user->ID,
        'nb_login_attempts',
        $attempts
    );

    $max = (int) Helpers::option(
        'max_login_attempts',
        5
    );

    if ($attempts >= $max) {

        $minutes = (int) Helpers::option(
            'lockout_minutes',
            30
        );

        update_user_meta(
            $user->ID,
            'nb_lockout_expires',
            time() + ($minutes * MINUTE_IN_SECONDS)
        );

    }
}

/**
 * Clear login lockout.
 */
private function clear_lockout(int $user_id): void
{
    update_user_meta(
        $user_id,
        'nb_login_attempts',
        0
    );

    delete_user_meta(
        $user_id,
        'nb_lockout_expires'
    );
}

/**
 * Log successful login.
 */
private function log_login(int $user_id): void
{
    update_user_meta(
        $user_id,
        'nb_last_login',
        current_time('mysql')
    );

    update_user_meta(
        $user_id,
        'nb_last_login_ip',
        sanitize_text_field(
            wp_unslash(
                $_SERVER['REMOTE_ADDR'] ?? ''
            )
        )
    );
}


    

    /**
     * Authenticate user.
     */
    private function authenticate(): void
    {
        $username = sanitize_user(
            wp_unslash($_POST['nbe_username'] ?? $_POST['username'] ?? '')
        );

        $password = (string) ($_POST['nbe_password'] ?? $_POST['password'] ?? '');

        $remember = ! empty($_POST['nbe_remember']) || ! empty($_POST['remember']);
        
        
        if ($this->is_locked_out($username)) {

    $this->error(
        'Too many failed login attempts. Please try again later.'
    );
    
    }

        if (
            empty($username)
            || empty($password)
        ) {

            $this->error(
                'Please enter your username and password.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Attempt Login
        |--------------------------------------------------------------------------
        */

        $user = wp_signon(

            [

                'user_login'    => $username,

                'user_password' => $password,

                'remember'      => $remember

            ],

            is_ssl()

        );

        if (is_wp_error($user)) {

        $this->record_failed_attempt($username);

        $this->error(
        'Invalid username or password.'
        );

        }

        /*
        |--------------------------------------------------------------------------
        | Email Verification
        |--------------------------------------------------------------------------
        */

        if (

            Helpers::option(
                'require_email_verification',
                1
            )

        ) {

            if (

                !Helpers::email_verified($user->ID)

            ) {

                wp_logout();

                $this->error(
                    'Please verify your email address before logging in.'
                );

            }

        }

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        */

        $status = Helpers::account_status($user->ID);

        if ($status === 'pending_approval') {

            wp_logout();

            $this->error(
                'Your author account is awaiting approval.'
            );

        }

        if ($status === 'restricted') {

            wp_logout();

            $this->error(
                'Your account has been restricted.'
            );

        }

        if ($status === 'suspended') {

            wp_logout();

            $this->error(
                'Your account has been suspended.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Update Last Login
        |--------------------------------------------------------------------------
        */


        $this->log_login($user->ID);

        $this->clear_lockout($user->ID);

        $this->after_login($user);

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        $redirect = apply_filters(
            'nb_accounts_login_redirect',
            $this->redirect_url($user),
            $user
        );

        Helpers::redirect($redirect);

    }
    

        /**
        * Determine redirect URL after login.
        */
        private function redirect_url(\WP_User $user): string
        {
        if (in_array('administrator', $user->roles, true)) {
        return admin_url();
        }

        if (in_array('nb_editor', $user->roles, true)) {
        return home_url('/editor-dashboard/');
        }

        if (in_array('nbe_author', $user->roles, true)) {
        return home_url('/dashboard/');
        }

        return home_url('/');
        }

        /**
        * Fire login actions.
        */
        private function after_login(\WP_User $user): void
        {
        do_action(
        'nb_accounts_after_login',
        $user
        );

        do_action(
        'nb_accounts_user_logged_in',
        $user->ID
        );
        }
    
    
    /**
     * Display login error.
     */
    private function error(string $message): void
    {
        wp_die(

            esc_html($message),

            esc_html__('Login Failed', 'newsblenda-accounts'),

            [

                'response' => 403

            ]

        );
    }
}