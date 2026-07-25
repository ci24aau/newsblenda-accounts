<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$login = home_url('/login/');
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

		<?php if (isset($_GET['error'])) : ?>

			<div class="nba-message nba-message-error">

				<?php esc_html_e(
					'Registration could not be completed. Please check your details and try again.',
					'newsblenda-accounts'
				); ?>

			</div>

		<?php endif; ?>

		<form
			method="post"
			class="nba-register-form"
			autocomplete="off"
		>

			<?php wp_nonce_field('nbe_nonce'); ?>

			<input
				type="hidden"
				name="nbe_register_submit"
				value="1"
			>

			<h3><?php esc_html_e('Account Information', 'newsblenda-accounts'); ?></h3>

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
				>

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
				>

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
				>

			</p>

			<p>

				<label for="nba_phone">

					<?php esc_html_e('Phone Number', 'newsblenda-accounts'); ?>

				</label>

				<input
					id="nba_phone"
					type="text"
					name="nbe_phone"
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
				>

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
				>

			</p>

			<p class="description">

				<?php esc_html_e(
					'Your password should contain at least 8 characters including uppercase letters, lowercase letters and numbers.',
					'newsblenda-accounts'
				); ?>

			</p>

			<p>

				<label>

					<input
						type="checkbox"
						name="nbe_terms"
						value="1"
						required
					>

					<?php esc_html_e(
						'I agree to the Newsblenda Terms and Conditions and Editorial Guidelines.',
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
						'Create Account',
						'newsblenda-accounts'
					); ?>

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