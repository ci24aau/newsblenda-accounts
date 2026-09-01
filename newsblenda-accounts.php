<?php
/**
 * Plugin Name: Newsblenda Accounts
 * Plugin URI: https://newsblenda.com
 * Description: Frontend accounts, authentication and editorial management for Newsblenda.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Law Blessing
 * Author URI: https://newsblenda.com
 * License: GPL-2.0-or-later
 * Text Domain: newsblenda-accounts
 * Domain Path: /languages
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (version_compare(PHP_VERSION, '8.0', '<')) {

	add_action(
		'admin_notices',
		static function () {

			echo '<div class="notice notice-error"><p>';

			esc_html_e(
				'Newsblenda Accounts requires PHP 8.0 or later.',
				'newsblenda-accounts'
			);

			echo '</p></div>';

		}
	);

	return;
}

global $wp_version;

if (version_compare($wp_version, '6.4', '<')) {

	add_action(
		'admin_notices',
		static function () {

			echo '<div class="notice notice-error"><p>';

			esc_html_e(
				'Newsblenda Accounts requires WordPress 6.4 or later.',
				'newsblenda-accounts'
			);

			echo '</p></div>';

		}
	);

	return;
}

/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/

define('NB_ACCOUNTS_VERSION', '1.0.0');
define('NB_ACCOUNTS_FILE', __FILE__);
define('NB_ACCOUNTS_PATH', plugin_dir_path(__FILE__));
define('NB_ACCOUNTS_URL', plugin_dir_url(__FILE__));
define('NB_ACCOUNTS_BASENAME', plugin_basename(__FILE__));

/*
|--------------------------------------------------------------------------
| Composer / PSR-4 Autoloader
|--------------------------------------------------------------------------
*/

$autoload = NB_ACCOUNTS_PATH . 'vendor/autoload.php';

if (file_exists($autoload)) {
	require_once $autoload;
} else {
require_once NB_ACCOUNTS_PATH . 'includes/Core/Loader.php';

\Newsblenda\Accounts\Core\Loader::register(
    NB_ACCOUNTS_PATH . 'includes'
);
}

foreach ([
    NB_ACCOUNTS_PATH . 'includes/classes/QueryOptimizer.php',
    NB_ACCOUNTS_PATH . 'includes/classes/CacheManager.php',
    NB_ACCOUNTS_PATH . 'includes/classes/AssetManager.php',
    NB_ACCOUNTS_PATH . 'includes/classes/CronScheduler.php',
    NB_ACCOUNTS_PATH . 'includes/migrations/Migration001AddIndexes.php',
    NB_ACCOUNTS_PATH . 'includes/migrations/Migrator.php',
] as $phase_six_file) {
    if (is_readable($phase_six_file)) {
        require_once $phase_six_file;
    }
}

/*
|--------------------------------------------------------------------------
| Plugin Activation
|--------------------------------------------------------------------------
*/

register_activation_hook(
	__FILE__,
	[
		\Newsblenda\Accounts\Core\Activator::class,
		'activate',
	]
);

/*
|--------------------------------------------------------------------------
| Plugin Deactivation
|--------------------------------------------------------------------------
*/

register_deactivation_hook(
	__FILE__,
	[
		\Newsblenda\Accounts\Core\Deactivator::class,
		'deactivate',
	]
);

if (! function_exists('newsblenda_invalidate_caches_on_post_save')) {
    function newsblenda_invalidate_caches_on_post_save(
        int $post_id
    ): void {
        \Newsblenda\Accounts\Classes\CacheManager::invalidate_post_caches($post_id);
    }
}

if (! function_exists('newsblenda_invalidate_caches_on_post_delete')) {
    function newsblenda_invalidate_caches_on_post_delete(
        int $post_id
    ): void {
        \Newsblenda\Accounts\Classes\CacheManager::invalidate_post_caches($post_id);
    }
}

if (! function_exists('newsblenda_invalidate_caches_on_user_update')) {
    function newsblenda_invalidate_caches_on_user_update(
        $meta_id_or_user_id,
        $user_id = null
    ): void {
        $resolved_user_id = $user_id !== null ? (int) $user_id : (int) $meta_id_or_user_id;
        \Newsblenda\Accounts\Classes\CacheManager::invalidate_user_cache($resolved_user_id);
    }
}

if (! function_exists('newsblenda_invalidate_workflow_caches')) {
    function newsblenda_invalidate_workflow_caches(
        int $post_id
    ): void {
        \Newsblenda\Accounts\Classes\CacheManager::invalidate_post_caches($post_id);
    }
}

if (! function_exists('newsblenda_calculate_daily_earnings')) {
    function newsblenda_calculate_daily_earnings(): void
    {
        global $wpdb;

        $rate          = (float) get_option('nb_rate_per_1000_views', 2);
        $capability_key = $wpdb->prefix . 'capabilities';

        $authors = (array) $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    u.ID AS user_id,
                    COALESCE(SUM(CAST(view_meta.meta_value AS UNSIGNED)), 0) AS total_views
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} capabilities_meta
                    ON capabilities_meta.user_id = u.ID
                    AND capabilities_meta.meta_key = %s
                    AND (
                        capabilities_meta.meta_value LIKE %s
                        OR capabilities_meta.meta_value LIKE %s
                    )
                LEFT JOIN {$wpdb->posts} p
                    ON p.post_author = u.ID
                    AND p.post_type = 'post'
                    AND p.post_status = 'publish'
                LEFT JOIN {$wpdb->postmeta} view_meta
                    ON view_meta.post_id = p.ID
                    AND view_meta.meta_key = 'nb_valid_views'
                GROUP BY u.ID
                ",
                $capability_key,
                '%\"nb_author\"%',
                '%\"nb_author_restricted\"%'
            )
        );

        foreach ($authors as $author) {
            $user_id       = (int) $author->user_id;
            $total_views   = (int) $author->total_views;
            $total_earnings = round(($total_views / 1000) * $rate, 2);
            $paid_amount   = (float) get_user_meta($user_id, 'nb_paid_amount', true);

            update_user_meta($user_id, 'nb_total_views', $total_views);
            update_user_meta($user_id, 'nb_total_earnings', $total_earnings);
            update_user_meta($user_id, 'nb_unpaid_balance', max(0, round($total_earnings - $paid_amount, 2)));
            update_user_meta($user_id, 'nb_last_earnings_update', current_time('mysql'));

            \Newsblenda\Accounts\Classes\CacheManager::invalidate_user_cache($user_id);
        }
    }
}

if (! function_exists('newsblenda_process_pending_payouts')) {
    function newsblenda_process_pending_payouts(): void
    {
        global $wpdb;

        $minimum        = (float) get_option('nb_minimum_payout', 10);
        $capability_key = $wpdb->prefix . 'capabilities';
        $payouts_table  = \Newsblenda\Accounts\Database\Database::table('payouts');

        $eligible_authors = (array) $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    u.ID AS user_id,
                    COALESCE(MAX(CAST(unpaid_meta.meta_value AS DECIMAL(12,2))), 0) AS unpaid_balance,
                    COALESCE(MAX(method_meta.meta_value), '') AS payment_method
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} capabilities_meta
                    ON capabilities_meta.user_id = u.ID
                    AND capabilities_meta.meta_key = %s
                    AND (
                        capabilities_meta.meta_value LIKE %s
                        OR capabilities_meta.meta_value LIKE %s
                    )
                LEFT JOIN {$wpdb->usermeta} unpaid_meta
                    ON unpaid_meta.user_id = u.ID
                    AND unpaid_meta.meta_key = 'nb_unpaid_balance'
                LEFT JOIN {$wpdb->usermeta} method_meta
                    ON method_meta.user_id = u.ID
                    AND method_meta.meta_key = 'nb_payment_method'
                GROUP BY u.ID
                HAVING unpaid_balance >= %f
                ORDER BY unpaid_balance DESC
                LIMIT 100
                ",
                $capability_key,
                '%\"nb_author\"%',
                '%\"nb_author_restricted\"%',
                $minimum
            )
        );

        foreach ($eligible_authors as $author) {
            $user_id     = (int) $author->user_id;
            $amount      = round((float) $author->unpaid_balance, 2);
            $paid_amount = (float) get_user_meta($user_id, 'nb_paid_amount', true);

            if ($amount <= 0) {
                continue;
            }

            $wpdb->insert(
                $payouts_table,
                [
                    'user_id'        => $user_id,
                    'amount'         => $amount,
                    'payment_method' => sanitize_text_field((string) $author->payment_method),
                    'reference'      => 'cron-' . $user_id . '-' . gmdate('YmdHis'),
                    'status'         => 'paid',
                    'paid_at'        => current_time('mysql'),
                    'created_at'     => current_time('mysql'),
                ],
                [
                    '%d',
                    '%f',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

            update_user_meta($user_id, 'nb_paid_amount', round($paid_amount + $amount, 2));
            update_user_meta($user_id, 'nb_unpaid_balance', 0);
            update_user_meta($user_id, 'nb_last_payment_date', current_time('mysql'));

            do_action('nb_accounts_payout_recorded', $user_id, $amount);
            \Newsblenda\Accounts\Classes\CacheManager::invalidate_user_cache($user_id);
        }
    }
}

add_action('save_post', 'newsblenda_invalidate_caches_on_post_save', 20, 3);
add_action('delete_post', 'newsblenda_invalidate_caches_on_post_delete', 20);
add_action('update_user_meta', 'newsblenda_invalidate_caches_on_user_update', 20, 4);
add_action('updated_user_meta', 'newsblenda_invalidate_caches_on_user_update', 20, 4);
add_action('added_user_meta', 'newsblenda_invalidate_caches_on_user_update', 20, 4);
add_action('deleted_user_meta', 'newsblenda_invalidate_caches_on_user_update', 20, 4);
add_action('newsblenda_workflow_status_changed', 'newsblenda_invalidate_workflow_caches', 20, 4);
add_action(\Newsblenda\Accounts\Classes\CronScheduler::HOOK_DAILY_EARNINGS, 'newsblenda_calculate_daily_earnings');
add_action(\Newsblenda\Accounts\Classes\CronScheduler::HOOK_DAILY_PAYOUTS, 'newsblenda_process_pending_payouts');

/*
|--------------------------------------------------------------------------
| Bootstrap Plugin
|--------------------------------------------------------------------------
*/

add_action(
	'plugins_loaded',
	static function () {

		do_action('nb_accounts_before_load');

		if (! class_exists('Newsblenda\\Accounts\\Core\\Plugin')) {
			return;
		}

		Newsblenda\Accounts\Core\Plugin::instance()->run();

		do_action('nb_accounts_loaded');

	},
	20
);