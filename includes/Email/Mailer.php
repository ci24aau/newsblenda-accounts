<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Email;

use Newsblenda\Accounts\Database\Database;
use Newsblenda\Accounts\Helpers\Helpers;
use Newsblenda\Accounts\Notifications\Notifications;

defined('ABSPATH') || exit;

class Mailer
{
    public function __construct()
    {
        add_filter('wp_mail_from', [$this, 'from_email']);
        add_filter('wp_mail_from_name', [$this, 'from_name']);
        add_filter('wp_mail_content_type', [$this, 'content_type']);

        add_action('nb_accounts_article_submitted', [__CLASS__, 'on_article_submitted'], 10, 2);
        add_action('nb_accounts_article_approved', [__CLASS__, 'on_article_approved'], 10, 2);
        add_action('nb_accounts_article_rejected', [__CLASS__, 'on_article_rejected'], 10, 3);
        add_action('nb_accounts_revision_requested', [__CLASS__, 'on_revision_requested'], 10, 3);
        add_action('nb_accounts_article_resubmitted', [__CLASS__, 'on_article_resubmitted'], 10, 2);
    }

    public function from_email(): string
    {
        return Helpers::option('sender_email', get_option('admin_email'));
    }

    public function from_name(): string
    {
        return Helpers::option('sender_name', get_bloginfo('name'));
    }

    public function content_type(): string
    {
        return 'text/html';
    }

    public static function send(string $to, string $subject, string $message, array $attachments = []): bool
    {
        $sent = wp_mail(
            $to,
            $subject,
            self::layout($message),
            ['Content-Type: text/html; charset=UTF-8'],
            $attachments
        );

        do_action('nb_accounts_email_sent', $to, $subject, $sent);

        return $sent;
    }

    public static function send_password_reset(int $user_id, string $reset_url): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Reset Your Password', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Password Reset', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('A password reset was requested for your account.', 'newsblenda-accounts') . '</p>' .
            '<p><a href="' . esc_url($reset_url) . '" style="display:inline-block;padding:12px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Reset Password', 'newsblenda-accounts') . '</a></p>' .
            '<p>' . esc_html__('If you did not request this, you can ignore this email.', 'newsblenda-accounts') . '</p>'
        );
    }

    public static function send_verification(int $user_id, string $token): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $url = add_query_arg(['nb_verify' => $token], home_url('/verify-email/'));

        return self::send(
            $user->user_email,
            __('Verify your Newsblenda account', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Welcome to Newsblenda', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Thank you for registering.', 'newsblenda-accounts') . '</p>' .
            '<p>' . esc_html__('Please verify your email address.', 'newsblenda-accounts') . '</p>' .
            '<p><a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 22px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;">' . esc_html__('Verify Email', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function send_account_approved(int $user_id): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Your Author Account Has Been Approved', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Congratulations!', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Your Newsblenda author account has been approved.', 'newsblenda-accounts') . '</p>' .
            '<p>' . esc_html__('You may now sign in and begin submitting articles.', 'newsblenda-accounts') . '</p>' .
            '<p><a href="' . esc_url(home_url('/login/')) . '" style="display:inline-block;padding:12px 22px;background:#198754;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Login', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function send_account_rejected(int $user_id, string $reason = ''): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Newsblenda Registration Update', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Registration Update', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Unfortunately your author application was not approved.', 'newsblenda-accounts') . '</p>' .
            '<p>' . esc_html($reason) . '</p>'
        );
    }

    public static function send_article_approved(int $user_id, string $title): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Article Approved', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Article Approved', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Your article has been approved and published.', 'newsblenda-accounts') . '</p>' .
            '<p><strong>' . esc_html($title) . '</strong></p>'
        );
    }

    public static function send_article_rejected(int $user_id, string $title, string $reason = ''): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Article Requires Revision', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Article Review', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Your submission requires revision.', 'newsblenda-accounts') . '</p>' .
            '<p><strong>' . esc_html($title) . '</strong></p>' .
            '<p>' . esc_html($reason) . '</p>'
        );
    }

    public static function send_notification(int $user_id, string $title, string $message): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send($user->user_email, $title, '<h2>' . esc_html($title) . '</h2><p>' . wp_kses_post($message) . '</p>');
    }

    public static function send_earnings(int $user_id, float $amount): bool
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        return self::send(
            $user->user_email,
            __('Earnings Updated', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Earnings Updated', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Your Newsblenda earnings have been updated.', 'newsblenda-accounts') . '</p>' .
            '<p><strong>£' . esc_html(number_format($amount, 2)) . '</strong></p>'
        );
    }

    public static function send_article_submitted_to_editor(int $post_id, int $author_id): bool
    {
        $post = get_post($post_id);
        $author = get_userdata($author_id);
        $recipients = self::reviewer_users();

        if (!$post instanceof \WP_Post || !$author || empty($recipients)) {
            return false;
        }

        $review_url = home_url('/editor-dashboard/?review=' . $post_id);
        $message = '<h2>' . esc_html__('New Article Submitted for Review', 'newsblenda-accounts') . '</h2>' .
            '<p><strong>' . esc_html__('Article:', 'newsblenda-accounts') . '</strong> ' . esc_html(get_the_title($post_id)) . '</p>' .
            '<p><strong>' . esc_html__('Author:', 'newsblenda-accounts') . '</strong> ' . esc_html($author->display_name) . '</p>' .
            '<p><a href="' . esc_url($review_url) . '" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Open Editor Dashboard', 'newsblenda-accounts') . '</a></p>';

        $sent = false;
        foreach ($recipients as $recipient) {
            $sent = self::send($recipient->user_email, __('New Article Submitted for Review', 'newsblenda-accounts'), $message) || $sent;
        }

        return $sent;
    }

    public static function send_article_approved_to_author(int $post_id, int $author_id): bool
    {
        $author = get_userdata($author_id);

        if (!$author) {
            return false;
        }

        return self::send(
            $author->user_email,
            __('Your Article Has Been Approved', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Great news!', 'newsblenda-accounts') . '</h2>' .
            '<p>' . esc_html__('Your article has been approved by the editorial team.', 'newsblenda-accounts') . '</p>' .
            '<p><strong>' . esc_html(get_the_title($post_id)) . '</strong></p>' .
            '<p><a href="' . esc_url(home_url('/dashboard/')) . '" style="display:inline-block;padding:12px 18px;background:#198754;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Open Dashboard', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function send_article_rejected_to_author(int $post_id, int $author_id, string $reason): bool
    {
        $author = get_userdata($author_id);

        if (!$author) {
            return false;
        }

        return self::send(
            $author->user_email,
            __('Article Review Update', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Article Review Update', 'newsblenda-accounts') . '</h2>' .
            '<p><strong>' . esc_html(get_the_title($post_id)) . '</strong></p>' .
            '<p>' . esc_html__('Your article was not approved for publication.', 'newsblenda-accounts') . '</p>' .
            '<p>' . esc_html($reason) . '</p>' .
            '<p><a href="' . esc_url(home_url('/dashboard/')) . '" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('View Dashboard', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function send_revision_requested_to_author(int $post_id, int $author_id, string $feedback, string $severity): bool
    {
        $author = get_userdata($author_id);

        if (!$author) {
            return false;
        }

        return self::send(
            $author->user_email,
            __('Revision Requested for Your Article', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Revision Requested', 'newsblenda-accounts') . '</h2>' .
            '<p><strong>' . esc_html(get_the_title($post_id)) . '</strong></p>' .
            '<p><strong>' . esc_html__('Severity:', 'newsblenda-accounts') . '</strong> ' . esc_html(ucfirst($severity)) . '</p>' .
            '<p>' . nl2br(esc_html($feedback)) . '</p>' .
            '<p><a href="' . esc_url(home_url('/submit/?resubmit=' . $post_id)) . '" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Review and Resubmit', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function send_article_resubmitted_to_editor(int $post_id, int $author_id): bool
    {
        $post = get_post($post_id);
        $author = get_userdata($author_id);
        $recipients = self::reviewer_users();

        if (!$post instanceof \WP_Post || !$author || empty($recipients)) {
            return false;
        }

        $review_url = home_url('/editor-dashboard/?review=' . $post_id);
        $message = '<h2>' . esc_html__('Article Resubmitted for Review', 'newsblenda-accounts') . '</h2>' .
            '<p><strong>' . esc_html__('Article:', 'newsblenda-accounts') . '</strong> ' . esc_html(get_the_title($post_id)) . '</p>' .
            '<p><strong>' . esc_html__('Author:', 'newsblenda-accounts') . '</strong> ' . esc_html($author->display_name) . '</p>' .
            '<p><a href="' . esc_url($review_url) . '" style="display:inline-block;padding:12px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('Review Article', 'newsblenda-accounts') . '</a></p>';

        $sent = false;
        foreach ($recipients as $recipient) {
            $sent = self::send($recipient->user_email, __('Article Resubmitted for Review', 'newsblenda-accounts'), $message) || $sent;
        }

        return $sent;
    }

    public static function send_article_published_to_author(int $post_id, int $author_id): bool
    {
        $author = get_userdata($author_id);

        if (!$author) {
            return false;
        }

        return self::send(
            $author->user_email,
            __('Your Article Has Been Published!', 'newsblenda-accounts'),
            '<h2>' . esc_html__('Your Article Has Been Published!', 'newsblenda-accounts') . '</h2>' .
            '<p><strong>' . esc_html(get_the_title($post_id)) . '</strong></p>' .
            '<p><a href="' . esc_url(get_permalink($post_id)) . '" style="display:inline-block;padding:12px 18px;background:#198754;color:#fff;text-decoration:none;border-radius:5px;">' . esc_html__('View Article', 'newsblenda-accounts') . '</a></p>'
        );
    }

    public static function notify(int $user_id, string $title, string $message, string $type, string $url): bool
    {
        return Notifications::add($user_id, $title, $message, $type, $url);
    }

    public static function on_article_submitted(int $post_id, int $author_id): void
    {
        self::send_article_submitted_to_editor($post_id, $author_id);

        foreach (self::reviewer_users() as $recipient) {
            self::notify(
                (int) $recipient->ID,
                __('New article submitted', 'newsblenda-accounts'),
                sprintf(__('A new article, "%s", is ready for review.', 'newsblenda-accounts'), get_the_title($post_id)),
                'info',
                home_url('/editor-dashboard/?review=' . $post_id)
            );
        }
    }

    public static function on_article_approved(int $post_id, int $editor_id): void
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post) {
            return;
        }

        self::send_article_approved_to_author($post_id, (int) $post->post_author);

        self::notify(
            (int) $post->post_author,
            __('Article approved', 'newsblenda-accounts'),
            sprintf(__('Your article "%s" has been approved and published.', 'newsblenda-accounts'), get_the_title($post_id)),
            'success',
            get_permalink($post_id) ?: home_url('/dashboard/')
        );
    }

    public static function on_article_rejected(int $post_id, int $editor_id, string $reason): void
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post) {
            return;
        }

        self::send_article_rejected_to_author($post_id, (int) $post->post_author, $reason);

        self::notify(
            (int) $post->post_author,
            __('Article rejected', 'newsblenda-accounts'),
            sprintf(__('Your article "%s" was rejected. %s', 'newsblenda-accounts'), get_the_title($post_id), $reason),
            'error',
            home_url('/dashboard/')
        );
    }

    public static function on_revision_requested(int $post_id, int $editor_id, string $feedback): void
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post) {
            return;
        }

        self::send_revision_requested_to_author($post_id, (int) $post->post_author, $feedback, self::latest_revision_severity($post_id));

        self::notify(
            (int) $post->post_author,
            __('Revision requested', 'newsblenda-accounts'),
            sprintf(__('Revisions were requested for "%s".', 'newsblenda-accounts'), get_the_title($post_id)),
            'warning',
            home_url('/submit/?resubmit=' . $post_id)
        );
    }

    public static function on_article_resubmitted(int $post_id, int $author_id): void
    {
        self::send_article_resubmitted_to_editor($post_id, $author_id);

        foreach (self::reviewer_users() as $recipient) {
            self::notify(
                (int) $recipient->ID,
                __('Article resubmitted', 'newsblenda-accounts'),
                sprintf(__('The article "%s" has been resubmitted for review.', 'newsblenda-accounts'), get_the_title($post_id)),
                'info',
                home_url('/editor-dashboard/?review=' . $post_id)
            );
        }
    }

    private static function reviewer_users(): array
    {
        $users = get_users(['role' => 'nb_editor']);
        $cap_users = get_users(['capability' => 'nb_review_articles']);
        $indexed = [];

        foreach (array_merge($users, $cap_users) as $user) {
            if ($user instanceof \WP_User) {
                $indexed[$user->ID] = $user;
            }
        }

        return array_values($indexed);
    }

    private static function latest_revision_severity(int $post_id): string
    {
        global $wpdb;

        $severity = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT severity FROM ' . Database::table('article_revisions') . ' WHERE article_id = %d ORDER BY requested_at DESC LIMIT 1',
                $post_id
            )
        );

        return is_string($severity) && $severity !== '' ? $severity : 'minor';
    }

    private static function layout(string $content): string
    {
        $site_name = esc_html(get_bloginfo('name'));
        $year      = esc_html((string) gmdate('Y'));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:40px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:700px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
        <div style="background:#0d6efd;padding:24px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;">{$site_name}</h1>
        </div>
        <div style="padding:40px;">
            {$content}
        </div>
        <div style="padding:20px 40px;background:#fafafa;border-top:1px solid #e5e5e5;">
            <p style="margin:0;font-size:13px;color:#777777;">&copy; {$year} {$site_name}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public static function plain_text(string $html): string
    {
        return wp_strip_all_tags($html);
    }

    public static function valid(string $email): bool
    {
        return (bool) is_email($email);
    }

    public static function queue(string $to, string $subject, string $message, array $attachments = []): bool
    {
        return self::send($to, $subject, $message, $attachments);
    }

    public static function headers(): array
    {
        return ['Content-Type: text/html; charset=UTF-8'];
    }

    public static function html(string $to, string $subject, string $html): bool
    {
        return self::send($to, $subject, $html);
    }

    public static function text(string $to, string $subject, string $text): bool
    {
        return wp_mail($to, $subject, $text);
    }

    public static function sender_email(): string
    {
        return Helpers::option('sender_email', get_option('admin_email'));
    }

    public static function sender_name(): string
    {
        return Helpers::option('sender_name', get_bloginfo('name'));
    }
}
