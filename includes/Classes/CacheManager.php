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
     * @return array{user:\WP_User|false,completion:int,payment:array<string,string>,socials:array<string,string>,updated:string}
     */
    public static function get_user_profile(int $user_id): array
    {
        $cache_key = 'nb_accounts_user_profile_' . $user_id;
        $profile   = get_transient($cache_key);

        if (is_array($profile)) {
            return $profile;
        }

        $profile = [
            'user' => get_userdata($user_id),
            'completion' => self::calculate_profile_completion($user_id),
            'payment' => [
                'method' => (string) get_user_meta($user_id, 'nb_payment_method', true),
                'bank_name' => (string) get_user_meta($user_id, 'nb_bank_name', true),
                'account_name' => (string) get_user_meta($user_id, 'nb_account_name', true),
                'account_number' => (string) get_user_meta($user_id, 'nb_account_number', true),
            ],
            'socials' => [
                'facebook' => (string) get_user_meta($user_id, 'nb_facebook', true),
                'twitter' => (string) get_user_meta($user_id, 'nb_twitter', true),
                'instagram' => (string) get_user_meta($user_id, 'nb_instagram', true),
                'linkedin' => (string) get_user_meta($user_id, 'nb_linkedin', true),
                'website' => (string) get_user_meta($user_id, 'nb_website', true),
            ],
            'updated' => (string) get_user_meta($user_id, 'nb_profile_updated', true),
        ];

        set_transient($cache_key, $profile, self::CACHE_DURATION);

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

    private static function calculate_profile_completion(int $user_id): int
    {
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
            'nb_whatsapp',
            'nb_gender',
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
