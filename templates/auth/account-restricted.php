<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$dashboard = home_url('/dashboard/');
$home      = home_url('/');
?>

<div class="nba-auth-wrapper nba-account-restricted">

	<div class="nba-auth-card">

		<div class="nba-warning-icon">

			<span style="font-size:64px;">⚠️</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Account Restricted',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p class="nba-message">

			<?php esc_html_e(
				'Your Newsblenda account has been temporarily restricted.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<?php esc_html_e(
				'While your account is restricted, you cannot submit new articles or access author-only features.',
				'newsblenda-accounts'
			); ?>

		</p>

		<hr>

		<h3>

			<?php esc_html_e(
				'Possible Reasons',
				'newsblenda-accounts'
			); ?>

		</h3>

		<ul class="nba-restriction-list">

			<li>

				<?php esc_html_e(
					'Your article rejection rate exceeded the allowed threshold.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Repeated violations of the Newsblenda editorial guidelines.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Your account is currently under editorial review.',
					'newsblenda-accounts'
				); ?>

			</li>

		</ul>

		<p>

			<?php esc_html_e(
				'If you believe this restriction was applied in error, please contact the Newsblenda editorial team for assistance.',
				'newsblenda-accounts'
			); ?>

		</p>

		<div class="nba-auth-actions">

			<a
				class="button button-primary"
				href="<?php echo esc_url($dashboard); ?>"
			>

				<?php esc_html_e(
					'Go to Dashboard',
					'newsblenda-accounts'
				); ?>

			</a>

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
				'Your existing articles and account information remain safe. Once the restriction is lifted, all author features will be restored automatically.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Allow additional content on the restricted account page.
		 */
		do_action('nb_accounts_account_restricted_footer');
		?>

	</div>

</div>