<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Performance;

defined('ABSPATH') || exit;

/**
 * Registers cache-invalidation hooks and cron job handlers.
 *
 * Instantiated once by Plugin::load_dependencies().
 */
class PerformanceHooks
{
    public function __construct()
    {
        // Cache invalidation.
        add_action('save_post',        [$this, 'on_save_post']);
        add_action('delete_post',      [$this, 'on_delete_post']);
        add_action('update_user_meta', [$this, 'on_update_user_meta'], 10, 4);

        // Cron job handlers.
        add_action(
            CronScheduler::DAILY_EARNINGS_HOOK,
            [$this, 'calculate_daily_earnings']
        );
        add_action(
            CronScheduler::PAYOUT_PROCESSING_HOOK,
            [$this, 'process_pending_payouts']
        );
    }

    /**
     * Invalidate caches when a post is saved.
     *
     * @param int $post_id
     */
    public function on_save_post(int $post_id): void
    {
        CacheManager::invalidate_post_caches($post_id);
    }

    /**
     * Invalidate caches when a post is deleted.
     *
     * @param int $post_id
     */
    public function on_delete_post(int $post_id): void
    {
        CacheManager::invalidate_post_caches($post_id);
    }

    /**
     * Invalidate user caches when user meta is updated.
     *
     * @param int    $meta_id
     * @param int    $user_id
     * @param string $meta_key
     * @param mixed  $meta_value
     */
    public function on_update_user_meta(
        int $meta_id,
        int $user_id,
        string $meta_key,
        $meta_value
    ): void {
        $watched_keys = [
            'total_earnings',
            'unpaid_earnings',
            'nb_accounts_profile',
        ];

        if (in_array($meta_key, $watched_keys, true)) {
            CacheManager::invalidate_user_cache($user_id);
        }
    }

    /**
     * Cron handler: calculate and persist daily earnings for all authors.
     */
    public function calculate_daily_earnings(): void
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT p.post_author, COUNT(DISTINCT pv.id) AS views
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->prefix}newsblenda_page_views pv ON pv.post_id = p.ID
             WHERE p.post_status = 'publish'
               AND p.post_type   = 'post'
               AND DATE(pv.created_at) = CURDATE()
             GROUP BY p.post_author"
        );

        if (empty($results)) {
            return;
        }

        $rate = (float) get_option('nb_accounts_rate_per_view', 0.01);

        foreach ($results as $row) {
            $daily_earnings = (int) $row->views * $rate;
            $user_id        = (int) $row->post_author;
            $total          = (float) get_user_meta($user_id, 'total_earnings', true);

            update_user_meta($user_id, 'total_earnings', $total + $daily_earnings);
            CacheManager::invalidate_user_cache($user_id);
        }
    }

    /**
     * Cron handler: trigger payouts for users who have met the minimum threshold.
     */
    public function process_pending_payouts(): void
    {
        global $wpdb;

        $min_payout = (float) get_option('nb_accounts_min_payout', 50.0);

        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, meta_value AS earnings
                 FROM {$wpdb->usermeta}
                 WHERE meta_key   = 'unpaid_earnings'
                   AND meta_value >= %f",
                $min_payout
            )
        );

        if (empty($users)) {
            return;
        }

        foreach ($users as $user) {
            /**
             * Fires when a user's unpaid earnings meet the payout threshold.
             *
             * @param int   $user_id  User ID.
             * @param float $earnings Unpaid earnings amount.
             */
            do_action(
                'nb_accounts_process_payout',
                (int) $user->user_id,
                (float) $user->earnings
            );
        }
    }
}
