<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Performance;

defined('ABSPATH') || exit;

/**
 * Optimized database query helpers.
 *
 * All methods use prepared statements and avoid N+1 patterns.
 */
class QueryOptimizer
{
    /**
     * Fetch paginated article listings for a given author.
     *
     * @param int $author_id  WP user ID.
     * @param int $per_page   Number of results per page.
     * @param int $paged      1-based page number.
     * @return object[]
     */
    public static function get_author_articles(
        int $author_id,
        int $per_page = 50,
        int $paged = 1
    ): array {
        global $wpdb;

        $offset = ($paged - 1) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_status, p.post_date,
                        p.post_type, pm.meta_value AS word_count
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm
                        ON pm.post_id = p.ID AND pm.meta_key = '_word_count'
                 WHERE p.post_author = %d
                   AND p.post_type   = 'post'
                   AND p.post_status != 'auto-draft'
                 ORDER BY p.post_date DESC
                 LIMIT %d OFFSET %d",
                $author_id,
                $per_page,
                $offset
            )
        );
    }

    /**
     * Aggregate statistics for an author.
     *
     * @param int $user_id
     * @return array{
     *     total: int,
     *     published: int,
     *     pending: int,
     *     rejected: int,
     *     drafts: int,
     *     total_views: int,
     *     total_earnings: float
     * }
     */
    public static function get_author_statistics(int $user_id): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_status, COUNT(*) AS cnt
                 FROM {$wpdb->posts}
                 WHERE post_author = %d
                   AND post_type   = 'post'
                   AND post_status != 'auto-draft'
                 GROUP BY post_status",
                $user_id
            )
        );

        $stats = [
            'total'          => 0,
            'published'      => 0,
            'pending'        => 0,
            'rejected'       => 0,
            'drafts'         => 0,
            'total_views'    => 0,
            'total_earnings' => (float) get_user_meta($user_id, 'total_earnings', true),
        ];

        foreach ($rows as $row) {
            $count         = (int) $row->cnt;
            $stats['total'] += $count;

            switch ($row->post_status) {
                case 'publish':
                    $stats['published'] = $count;
                    break;
                case 'pending':
                    $stats['pending'] = $count;
                    break;
                case 'nb-rejected':
                    $stats['rejected'] = $count;
                    break;
                case 'draft':
                    $stats['drafts'] = $count;
                    break;
            }
        }

        // Total view count via a single aggregate query.
        $views = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(pv.view_count), 0)
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->prefix}newsblenda_page_views pv ON pv.post_id = p.ID
                 WHERE p.post_author = %d
                   AND p.post_type   = 'post'
                   AND p.post_status = 'publish'",
                $user_id
            )
        );

        $stats['total_views'] = (int) $views;

        return $stats;
    }

    /**
     * Fetch the editor review queue (articles awaiting review), oldest first.
     *
     * @param int    $per_page
     * @param int    $paged
     * @param string $category Optional category slug.
     * @param int    $author_id Optional author filter.
     * @return object[]
     */
    public static function get_editor_review_queue(
        int $per_page = 50,
        int $paged = 1,
        string $category = '',
        int $author_id = 0
    ): array {
        global $wpdb;

        $offset = ($paged - 1) * $per_page;

        $where  = "WHERE p.post_status = 'pending' AND p.post_type = 'post'";
        $params = [];

        if ($author_id > 0) {
            $where   .= ' AND p.post_author = %d';
            $params[] = $author_id;
        }

        if ($category !== '') {
            $where   .= " AND t.slug = %s";
            $params[] = $category;
        }

        $join = '';
        if ($category !== '') {
            $join = "INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                     INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND tt.taxonomy = 'category'";
        }

        $params[] = $per_page;
        $params[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_date, p.post_author,
                    u.display_name AS author_name
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->users} u ON u.ID = p.post_author
             {$join}
             {$where}
             ORDER BY p.post_date ASC
             LIMIT %d OFFSET %d",
            ...$params
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results($sql);
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $user_id
     * @return int
     */
    public static function count_unread_notifications(int $user_id): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}newsblenda_notifications
                 WHERE user_id = %d AND is_read = 0",
                $user_id
            )
        );
    }

    /**
     * Calculate total and unpaid earnings for a user.
     *
     * @param int $user_id
     * @return array{total: float, unpaid: float}
     */
    public static function get_earnings_summary(int $user_id): array
    {
        $total  = (float) get_user_meta($user_id, 'total_earnings', true);
        $unpaid = (float) get_user_meta($user_id, 'unpaid_earnings', true);

        return [
            'total'  => $total,
            'unpaid' => $unpaid,
        ];
    }

    /**
     * Fetch daily earnings for a user over the last N days.
     *
     * @param int $user_id
     * @param int $days
     * @return object[]
     */
    public static function get_daily_earnings(int $user_id, int $days = 30): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(earned_at) AS day, SUM(amount) AS total
                 FROM {$wpdb->prefix}newsblenda_earnings
                 WHERE user_id = %d
                   AND earned_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                 GROUP BY DATE(earned_at)
                 ORDER BY day ASC",
                $user_id,
                $days
            )
        );
    }
}
