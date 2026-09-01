<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Classes;

defined('ABSPATH') || exit;

final class CronScheduler
{
    public const HOOK_DAILY_EARNINGS = 'newsblenda_calculate_daily_earnings';
    public const HOOK_DAILY_PAYOUTS  = 'newsblenda_process_pending_payouts';

    /**
     * Schedule all Phase 6 cron hooks.
     */
    public static function schedule_all(): void
    {
        foreach ([self::HOOK_DAILY_EARNINGS, self::HOOK_DAILY_PAYOUTS] as $hook) {
            if (! wp_next_scheduled($hook)) {
                wp_schedule_event(time(), 'daily', $hook);
            }
        }
    }

    /**
     * Remove all Phase 6 cron hooks.
     */
    public static function unschedule_all(): void
    {
        foreach ([self::HOOK_DAILY_EARNINGS, self::HOOK_DAILY_PAYOUTS] as $hook) {
            while ($timestamp = wp_next_scheduled($hook)) {
                wp_unschedule_event($timestamp, $hook);
            }

            wp_clear_scheduled_hook($hook);
        }
    }
}
