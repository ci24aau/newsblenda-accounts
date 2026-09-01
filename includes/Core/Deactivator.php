<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Core;

use Newsblenda\Accounts\Classes\CronScheduler;

defined('ABSPATH') || exit;

class Deactivator
{
    /**
     * Runs when the plugin is deactivated.
     */
    public static function deactivate(): void
    {
        self::clear_scheduled_events();

        self::clear_activation_flags();

        self::flush_cache();

        do_action(
            'nb_accounts_deactivated'
        );

        flush_rewrite_rules();
    }

    /**
     * Remove scheduled cron events.
     */
    private static function clear_scheduled_events(): void
    {
        if (
            class_exists(
                '\Newsblenda\Accounts\Classes\CronScheduler'
            )
        ) {

            \Newsblenda\Accounts\Classes\CronScheduler::unschedule_all();

        }

        $events = [

            'nb_accounts_daily',

            'nb_accounts_hourly',

            'nb_accounts_cleanup',

            'nb_accounts_email_queue',

            'nb_accounts_daily_event',

            'nb_accounts_hourly_event',

            'nb_accounts_five_minutes',

        ];

        foreach ($events as $event) {

            while ($timestamp = wp_next_scheduled($event)) {

                wp_unschedule_event(
                    $timestamp,
                    $event
                );

            }

            wp_clear_scheduled_hook($event);

        }

    }

    /**
     * Remove temporary activation flags.
     */
    private static function clear_activation_flags(): void
    {
        delete_option(
            'nb_accounts_activation_redirect'
        );
    }

    /**
     * Flush caches.
     */
    private static function flush_cache(): void
    {
        if (function_exists('wp_cache_flush')) {

            wp_cache_flush();

        }

        if (function_exists('wp_cache_delete')) {

            wp_cache_delete(
                'alloptions',
                'options'
            );

        }
    }

    /**
     * Check whether plugin is active.
     */
    public static function is_active(): bool
    {
        return is_plugin_active(
            plugin_basename(
                NB_ACCOUNTS_FILE
            )
        );
    }

    /**
     * Get installed plugin version.
     */
    public static function installed_version(): string
    {
        return (string) get_option(
            'nb_accounts_version',
            ''
        );
    }

    /**
     * Remove all scheduled events.
     */
    public static function unschedule_all(): void
    {
        self::clear_scheduled_events();
    }

    /**
     * Flush rewrite rules manually.
     */
    public static function flush_rules(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Execute deactivation hooks.
     */
    public static function run_hooks(): void
    {
        do_action(
            'nb_accounts_before_deactivate'
        );

        do_action(
            'nb_accounts_after_deactivate'
        );
    }
}