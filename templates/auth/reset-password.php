<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$key = isset($_GET['key'])
	? sanitize_text_field(wp_unslash($_GET['key']))
	: '';

$login = isset($_GET['login'])
	? sanitize_text_field(wp_unslash($_GET['login']))
	: '';

$login_url = home_url('/login/');
?>

<div class="nba-auth-wrapper nba-reset-password">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">🔒</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Reset Password',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Create a new password for your Newsblenda account.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php if (isset($_GET['error'])) : ?>

			<div class="nba-message nba-message-error">

				<?php esc_html_e(
					'Unable to reset your password. Please try again.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<form
			method="post"
			class="nba-reset-password-form"
			autocomplete="off"
		>

			<?php wp_nonce_field('nbe_nonce'); ?>

			<input
				type="hidden"
				name="nbe_reset_submit"
				value="1"
			>

			<input
				type="hidden"
				name="nbe_key"
				value="<?php echo esc_attr($key); ?>"
			>

			<input
				type="hidden"
				name="nbe_login"
				value="<?php echo esc_attr($login); ?>"
			>

			<p>

				<label for="password">

					<?php esc_html_e(
						'New Password',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="password"
					type="password"
					name="nbe_password"
					required
					autocomplete="new-password"
					placeholder="<?php esc_attr_e('Enter your new password', 'newsblenda-accounts'); ?>"
				>

			</p>

			<p>

				<label for="confirm_password">

					<?php esc_html_e(
						'Confirm Password',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="confirm_password"
					type="password"
					name="nbe_confirm_password"
					required
					autocomplete="new-password"
					placeholder="<?php esc_attr_e('Confirm your new password', 'newsblenda-accounts'); ?>"
				>

			</p>

			<p class="description">

				<?php esc_html_e(
					'Your password should contain at least 8 characters, including uppercase letters, lowercase letters and numbers.',
					'newsblenda-accounts'
				); ?>

			</p>

			<p>

				<button
					type="submit"
					class="button button-primary"
				>

					<?php esc_html_e(
						'Reset Password',
						'newsblenda-accounts'
					); ?>

				</button>

			</p>

		</form>

		<hr>

		<h3>

			<?php esc_html_e(
				'Security Notice',
				'newsblenda-accounts'
			); ?>

		</h3>

		<p>

			<?php esc_html_e(
				'For your security, this password reset link can only be used once. After successfully resetting your password, please sign in using your new credentials.',
				'newsblenda-accounts'
			); ?>

		</p>

		<div class="nba-auth-links">

			<p>

				<a href="<?php echo esc_url($login_url); ?>">

					<?php esc_html_e(
						'Back to Login',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

		</div>

		<?php
		/**
		 * Allow additional content below the reset password form.
		 */
		do_action('nb_accounts_reset_password_footer');
		?>

	</div>

</div>