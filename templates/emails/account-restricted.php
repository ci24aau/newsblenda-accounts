<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$site_name = wp_specialchars_decode(
	get_bloginfo('name'),
	ENT_QUOTES
);

$user_name = $user_name ?? __('Author', 'newsblenda-accounts');

$dashboard_url = home_url('/dashboard/');

$support_url = home_url('/contact/');

$rejection_rate = $rejection_rate ?? 0;

$limit = $limit ?? get_option(
	'nb_rejection_limit',
	60
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>">

<title>

<?php

printf(

	esc_html__(
		'Account Restricted - %s',
		'newsblenda-accounts'
	),

	$site_name

);

?>

</title>

<style>

body{
	margin:0;
	padding:40px;
	background:#f5f6f8;
	font-family:Arial,Helvetica,sans-serif;
	color:#333;
}

.wrapper{
	max-width:700px;
	margin:auto;
	background:#fff;
	border-radius:8px;
	overflow:hidden;
	box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.header{
	background:#dc2626;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.notice{
	background:#fff4f4;
	border-left:4px solid #dc2626;
	padding:20px;
	margin:30px 0;
}

.button{
	display:inline-block;
	padding:15px 30px;
	background:#dc2626;
	color:#fff !important;
	text-decoration:none;
	font-weight:bold;
	border-radius:5px;
}

.footer{
	padding:30px;
	background:#fafafa;
	text-align:center;
	font-size:13px;
	color:#777;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

<?php esc_html_e(

'Account Temporarily Restricted',

'newsblenda-accounts'

); ?>

</h1>

</div>

<div class="content">

<p>

<?php

printf(

esc_html__(

'Hello %s,',

'newsblenda-accounts'

),

esc_html($user_name)

);

?>

</p>

<p>

<?php esc_html_e(

'Your Newsblenda author account has been temporarily restricted from submitting new articles.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Reason for Restriction',

'newsblenda-accounts'

); ?>

</strong>

<p>

<?php

printf(

esc_html__(

'Your current article rejection rate is %1$s%%, which exceeds the allowed limit of %2$s%%.',

'newsblenda-accounts'

),

number_format((float) $rejection_rate, 2),

number_format((float) $limit, 2)

);

?>

</p>

</div>

<h2>

<?php esc_html_e(

'What Happens Next?',

'newsblenda-accounts'

); ?>

</h2>

<ul>

<li>

<?php esc_html_e(

'Your existing published articles remain online.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You can still access your dashboard and profile.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You cannot submit new articles while the restriction is active.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'An administrator may review and remove the restriction after evaluating your account.',

'newsblenda-accounts'

); ?>

</li>

</ul>

<h2>

	<?php esc_html_e(
		'How to Regain Submission Access',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Our editorial team encourages you to continue improving your writing skills. Restrictions are intended to maintain the quality of content published on Newsblenda and are not necessarily permanent.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Review the editorial feedback provided on your previous submissions.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Follow the Newsblenda Editorial Guidelines carefully.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Ensure future articles are original, accurate and well-researched.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Contact an editor if you believe your restriction was applied in error.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<p style="text-align:center;margin:40px 0;">

	<a
		href="<?php echo esc_url($dashboard_url); ?>"
		class="button"
	>

		<?php esc_html_e(
			'Open Dashboard',
			'newsblenda-accounts'
		); ?>

	</a>

</p>

<h2>

	<?php esc_html_e(
		'Need Assistance?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'If you have questions about this restriction or would like further clarification, please contact the Newsblenda editorial team.',
		'newsblenda-accounts'
	); ?>

</p>

<p style="text-align:center;">

	<a
		href="<?php echo esc_url($support_url); ?>"
		class="button"
		style="background:#444;"
	>

		<?php esc_html_e(
			'Contact Support',
			'newsblenda-accounts'
		); ?>

	</a>

</p>

</div>

<div class="footer">

	<p>

		&copy;
		<?php echo esc_html(date_i18n('Y')); ?>
		<?php echo esc_html($site_name); ?>

	</p>

	<p>

		<?php esc_html_e(
			'This notification was generated automatically by the Newsblenda Accounts system.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the account restricted email.
	 *
	 * Developers can append additional legal notices,
	 * support information or custom branding.
	 */
	do_action(
		'nb_accounts_email_account_restricted_footer'
	);
	?>

</div>

</div>

</body>

</html>