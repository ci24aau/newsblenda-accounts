<?php

declare(strict_types=1);

use Newsblenda\Accounts\Auth\Password;

if (! defined('ABSPATH')) {
	exit;
}

$user_id = isset($_GET['user'])
	? absint(wp_unslash($_GET['user']))
	: 0;

$token = isset($_GET['token'])
	? sanitize_text_field(wp_unslash($_GET['token']))
	: '';

$status = isset($_GET['status'])
	? sanitize_key(wp_unslash($_GET['status']))
	: '';

if ($status === '' && $user_id > 0 && $token !== '') {
	$status = Password::get_reset_token_status($user_id, $token);
}

$login_url = home_url('/login/');
$forgot_url = home_url('/forgot-password/');

$status_messages = [
	'valid' => [
		'type' => 'info',
		'message' => __('Create a strong password to secure your account.', 'newsblenda-accounts'),
	],
	'missing' => [
		'type' => 'error',
		'message' => __('Missing password reset token. Please request a new reset link.', 'newsblenda-accounts'),
	],
	'invalid' => [
		'type' => 'error',
		'message' => __('This password reset link is invalid. Please request a new one.', 'newsblenda-accounts'),
	],
	'expired' => [
		'type' => 'error',
		'message' => __('This password reset link has expired. Please request a new one.', 'newsblenda-accounts'),
	],
	'consumed' => [
		'type' => 'error',
		'message' => __('This password reset link has already been used. Please request a new one.', 'newsblenda-accounts'),
	],
	'nomatch' => [
		'type' => 'error',
		'message' => __('Passwords do not match. Please try again.', 'newsblenda-accounts'),
	],
	'weak' => [
		'type' => 'error',
		'message' => __('Please use a stronger password that meets all requirements.', 'newsblenda-accounts'),
	],
	'invalid-nonce' => [
		'type' => 'error',
		'message' => __('Security validation failed. Please refresh and try again.', 'newsblenda-accounts'),
	],
	'submit-throttled' => [
		'type' => 'error',
		'message' => __('Too many attempts. Please request a new reset link.', 'newsblenda-accounts'),
	],
	'error' => [
		'type' => 'error',
		'message' => __('Unable to reset your password right now. Please request a new link.', 'newsblenda-accounts'),
	],
];

$token_valid = ($status === 'valid');
$status_icon = $token_valid ? '🔒' : '⚠️';
?>

<div class="nba-auth-wrapper nba-reset-password">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">
			<span style="font-size:64px;"><?php echo esc_html($status_icon); ?></span>
		</div>

		<h1>
			<?php esc_html_e('Reset Password', 'newsblenda-accounts'); ?>
		</h1>

		<p>
			<?php esc_html_e('Create a new password for your Newsblenda account.', 'newsblenda-accounts'); ?>
		</p>

		<?php if (isset($status_messages[$status])) : ?>
			<div class="nba-message nba-message-<?php echo esc_attr($status_messages[$status]['type']); ?>">
				<?php echo esc_html($status_messages[$status]['message']); ?>
			</div>
		<?php endif; ?>

		<?php if ($token_valid) : ?>
			<form
				method="post"
				class="nba-reset-password-form"
				autocomplete="off"
				data-nb-lock-submit="1"
				data-nba-ajax="1"
			>

				<?php wp_nonce_field('nbe_nonce'); ?>

				<input type="hidden" name="nbe_reset_submit" value="1">
				<input type="hidden" name="nbe_token" value="<?php echo esc_attr($token); ?>">
				<input type="hidden" name="nbe_user" value="<?php echo esc_attr((string) $user_id); ?>">

				<p>
					<label for="nbe_password"><?php esc_html_e('New Password', 'newsblenda-accounts'); ?></label>
					<span class="nba-password-field">
						<input
							id="nbe_password"
							type="password"
							name="nbe_password"
							required
							autocomplete="new-password"
							placeholder="<?php esc_attr_e('Enter your new password', 'newsblenda-accounts'); ?>"
						>
						<button type="button" class="nba-password-toggle" aria-label="<?php esc_attr_e('Show password', 'newsblenda-accounts'); ?>">&#128065;</button>
					</span>
					<span class="nba-password-strength" aria-live="polite"></span>
				</p>

				<p>
					<label for="nbe_confirm_password"><?php esc_html_e('Confirm Password', 'newsblenda-accounts'); ?></label>
					<span class="nba-password-field">
						<input
							id="nbe_confirm_password"
							type="password"
							name="nbe_confirm_password"
							required
							autocomplete="new-password"
							placeholder="<?php esc_attr_e('Confirm your new password', 'newsblenda-accounts'); ?>"
						>
						<button type="button" class="nba-password-toggle" aria-label="<?php esc_attr_e('Show password', 'newsblenda-accounts'); ?>">&#128065;</button>
					</span>
					<span class="nba-password-match" aria-live="polite"></span>
				</p>

				<ul class="nba-password-requirements">
					<li><?php esc_html_e('At least 8 characters', 'newsblenda-accounts'); ?></li>
					<li><?php esc_html_e('At least one uppercase and one lowercase letter', 'newsblenda-accounts'); ?></li>
					<li><?php esc_html_e('At least one number and one special character', 'newsblenda-accounts'); ?></li>
				</ul>

				<input type="text" name="nb_website" value="" tabindex="-1" autocomplete="off" class="nba-honeypot" aria-hidden="true" role="presentation">

				<p>
					<button type="submit" class="button button-primary nba-submit-button">
						<span class="nba-submit-button-label"><?php esc_html_e('Reset Password', 'newsblenda-accounts'); ?></span>
						<span class="nba-submit-spinner" aria-hidden="true"></span>
					</button>
				</p>

				<div class="nba-form-response" aria-live="polite"></div>
			</form>
		<?php else : ?>
			<div class="nba-message nba-message-info">
				<?php esc_html_e('Need a new link? Request another password reset email below.', 'newsblenda-accounts'); ?>
			</div>
		<?php endif; ?>

		<hr>

		<div class="nba-auth-links">
			<p><a href="<?php echo esc_url($forgot_url); ?>"><?php esc_html_e('Request New Reset Link', 'newsblenda-accounts'); ?></a></p>
			<p><a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Back to Login', 'newsblenda-accounts'); ?></a></p>
		</div>

		<?php do_action('nb_accounts_reset_password_footer'); ?>

	</div>

</div>
