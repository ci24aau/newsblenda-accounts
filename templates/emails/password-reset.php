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

$reset_url = $reset_url ?? home_url('/reset-password/');
$expiry_hours = isset($expiry_hours)
	? (int) $expiry_hours
	: (int) (DAY_IN_SECONDS / HOUR_IN_SECONDS);

$login_url = home_url('/login/');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>">

<title>

<?php

printf(

	esc_html__(
		'Reset Your Password - %s',
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
	background:#d63638;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.notice{
	background:#fff5f5;
	border-left:4px solid #d63638;
	padding:20px;
	margin:30px 0;
}

.button{
	display:inline-block;
	padding:15px 30px;
	background:#d63638;
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

.code{
	padding:15px;
	background:#f5f5f5;
	border-radius:4px;
	font-family:monospace;
	word-break:break-all;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

<?php esc_html_e(

'Password Reset Request',

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

'We received a request to reset the password for your Newsblenda account.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<p>

<?php esc_html_e(

'If you requested this password reset, click the button below to create a new password.',

'newsblenda-accounts'

); ?>

</p>

</div>

<div style="text-align:center;margin:40px 0;">

<a

href="<?php echo esc_url($reset_url); ?>"

class="button"

>

<?php esc_html_e(

'Reset Password',

'newsblenda-accounts'

); ?>

</a>

</div>

<div class="notice">

	<p>

		<?php esc_html_e(
			'If the button above does not work, copy and paste the following link into your browser:',
			'newsblenda-accounts'
		); ?>

	</p>

	<div class="code">

		<?php echo esc_html($reset_url); ?>

	</div>

</div>

<h2>

	<?php esc_html_e(
		'Security Information',
		'newsblenda-accounts'
	); ?>

</h2>

<ul>

	<li>

		<?php
		printf(
			esc_html__(
				'This password reset link is valid for %d hours.',
				'newsblenda-accounts'
			),
			$expiry_hours
		);
		?>

	</li>

	<li>

		<?php esc_html_e(
			'Once your password has been changed, this reset link can no longer be used.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Choose a strong password that is unique to your Newsblenda account.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Never share your password with anyone.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Newsblenda staff will never ask you for your password.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<h2>

	<?php esc_html_e(
		'Didn\'t request this?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged unless you use the reset link above.',
		'newsblenda-accounts'
	); ?>

</p>

<p>

	<?php esc_html_e(
		'If you believe someone is trying to access your account, we recommend changing your password immediately after logging in and contacting the site administrator.',
		'newsblenda-accounts'
	); ?>

</p>

<p style="text-align:center;">

	<a
		href="<?php echo esc_url($login_url); ?>"
		class="button"
		style="background:#444;"
	>

		<?php esc_html_e(
			'Return to Login',
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
			'This password reset email was generated automatically by the Newsblenda Accounts system. Please do not reply directly to this email.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the password reset email.
	 *
	 * Developers can append branding, legal notices,
	 * or additional security information.
	 */
	do_action(
		'nb_accounts_email_password_reset_footer'
	);
	?>

</div>

</div>

</body>

</html>