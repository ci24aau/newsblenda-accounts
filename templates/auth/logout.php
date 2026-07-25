<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$logout_url = \Newsblenda\Accounts\Auth\Logout::get_logout_url();
$dashboard  = home_url('/dashboard/');
$home       = home_url('/');
?>

<div class="nba-auth-wrapper nba-logout">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">🚪</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Sign Out',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p class="nba-message">

			<?php esc_html_e(
				'You are about to sign out of your Newsblenda account.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<?php esc_html_e(
				'After signing out, you will need to log in again to access your dashboard, profile, notifications and author tools.',
				'newsblenda-accounts'
			); ?>

		</p>

		<div class="nba-auth-actions">

			<a
				class="button button-primary"
				href="<?php echo esc_url($logout_url); ?>"
			>

				<?php esc_html_e(
					'Sign Out',
					'newsblenda-accounts'
				); ?>

			</a>

			<a
				class="button"
				href="<?php echo esc_url($dashboard); ?>"
			>

				<?php esc_html_e(
					'Cancel',
					'newsblenda-accounts'
				); ?>

			</a>

		</div>

		<hr>

		<h3>

			<?php esc_html_e(
				'Security Reminder',
				'newsblenda-accounts'
			); ?>

		</h3>

		<p>

			<?php esc_html_e(
				'If you are using a public or shared computer, signing out helps keep your Newsblenda account secure.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<a
				href="<?php echo esc_url($home); ?>"
			>

				<?php esc_html_e(
					'Return to Home Page',
					'newsblenda-accounts'
				); ?>

			</a>

		</p>

		<?php
		/**
		 * Allow additional content on the logout page.
		 */
		do_action('nb_accounts_logout_footer');
		?>

	</div>

</div>