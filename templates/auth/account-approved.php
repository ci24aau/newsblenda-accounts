<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$dashboard = home_url('/dashboard/');
$profile   = home_url('/profile/');
$submit    = home_url('/submit/');
?>

<div class="nba-auth-wrapper nba-account-approved">

	<div class="nba-auth-card">

		<div class="nba-success-icon">

			<span style="font-size:64px;">✅</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Account Approved',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p class="nba-message">

			<?php esc_html_e(
				'Congratulations! Your Newsblenda author account has been approved.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<?php esc_html_e(
				'You can now access your dashboard, update your profile and begin submitting articles for editorial review.',
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
				href="<?php echo esc_url($submit); ?>"
			>

				<?php esc_html_e(
					'Submit Article',
					'newsblenda-accounts'
				); ?>

			</a>

			<a
				class="button"
				href="<?php echo esc_url($profile); ?>"
			>

				<?php esc_html_e(
					'Complete Your Profile',
					'newsblenda-accounts'
				); ?>

			</a>

		</div>

		<hr>

		<h3>

			<?php esc_html_e(
				'Next Steps',
				'newsblenda-accounts'
			); ?>

		</h3>

		<ul class="nba-next-steps">

			<li>

				<?php esc_html_e(
					'Complete your author profile.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Add your payment information.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Submit your first article for editorial review.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Track your notifications and earnings from your dashboard.',
					'newsblenda-accounts'
				); ?>

			</li>

		</ul>

		<?php
		/**
		 * Allow additional content after account approval.
		 */
		do_action('nb_accounts_account_approved_footer');
		?>

	</div>

</div>