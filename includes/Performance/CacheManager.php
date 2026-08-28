<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Performance;

defined('ABSPATH') || exit;

/**
 * Transient-based caching helpers with automatic invalidation.
 */
class CacheManager
{
    /**
     * Default cache lifetime in seconds (1 hour).
     */
    const CACHE_DURATION = 3600;

    /**
     * Get cached author statistics, computing on miss.
     *
     * @param int $user_id
     * @return array
     */
    public static function get_author_stats(int $user_id): array
    {
        $cache_key = 'nb_author_stats_' . $user_id;
        $stats     = get_transient($cache_key);

        if (false === $stats) {
            $stats = QueryOptimizer::get_author_statistics($user_id);
            set_transient($cache_key, $stats, self::CACHE_DURATION);
        }

        return (array) $stats;
    }

    /**
     * Get cached unread-notification count, computing on miss.
     *
     * @param int $user_id
     * @return int
     */
    public static function get_unread_notifications(int $user_id): int
    {
        $cache_key = 'nb_unread_notifs_' . $user_id;
        $count     = get_transient($cache_key);

        if (false === $count) {
            $count = QueryOptimizer::count_unread_notifications($user_id);
            set_transient($cache_key, $count, self::CACHE_DURATION);
        }

        return (int) $count;
    }

    /**
     * Invalidate all cached data for a specific user.
     *
     * @param int $user_id
     */
    public static function invalidate_user_cache(int $user_id): void
    {
        delete_transient('nb_author_stats_' . $user_id);
        delete_transient('nb_unread_notifs_' . $user_id);
        delete_transient('nb_user_profile_' . $user_id);
    }

    /**
     * Invalidate caches related to a post (including the author's caches).
     *
     * @param int $post_id
     */
    public static function invalidate_post_caches(int $post_id): void
    {
        $post = get_post($post_id);

        if ($post instanceof \WP_Post) {
            self::invalidate_user_cache((int) $post->post_author);
        }
    }
}
