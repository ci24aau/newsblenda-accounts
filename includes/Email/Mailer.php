<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Email;

use Newsblenda\Accounts\Helpers\Helpers;

defined('ABSPATH') || exit;

class Mailer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter(
            'wp_mail_from',
            [$this, 'from_email']
        );

        add_filter(
            'wp_mail_from_name',
            [$this, 'from_name']
        );

        add_filter(
            'wp_mail_content_type',
            [$this, 'content_type']
        );
    }

    /**
     * Sender email.
     */
    public function from_email(): string
    {
        return Helpers::option(
            'sender_email',
            get_option('admin_email')
        );
    }

    /**
     * Sender name.
     */
    public function from_name(): string
    {
        return Helpers::option(
            'sender_name',
            get_bloginfo('name')
        );
    }

    /**
     * HTML content type.
     */
    public function content_type(): string
    {
        return 'text/html';
    }

    /**
     * Send email.
     */
    public static function send(
        string $to,
        string $subject,
        string $message,
        array $attachments = []
    ): bool {

        $headers = [

            'Content-Type: text/html; charset=UTF-8'

        ];

        $sent = wp_mail(
            $to,
            $subject,
            self::layout($message),
            $headers,
            $attachments
        );

        do_action(
            'nb_accounts_email_sent',
            $to,
            $subject,
            $sent
        );

        return $sent;
    }

    /**
     * Send password reset email.
     */
    public static function send_password_reset(
        int $user_id,
        string $reset_url
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        $subject = __('Reset Your Password', 'newsblenda-accounts');

        $message = '

            <h2>Password Reset</h2>

            <p>A password reset was requested for your account.</p>

            <p>

                <a href="' . esc_url($reset_url) . '" style="display:inline-block;padding:12px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">

                    Reset Password

                </a>

            </p>

            <p>If you did not request this, you can ignore this email.</p>

        ';

        return self::send(
            $user->user_email,
            $subject,
            $message
        );
    }

    /**
     * Send verification email.
     */
    public static function send_verification(
        int $user_id,
        string $token
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        $url = add_query_arg(
            [
                'nb_verify' => $token,
            ],
            home_url('/verify-email/')
        );

        $subject = __('Verify your Newsblenda account', 'newsblenda-accounts');

        $message = '

            <h2>Welcome to Newsblenda</h2>

            <p>Thank you for registering.</p>

            <p>Please verify your email address.</p>

            <p>

                <a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 22px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;">

                    Verify Email

                </a>

            </p>

        ';

        return self::send(
            $user->user_email,
            $subject,
            $message
        );
    }
    
        /**
     * Send account approved email.
     */
    public static function send_account_approved(
        int $user_id
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        $subject = __(
            'Your Author Account Has Been Approved',
            'newsblenda-accounts'
        );

        $message = '

            <h2>Congratulations!</h2>

            <p>Your Newsblenda author account has been approved.</p>

            <p>You may now sign in and begin submitting articles.</p>

            <p>

                <a href="' . esc_url(home_url('/login/')) . '" style="display:inline-block;padding:12px 22px;background:#198754;color:#fff;text-decoration:none;border-radius:5px;">

                    Login

                </a>

            </p>

        ';

        return self::send(
            $user->user_email,
            $subject,
            $message
        );
    }

    /**
     * Send account rejected email.
     */
    public static function send_account_rejected(
        int $user_id,
        string $reason = ''
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        $subject = __(
            'Newsblenda Registration Update',
            'newsblenda-accounts'
        );

        $message = '

            <h2>Registration Update</h2>

            <p>Unfortunately your author application was not approved.</p>

            <p>' . esc_html($reason) . '</p>

        ';

        return self::send(
            $user->user_email,
            $subject,
            $message
        );
    }

    /**
     * Send article approved email.
     */
    public static function send_article_approved(
        int $user_id,
        string $title
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        return self::send(

            $user->user_email,

            __('Article Approved', 'newsblenda-accounts'),

            '

            <h2>Article Approved</h2>

            <p>Your article has been approved and published.</p>

            <p><strong>' . esc_html($title) . '</strong></p>

            '

        );
    }

    /**
     * Send article rejected email.
     */
    public static function send_article_rejected(
        int $user_id,
        string $title,
        string $reason = ''
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        return self::send(

            $user->user_email,

            __('Article Requires Revision', 'newsblenda-accounts'),

            '

            <h2>Article Review</h2>

            <p>Your submission requires revision.</p>

            <p><strong>' . esc_html($title) . '</strong></p>

            <p>' . esc_html($reason) . '</p>

            '

        );
    }

    /**
     * Send notification email.
     */
    public static function send_notification(
        int $user_id,
        string $title,
        string $message
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        return self::send(

            $user->user_email,

            $title,

            '<h2>' . esc_html($title) . '</h2><p>' .
            wp_kses_post($message) .
            '</p>'

        );
    }
    
        /**
     * Send earnings notification.
     */
    public static function send_earnings(
        int $user_id,
        float $amount
    ): bool {

        $user = get_userdata($user_id);

        if (! $user) {
            return false;
        }

        return self::send(

            $user->user_email,

            __('Earnings Updated', 'newsblenda-accounts'),

            '

            <h2>Earnings Updated</h2>

            <p>Your Newsblenda earnings have been updated.</p>

            <p><strong>£' . number_format($amount, 2) . '</strong></p>

            '

        );

    }

    /**
     * Email layout.
     */
    private static function layout(
        string $content
    ): string {

        return '

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="UTF-8">

        </head>

        <body style="margin:0;padding:40px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">

            <div style="max-width:700px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">

                <div style="background:#0d6efd;padding:24px;text-align:center;">

                    <h1 style="margin:0;color:#ffffff;">

                        ' . esc_html(get_bloginfo('name')) . '

                    </h1>

                </div>

                <div style="padding:40px;">

                    ' . $content . '

                </div>

                <div style="padding:20px 40px;background:#fafafa;border-top:1px solid #e5e5e5;">

                    <p style="margin:0;font-size:13px;color:#777777;">

                        © ' . date('Y') . ' ' .
                        esc_html(get_bloginfo('name')) . '

                    </p>

                </div>

            </div>

        </body>

        </html>

        ';

    }

    /**
     * Plain text version.
     */
    public static function plain_text(
        string $html
    ): string {

        return wp_strip_all_tags($html);

    }

    /**
     * Validate an email address.
     */
    public static function valid(
        string $email
    ): bool {

        return (bool) is_email($email);

    }

    /**
     * Queue-ready wrapper.
     */
    public static function queue(
        string $to,
        string $subject,
        string $message,
        array $attachments = []
    ): bool {

        return self::send(
            $to,
            $subject,
            $message,
            $attachments
        );

    }

    /**
     * Default email headers.
     */
    public static function headers(): array
    {
        return [

            'Content-Type: text/html; charset=UTF-8',

        ];
    }

    /**
     * Send a raw HTML email.
     */
    public static function html(
        string $to,
        string $subject,
        string $html
    ): bool {

        return self::send(
            $to,
            $subject,
            $html
        );

    }

    /**
     * Send a plain text email.
     */
    public static function text(
        string $to,
        string $subject,
        string $text
    ): bool {

        return wp_mail(
            $to,
            $subject,
            $text
        );

    }

    /**
     * Get configured sender email.
     */
    public static function sender_email(): string
    {
        return Helpers::option(
            'sender_email',
            get_option('admin_email')
        );
    }

    /**
     * Get configured sender name.
     */
    public static function sender_name(): string
    {
        return Helpers::option(
            'sender_name',
            get_bloginfo('name')
        );
    }
}