<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

use Newsblenda\Accounts\Admin\SettingsManager;
use Newsblenda\Accounts\Database\Database;

defined('ABSPATH') || exit;

class CronScheduler
{
    public const DAILY_EARNINGS_HOOK = 'nb_accounts_daily_event';
    public const PAYOUT_PROCESSING_HOOK = 'nb_accounts_payout_processing_event';

    public static function schedule_all(): void
    {
        if (! wp_next_scheduled(self::DAILY_EARNINGS_HOOK)) {
            wp_schedule_event(time(), 'daily', self::DAILY_EARNINGS_HOOK);
        }

        if (! wp_next_scheduled(self::PAYOUT_PROCESSING_HOOK)) {
            wp_schedule_event(time(), 'daily', self::PAYOUT_PROCESSING_HOOK);
        }
    }

    public static function unschedule_all(): void
    {
        wp_clear_scheduled_hook(self::DAILY_EARNINGS_HOOK);
        wp_clear_scheduled_hook(self::PAYOUT_PROCESSING_HOOK);
    }

    public static function calculate_daily_earnings(): void
    {
        global $wpdb;

        if (! (bool) SettingsManager::get('earnings', 'enable_earnings', 1)) {
            return;
        }

        $rate           = (float) SettingsManager::get('earnings', 'price_per_view', 0.001);
        $earnings_table = Database::table('earnings');
        $rows           = (array) $wpdb->get_results(
            "SELECT
                p.ID AS post_id,
                p.post_author AS user_id,
                CAST(COALESCE(current_views.meta_value, 0) AS UNSIGNED) AS current_views,
                CAST(COALESCE(last_sync.meta_value, 0) AS UNSIGNED) AS synced_views
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} current_views
                ON current_views.post_id = p.ID
                AND current_views.meta_key = 'nb_valid_views'
            LEFT JOIN {$wpdb->postmeta} last_sync
                ON last_sync.post_id = p.ID
                AND last_sync.meta_key = 'nb_last_earnings_views'
            WHERE p.post_type = 'post'
                AND p.post_status = 'publish'"
        );

        $touched_users = [];

        foreach ($rows as $row) {
            $current_views = (int) ($row->current_views ?? 0);
            $synced_views  = (int) ($row->synced_views ?? 0);
            $delta_views   = max(0, $current_views - $synced_views);

            if ($delta_views < 1) {
                continue;
            }

            $amount = round($delta_views * $rate, 2);

            if ($amount <= 0) {
                continue;
            }

            $wpdb->insert(
                $earnings_table,
                [
                    'user_id' => (int) $row->user_id,
                    'post_id' => (int) $row->post_id,
                    'views' => $delta_views,
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'calculated_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%f', '%s', '%s']
            );

            update_post_meta((int) $row->post_id, 'nb_last_earnings_views', $current_views);
            $touched_users[(int) $row->user_id] = true;
        }

        foreach (array_keys($touched_users) as $user_id) {
            self::refresh_user_earnings((int) $user_id);
        }
    }

    public static function process_pending_payouts(): void
    {
        global $wpdb;

        if (! (bool) SettingsManager::get('earnings', 'enable_earnings', 1)) {
            return;
        }

        $minimum        = (float) SettingsManager::get('earnings', 'min_payout_amount', 50);
        $auto_payout    = (bool) SettingsManager::get('earnings', 'auto_payout', 0);
        $earnings_table = Database::table('earnings');
        $payouts_table  = Database::table('payouts');
        $eligible_users = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    user_id,
                    ROUND(COALESCE(SUM(amount), 0), 2) AS earnings
                FROM {$earnings_table}
                WHERE status = 'unpaid'
                GROUP BY user_id
                HAVING SUM(amount) >= %f",
                $minimum
            )
        );

        foreach ($eligible_users as $user) {
            $user_id = (int) ($user->user_id ?? 0);
            $amount  = round((float) ($user->earnings ?? 0), 2);

            if ($user_id <= 0 || $amount <= 0) {
                continue;
            }

            $pending = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$payouts_table}
                    WHERE user_id = %d
                        AND status = 'pending'
                    ORDER BY created_at DESC
                    LIMIT 1",
                    $user_id
                )
            );

            if (! $auto_payout) {
                if ($pending instanceof \stdClass) {
                    $wpdb->update(
                        $payouts_table,
                        ['amount' => $amount],
                        ['id' => (int) $pending->id],
                        ['%f'],
                        ['%d']
                    );
                } else {
                    $wpdb->insert(
                        $payouts_table,
                        [
                            'user_id' => $user_id,
                            'amount' => $amount,
                            'payment_method' => (string) get_user_meta($user_id, 'nb_payment_method', true),
                            'reference' => '',
                            'status' => 'pending',
                            'created_at' => current_time('mysql'),
                        ],
                        ['%d', '%f', '%s', '%s', '%s', '%s']
                    );
                }

                update_user_meta($user_id, 'nb_payout_status', 'pending');
                CacheManager::invalidate_user_cache($user_id);
                continue;
            }

            if ($pending instanceof \stdClass) {
                $wpdb->update(
                    $payouts_table,
                    [
                        'amount' => $amount,
                        'status' => 'paid',
                        'paid_at' => current_time('mysql'),
                    ],
                    ['id' => (int) $pending->id],
                    ['%f', '%s', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $payouts_table,
                    [
                        'user_id' => $user_id,
                        'amount' => $amount,
                        'payment_method' => (string) get_user_meta($user_id, 'nb_payment_method', true),
                        'reference' => '',
                        'status' => 'paid',
                        'paid_at' => current_time('mysql'),
                        'created_at' => current_time('mysql'),
                    ],
                    ['%d', '%f', '%s', '%s', '%s', '%s', '%s']
                );
            }

            $wpdb->update(
                $earnings_table,
                ['status' => 'paid'],
                [
                    'user_id' => $user_id,
                    'status' => 'unpaid',
                ],
                ['%s'],
                ['%d', '%s']
            );

            $paid = (float) get_user_meta($user_id, 'nb_paid_amount', true);

            update_user_meta($user_id, 'nb_paid_amount', round($paid + $amount, 2));
            update_user_meta($user_id, 'nb_unpaid_balance', 0);
            update_user_meta($user_id, 'nb_last_payment_date', current_time('mysql'));
            update_user_meta($user_id, 'nb_payout_status', 'paid');

            do_action('nb_accounts_payout_recorded', $user_id, $amount);

            CacheManager::invalidate_user_cache($user_id);
        }
    }

    private static function refresh_user_earnings(int $user_id): void
    {
        global $wpdb;

        $earnings = QueryOptimizer::get_earnings_data($user_id);
        $views    = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0)
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                    AND pm.meta_key = 'nb_valid_views'
                WHERE p.post_type = 'post'
                    AND p.post_status = 'publish'
                    AND p.post_author = %d",
                $user_id
            )
        );

        update_user_meta($user_id, 'nb_total_earnings', round((float) ($earnings->total_earnings ?? 0), 2));
        update_user_meta($user_id, 'nb_total_views', $views);
        update_user_meta($user_id, 'nb_unpaid_balance', round((float) ($earnings->unpaid_balance ?? 0), 2));
        update_user_meta($user_id, 'nb_last_earnings_update', current_time('mysql'));

        $articles = QueryOptimizer::get_author_articles($user_id, 'publish', 1, 0);
        $top      = $articles[0]->post_title ?? '';

        update_user_meta($user_id, 'nb_top_article', (string) $top);
        CacheManager::invalidate_user_cache($user_id);
    }
}
