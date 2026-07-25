<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$user = wp_get_current_user();

$total_earnings = isset($total_earnings)
	? (float) $total_earnings
	: 0;

$paid_amount = isset($paid_amount)
	? (float) $paid_amount
	: 0;

$unpaid_balance = isset($unpaid_balance)
	? (float) $unpaid_balance
	: 0;

$total_views = isset($total_views)
	? (int) $total_views
	: 0;

$top_article = isset($top_article)
	? (string) $top_article
	: '';

$last_update = isset($last_update)
	? (string) $last_update
	: '';

$minimum_payout = (float) get_option(
	'nb_minimum_payout',
	10
);

$payout_ready = $unpaid_balance >= $minimum_payout;

?>

<div class="nba-earnings-page">

	<header class="nba-page-header">

		<h1>

			<?php esc_html_e(
				'My Earnings',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Monitor your article earnings, views and payout progress.',
				'newsblenda-accounts'
			); ?>

		</p>

	</header>

	<div class="nba-dashboard-grid">

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Total Earnings',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html(
					'£' . number_format($total_earnings, 2)
				); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Paid',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html(
					'£' . number_format($paid_amount, 2)
				); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Unpaid Balance',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html(
					'£' . number_format($unpaid_balance, 2)
				); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Article Views',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html(
					number_format($total_views)
				); ?>

			</div>

		</div>

	</div>
    
    	<section class="nba-earnings-summary">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Earnings Summary',
					'newsblenda-accounts'
				); ?>

			</h2>

			<table class="nba-summary-table">

				<tr>

					<th>

						<?php esc_html_e(
							'Total Earnings',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							'£' . number_format($total_earnings, 2)
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Paid Amount',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							'£' . number_format($paid_amount, 2)
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Available Balance',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							'£' . number_format($unpaid_balance, 2)
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Total Valid Views',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							number_format($total_views)
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Top Performing Article',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							$top_article ?: __('None', 'newsblenda-accounts')
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Last Earnings Update',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php

						if (! empty($last_update)) {

							echo esc_html(

								wp_date(

									get_option('date_format') . ' ' .

									get_option('time_format'),

									strtotime($last_update)

								)

							);

						} else {

							esc_html_e(
								'Never',
								'newsblenda-accounts'
							);

						}

						?>

					</td>

				</tr>

			</table>

		</div>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Payout Status',
					'newsblenda-accounts'
				); ?>

			</h2>

			<?php if ($payout_ready) : ?>

				<div class="nba-message nba-message-success">

					<strong>

						<?php esc_html_e(
							'Eligible for Payout',
							'newsblenda-accounts'
						); ?>

					</strong>

					<p>

						<?php
						printf(
							esc_html__(
								'Your unpaid balance has reached the minimum payout threshold of £%s.',
								'newsblenda-accounts'
							),
							number_format($minimum_payout, 2)
						);
						?>

					</p>

				</div>

			<?php else : ?>

				<div class="nba-message nba-message-info">

					<strong>

						<?php esc_html_e(
							'Not Yet Eligible',
							'newsblenda-accounts'
						); ?>

					</strong>

					<p>

						<?php
						printf(
							esc_html__(
								'You need a minimum unpaid balance of £%s before a payout can be processed.',
								'newsblenda-accounts'
							),
							number_format($minimum_payout, 2)
						);
						?>

					</p>

				</div>

			<?php endif; ?>

		</div>

	</section>
    
    	<section class="nba-earnings-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'About Earnings',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Your earnings are calculated using valid article views recorded by Newsblenda. Invalid, duplicate or suspicious traffic is automatically excluded from payout calculations.',
					'newsblenda-accounts'
				); ?>

			</p>

			<ul>

				<li>

					<?php esc_html_e(
						'Earnings are updated during the daily synchronisation process.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Only published articles contribute to your earnings.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Repeated page refreshes and invalid traffic are filtered automatically.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Payment requests are processed by the Newsblenda administration team after verification.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Quick Actions',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<a
					class="button button-primary"
					href="<?php echo esc_url(home_url('/profile/')); ?>"
				>

					<?php esc_html_e(
						'Update Payment Details',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

			<p>

				<a
					class="button"
					href="<?php echo esc_url(home_url('/dashboard/')); ?>"
				>

					<?php esc_html_e(
						'Return to Dashboard',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

			<p>

				<a
					class="button"
					href="<?php echo esc_url(home_url('/submit/')); ?>"
				>

					<?php esc_html_e(
						'Submit New Article',
						'newsblenda-accounts'
					); ?>

				</a>

			</p>

		</div>

	</section>

	<?php
	/**
	 * Fires at the bottom of the earnings page.
	 *
	 * Developers can use this hook to add custom
	 * payout history, charts or reporting widgets.
	 */
	do_action(
		'nb_accounts_earnings_footer',
		$user
	);
	?>

</div>