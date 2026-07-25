<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Auth;

use Newsblenda\Accounts\Email\Mailer;
use Newsblenda\Accounts\Helpers\Helpers;

defined('ABSPATH') || exit;

class Register
{
    private const VERIFICATION_WINDOW = 2 * DAY_IN_SECONDS;
    private const REG_STATE_PREFIX = 'nb_register_state_';
    private const REG_STATE_TTL = 15 * MINUTE_IN_SECONDS;
    private const RESEND_MIN_INTERVAL = MINUTE_IN_SECONDS;
    private const RESEND_HOURLY_MAX   = 5;
    private const HONEYPOT_FIELD      = 'nb_website';

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
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
            isset($_POST['nbe_resend_verification_submit'])
        ) {
            $this->resend_verification_email();
            return;
        }

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
            $this->redirect_with_form_state(
                $this->request_values(),
                [
                    '_general' => __(
                        'Security validation failed. Please refresh the page and try again.',
                        'newsblenda-accounts'
                    ),
                ]
            );
        }

        $this->register_user();
    }

    /**
     * Register a new account.
     */
    private function register_user(): void
    {
        $values = $this->request_values();
        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Honeypot — silently reject bots that fill the hidden field.
        |--------------------------------------------------------------------------
        */
        if (! empty($_POST[self::HONEYPOT_FIELD])) {
            $this->redirect_with_form_state($values, []);
        }

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

        if (empty($username)) {
            $errors['nbe_username'] = __(
                'Username is required.',
                'newsblenda-accounts'
            );
        }

        if (empty($email)) {
            $errors['nbe_email'] = __(
                'Email address is required.',
                'newsblenda-accounts'
            );
        } elseif (! is_email($email)) {
            $errors['nbe_email'] = __(
                'Please enter a valid email address.',
                'newsblenda-accounts'
            );
        }

        if (empty($password)) {
            $errors['nbe_password'] = __(
                'Password is required.',
                'newsblenda-accounts'
            );
        }

        if (empty($confirm)) {
            $errors['nbe_confirm_password'] = __(
                'Please confirm your password.',
                'newsblenda-accounts'
            );
        } elseif ($password !== $confirm) {
            $errors['nbe_confirm_password'] = __(
                'Passwords do not match.',
                'newsblenda-accounts'
            );
        }

        if (! empty($password) && ! $this->password_is_strong($password)) {
            $errors['nbe_password'] = __(
                'Password must contain at least 8 characters, uppercase, lowercase, number, and special character.',
                'newsblenda-accounts'
            );
        }

        if (username_exists($username)) {
            $errors['nbe_username'] = __(
                'Username already exists.',
                'newsblenda-accounts'
            );
        }

        if (email_exists($email)) {
            $errors['nbe_email'] = __(
                'Email address already exists.',
                'newsblenda-accounts'
            );
        }

        if (! in_array($account, ['author', 'subscriber'], true)) {
            $errors['account_type'] = __(
                'Invalid account role selected.',
                'newsblenda-accounts'
            );
        }

        if (empty($_POST['nbe_terms'])) {
            $errors['nbe_terms'] = __(
                'You must agree to the terms and guidelines.',
                'newsblenda-accounts'
            );
        }

        if (
            $account === 'author' &&
            ! Helpers::option(
                'allow_author_registration',
                1
            )
        ) {
            $errors['account_type'] = __(
                'Author registration is currently disabled.',
                'newsblenda-accounts'
            );
        }

        if (
            $account === 'subscriber' &&
            ! Helpers::option(
                'allow_subscriber_registration',
                1
            )
        ) {
            $errors['account_type'] = __(
                'Reader registration is currently disabled.',
                'newsblenda-accounts'
            );
        }

        if (! empty($errors)) {
            $this->redirect_with_form_state($values, $errors);
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
            $this->redirect_with_form_state(
                $values,
                [
                    '_general' => $user_id->get_error_message(),
                ]
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
            $token = $this->issue_verification_token($user_id);
            $sent = $token !== '' && $this->send_verification_email(
                $user_id,
                $email,
                $username,
                $token
            );

            if (! $sent) {
                if (! function_exists('wp_delete_user')) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                }

                wp_delete_user($user_id);

                $this->redirect_with_form_state(
                    $values,
                    [
                        '_general' => __(
                            "We couldn't send your verification email. Please try again.",
                            'newsblenda-accounts'
                        ),
                    ]
                );
            }
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

        if ($verification_required) {
            Helpers::redirect(
                add_query_arg(
                    [
                        'status' => 'registered',
                    ],
                    home_url('/verify-email/')
                )
            );
        }

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
     *
     * Requires at least 8 characters, one uppercase, one lowercase,
     * one number and one special character.
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

        // Require at least one non-alphanumeric character.
        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
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
        return self::verify_email_status($user_id, $token) === 'success';
    }

    /**
     * Verify an email token and return status.
     */
    public static function verify_email_status(
        int $user_id,
        string $token
    ): string {
        $user = get_userdata($user_id);
        if (! $user) {
            return 'invalid';
        }

        if (self::email_verified($user_id)) {
            return 'already-verified';
        }

        $hash = (string) get_user_meta(
            $user_id,
            'nb_email_verification_token_hash',
            true
        );
        $expires = (int) get_user_meta(
            $user_id,
            'nb_email_verification_expires',
            true
        );

        if ($hash === '' || $expires <= 0) {
            return 'invalid';
        }

        if (time() > $expires) {
            self::clear_verification_token($user_id);
            return 'expired';
        }

        if (! wp_check_password($token, $hash)) {
            return 'invalid';
        }

        update_user_meta(
            $user_id,
            'nb_email_verified',
            1
        );
        self::clear_verification_token($user_id);

        do_action(
            'nb_accounts_email_verified',
            $user_id
        );

        return 'success';
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
     * Consume persisted registration form state.
     */
    public static function consume_form_state(): array
    {
        $key = isset($_GET['reg_state'])
            ? sanitize_key(wp_unslash($_GET['reg_state']))
            : '';

        if ($key === '') {
            return [
                'values' => [],
                'errors' => [],
            ];
        }

        $state = get_transient(self::REG_STATE_PREFIX . $key);
        delete_transient(self::REG_STATE_PREFIX . $key);

        if (! is_array($state)) {
            return [
                'values' => [],
                'errors' => [],
            ];
        }

        return [
            'values' => is_array($state['values'] ?? null) ? $state['values'] : [],
            'errors' => is_array($state['errors'] ?? null) ? $state['errors'] : [],
        ];
    }

    /**
     * Handle verification resend submission.
     */
    private function resend_verification_email(): void
    {
        if (
            ! isset($_POST['_wpnonce']) ||
            ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'nb_resend_verification'
            )
        ) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'resend-invalid-nonce',
                    home_url('/verify-email/')
                )
            );
        }

        $email = sanitize_email(
            wp_unslash($_POST['nbe_resend_email'] ?? '')
        );

        if (! is_email($email)) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'resend-invalid-email',
                    home_url('/verify-email/')
                )
            );
        }

        $user = get_user_by('email', $email);
        if (! $user instanceof \WP_User) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'resent',
                    home_url('/verify-email/')
                )
            );
        }

        if (self::email_verified($user->ID)) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'already-verified',
                    home_url('/verify-email/')
                )
            );
        }

        $last_sent = (int) get_user_meta(
            $user->ID,
            'nb_email_verification_last_sent',
            true
        );

        if ($last_sent > 0 && (time() - $last_sent) < self::RESEND_MIN_INTERVAL) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'resend-throttled',
                    home_url('/verify-email/')
                )
            );
        }

        // Enforce hourly cap: max 5 resend attempts per hour per email address.
        $rate_key     = 'nb_resend_rate_' . wp_hash(strtolower($user->user_email));
        $hourly_count = (int) get_transient($rate_key);
        if ($hourly_count >= self::RESEND_HOURLY_MAX) {
            Helpers::redirect(
                add_query_arg(
                    'status',
                    'resend-throttled',
                    home_url('/verify-email/')
                )
            );
        }
        set_transient($rate_key, $hourly_count + 1, HOUR_IN_SECONDS);

        $token = $this->issue_verification_token($user->ID);
        $sent = $token !== '' && $this->send_verification_email(
            $user->ID,
            $user->user_email,
            $user->user_login,
            $token
        );

        Helpers::redirect(
            add_query_arg(
                'status',
                $sent ? 'resent' : 'resend-failed',
                home_url('/verify-email/')
            )
        );
    }

    /**
     * Store form state and redirect to register page.
     */
    private function redirect_with_form_state(
        array $values,
        array $errors
    ): void {
        $state_key = wp_generate_uuid4();
        set_transient(
            self::REG_STATE_PREFIX . $state_key,
            [
                'values' => $values,
                'errors' => $errors,
            ],
            self::REG_STATE_TTL
        );

        Helpers::redirect(
            add_query_arg(
                'reg_state',
                $state_key,
                home_url('/register/')
            )
        );
    }

    /**
     * Collect safe form values for refill.
     */
    private function request_values(): array
    {
        return [
            'nbe_username' => sanitize_user(
                wp_unslash($_POST['nbe_username'] ?? $_POST['username'] ?? '')
            ),
            'nbe_email' => sanitize_email(
                wp_unslash($_POST['nbe_email'] ?? $_POST['email'] ?? '')
            ),
            'nbe_full_name' => sanitize_text_field(
                wp_unslash($_POST['nbe_full_name'] ?? $_POST['display_name'] ?? '')
            ),
            'nbe_phone' => sanitize_text_field(
                wp_unslash($_POST['nbe_phone'] ?? $_POST['nb_phone'] ?? '')
            ),
            'nbe_country' => sanitize_text_field(
                wp_unslash($_POST['nbe_country'] ?? $_POST['nb_country'] ?? '')
            ),
            'nbe_state' => sanitize_text_field(
                wp_unslash($_POST['nbe_state'] ?? $_POST['nb_state'] ?? '')
            ),
            'nbe_niche' => sanitize_text_field(
                wp_unslash($_POST['nbe_niche'] ?? $_POST['nb_niche'] ?? '')
            ),
            'nbe_terms' => ! empty($_POST['nbe_terms']) ? '1' : '0',
            'account_type' => sanitize_text_field(
                wp_unslash($_POST['account_type'] ?? 'subscriber')
            ),
        ];
    }

    /**
     * Generate and store a verification token hash.
     */
    private function issue_verification_token(int $user_id): string
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

        if (empty($token)) {
            do_action(
                'nb_accounts_verification_token_fallback',
                $user_id
            );
            return '';
        }

        update_user_meta(
            $user_id,
            'nb_email_verification_token_hash',
            wp_hash_password($token)
        );
        update_user_meta(
            $user_id,
            'nb_email_verification_expires',
            time() + self::VERIFICATION_WINDOW
        );
        update_user_meta(
            $user_id,
            'nb_email_verification_last_sent',
            time()
        );

        // Clean up legacy token storage.
        delete_user_meta($user_id, 'nb_email_verification_token');

        return $token;
    }

    /**
     * Clear token metadata.
     */
    private static function clear_verification_token(int $user_id): void
    {
        delete_user_meta($user_id, 'nb_email_verification_token_hash');
        delete_user_meta($user_id, 'nb_email_verification_expires');
        delete_user_meta($user_id, 'nb_email_verification_token');
    }

    /**
     * Send verification message using the HTML email template.
     */
    private function send_verification_email(
        int $user_id,
        string $email,
        string $username,
        string $token
    ): bool {
        $verification_url = self::verification_url($user_id, $token);
        $subject          = __('Verify your email address', 'newsblenda-accounts');

        $template = NB_ACCOUNTS_PATH . 'templates/emails/verify-email.php';

        if (file_exists($template)) {
            ob_start();
            $user_name = $username;
            include $template;
            $message = (string) ob_get_clean();
        } else {
            // Fallback inline HTML when the email template file is missing.
            // Mailer::send() internally fires the 'nb_accounts_email_sent' action.
            $expiry_hours = (int) (self::VERIFICATION_WINDOW / HOUR_IN_SECONDS);
            $message  = '<p>' . sprintf(
                esc_html__('Hello %s,', 'newsblenda-accounts'),
                esc_html($username)
            ) . '</p>';
            $message .= '<p>' . sprintf(
                esc_html__(
                    'Thank you for registering your Newsblenda account. Please verify your email within %d hours to activate your account.',
                    'newsblenda-accounts'
                ),
                $expiry_hours
            ) . '</p>';
            $message .= '<p><a href="' . esc_url($verification_url) . '" style="display:inline-block;padding:12px 20px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;">' .
                esc_html__('Verify Email Address', 'newsblenda-accounts') .
                '</a></p>';
            $message .= '<p>' . esc_html__(
                'If the button does not work, copy and paste this URL into your browser:',
                'newsblenda-accounts'
            ) . '<br><a href="' . esc_url($verification_url) . '">' . esc_html($verification_url) . '</a></p>';
            return Mailer::send($email, $subject, $message);
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent    = wp_mail($email, $subject, $message, $headers);

        do_action('nb_accounts_email_sent', $email, $subject, $sent);

        return $sent;
    }
}