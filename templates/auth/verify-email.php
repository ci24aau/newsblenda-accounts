<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$status = isset($_GET['status'])
	? sanitize_text_field(wp_unslash($_GET['status']))
	: '';

$login = home_url('/login/');
$home  = home_url('/');

$notices = [
	'registered' => [
		'type' => 'success',
		'message' => __('Registration successful. Please check your email to verify your account.', 'newsblenda-accounts'),
	],
	'success' => [
		'type' => 'success',
		'message' => __('Your email address has been verified successfully.', 'newsblenda-accounts'),
	],
	'already-verified' => [
		'type' => 'info',
		'message' => __('Your email is already verified. You can sign in now.', 'newsblenda-accounts'),
	],
	'expired' => [
		'type' => 'error',
		'message' => __('This verification link has expired. Please request a new verification email.', 'newsblenda-accounts'),
	],
	'invalid' => [
		'type' => 'error',
		'message' => __('The verification link is invalid. Please request a new verification email.', 'newsblenda-accounts'),
	],
	'resent' => [
		'type' => 'success',
		'message' => __('If an unverified account exists for that email, a new verification link has been sent.', 'newsblenda-accounts'),
	],
	'resend-failed' => [
		'type' => 'error',
		'message' => __("We couldn't send your verification email. Please try again.", 'newsblenda-accounts'),
	],
	'resend-throttled' => [
		'type' => 'info',
		'message' => __('Please wait a minute before requesting another verification email.', 'newsblenda-accounts'),
	],
	'resend-invalid-email' => [
		'type' => 'error',
		'message' => __('Please enter a valid email address to resend verification.', 'newsblenda-accounts'),
	],
	'resend-invalid-nonce' => [
		'type' => 'error',
		'message' => __('Security validation failed. Please refresh the page and try again.', 'newsblenda-accounts'),
	],
];

$notice = $notices[$status] ?? [
	'type' => 'info',
	'message' => __("We've sent a verification email to the address you registered with.", 'newsblenda-accounts'),
];
?>

<div class="nba-auth-wrapper nba-verify-email">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<?php if (in_array($status, ['success', 'registered', 'resent', 'already-verified'], true)) : ?>

				<span style="font-size:64px;">✅</span>

			<?php elseif (in_array($status, ['invalid', 'expired', 'resend-failed', 'resend-invalid-email', 'resend-invalid-nonce'], true)) : ?>

				<span style="font-size:64px;">❌</span>

			<?php else : ?>

				<span style="font-size:64px;">📧</span>

			<?php endif; ?>

		</div>

		<h1>

			<?php esc_html_e(
				'Verify Your Email',
				'newsblenda-accounts'
			); ?>

		</h1>

		<div class="nba-message nba-message-<?php echo esc_attr($notice['type']); ?>">
			<p><?php echo esc_html($notice['message']); ?></p>
		</div>

		<?php if ($status === 'success' || $status === 'already-verified') : ?>

			<p>

				<?php esc_html_e(
					'You can now continue to sign in and access your account.',
					'newsblenda-accounts'
				); ?>

			</p>

			<p>

				<?php esc_html_e(
					'If your account has already been approved by a Newsblenda administrator, you may now sign in. Otherwise, your account will remain pending until approval is complete.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php endif; ?>

		<?php if (in_array($status, ['', 'registered', 'resent'], true)) : ?>
			<p>
				<?php esc_html_e(
					'If you cannot find the message, please check your Spam, Junk or Promotions folder.',
					'newsblenda-accounts'
				); ?>
			</p>
		<?php endif; ?>

		<div class="nba-card nba-resend-verification-card">
			<h3><?php esc_html_e('Resend Verification Email', 'newsblenda-accounts'); ?></h3>
			<form method="post" class="nba-resend-verification-form" data-nb-lock-submit="1">
				<?php wp_nonce_field('nb_resend_verification'); ?>
				<input type="hidden" name="nbe_resend_verification_submit" value="1">
				<p>
					<label for="nbe_resend_email"><?php esc_html_e('Email Address', 'newsblenda-accounts'); ?></label>
					<input id="nbe_resend_email" type="email" name="nbe_resend_email" required autocomplete="email">
				</p>
				<p>
					<button type="submit" class="button button-primary nba-submit-button">
						<span class="nba-submit-button-label"><?php esc_html_e('Resend Verification Email', 'newsblenda-accounts'); ?></span>
						<span class="nba-submit-spinner" aria-hidden="true"></span>
					</button>
				</p>
			</form>
		</div>

		<hr>

		<h3>

			<?php esc_html_e(
				'Need Help?',
				'newsblenda-accounts'
			); ?>

		</h3>

		<ul class="nba-next-steps">

			<li>

				<?php esc_html_e(
					'Ensure you are checking the correct email address.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Check your Spam or Junk folder.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'If your verification link has expired, request a new one.',
					'newsblenda-accounts'
				); ?>

			</li>

		</ul>

		<div class="nba-auth-actions">

			<a
				class="button button-primary"
				href="<?php echo esc_url($login); ?>"
			>

				<?php esc_html_e(
					'Go to Login',
					'newsblenda-accounts'
				); ?>

			</a>

			<a
				class="button"
				href="<?php echo esc_url($home); ?>"
			>

				<?php esc_html_e(
					'Return Home',
					'newsblenda-accounts'
				); ?>

			</a>

		</div>

		<hr>

		<p class="description">

			<?php esc_html_e(
				'Email verification helps protect your Newsblenda account and ensures you receive important notifications about your articles, account approval and earnings.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Allow additional content below the email verification page.
		 */
		do_action('nb_accounts_verify_email_footer');
		?>

	</div>

</div>