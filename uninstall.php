<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

/*
|--------------------------------------------------------------------------
| Plugin Tables
|--------------------------------------------------------------------------
*/

$tables = [
	$wpdb->prefix . 'nb_activity',
	$wpdb->prefix . 'nb_notifications',
	$wpdb->prefix . 'nb_email_verification_tokens',
	$wpdb->prefix . 'nb_password_reset_tokens',
	$wpdb->prefix . 'nb_article_revisions',
	$wpdb->prefix . 'nb_article_feedback',
	$wpdb->prefix . 'nb_workflow_log',
];

foreach ($tables as $table) {

	$wpdb->query(
		"DROP TABLE IF EXISTS {$table}"
	);

}

/*
|--------------------------------------------------------------------------
| Plugin Options
|--------------------------------------------------------------------------
*/

$options = [

	'newsblenda_accounts_settings',

	'nb_accounts_version',

	'nb_accounts_db_version',

	'nb_accounts_rate_limit',

];

foreach ($options as $option) {

	delete_option($option);

	delete_site_option($option);

}

/*
|--------------------------------------------------------------------------
| User Meta
|--------------------------------------------------------------------------
*/

$meta_keys = [

	'nb_email_verified',

	'nb_account_status',

	'nb_submission_restricted',

	'nb_phone',

	'nb_whatsapp',

	'nb_country',

	'nb_state',

	'nb_city',

	'nb_address',

	'nb_gender',

	'nb_dob',

	'nb_occupation',

	'nb_niche',

	'nb_categories',

	'nb_experience',

	'nb_payment_method',

	'nb_bank_name',

	'nb_account_name',

	'nb_account_number',

	'nb_paypal',

	'nb_opay',

	'nb_palmpay',

	'nb_moniepoint',

	'nb_facebook',

	'nb_twitter',

	'nb_instagram',

	'nb_linkedin',

	'nb_website',

	'nb_total_earnings',

	'nb_unpaid_balance',

	'nb_paid_amount',

	'nb_total_views',

	'nb_top_article',

	'nb_last_earnings_update',

	'nb_profile_updated',

	'nb_rejection_rate',

];

foreach ($meta_keys as $meta_key) {

	$wpdb->delete(

		$wpdb->usermeta,

		[
			'meta_key' => $meta_key,
		],

		[
			'%s',
		]

	);

}

/*
|--------------------------------------------------------------------------
| Remove Roles
|--------------------------------------------------------------------------
*/

$roles = [

	'nb_author_pending',

	'nb_author',

	'nb_author_restricted',

	'nb_editor',

];

foreach ($roles as $role) {

	remove_role($role);

}

/*
|--------------------------------------------------------------------------
| Remove Administrator Capabilities
|--------------------------------------------------------------------------
*/

$admin = get_role('administrator');

if ($admin) {

	$caps = [

		'nb_access_dashboard',

		'nb_submit_articles',

		'nb_edit_profile',

		'nb_view_earnings',

		'nb_receive_notifications',

		'nb_upload_media',

		'nb_review_articles',

		'nb_request_revision',

		'nb_approve_articles',

		'nb_reject_articles',

		'nb_manage_notifications',

		'nb_manage_authors',

		'nb_manage_accounts',

		'nb_manage_settings',

		'nb_manage_payouts',

	];

	foreach ($caps as $cap) {

		$admin->remove_cap($cap);

	}

}

/*
|--------------------------------------------------------------------------
| Clear Scheduled Events
|--------------------------------------------------------------------------
*/

$events = [

	'nb_accounts_daily_sync',

	'nb_accounts_cleanup',

];

foreach ($events as $event) {

	wp_clear_scheduled_hook($event);

}

/*
|--------------------------------------------------------------------------
| Allow Extensions Cleanup
|--------------------------------------------------------------------------
*/

do_action('nb_accounts_uninstall');

/*
|--------------------------------------------------------------------------
| Flush Rewrite Rules
|--------------------------------------------------------------------------
*/

flush_rewrite_rules();