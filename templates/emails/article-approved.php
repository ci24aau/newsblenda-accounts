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

$article_title = $article_title ?? __('Your Article', 'newsblenda-accounts');

$article_url = $article_url ?? home_url('/');

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
		'Article Approved - %s',
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

'Article Approved',

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

'Congratulations! One of your articles has successfully passed editorial review and has now been published on Newsblenda.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Published Article',

'newsblenda-accounts'

); ?>

</strong>

<p>

<strong>

<?php echo esc_html($article_title); ?>

</strong>

</p>

</div>

<div style="text-align:center;margin:40px 0;">

<a

href="<?php echo esc_url($article_url); ?>"

class="button"

>

<?php esc_html_e(

'Read Your Article',

'newsblenda-accounts'

); ?>

</a>

</div>

<h2>

	<?php esc_html_e(
		'Keep Up the Great Work!',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Thank you for contributing quality journalism to Newsblenda. Every approved article helps strengthen our platform and provides valuable information to our readers.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Continue writing original, well-researched articles.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Monitor your article performance from your dashboard.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Keep your payment details up to date to avoid payout delays.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Follow the editorial guidelines for every future submission.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<div class="notice">

	<strong>

		<?php esc_html_e(
			'Earnings Reminder',
			'newsblenda-accounts'
		); ?>

	</strong>

	<p>

		<?php esc_html_e(
			'Eligible article views may contribute towards your earnings based on your site's configured payout rules. Visit your dashboard to monitor views, earnings and payment status.',
			'newsblenda-accounts'
		); ?>

	</p>

</div>

<p style="text-align:center;margin:40px 0;">

	<a
		href="<?php echo esc_url($dashboard_url); ?>"
		class="button"
		style="background:#0b5cff;"
	>

		<?php esc_html_e(
			'Open Dashboard',
			'newsblenda-accounts'
		); ?>

	</a>

</p>

<p>

	<?php esc_html_e(
		'We look forward to publishing more of your work. Thank you for being part of the Newsblenda author community.',
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
			'This email was sent automatically by the Newsblenda Accounts system.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the article approved email.
	 *
	 * Developers can append additional branding,
	 * analytics, legal notices or custom content.
	 */
	do_action(
		'nb_accounts_email_article_approved_footer',
		$article_title,
		$article_url
	);
	?>

</div>

</div>

</body>

</html>