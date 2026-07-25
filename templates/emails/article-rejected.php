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

$feedback = $feedback ?? '';

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
		'Article Requires Attention - %s',
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
	background:#fff5f5;
	border-left:4px solid #dc2626;
	padding:20px;
	margin:30px 0;
}

.feedback{
	background:#f7f7f7;
	padding:20px;
	border-radius:6px;
	border:1px solid #ddd;
	margin-top:20px;
	white-space:pre-wrap;
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

'Article Not Approved',

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

'After editorial review, your article could not be approved for publication in its current form.',

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

<?php if (! empty($feedback)) : ?>

<h2>

<?php esc_html_e(

'Editorial Feedback',

'newsblenda-accounts'

); ?>

</h2>

<div class="feedback">

<?php echo nl2br(esc_html($feedback)); ?>

</div>

<?php endif; ?>

<h2>

	<?php esc_html_e(
		'How to Improve Your Submission',
		'newsblenda-accounts'
	); ?>

</h2>

<p>

	<?php esc_html_e(
		'Article rejections are part of the editorial process and provide an opportunity to improve your writing. Please review the editor feedback carefully before preparing another submission.',
		'newsblenda-accounts'
	); ?>

</p>

<ul>

	<li>

		<?php esc_html_e(
			'Address every point raised by the editor.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Verify all facts and update unreliable sources.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Ensure your article follows the Newsblenda editorial guidelines.',
			'newsblenda-accounts'
		); ?>

	</li>

	<li>

		<?php esc_html_e(
			'Review grammar, spelling and formatting before submitting another article.',
			'newsblenda-accounts'
		); ?>

	</li>

</ul>

<div class="notice">

	<strong>

		<?php esc_html_e(
			'Submission Quality',
			'newsblenda-accounts'
		); ?>

	</strong>

	<p>

		<?php esc_html_e(
			'Consistently high-quality submissions improve your approval rate. Multiple rejected submissions may affect your author statistics in accordance with the Newsblenda editorial policy.',
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
		'We appreciate your contribution and encourage you to continue improving your work. We look forward to reviewing your future submissions.',
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
	 * Fires at the bottom of the article rejected email.
	 *
	 * Developers can append additional guidance,
	 * branding or legal information.
	 */
	do_action(
		'nb_accounts_email_article_rejected_footer',
		$article_title
	);
	?>

</div>

</div>

</body>

</html>