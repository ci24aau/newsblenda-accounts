<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$login    = home_url('/login/');
$register = home_url('/register/');
?>

<div class="nba-auth-wrapper nba-forgot-password">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">🔑</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Forgot Password',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Enter the email address associated with your Newsblenda account and we will send you a secure password reset link.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php if (isset($_GET['sent'])) : ?>

			<div class="nba-message nba-message-success">

				<?php esc_html_e(
					'If an account exists with that email address, a password reset link has been sent.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<?php if (isset($_GET['error'])) : ?>

			<div class="nba-message nba-message-error">

				<?php esc_html_e(
					'Unable to process your request. Please try again.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<form
			method="post"
			class="nba-forgot-password-form"
			autocomplete="off"
		>

			<?php wp_nonce_field('nbe_nonce'); ?>

			<input
				type="hidden"
				name="nbe_forgot_password"
				value="1"
			>

			<p>

				<label for="email">

					<?php esc_html_e(
						'Email Address',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="email"
					type="email"
					name="nbe_email"
					required
					autocomplete="email"
					placeholder="<?php esc_attr_e('Enter your email address', 'newsblenda-accounts'); ?>"
				>

			</p>

			<p>

				<button
					type="submit"
					class="button button-primary"
				>

					<?php esc_html_e(
						'Send Reset Link',
						'newsblenda-accounts'
					); ?>

				</button>

			</p>

		</form>

		<hr>

		<h3>

			<?php esc_html_e(
				'Need Help?',
				'newsblenda-accounts'
			); ?>

		</h3>

		<p>

			<?php esc_html_e(
				'For security reasons, we never reveal whether an email address exists in our system. If your account exists, you will receive reset instructions shortly.',
				'newsblenda-accounts'
			); ?>

		</p>

		<div class="nba-auth-links">

			<p>

				<a href="<?php echo esc_url($login); ?>">

					<?php esc_html_e(
						'Back to Login',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

			<p>

				<a href="<?php echo esc_url($register); ?>">

					<?php esc_html_e(
						'Create an Account',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

		</div>

		<?php
		/**
		 * Additional content for the forgot password page.
		 */
		do_action('nb_accounts_forgot_password_footer');
		?>

	</div>

</div>