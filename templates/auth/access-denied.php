<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$dashboard = home_url('/dashboard/');
$login     = home_url('/login/');
$home      = home_url('/');
?>

<div class="nba-auth-wrapper nba-access-denied">

	<div class="nba-auth-card">

		<div class="nba-access-icon">

			<span style="font-size:64px;">🔒</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Access Denied',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p class="nba-message">

			<?php esc_html_e(
				'You do not have permission to access this page.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<?php esc_html_e(
				'This page is restricted to authorised Newsblenda users.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php if (is_user_logged_in()) : ?>

			<p>

				<?php esc_html_e(
					'If you believe you should have access, please contact a Newsblenda administrator.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php else : ?>

			<p>

				<?php esc_html_e(
					'Please sign in to continue.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php endif; ?>

		<div class="nba-auth-actions">

			<?php if (is_user_logged_in()) : ?>

				<a
					class="button button-primary"
					href="<?php echo esc_url($dashboard); ?>"
				>

					<?php esc_html_e(
						'Go to Dashboard',
						'newsblenda-accounts'
					); ?>

				</a>

			<?php else : ?>

				<a
					class="button button-primary"
					href="<?php echo esc_url($login); ?>"
				>

					<?php esc_html_e(
						'Login',
						'newsblenda-accounts'
					); ?>

				</a>

			<?php endif; ?>

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
				'If this restriction is unexpected, your account may still be awaiting approval or may not have sufficient permissions.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Allow additional content to be displayed.
		 */
		do_action('nb_accounts_access_denied_footer');
		?>

	</div>

</div>