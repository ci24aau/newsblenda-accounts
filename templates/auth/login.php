<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$forgot  = home_url('/forgot-password/');
$register = home_url('/register/');
$verify = home_url('/verify-email/');

$redirect = isset($_GET['redirect_to'])
	? esc_url(wp_unslash($_GET['redirect_to']))
	: home_url('/dashboard/');

$login_error = isset($_GET['login_error'])
	? sanitize_key(wp_unslash($_GET['login_error']))
	: '';

$prefill_username = isset($_GET['login_user'])
	? sanitize_text_field(wp_unslash($_GET['login_user']))
	: '';

$login_messages = [
	'nonce' => __('Security check failed. Please try again.', 'newsblenda-accounts'),
	'required' => __('Please enter your username and password.', 'newsblenda-accounts'),
	'invalid' => __('Invalid username or password.', 'newsblenda-accounts'),
	'locked' => __('Too many failed login attempts. Please try again later.', 'newsblenda-accounts'),
	'unverified' => __('Please verify your email address before logging in.', 'newsblenda-accounts'),
	'pending' => __('Your author account is awaiting approval.', 'newsblenda-accounts'),
	'restricted' => __('Your account has been restricted.', 'newsblenda-accounts'),
	'suspended' => __('Your account has been suspended.', 'newsblenda-accounts'),
];
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

		<?php if ($login_error !== '' && isset($login_messages[$login_error])) : ?>

			<div class="nba-message nba-message-error">

				<?php echo esc_html($login_messages[$login_error]); ?>

			</div>

		<?php endif; ?>

		<?php if ($login_error === 'unverified') : ?>
			<div class="nba-message nba-message-info">
				<p><?php esc_html_e('Need a new verification email?', 'newsblenda-accounts'); ?></p>
				<p><a href="<?php echo esc_url($verify); ?>"><?php esc_html_e('Resend verification email', 'newsblenda-accounts'); ?></a></p>
			</div>
		<?php endif; ?>

		<form
			method="post"
			class="nba-login-form"
			autocomplete="off"
			data-nb-lock-submit="1"
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
					value="<?php echo esc_attr($prefill_username); ?>"
				>

			</p>

			<p>

				<label for="nba_password">

					<?php esc_html_e(
						'Password',
						'newsblenda-accounts'
					); ?>

				</label>

				<span class="nba-password-field">
					<input
						id="nba_password"
						type="password"
						name="nbe_password"
						required
						autocomplete="current-password"
						placeholder="<?php esc_attr_e('Enter your password', 'newsblenda-accounts'); ?>"
					>
					<button
						type="button"
						class="nba-password-toggle"
						aria-label="<?php esc_attr_e('Show password', 'newsblenda-accounts'); ?>"
					>&#128065;</button>
				</span>

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
					class="button button-primary nba-submit-button"
				>
					<span class="nba-submit-button-label">
					<?php esc_html_e(
						'Sign In',
						'newsblenda-accounts'
					); ?>
					</span>
					<span class="nba-submit-spinner" aria-hidden="true"></span>
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