<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

use Newsblenda\Accounts\Database\Database;
use Newsblenda\Accounts\Workflow\WorkflowManager;

defined('ABSPATH') || exit;

class QueryOptimizer
{
    /**
     * Get an author's articles with view counts.
     *
     * @return array<int, object>
     */
    public static function get_author_articles(
        int $user_id,
        string $status = 'publish',
        int $limit = 10,
        int $offset = 0
    ): array {
        global $wpdb;

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    p.ID,
                    p.post_title,
                    p.post_date,
                    p.post_status,
                    COALESCE(MAX(CAST(pm.meta_value AS UNSIGNED)), 0) AS view_count
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                    AND pm.meta_key = 'nb_post_views'
                WHERE p.post_author = %d
                    AND p.post_type = 'post'
                    AND p.post_status = %s
                GROUP BY p.ID, p.post_title, p.post_date, p.post_status
                ORDER BY p.post_date DESC
                LIMIT %d OFFSET %d",
                $user_id,
                $status,
                $limit,
                $offset
            )
        );
    }

    /**
     * Get author dashboard statistics with a single query.
     */
    public static function get_author_statistics(int $user_id): object
    {
        global $wpdb;

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT CASE WHEN p.post_status = 'publish' THEN p.ID END) AS published_count,
                    COUNT(DISTINCT CASE WHEN p.post_status = 'pending' THEN p.ID END) AS pending_count,
                    COUNT(DISTINCT CASE
                        WHEN p.post_status = 'draft'
                            AND (ws.meta_value IS NULL OR ws.meta_value = %s)
                        THEN p.ID
                    END) AS draft_count,
                    COUNT(DISTINCT CASE
                        WHEN p.post_status = 'draft'
                            AND ws.meta_value = %s
                        THEN p.ID
                    END) AS rejected_count,
                    COUNT(DISTINCT CASE
                        WHEN p.post_status = 'draft'
                            AND ws.meta_value = %s
                        THEN p.ID
                    END) AS revision_count,
                    COALESCE(SUM(CAST(vp.meta_value AS UNSIGNED)), 0) AS total_views
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} ws
                    ON ws.post_id = p.ID
                    AND ws.meta_key = 'nb_workflow_status'
                LEFT JOIN {$wpdb->postmeta} vp
                    ON vp.post_id = p.ID
                    AND vp.meta_key = 'nb_post_views'
                WHERE p.post_author = %d
                    AND p.post_type = 'post'",
                WorkflowManager::STATUS_DRAFT,
                WorkflowManager::STATUS_REJECTED,
                WorkflowManager::STATUS_REVISION_REQUESTED,
                $user_id
            )
        );

        if ($stats instanceof \stdClass) {
            $stats->total_submissions = (int) ($stats->published_count ?? 0)
                + (int) ($stats->pending_count ?? 0)
                + (int) ($stats->draft_count ?? 0)
                + (int) ($stats->rejected_count ?? 0)
                + (int) ($stats->revision_count ?? 0);

            return $stats;
        }

        return (object) [
            'published_count' => 0,
            'pending_count' => 0,
            'draft_count' => 0,
            'rejected_count' => 0,
            'revision_count' => 0,
            'total_views' => 0,
            'total_submissions' => 0,
        ];
    }

    /**
     * Get the editor pending review queue.
     *
     * @param array<string, mixed> $filters
     * @return array<int, object>
     */
    public static function get_pending_articles(
        int $limit = 50,
        int $offset = 0,
        array $filters = []
    ): array {
        global $wpdb;

        $workflow_table = Database::table('workflow_history');
        $sql            = "SELECT
                p.ID,
                p.post_title,
                p.post_author,
                p.post_date,
                p.post_content,
                COUNT(DISTINCT wh.id) AS review_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$workflow_table} wh
                ON p.ID = wh.post_id
            WHERE p.post_type = 'post'
                AND p.post_status = 'pending'";
        $args           = [];

        if (! empty($filters['author'])) {
            $sql    .= ' AND p.post_author = %d';
            $args[] = (int) $filters['author'];
        }

        if (! empty($filters['search'])) {
            $sql    .= ' AND p.post_title LIKE %s';
            $args[] = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
        }

        $sql .= ' GROUP BY p.ID, p.post_title, p.post_author, p.post_date, p.post_content
            ORDER BY p.post_date ASC
            LIMIT %d OFFSET %d';

        $args[] = $limit;
        $args[] = $offset;

        return (array) $wpdb->get_results(
            $wpdb->prepare($sql, ...$args)
        );
    }

    /**
     * Count unread notifications for a user.
     */
    public static function count_unread_notifications(int $user_id): int
    {
        global $wpdb;

        $table = Database::table('notifications');

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d
                    AND is_read = 0",
                $user_id
            )
        );
    }

    /**
     * Get earnings data for a user.
     */
    public static function get_earnings_data(int $user_id): object
    {
        global $wpdb;

        $table = Database::table('earnings');
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COALESCE(SUM(amount), 0) AS total_earnings,
                    COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END), 0) AS unpaid_balance,
                    MAX(calculated_at) AS last_earning
                FROM {$table}
                WHERE user_id = %d",
                $user_id
            )
        );

        return $row instanceof \stdClass
            ? $row
            : (object) [
                'total_earnings' => 0,
                'unpaid_balance' => 0,
                'last_earning' => null,
            ];
    }
}
