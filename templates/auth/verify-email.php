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
?>

<div class="nba-auth-wrapper nba-verify-email">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<?php if ($status === 'success') : ?>

				<span style="font-size:64px;">✅</span>

			<?php elseif ($status === 'invalid') : ?>

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

		<?php if ($status === 'success') : ?>

			<div class="nba-message nba-message-success">

				<p>

					<?php esc_html_e(
						'Your email address has been verified successfully.',
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

			<p>

				<?php esc_html_e(
					'If your account has already been approved by a Newsblenda administrator, you may now sign in. Otherwise, your account will remain pending until approval is complete.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php elseif ($status === 'invalid') : ?>

			<div class="nba-message nba-message-error">

				<p>

					<?php esc_html_e(
						'The verification link is invalid, expired or has already been used.',
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

			<p>

				<?php esc_html_e(
					'If you still cannot verify your account, request a new verification email or contact the Newsblenda administrator.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php else : ?>

			<div class="nba-message nba-message-info">

				<p>

					<?php esc_html_e(
						"We've sent a verification email to the address you registered with.",
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

			<p>

				<?php esc_html_e(
					'Open the email and click the verification link to activate your account.',
					'newsblenda-accounts'
				); ?>

			</p>

			<p>

				<?php esc_html_e(
					'If you cannot find the message, please check your Spam, Junk or Promotions folder.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php endif; ?>

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