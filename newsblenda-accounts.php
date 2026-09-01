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

add_action(
'init',
[\Newsblenda\Accounts\Classes\AssetManager::class, 'register_assets']
);

add_action(
'wp_enqueue_scripts',
[\Newsblenda\Accounts\Classes\AssetManager::class, 'enqueue_frontend_assets']
);

add_action(
'admin_enqueue_scripts',
[\Newsblenda\Accounts\Classes\AssetManager::class, 'enqueue_admin_assets']
);

add_action(
'save_post',
[\Newsblenda\Accounts\Classes\CacheManager::class, 'invalidate_post_caches']
);

add_action(
'delete_post',
[\Newsblenda\Accounts\Classes\CacheManager::class, 'invalidate_post_caches']
);

add_action(
'added_user_meta',
[\Newsblenda\Accounts\Classes\CacheManager::class, 'invalidate_user_cache_from_meta'],
10,
4
);

add_action(
'updated_user_meta',
[\Newsblenda\Accounts\Classes\CacheManager::class, 'invalidate_user_cache_from_meta'],
10,
4
);

add_action(
'deleted_user_meta',
[\Newsblenda\Accounts\Classes\CacheManager::class, 'invalidate_user_cache_from_meta'],
10,
4
);

add_action(
\Newsblenda\Accounts\Classes\CronScheduler::DAILY_EARNINGS_HOOK,
[\Newsblenda\Accounts\Classes\CronScheduler::class, 'calculate_daily_earnings']
);

add_action(
\Newsblenda\Accounts\Classes\CronScheduler::PAYOUT_PROCESSING_HOOK,
[\Newsblenda\Accounts\Classes\CronScheduler::class, 'process_pending_payouts']
);

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