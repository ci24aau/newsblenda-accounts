<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$login = home_url('/login/');
$home  = home_url('/');
?>

<div class="nba-auth-wrapper nba-pending-approval">

	<div class="nba-auth-card">

		<div class="nba-auth-icon">

			<span style="font-size:64px;">⏳</span>

		</div>

		<h1>

			<?php esc_html_e(
				'Account Pending Approval',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p class="nba-message">

			<?php esc_html_e(
				'Your Newsblenda account has been created successfully.',
				'newsblenda-accounts'
			); ?>

		</p>

		<p>

			<?php esc_html_e(
				'Your email address has been verified successfully. Before you can submit articles, your account must be reviewed and approved by a Newsblenda administrator.',
				'newsblenda-accounts'
			); ?>

		</p>

		<hr>

		<h3>

			<?php esc_html_e(
				'What Happens Next?',
				'newsblenda-accounts'
			); ?>

		</h3>

		<ul class="nba-next-steps">

			<li>

				<?php esc_html_e(
					'Our editorial team will review your registration.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'You will receive an email once your account has been approved.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'After approval, you can log in and start submitting articles.',
					'newsblenda-accounts'
				); ?>

			</li>

		</ul>

		<p>

			<?php esc_html_e(
				'Approval times may vary depending on the number of applications being reviewed.',
				'newsblenda-accounts'
			); ?>

		</p>

		<div class="nba-auth-actions">

			<a
				class="button button-primary"
				href="<?php echo esc_url($login); ?>"
			>

				<?php esc_html_e(
					'Return to Login',
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
				'Please avoid creating multiple accounts while waiting for approval. If you believe there is an issue with your application, contact the Newsblenda editorial team.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Allow additional content on the pending approval page.
		 */
		do_action('nb_accounts_pending_approval_footer');
		?>

	</div>

</div>