<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

defined('ABSPATH') || exit;

class CacheManager
{
    public const CACHE_DURATION = HOUR_IN_SECONDS;

    public static function get_author_stats(int $user_id): object
    {
        $cache_key = 'nb_accounts_author_stats_' . $user_id;
        $stats     = get_transient($cache_key);

        if ($stats === false) {
            $stats = QueryOptimizer::get_author_statistics($user_id);
            set_transient($cache_key, $stats, self::CACHE_DURATION);
        }

        return $stats instanceof \stdClass ? $stats : (object) $stats;
    }

    public static function get_unread_notifications(int $user_id): int
    {
        $cache_key = 'nb_accounts_unread_notifications_' . $user_id;
        $count     = get_transient($cache_key);

        if ($count === false) {
            $count = QueryOptimizer::count_unread_notifications($user_id);
            set_transient($cache_key, $count, self::CACHE_DURATION);
        }

        return (int) $count;
    }

    /**
     * @return \WP_User|false
     */
    public static function get_user_profile(int $user_id)
    {
        $cache_key = 'nb_accounts_user_profile_' . $user_id;
        $profile   = get_transient($cache_key);

        if ($profile === false) {
            $profile = get_userdata($user_id);
            set_transient($cache_key, $profile, self::CACHE_DURATION);
        }

        return $profile;
    }

    public static function invalidate_user_cache(int $user_id): void
    {
        if ($user_id <= 0) {
            return;
        }

        delete_transient('nb_accounts_author_stats_' . $user_id);
        delete_transient('nb_accounts_unread_notifications_' . $user_id);
        delete_transient('nb_accounts_user_profile_' . $user_id);
        delete_transient('nb_profile_data_' . $user_id);
        delete_transient('nb_profile_completion_' . $user_id);
        delete_transient('nb_payouts_statistics');

        update_option(
            'nb_dashboard_cache_version',
            (string) time(),
            false
        );
    }

    public static function invalidate_post_caches(int $post_id): void
    {
        $post = get_post($post_id);

        if (! $post instanceof \WP_Post) {
            return;
        }

        self::invalidate_user_cache((int) $post->post_author);
    }

    /**
     * @param mixed $meta_id
     * @param mixed $meta_value
     */
    public static function invalidate_user_cache_from_meta(
        $meta_id,
        int $user_id,
        string $meta_key,
        $meta_value
    ): void {
        unset($meta_id, $meta_key, $meta_value);

        self::invalidate_user_cache($user_id);
    }
}
