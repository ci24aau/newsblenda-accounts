<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Performance;

defined('ABSPATH') || exit;

/**
 * WordPress cron schedule management.
 */
class CronScheduler
{
    const DAILY_EARNINGS_HOOK   = 'nb_accounts_daily_earnings';
    const PAYOUT_PROCESSING_HOOK = 'nb_accounts_process_payouts';

    /**
     * Schedule all plugin cron events if they are not already scheduled.
     */
    public static function schedule_all(): void
    {
        if (! wp_next_scheduled(self::DAILY_EARNINGS_HOOK)) {
            wp_schedule_event(time(), 'daily', self::DAILY_EARNINGS_HOOK);
        }

        if (! wp_next_scheduled(self::PAYOUT_PROCESSING_HOOK)) {
            wp_schedule_event(time(), 'daily', self::PAYOUT_PROCESSING_HOOK);
        }
    }

    /**
     * Remove all plugin cron events.
     */
    public static function unschedule_all(): void
    {
        wp_unschedule_hook(self::DAILY_EARNINGS_HOOK);
        wp_unschedule_hook(self::PAYOUT_PROCESSING_HOOK);
    }
}
