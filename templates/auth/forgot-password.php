<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$login    = home_url('/login/');
$register = home_url('/register/');
$status = isset($_GET['status'])
	? sanitize_key(wp_unslash($_GET['status']))
	: '';

$status_messages = [
	'sent' => [
		'type' => 'success',
		'message' => __('If an account exists with that email address, a password reset link has been sent.', 'newsblenda-accounts'),
	],
	'throttled' => [
		'type' => 'info',
		'message' => __('Too many reset requests. Please wait before trying again.', 'newsblenda-accounts'),
	],
	'invalid-email' => [
		'type' => 'error',
		'message' => __('Please enter a valid email address.', 'newsblenda-accounts'),
	],
	'invalid-nonce' => [
		'type' => 'error',
		'message' => __('Security validation failed. Please refresh and try again.', 'newsblenda-accounts'),
	],
	'error' => [
		'type' => 'error',
		'message' => __('Unable to process your request right now. Please try again.', 'newsblenda-accounts'),
	],
];
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

		<?php if (isset($status_messages[$status])) : ?>
			<div class="nba-message nba-message-<?php echo esc_attr($status_messages[$status]['type']); ?>">
				<?php echo esc_html($status_messages[$status]['message']); ?>
			</div>
		<?php endif; ?>

		<form
			method="post"
			class="nba-forgot-password-form"
			autocomplete="off"
			data-nb-lock-submit="1"
			data-nba-ajax="1"
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
				<input type="text" name="nb_website" value="" tabindex="-1" autocomplete="off" class="nba-honeypot" aria-hidden="true" role="presentation">

			</p>

			<p>

				<button
					type="submit"
					class="button button-primary nba-submit-button"
				>
					<span class="nba-submit-button-label"><?php esc_html_e(
						'Send Reset Link',
						'newsblenda-accounts'
					); ?></span>
					<span class="nba-submit-spinner" aria-hidden="true"></span>
				</button>

			</p>

			<div class="nba-form-response" aria-live="polite"></div>

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