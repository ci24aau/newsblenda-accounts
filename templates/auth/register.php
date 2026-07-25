<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$login = home_url('/login/');
$state = \Newsblenda\Accounts\Auth\Register::consume_form_state();
$values = $state['values'] ?? [];
$errors = $state['errors'] ?? [];

$value = static function (string $key, string $default = '') use ($values): string {
	return isset($values[$key]) ? (string) $values[$key] : $default;
};

$field_error = static function (string $key) use ($errors): string {
	return isset($errors[$key]) ? (string) $errors[$key] : '';
};

$field_class = static function (string $key) use ($errors): string {
	return isset($errors[$key]) ? ' nba-invalid' : '';
};

$general_errors = [];
if (isset($errors['_general']) && is_string($errors['_general'])) {
	$general_errors[] = $errors['_general'];
}

foreach ($errors as $error_key => $error_message) {
	if ($error_key !== '_general' && is_string($error_message)) {
		$general_errors[] = $error_message;
	}
}

$general_errors = array_values(array_unique($general_errors));
?>

<div class="nba-auth-wrapper nba-register">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">📝</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Create Account',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Create your Newsblenda Author account to submit articles, track your earnings and manage your profile.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php if (! empty($general_errors)) : ?>
			<div class="nba-message nba-message-error">
				<ul class="nba-error-list">
					<?php foreach ($general_errors as $error_message) : ?>
						<li><?php echo esc_html($error_message); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form
			method="post"
			class="nba-register-form"
			autocomplete="off"
			data-nb-lock-submit="1"
		>

			<?php wp_nonce_field('nbe_nonce'); ?>

			<input
				type="hidden"
				name="nbe_register_submit"
				value="1"
			>
			<input
				type="hidden"
				name="account_type"
				value="<?php echo esc_attr($value('account_type', 'subscriber')); ?>"
			>

			<h3><?php esc_html_e('Account Information', 'newsblenda-accounts'); ?></h3>
			<?php if ($field_error('account_type') !== '') : ?>
				<p class="nba-field-error"><?php echo esc_html($field_error('account_type')); ?></p>
			<?php endif; ?>

			<p>

				<label for="nba_username">

					<?php esc_html_e('Username', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_username"
					type="text"
					name="nbe_username"
					required
					autocomplete="username"
					value="<?php echo esc_attr($value('nbe_username')); ?>"
					class="<?php echo esc_attr(trim($field_class('nbe_username'))); ?>"
				>
				<?php if ($field_error('nbe_username') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_username')); ?></span>
				<?php endif; ?>

			</p>

			<p>

				<label for="nba_email">

					<?php esc_html_e('Email Address', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_email"
					type="email"
					name="nbe_email"
					required
					autocomplete="email"
					value="<?php echo esc_attr($value('nbe_email')); ?>"
					class="<?php echo esc_attr(trim($field_class('nbe_email'))); ?>"
				>
				<?php if ($field_error('nbe_email') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_email')); ?></span>
				<?php endif; ?>

			</p>

			<p>

				<label for="nba_display_name">

					<?php esc_html_e('Display Name', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_display_name"
					type="text"
					name="nbe_full_name"
					required
					value="<?php echo esc_attr($value('nbe_full_name')); ?>"
					class="<?php echo esc_attr(trim($field_class('nbe_full_name'))); ?>"
				>
				<?php if ($field_error('nbe_full_name') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_full_name')); ?></span>
				<?php endif; ?>

			</p>

			<p>

				<label for="nba_phone">

					<?php esc_html_e('Phone Number', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_phone"
					type="text"
					name="nbe_phone"
					value="<?php echo esc_attr($value('nbe_phone')); ?>"
				>

			</p>

			<p>

				<label for="nba_country">

					<?php esc_html_e('Country', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_country"
					type="text"
					name="nbe_country"
					value="<?php echo esc_attr($value('nbe_country')); ?>"
				>

			</p>

			<p>

				<label for="nba_niche">

					<?php esc_html_e('Preferred Writing Niche', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_niche"
					type="text"
					name="nbe_niche"
					value="<?php echo esc_attr($value('nbe_niche')); ?>"
				>

			</p>

			<h3><?php esc_html_e('Security', 'newsblenda-accounts'); ?></h3>

			<p>

				<label for="nba_password">

					<?php esc_html_e('Password', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_password"
					type="password"
					name="nbe_password"
					required
					autocomplete="new-password"
					class="<?php echo esc_attr(trim($field_class('nbe_password'))); ?>"
					aria-describedby="nba-password-requirements"
				>
				<?php if ($field_error('nbe_password') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_password')); ?></span>
				<?php endif; ?>

			</p>

			<p>

				<label for="nba_confirm_password">

					<?php esc_html_e('Confirm Password', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_confirm_password"
					type="password"
					name="nbe_confirm_password"
					required
					autocomplete="new-password"
					class="<?php echo esc_attr(trim($field_class('nbe_confirm_password'))); ?>"
				>
				<?php if ($field_error('nbe_confirm_password') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_confirm_password')); ?></span>
				<?php endif; ?>

			</p>

			<p class="description">

				<?php esc_html_e(
					'Password must contain:',
					'newsblenda-accounts'
				); ?>

			</p>
			<ul id="nba-password-requirements" class="nba-password-requirements" role="list" aria-label="<?php esc_attr_e('Password requirements', 'newsblenda-accounts'); ?>">
				<li><?php esc_html_e('at least 8 characters', 'newsblenda-accounts'); ?></li>
				<li><?php esc_html_e('an uppercase letter', 'newsblenda-accounts'); ?></li>
				<li><?php esc_html_e('a lowercase letter', 'newsblenda-accounts'); ?></li>
				<li><?php esc_html_e('a number', 'newsblenda-accounts'); ?></li>
				<li><?php esc_html_e('a special character', 'newsblenda-accounts'); ?></li>
			</ul>

			<p>

				<label>

					<input
						type="checkbox"
						name="nbe_terms"
						value="1"
						required
						<?php checked($value('nbe_terms'), '1'); ?>
					>

					<?php esc_html_e(
						'I agree to the Newsblenda Terms and Conditions and Editorial Guidelines.',
						'newsblenda-accounts'
					); ?>

				</label>
				<?php if ($field_error('nbe_terms') !== '') : ?>
					<span class="nba-field-error"><?php echo esc_html($field_error('nbe_terms')); ?></span>
				<?php endif; ?>

			</p>

			<p>

				<button
					type="submit"
					class="button button-primary nba-submit-button"
				>
					<span class="nba-submit-button-label">
					<?php esc_html_e(
						'Create Account',
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

				<a href="<?php echo esc_url($login); ?>">

					<?php esc_html_e(
						'Already have an account? Sign In',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

		</div>

		<?php
		/**
		 * Allow additional content below the registration form.
		 */
		do_action('nb_accounts_register_footer');
		?>

	</div>

</div>