<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$site_name = wp_specialchars_decode(
	get_bloginfo('name'),
	ENT_QUOTES
);

$site_url = home_url('/');

$login_url = home_url('/login/');

$dashboard_url = home_url('/dashboard/');

$user_name = $user_name ?? __('Author', 'newsblenda-accounts');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>">

<title>

<?php
printf(
	esc_html__(
		'Welcome to %s',
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
	margin:0 auto;
	background:#fff;
	border-radius:8px;
	overflow:hidden;
	box-shadow:0 2px 8px rgba(0,0,0,.08);
}

.header{
	background:#0b5cff;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.button{
	display:inline-block;
	padding:14px 28px;
	background:#0b5cff;
	color:#fff !important;
	text-decoration:none;
	border-radius:5px;
	font-weight:bold;
}

.footer{
	padding:30px;
	font-size:13px;
	color:#666;
	text-align:center;
	background:#fafafa;
}

.notice{
	padding:18px;
	background:#f0f7ff;
	border-left:4px solid #0b5cff;
	margin:25px 0;
}

ul{
	line-height:2;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

<?php

printf(

esc_html__(

'Welcome to %s',

'newsblenda-accounts'

),

esc_html($site_name)

);

?>

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

'Thank you for creating a Newsblenda author account. Your registration was successful and we are excited to have you join our growing editorial community.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Next Steps',

'newsblenda-accounts'

); ?>

</strong>

<ul>

<li>

<?php esc_html_e(

'Verify your email address.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Complete your author profile.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Wait for administrator approval if required.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Start submitting quality articles.',

'newsblenda-accounts'

); ?>

</li>

</ul>

</div>

<div style="text-align:center;margin:40px 0;">

	<a
		href="<?php echo esc_url($login_url); ?>"
		class="button"
	>

		<?php esc_html_e(
			'Sign In',
			'newsblenda-accounts'
		); ?>

	</a>

	&nbsp;

	<a
		href="<?php echo esc_url($dashboard_url); ?>"
		class="button"
		style="background:#28a745;"
	>

		<?php esc_html_e(
			'Author Dashboard',
			'newsblenda-accounts'
		); ?>

	</a>

</div>

<h2>

	<?php esc_html_e(
		'Editorial Standards',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Newsblenda values high-quality, accurate and original journalism. Before submitting articles, please ensure they comply with our editorial standards.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Write original, well-researched articles.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Include reliable references and sources.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Provide a featured image for every submission.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Follow SEO and formatting guidelines.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Respect copyright and intellectual property rights.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<h2>

	<?php esc_html_e(
		'Need Help?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'If you have questions about your account, article submissions or editorial policies, please contact the Newsblenda editorial team.',
		'newsblenda-accounts'
	); ?>

</p>

<p style="text-align:center;">

	<a
		href="<?php echo esc_url($site_url); ?>"
		class="button"
		style="background:#444;"
	>

		<?php esc_html_e(
			'Visit Newsblenda',
			'newsblenda-accounts'
		); ?>

	</a>

</p>

</div>

<div class="footer">

<p>

&copy; <?php echo esc_html(date_i18n('Y')); ?>

<?php echo esc_html($site_name); ?>

</p>

<p>

<?php esc_html_e(

'This email was sent automatically. Please do not reply directly to this message.',

'newsblenda-accounts'

); ?>

</p>

<?php
/**
 * Fires at the bottom of the welcome email.
 *
 * Developers can append additional content,
 * legal notices or branding.
 */
do_action(
	'nb_accounts_email_welcome_footer'
);
?>

</div>

</div>

</body>
</html>