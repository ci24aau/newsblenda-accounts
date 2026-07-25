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

$revision_notes = $revision_notes ?? '';

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
		'Revision Requested - %s',
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

.notes{
	background:#f7f7f7;
	border:1px solid #ddd;
	border-radius:6px;
	padding:20px;
	margin-top:20px;
	white-space:pre-wrap;
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

'Revision Requested',

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

'An editor has reviewed your submission and would like you to make a few changes before it can be published.',

'newsblenda-accounts'

); ?>

</p>

<div class="notice">

<strong>

<?php esc_html_e(

'Article',

'newsblenda-accounts'

); ?>

</strong>

<p>

<strong>

<?php echo esc_html($article_title); ?>

</strong>

</p>

</div>

<?php if (! empty($revision_notes)) : ?>

<h2>

<?php esc_html_e(

'Revision Notes',

'newsblenda-accounts'

); ?>

</h2>

<div class="notes">

<?php echo nl2br(esc_html($revision_notes)); ?>

</div>

<?php endif; ?>

<h2>

	<?php esc_html_e(
		'Revision Checklist',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Please review the editor comments carefully and update your article before submitting it again.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Address every revision requested by the editor.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Verify all facts, quotations and statistics.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Ensure the article meets the required word count and editorial standards.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Review grammar, spelling, formatting and SEO details.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Confirm that all required sources and internal links have been included.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<div class="notice">

	<strong>

		<?php esc_html_e(
			'Good News',
			'newsblenda-accounts'
		); ?>

	</strong>

	<p>

		<?php esc_html_e(
			'Your article has not been rejected. Once you complete the requested revisions, you can resubmit it for editorial review.',
			'newsblenda-accounts'
		); ?>

	</p>

</div>

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

<p>

	<?php esc_html_e(
		'Thank you for your contribution. We appreciate your effort and look forward to reviewing your revised article.',
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
			'This notification was generated automatically by the Newsblenda Accounts system.',
			'newsblenda-accounts'
		); ?>

	</p>

	<?php
	/**
	 * Fires at the bottom of the revision requested email.
	 *
	 * Developers can append additional guidance,
	 * branding or legal notices.
	 */
	do_action(
		'nb_accounts_email_revision_requested_footer',
		$article_title
	);
	?>

</div>

</div>

</body>

</html>