<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

defined('ABSPATH') || exit;

final class CacheManager
{
    private const TTL = HOUR_IN_SECONDS;

    /**
     * Cache author statistics for one hour.
     */
    public static function get_author_stats(
        int $user_id
    ): array {
        $cache_key = 'nb_author_stats_' . $user_id;
        $cached    = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $stats = QueryOptimizer::get_author_statistics($user_id);

        set_transient($cache_key, $stats, self::TTL);

        return $stats;
    }

    /**
     * Cache unread notification count for one hour.
     */
    public static function get_unread_notifications(
        int $user_id
    ): int {
        $cache_key = 'nb_unread_notifications_' . $user_id;
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return (int) $cached;
        }

        $count = QueryOptimizer::count_unread_notifications($user_id);

        set_transient($cache_key, $count, self::TTL);

        return $count;
    }

    /**
     * Cache a normalized user profile payload for one hour.
     */
    public static function get_user_profile(
        int $user_id
    ): array {
        $cache_key = 'nb_user_profile_' . $user_id;
        $cached    = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $profile = [
            'user'       => get_userdata($user_id),
            'completion' => self::profile_completion($user_id),
            'payment'    => [
                'method'         => (string) get_user_meta($user_id, 'nb_payment_method', true),
                'bank_name'      => (string) get_user_meta($user_id, 'nb_bank_name', true),
                'account_name'   => (string) get_user_meta($user_id, 'nb_account_name', true),
                'account_number' => (string) get_user_meta($user_id, 'nb_account_number', true),
            ],
            'socials'    => [
                'facebook'  => (string) get_user_meta($user_id, 'nb_facebook', true),
                'twitter'   => (string) get_user_meta($user_id, 'nb_twitter', true),
                'instagram' => (string) get_user_meta($user_id, 'nb_instagram', true),
                'linkedin'  => (string) get_user_meta($user_id, 'nb_linkedin', true),
                'website'   => (string) get_user_meta($user_id, 'nb_website', true),
            ],
            'updated'    => (string) get_user_meta($user_id, 'nb_profile_updated', true),
        ];

        set_transient($cache_key, $profile, self::TTL);

        return $profile;
    }

    /**
     * Clear all user-related caches.
     */
    public static function invalidate_user_cache(
        int $user_id
    ): void {
        if ($user_id <= 0) {
            return;
        }

        delete_transient('nb_author_stats_' . $user_id);
        delete_transient('nb_unread_notifications_' . $user_id);
        delete_transient('nb_user_profile_' . $user_id);
        delete_transient('nb_profile_data_' . $user_id);
        delete_transient('nb_profile_completion_' . $user_id);
        delete_transient('nb_dashboard_author_' . $user_id);

        $cache_version = (string) get_option('nb_dashboard_cache_version', '1');
        delete_transient('nb_dashboard_author_' . $user_id . '_' . md5($cache_version));
        delete_transient('nb_earnings_summary_' . $user_id . '_' . md5($cache_version));

        update_option('nb_dashboard_cache_version', (string) microtime(true), false);
    }

    /**
     * Clear caches related to a post and its author.
     */
    public static function invalidate_post_caches(
        int $post_id
    ): void {
        if ($post_id <= 0) {
            return;
        }

        $post = get_post($post_id);

        if ($post instanceof \WP_Post && (int) $post->post_author > 0) {
            self::invalidate_user_cache((int) $post->post_author);
            return;
        }

        update_option('nb_dashboard_cache_version', (string) microtime(true), false);
    }

    /**
     * Calculate profile completion without repeatedly rebuilding callers.
     */
    private static function profile_completion(
        int $user_id
    ): int {
        $fields = [
            'first_name',
            'last_name',
            'description',
            'nb_phone',
            'nb_country',
            'nb_state',
            'nb_city',
            'nb_niche',
            'nb_payment_method',
        ];

        $completed = 0;

        foreach ($fields as $field) {
            if (! empty(get_user_meta($user_id, $field, true))) {
                $completed++;
            }
        }

        return (int) round(($completed / count($fields)) * 100);
    }
}
