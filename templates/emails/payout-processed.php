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

$amount = (float) ($amount ?? 0);

$payment_method = $payment_method ?? __('Not specified', 'newsblenda-accounts');

$transaction_reference = $transaction_reference ?? '';

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
		'Payout Processed - %s',
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

.summary{
	background:#f8f8f8;
	border:1px solid #ddd;
	border-radius:6px;
	padding:20px;
	margin-top:20px;
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

table{
	width:100%;
	border-collapse:collapse;
	margin-top:15px;
}

td{
	padding:10px 0;
	border-bottom:1px solid #eee;
}

td:first-child{
	font-weight:bold;
	width:40%;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

<?php esc_html_e(

'Payout Processed Successfully',

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

'We're pleased to let you know that your author payout has been successfully processed.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Payment Summary',

'newsblenda-accounts'

); ?>

</strong>

<div class="summary">

<table>

<tr>

<td>

<?php esc_html_e(

'Amount',

'newsblenda-accounts'

); ?>

</td>

<td>

<strong>

£<?php echo esc_html(number_format($amount, 2)); ?>

</strong>

</td>

</tr>

<tr>

<td>

<?php esc_html_e(

'Payment Method',

'newsblenda-accounts'

); ?>

</td>

<td>

<?php echo esc_html($payment_method); ?>

</td>

</tr>

<?php if (! empty($transaction_reference)) : ?>

<tr>

<td>

<?php esc_html_e(

'Transaction Reference',

'newsblenda-accounts'

); ?>

</td>

<td>

<?php echo esc_html($transaction_reference); ?>

</td>

</tr>

<?php endif; ?>

<tr>

<td>

<?php esc_html_e(

'Processed',

'newsblenda-accounts'

); ?>

</td>

<td>

<?php echo esc_html(current_time(get_option('date_format') . ' ' . get_option('time_format'))); ?>

</td>

</tr>

</table>

</div>

</div>

<h2>

	<?php esc_html_e(
		'What Happens Next?',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Depending on your selected payment method, it may take some time for the funds to appear in your account. Please keep this email for your records.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Check your payment account for the transferred funds.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Keep your payment information up to date to avoid future delays.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Continue publishing quality articles to increase your future earnings.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Visit your dashboard anytime to monitor your earnings and payout history.',
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
			'View Dashboard',
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
		'If you believe there is a problem with this payment or you have any questions about your earnings, please contact the Newsblenda administration team.',
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
			'This payment notification was generated automatically by the Newsblenda Accounts system.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the payout processed email.
	 *
	 * Developers can append payment gateway information,
	 * branding, legal notices or accounting integrations.
	 */
	do_action(
		'nb_accounts_email_payout_processed_footer',
		$amount,
		$payment_method,
		$transaction_reference
	);
	?>

</div>

</div>

</body>

</html>