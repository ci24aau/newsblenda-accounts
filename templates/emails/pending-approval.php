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

$support_url = home_url('/contact/');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>">

<title>

<?php

printf(

	esc_html__(
		'Account Pending Approval - %s',
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
	background:#f59e0b;
	color:#fff;
	padding:40px;
	text-align:center;
}

.content{
	padding:40px;
	line-height:1.8;
}

.notice{
	background:#fff8e8;
	border-left:4px solid #f59e0b;
	padding:20px;
	margin:30px 0;
}

.button{
	display:inline-block;
	padding:15px 30px;
	background:#f59e0b;
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

'Registration Received',

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

'Thank you for registering as a Newsblenda author. Your account has been created successfully and is currently awaiting approval from our editorial team.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Current Account Status',

'newsblenda-accounts'

); ?>

</strong>

<ul>

<li>

<?php esc_html_e(

'Your registration has been received successfully.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Your account is currently pending administrator approval.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'You will receive another email once your account has been approved.',

'newsblenda-accounts'

); ?>

</li>

<li>

<?php esc_html_e(

'Until approval, article submission is unavailable.',

'newsblenda-accounts'

); ?>

</li>

</ul>

</div>

<h2>

<?php esc_html_e(

'What Happens Next?',

'newsblenda-accounts'

); ?>

</h2>

<p>

<?php esc_html_e(

'Our editorial team reviews every author application to help maintain the quality of Newsblenda content. This review normally takes a short period, although it may take longer during busy periods.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

	<h3>

		<?php esc_html_e(
			'Approval Timeline',
			'newsblenda-accounts'
		); ?>

	</h3>

	<p>

		<?php esc_html_e(
			'Most author applications are reviewed within 1–3 business days. You will receive an email notification as soon as a decision has been made.',
			'newsblenda-accounts'
		); ?>

	</p>

</div>

<h2>

	<?php esc_html_e(
		'While You Wait',
		'newsblenda-accounts'
	); ?>

</h2>

<ul>

	<li>

		<?php esc_html_e(
			'Complete your profile information.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Prepare article drafts offline.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Read the Newsblenda editorial guidelines.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Make sure your payment information is accurate after approval.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<p>

	<?php esc_html_e(
		'Once your account has been approved, you will be able to log in, access your dashboard, submit articles for review, monitor earnings and manage your profile.',
		'newsblenda-accounts'
	); ?>

</p>

<p style="text-align:center;margin:40px 0;">

	<a
		href="<?php echo esc_url($login_url); ?>"
		class="button"
	>

		<?php esc_html_e(
			'Visit Login Page',
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
		'If you have questions about your application or believe your approval is taking longer than expected, please contact the Newsblenda editorial team.',
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
			'This email was generated automatically by the Newsblenda Accounts system.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the pending approval email.
	 *
	 * Developers can append additional branding,
	 * legal notices or support information.
	 */
	do_action(
		'nb_accounts_email_pending_approval_footer'
	);
	?>

</div>

</div>

</body>

</html>