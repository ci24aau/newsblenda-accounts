<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

use Newsblenda\Accounts\Database\Database;
use Newsblenda\Accounts\Workflow\WorkflowManager;

defined('ABSPATH') || exit;

final class QueryOptimizer
{
    /**
     * Get paginated author articles without N+1 metadata queries.
     */
    public static function get_author_articles(
        int $user_id,
        string $status = 'all',
        int $limit = 20,
        int $offset = 0
    ): array {
        global $wpdb;

        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $where  = [
            "p.post_type = 'post'",
            'p.post_author = %d',
        ];
        $params = [$user_id];

        switch ($status) {
            case 'publish':
            case 'pending':
            case 'draft':
            case 'future':
                $where[]  = 'p.post_status = %s';
                $params[] = $status;
                break;

            case WorkflowManager::STATUS_PENDING_REVIEW:
                $where[]  = 'p.post_status = %s';
                $params[] = 'pending';
                break;

            case WorkflowManager::STATUS_APPROVED:
            case WorkflowManager::STATUS_REJECTED:
            case WorkflowManager::STATUS_REVISION_REQUESTED:
                $where[]  = 'p.post_status = %s';
                $where[]  = 'workflow_meta.meta_value = %s';
                $params[] = 'draft';
                $params[] = $status;
                break;
        }

        $sql = "
            SELECT
                p.ID,
                p.post_author,
                p.post_title,
                p.post_status,
                p.post_date,
                p.post_modified,
                p.post_content,
                COALESCE(workflow_meta.meta_value, %s) AS workflow_status,
                COALESCE(CAST(view_meta.meta_value AS UNSIGNED), 0) AS view_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} workflow_meta
                ON workflow_meta.post_id = p.ID
                AND workflow_meta.meta_key = 'nb_workflow_status'
            LEFT JOIN {$wpdb->postmeta} view_meta
                ON view_meta.post_id = p.ID
                AND view_meta.meta_key = 'nb_valid_views'
            WHERE " . implode(' AND ', $where) . '
            ORDER BY p.post_modified DESC
            LIMIT %d OFFSET %d
        ';

        array_unshift($params, WorkflowManager::STATUS_DRAFT);
        $params[] = $limit;
        $params[] = $offset;

        return (array) $wpdb->get_results(
            $wpdb->prepare($sql, ...$params)
        );
    }

    /**
     * Get author dashboard statistics in one aggregate query.
     */
    public static function get_author_statistics(
        int $user_id
    ): array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    COALESCE(SUM(CASE WHEN p.post_status = 'publish' THEN 1 ELSE 0 END), 0) AS published_count,
                    COALESCE(SUM(CASE WHEN p.post_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                    COALESCE(SUM(CASE WHEN p.post_status = 'draft' AND (workflow_meta.meta_value IS NULL OR workflow_meta.meta_value = %s) THEN 1 ELSE 0 END), 0) AS draft_count,
                    COALESCE(SUM(CASE WHEN p.post_status = 'draft' AND workflow_meta.meta_value = %s THEN 1 ELSE 0 END), 0) AS rejected_count,
                    COALESCE(SUM(CASE WHEN p.post_status = 'draft' AND workflow_meta.meta_value = %s THEN 1 ELSE 0 END), 0) AS revision_count,
                    COALESCE(SUM(CASE WHEN p.post_status = 'publish' THEN CAST(COALESCE(view_meta.meta_value, 0) AS UNSIGNED) ELSE 0 END), 0) AS total_views,
                    COALESCE(MAX(CASE WHEN status_meta.meta_key = 'nb_account_status' THEN status_meta.meta_value END), 'pending') AS account_status,
                    COALESCE(MAX(CASE WHEN earnings_meta.meta_key = 'nb_total_earnings' THEN CAST(earnings_meta.meta_value AS DECIMAL(12,2)) END), 0) AS total_earnings
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->posts} p
                    ON p.post_author = u.ID
                    AND p.post_type = 'post'
                LEFT JOIN {$wpdb->postmeta} workflow_meta
                    ON workflow_meta.post_id = p.ID
                    AND workflow_meta.meta_key = 'nb_workflow_status'
                LEFT JOIN {$wpdb->postmeta} view_meta
                    ON view_meta.post_id = p.ID
                    AND view_meta.meta_key = 'nb_valid_views'
                LEFT JOIN {$wpdb->usermeta} status_meta
                    ON status_meta.user_id = u.ID
                    AND status_meta.meta_key = 'nb_account_status'
                LEFT JOIN {$wpdb->usermeta} earnings_meta
                    ON earnings_meta.user_id = u.ID
                    AND earnings_meta.meta_key = 'nb_total_earnings'
                WHERE u.ID = %d
                GROUP BY u.ID
                ",
                WorkflowManager::STATUS_DRAFT,
                WorkflowManager::STATUS_REJECTED,
                WorkflowManager::STATUS_REVISION_REQUESTED,
                $user_id
            ),
            ARRAY_A
        );

        $row = is_array($row) ? $row : [];

        $published = (int) ($row['published_count'] ?? 0);
        $pending   = (int) ($row['pending_count'] ?? 0);
        $draft     = (int) ($row['draft_count'] ?? 0);
        $rejected  = (int) ($row['rejected_count'] ?? 0);
        $revision  = (int) ($row['revision_count'] ?? 0);

        return [
            'published_count'  => $published,
            'pending_count'    => $pending,
            'draft_count'      => $draft,
            'rejected_count'   => $rejected,
            'revision_count'   => $revision,
            'total_submissions'=> $published + $pending + $draft + $rejected + $revision,
            'total_views'      => (int) ($row['total_views'] ?? 0),
            'account_status'   => ucfirst((string) ($row['account_status'] ?? 'pending')),
            'total_earnings'   => round((float) ($row['total_earnings'] ?? 0), 2),
        ];
    }

    /**
     * Get pending editor queue articles with author data attached.
     */
    public static function get_editor_pending_articles(
        int $limit = 20,
        int $offset = 0
    ): array {
        global $wpdb;

        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    p.ID,
                    p.post_author,
                    p.post_title,
                    p.post_status,
                    p.post_date,
                    p.post_modified,
                    p.post_content,
                    u.display_name AS author_name,
                    u.user_email AS author_email,
                    COALESCE(changed_meta.meta_value, p.post_date) AS submitted_at
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->users} u
                    ON u.ID = p.post_author
                LEFT JOIN {$wpdb->postmeta} changed_meta
                    ON changed_meta.post_id = p.ID
                    AND changed_meta.meta_key = 'nb_workflow_changed_at'
                WHERE p.post_type = 'post'
                    AND p.post_status = 'pending'
                ORDER BY COALESCE(changed_meta.meta_value, p.post_date) ASC
                LIMIT %d OFFSET %d
                ",
                $limit,
                $offset
            )
        );
    }

    /**
     * Count unread notifications for a user.
     */
    public static function count_unread_notifications(
        int $user_id
    ): int {
        global $wpdb;

        $table = Database::table('notifications');

        if (! Database::exists('notifications')) {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d
                    AND is_read = 0
                ",
                $user_id
            )
        );
    }

    /**
     * Get earnings aggregates for a user.
     */
    public static function get_earnings_data(
        int $user_id
    ): array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    COALESCE(SUM(CAST(view_meta.meta_value AS UNSIGNED)), 0) AS total_views,
                    COALESCE(MAX(CASE WHEN earnings_meta.meta_key = 'nb_total_earnings' THEN CAST(earnings_meta.meta_value AS DECIMAL(12,2)) END), 0) AS total_earnings,
                    COALESCE(MAX(CASE WHEN paid_meta.meta_key = 'nb_paid_amount' THEN CAST(paid_meta.meta_value AS DECIMAL(12,2)) END), 0) AS paid_amount,
                    COALESCE(MAX(CASE WHEN unpaid_meta.meta_key = 'nb_unpaid_balance' THEN CAST(unpaid_meta.meta_value AS DECIMAL(12,2)) END), 0) AS unpaid_balance
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->posts} p
                    ON p.post_author = u.ID
                    AND p.post_type = 'post'
                    AND p.post_status = 'publish'
                LEFT JOIN {$wpdb->postmeta} view_meta
                    ON view_meta.post_id = p.ID
                    AND view_meta.meta_key = 'nb_valid_views'
                LEFT JOIN {$wpdb->usermeta} earnings_meta
                    ON earnings_meta.user_id = u.ID
                    AND earnings_meta.meta_key = 'nb_total_earnings'
                LEFT JOIN {$wpdb->usermeta} paid_meta
                    ON paid_meta.user_id = u.ID
                    AND paid_meta.meta_key = 'nb_paid_amount'
                LEFT JOIN {$wpdb->usermeta} unpaid_meta
                    ON unpaid_meta.user_id = u.ID
                    AND unpaid_meta.meta_key = 'nb_unpaid_balance'
                WHERE u.ID = %d
                GROUP BY u.ID
                ",
                $user_id
            ),
            ARRAY_A
        );

        $row = is_array($row) ? $row : [];

        return [
            'total_views'    => (int) ($row['total_views'] ?? 0),
            'total_earnings' => round((float) ($row['total_earnings'] ?? 0), 2),
            'paid_amount'    => round((float) ($row['paid_amount'] ?? 0), 2),
            'unpaid_balance' => round((float) ($row['unpaid_balance'] ?? 0), 2),
        ];
    }
}
