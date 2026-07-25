<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$forgot  = home_url('/forgot-password/');
$register = home_url('/register/');

$redirect = isset($_GET['redirect_to'])
	? esc_url(wp_unslash($_GET['redirect_to']))
	: home_url('/dashboard/');
?>

<div class="nba-auth-wrapper nba-login">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">🔐</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Sign In',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Sign in to access your Newsblenda dashboard, manage your profile and submit articles.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php if (isset($_GET['logged_out'])) : ?>

			<div class="nba-message nba-message-success">

				<?php esc_html_e(
					'You have been logged out successfully.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<?php if (isset($_GET['login']) && $_GET['login'] === 'failed') : ?>

			<div class="nba-message nba-message-error">

				<?php esc_html_e(
					'Invalid username or password.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<form
			method="post"
			class="nba-login-form"
			autocomplete="off"
		>

			<?php wp_nonce_field('nbe_nonce'); ?>

			<input
				type="hidden"
				name="nbe_login_submit"
				value="1"
			>

			<input
				type="hidden"
				name="redirect_to"
				value="<?php echo esc_attr($redirect); ?>"
			>

			<p>

				<label for="nba_username">

					<?php esc_html_e(
						'Username or Email',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="nba_username"
					type="text"
					name="nbe_username"
					required
					autocomplete="username"
					placeholder="<?php esc_attr_e('Enter your username or email', 'newsblenda-accounts'); ?>"
				>

			</p>

			<p>

				<label for="nba_password">

					<?php esc_html_e(
						'Password',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="nba_password"
					type="password"
					name="nbe_password"
					required
					autocomplete="current-password"
					placeholder="<?php esc_attr_e('Enter your password', 'newsblenda-accounts'); ?>"
				>

			</p>

			<p>

				<label>

					<input
						type="checkbox"
						name="nbe_remember"
						value="1"
					>

					<?php esc_html_e(
						'Remember Me',
						'newsblenda-accounts'
					); ?>

				</label>

			</p>

			<p>

				<button
					type="submit"
					class="button button-primary"
				>

					<?php esc_html_e(
						'Sign In',
						'newsblenda-accounts'
					); ?>

				</button>

			</p>

		</form>

		<hr>

		<div class="nba-auth-links">

			<p>

				<a href="<?php echo esc_url($forgot); ?>">

					<?php esc_html_e(
						'Forgot your password?',
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

		<hr>

		<p class="description">

			<?php esc_html_e(
				'For your security, always sign out when using a shared or public computer.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Allow additional content below the login form.
		 */
		do_action('nb_accounts_login_footer');
		?>

	</div>

</div>