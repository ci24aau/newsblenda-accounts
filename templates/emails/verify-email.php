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

$verification_url = $verification_url ?? home_url('/verify-email/');

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
		'Verify Your Email - %s',
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
	background:#f3f5f7;
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
	background:#0b5cff;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.notice{
	background:#eef6ff;
	border-left:4px solid #0b5cff;
	padding:20px;
	margin:30px 0;
}

.button{
	display:inline-block;
	padding:15px 30px;
	background:#0b5cff;
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
	font-size:13px;
	word-break:break-all;
	font-family:monospace;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

<?php esc_html_e(

'Verify Your Email Address',

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

'Thank you for registering with Newsblenda. Before you can access all features of your account, you must verify your email address.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Why verify your email?',

'newsblenda-accounts'

); ?>

</strong>

<ul>

<li>

<?php esc_html_e(

'Protect your account from unauthorized access.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Receive editorial notifications.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Receive approval and payout updates.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Enable password recovery.',

'newsblenda-accounts'

); ?>

</li>

</ul>

</div>

<div style="text-align:center;margin:40px 0;">

<a

href="<?php echo esc_url($verification_url); ?>"

class="button"

>

<?php esc_html_e(

'Verify Email Address',

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

		<?php echo esc_html($verification_url); ?>

	</div>

</div>

<h2>

	<?php esc_html_e(
		'Important Information',
		'newsblenda-accounts'
	); ?>

</h2>

<ul>

	<li>

		<?php esc_html_e(
			'This verification link may expire for security reasons.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'If your link expires, simply request a new verification email from the login page.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Never share your verification link with anyone.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Newsblenda staff will never ask you for this verification link.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<h2>

	<?php esc_html_e(
		'Need Assistance?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'If you did not create this account, you can safely ignore this email. No further action is required.',
		'newsblenda-accounts'
	); ?>

</p>

<p>

	<?php esc_html_e(
		'If you require assistance, please contact the Newsblenda support team.',
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
			'Go to Login',
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
			'This email was sent automatically by the Newsblenda Accounts system. Please do not reply directly to this message.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the verification email.
	 *
	 * Developers can append additional branding,
	 * legal notices or custom content.
	 */
	do_action(
		'nb_accounts_email_verify_footer'
	);
	?>

</div>

</div>

</body>

</html>