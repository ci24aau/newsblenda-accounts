<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Workflow;

use Newsblenda\Accounts\Database\Database;

defined('ABSPATH') || exit;

class Workflow
{
    public static function submit(int $post_id, int $user_id): bool
    {
        if (!self::can_submit($post_id, $user_id)) {
            return false;
        }

        $previous_status = self::get_status($post_id);

        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'pending',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($post_id, 'nb_article_status', 'submitted');
        update_post_meta($post_id, 'nb_submitted_at', current_time('mysql'));
        delete_post_meta($post_id, 'nb_rejection_reason');

        self::log_action($post_id, $user_id, 'submitted', $previous_status, 'submitted');

        do_action('nb_accounts_article_submitted', $post_id, $user_id);

        return true;
    }

    public static function approve(int $post_id, int $editor_id): bool
    {
        if (!self::can_approve($editor_id)) {
            return false;
        }

        $previous_status = self::get_status($post_id);

        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'publish',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        $now = current_time('mysql');

        update_post_meta($post_id, 'nb_article_status', 'approved');
        update_post_meta($post_id, 'nb_reviewed_at', $now);
        update_post_meta($post_id, 'nb_published_at', $now);
        update_post_meta($post_id, 'nb_editor_status', 'approved');

        self::log_action($post_id, $editor_id, 'approved', $previous_status, 'approved');

        do_action('nb_accounts_article_approved', $post_id, $editor_id);

        return true;
    }

    public static function reject(int $post_id, int $editor_id, string $reason): bool
    {
        if (!self::can_reject($editor_id) || $reason === '') {
            return false;
        }

        $previous_status = self::get_status($post_id);

        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'draft',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($post_id, 'nb_article_status', 'rejected');
        update_post_meta($post_id, 'nb_reviewed_at', current_time('mysql'));
        update_post_meta($post_id, 'nb_editor_status', 'rejected');
        update_post_meta($post_id, 'nb_rejection_reason', $reason);

        self::insert_feedback($post_id, $editor_id, 'rejection', $reason);
        self::log_action(
            $post_id,
            $editor_id,
            'rejected',
            $previous_status,
            'rejected',
            ['reason' => $reason]
        );

        do_action('nb_accounts_article_rejected', $post_id, $editor_id, $reason);

        return true;
    }

    public static function request_revision(int $post_id, int $editor_id, string $feedback, string $severity = 'minor'): bool
    {
        if (!self::can_request_revision($editor_id) || $feedback === '') {
            return false;
        }

        global $wpdb;

        $severity = in_array($severity, ['minor', 'major'], true) ? $severity : 'minor';
        $previous_status = self::get_status($post_id);

        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'draft',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($post_id, 'nb_article_status', 'revision-requested');
        update_post_meta($post_id, 'nb_reviewed_at', current_time('mysql'));
        update_post_meta($post_id, 'nb_editor_status', 'revision_requested');

        $table = Database::table('article_revisions');
        $next_revision = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(MAX(revision_number), 0) FROM {$table} WHERE article_id = %d",
                $post_id
            )
        ) + 1;

        $inserted = $wpdb->insert(
            $table,
            [
                'article_id'       => $post_id,
                'revision_number'  => $next_revision,
                'editor_id'        => $editor_id,
                'feedback'         => $feedback,
                'severity'         => $severity,
                'requested_at'     => current_time('mysql'),
                'resubmitted_at'   => null,
                'status'           => 'pending',
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return false;
        }

        self::insert_feedback($post_id, $editor_id, 'revision-request', $feedback, $severity);
        self::log_action(
            $post_id,
            $editor_id,
            'revision-requested',
            $previous_status,
            'revision-requested',
            [
                'severity' => $severity,
                'feedback' => $feedback,
            ]
        );

        do_action('nb_accounts_revision_requested', $post_id, $editor_id, $feedback);

        return true;
    }

    public static function resubmit(int $post_id, int $user_id): bool
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post || (int) $post->post_author !== $user_id || !user_can($user_id, 'nb_submit_articles')) {
            return false;
        }

        global $wpdb;

        $previous_status = self::get_status($post_id);
        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'pending',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($post_id, 'nb_article_status', 'revision-submitted');
        update_post_meta($post_id, 'nb_submitted_at', current_time('mysql'));

        $table = Database::table('article_revisions');
        $revision_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE article_id = %d AND status = %s ORDER BY requested_at DESC LIMIT 1",
                $post_id,
                'pending'
            )
        );

        if ($revision_id > 0) {
            $wpdb->update(
                $table,
                [
                    'resubmitted_at' => current_time('mysql'),
                    'status'         => 'resubmitted',
                ],
                ['id' => $revision_id],
                ['%s', '%s'],
                ['%d']
            );
        }

        self::log_action($post_id, $user_id, 'revision-submitted', $previous_status, 'revision-submitted');

        do_action('nb_accounts_article_resubmitted', $post_id, $user_id);

        return true;
    }

    public static function publish(int $post_id, int $editor_id): bool
    {
        if (!self::can_approve($editor_id)) {
            return false;
        }

        $previous_status = self::get_status($post_id);
        $result = wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'publish',
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        $now = current_time('mysql');

        update_post_meta($post_id, 'nb_article_status', 'published');
        update_post_meta($post_id, 'nb_reviewed_at', $now);
        update_post_meta($post_id, 'nb_published_at', $now);
        update_post_meta($post_id, 'nb_editor_status', 'approved');

        self::log_action($post_id, $editor_id, 'published', $previous_status, 'published');

        return true;
    }

    public static function schedule(int $post_id, int $editor_id, string $date): bool
    {
        if (!self::can_approve($editor_id)) {
            return false;
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return false;
        }

        $scheduled_local = wp_date('Y-m-d H:i:s', $timestamp);
        $scheduled_gmt = get_gmt_from_date($scheduled_local);
        $previous_status = self::get_status($post_id);

        $result = wp_update_post(
            [
                'ID'            => $post_id,
                'post_status'   => 'future',
                'post_date'     => $scheduled_local,
                'post_date_gmt' => $scheduled_gmt,
            ],
            true
        );

        if (is_wp_error($result)) {
            return false;
        }

        update_post_meta($post_id, 'nb_article_status', 'scheduled');
        update_post_meta($post_id, 'nb_scheduled_at', $scheduled_local);

        self::log_action(
            $post_id,
            $editor_id,
            'scheduled',
            $previous_status,
            'scheduled',
            ['scheduled_at' => $scheduled_local]
        );

        return true;
    }

    public static function get_status(int $post_id): string
    {
        $status = get_post_meta($post_id, 'nb_article_status', true);

        if (is_string($status) && $status !== '') {
            return $status;
        }

        $post_status = get_post_status($post_id);

        return is_string($post_status) ? $post_status : '';
    }

    public static function get_workflow_log(int $post_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . Database::table('workflow_log') . ' WHERE article_id = %d ORDER BY created_at DESC',
                $post_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function get_revisions(int $post_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . Database::table('article_revisions') . ' WHERE article_id = %d ORDER BY requested_at DESC',
                $post_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function can_submit(int $post_id, int $user_id): bool
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post || (int) $post->post_author !== $user_id || !user_can($user_id, 'nb_submit_articles')) {
            return false;
        }

        $status = self::get_status($post_id);

        return in_array($post->post_status, ['draft'], true)
            || in_array($status, ['draft', 'revision-requested'], true);
    }

    public static function can_approve(int $user_id): bool
    {
        return user_can($user_id, 'nb_approve_articles');
    }

    public static function can_reject(int $user_id): bool
    {
        return user_can($user_id, 'nb_reject_articles');
    }

    public static function can_request_revision(int $user_id): bool
    {
        return user_can($user_id, 'nb_request_revision');
    }

    private static function log_action(int $post_id, int $user_id, string $action, string $prev, string $new, array $meta = []): void
    {
        global $wpdb;

        $wpdb->insert(
            Database::table('workflow_log'),
            [
                'article_id'       => $post_id,
                'user_id'          => $user_id,
                'action'           => sanitize_key($action),
                'previous_status'  => sanitize_text_field($prev),
                'new_status'       => sanitize_text_field($new),
                'metadata'         => !empty($meta) ? wp_json_encode($meta) : null,
                'created_at'       => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    private static function insert_feedback(int $post_id, int $user_id, string $type, string $text, string $severity = ''): void
    {
        global $wpdb;

        $wpdb->insert(
            Database::table('article_feedback'),
            [
                'article_id'     => $post_id,
                'user_id'        => $user_id,
                'feedback_type'  => sanitize_key($type),
                'feedback_text'  => $text,
                'severity'       => sanitize_text_field($severity),
                'created_at'     => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }
}
