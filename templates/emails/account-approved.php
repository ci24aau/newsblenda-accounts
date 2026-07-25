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

$login_url = home_url('/login/');

$dashboard_url = home_url('/dashboard/');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>">

<title>

<?php

printf(

	esc_html__(
		'Account Approved - %s',
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
	background:#16a34a;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.notice{
	background:#f0fff4;
	border-left:4px solid #16a34a;
	padding:20px;
	margin:30px 0;
}

.button{
	display:inline-block;
	padding:15px 30px;
	background:#16a34a;
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

'Congratulations!',

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

'Great news! Your Newsblenda author account has been reviewed and approved by our editorial team.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Your account is now fully active.',

'newsblenda-accounts'

); ?>

</strong>

<ul>

<li>

<?php esc_html_e(

'You can now log in to your account.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You can submit articles for editorial review.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You can access your author dashboard and earnings page.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You can update your profile and payment information at any time.',

'newsblenda-accounts'

); ?>

</li>

</ul>

</div>

<div style="text-align:center;margin:40px 0;">

<a

href="<?php echo esc_url($dashboard_url); ?>"

class="button"

>

<?php esc_html_e(

'Open Author Dashboard',

'newsblenda-accounts'

); ?>

</a>

</div>

<h2>

	<?php esc_html_e(
		'Editorial Reminders',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'As a Newsblenda author, every article you submit will be reviewed to ensure it meets our editorial standards before publication.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Write original, high-quality content.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Support your articles with reliable sources.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Include a featured image and SEO information.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Follow the Newsblenda editorial guidelines for every submission.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<p>

	<?php esc_html_e(
		'You can sign in at any time to begin writing and managing your articles.',
		'newsblenda-accounts'
	); ?>

</p>

<p style="text-align:center;">

	<a
		href="<?php echo esc_url($login_url); ?>"
		class="button"
		style="background:#0b5cff;"
	>

		<?php esc_html_e(
			'Sign In',
			'newsblenda-accounts'
		); ?>

	</a>

</p>

<h2>

	<?php esc_html_e(
		'Need Help?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'If you have any questions about your account, article submissions or editorial policies, please contact the Newsblenda editorial team.',
		'newsblenda-accounts'
	); ?>

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
			'Thank you for joining the Newsblenda author community.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the account approved email.
	 *
	 * Developers can append additional branding,
	 * legal notices or custom content.
	 */
	do_action(
		'nb_accounts_email_account_approved_footer'
	);
	?>

</div>

</div>

</body>

</html>