<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

if (! current_user_can('nb_manage_payouts')) {
	wp_die(
		esc_html__(
			'You do not have permission to access this page.',
			'newsblenda-accounts'
		)
	);
}

$stats = [];

if (class_exists('\Newsblenda\Accounts\Payouts\Payouts')) {

	$payouts = new \Newsblenda\Accounts\Payouts\Payouts();

	$stats = $payouts->statistics();

}

?>

<div class="wrap">

	<h1>

		<?php esc_html_e(
			'Author Payout Management',
			'newsblenda-accounts'
		); ?>

	</h1>

	<?php if (isset($_GET['payout'])) : ?>

		<?php if ($_GET['payout'] === 'success') : ?>

			<div class="notice notice-success is-dismissible">

				<p>

					<?php esc_html_e(
						'Payout processed successfully.',
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

		<?php endif; ?>

		<?php if ($_GET['payout'] === 'rejected') : ?>

			<div class="notice notice-warning is-dismissible">

				<p>

					<?php esc_html_e(
						'Payout request rejected.',
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

		<?php endif; ?>

	<?php endif; ?>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Overall Statistics',
				'newsblenda-accounts'
			); ?>

		</h2>

		<table class="widefat striped">

			<tbody>

				<tr>

					<th>

						<?php esc_html_e(
							'Authors',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php
						echo esc_html(
							(string) ($stats['authors'] ?? 0)
						);
						?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Total Paid',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						£<?php echo esc_html(number_format((float) ($stats['total_paid'] ?? 0), 2)); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Outstanding Balance',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						£<?php echo esc_html(number_format((float) ($stats['total_balance'] ?? 0), 2)); ?>

					</td>

				</tr>

			</tbody>

		</table>

	</div>

	<br>

	<table class="widefat striped">

		<thead>

			<tr>

				<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>

				<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>

				<th><?php esc_html_e('Balance', 'newsblenda-accounts'); ?></th>

				<th><?php esc_html_e('Paid', 'newsblenda-accounts'); ?></th>

				<th><?php esc_html_e('Payment Method', 'newsblenda-accounts'); ?></th>

				<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>

			</tr>

		</thead>

		<tbody>
        
        <?php foreach ($authors as $author) : ?>

	<?php

	$user_id = $author->ID;

	$balance = (float) get_user_meta(
		$user_id,
		'nb_unpaid_balance',
		true
	);

	$paid = (float) get_user_meta(
		$user_id,
		'nb_paid_amount',
		true
	);

	$method = get_user_meta(
		$user_id,
		'nb_payment_method',
		true
	);

	$account_name = get_user_meta(
		$user_id,
		'nb_account_name',
		true
	);

	$account_number = get_user_meta(
		$user_id,
		'nb_account_number',
		true
	);

	$bank_name = get_user_meta(
		$user_id,
		'nb_bank_name',
		true
	);

	$minimum = (float) get_option(
		'nb_minimum_payout',
		10
	);

	$eligible = $balance >= $minimum;

	?>

	<tr>

		<td>

			<strong>

				<?php echo esc_html($author->display_name); ?>

			</strong>

			<br>

			<small>#<?php echo esc_html((string) $user_id); ?></small>

		</td>

		<td>

			<?php echo esc_html($author->user_email); ?>

		</td>

		<td>

			<strong>

				£<?php echo esc_html(number_format($balance, 2)); ?>

			</strong>

			<?php if (! $eligible) : ?>

				<br>

				<span style="color:#999;">

					<?php esc_html_e(
						'Below minimum payout',
						'newsblenda-accounts'
					); ?>

				</span>

			<?php endif; ?>

		</td>

		<td>

			£<?php echo esc_html(number_format($paid, 2)); ?>

		</td>

		<td>

			<strong>

				<?php
				echo esc_html(
					$method ?: __('Not Set', 'newsblenda-accounts')
				);
				?>

			</strong>

			<?php if (! empty($bank_name)) : ?>

				<br>

				<?php echo esc_html($bank_name); ?>

			<?php endif; ?>

			<?php if (! empty($account_name)) : ?>

				<br>

				<?php echo esc_html($account_name); ?>

			<?php endif; ?>

			<?php if (! empty($account_number)) : ?>

				<br>

				<?php echo esc_html($account_number); ?>

			<?php endif; ?>

		</td>

		<td>

			<?php if ($eligible) : ?>

				<form
					method="post"
					action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
					style="margin-bottom:10px;"
				>

					<?php wp_nonce_field('nb_process_payout'); ?>

					<input
						type="hidden"
						name="action"
						value="nb_process_payout"
					>

					<input
						type="hidden"
						name="user_id"
						value="<?php echo esc_attr((string) $user_id); ?>"
					>

					<input
						type="number"
						name="amount"
						step="0.01"
						min="0.01"
						max="<?php echo esc_attr((string) $balance); ?>"
						value="<?php echo esc_attr(number_format($balance, 2, '.', '')); ?>"
						required
						style="width:110px;"
					>

					<br><br>

					<button
						type="submit"
						class="button button-primary"
					>

						<?php esc_html_e(
							'Process Payout',
							'newsblenda-accounts'
						); ?>

					</button>

				</form>

			<?php else : ?>

				<span class="dashicons dashicons-lock"></span>

			<?php endif; ?>

			<form
				method="post"
				action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
			>

				<?php wp_nonce_field('nb_reject_payout'); ?>

				<input
					type="hidden"
					name="action"
					value="nb_reject_payout"
				>

				<input
					type="hidden"
					name="user_id"
					value="<?php echo esc_attr((string) $user_id); ?>"
				>

				<button
					type="submit"
					class="button"
				>

					<?php esc_html_e(
						'Reject',
						'newsblenda-accounts'
					); ?>

				</button>

			</form>

		</td>

	</tr>

<?php endforeach; ?>

		</tbody>

	</table>
    
    	<div class="card" style="margin-top:20px;">

		<h2>

			<?php esc_html_e(
				'Payout Information',
				'newsblenda-accounts'
			); ?>

		</h2>

		<p>

			<?php esc_html_e(
				'Only authors whose unpaid balance meets or exceeds the configured minimum payout threshold are eligible for payment.',
				'newsblenda-accounts'
			); ?>

		</p>

		<ul>

			<li>

				<?php esc_html_e(
					'Verify the author's payment information before processing any payout.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Processing a payout updates the author's paid amount and unpaid balance automatically.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Rejected payouts do not modify earnings or balances.',
					'newsblenda-accounts'
				); ?>

			</li>

			<li>

				<?php esc_html_e(
					'Every payout action is logged in the Newsblenda activity log.',
					'newsblenda-accounts'
				); ?>

			</li>

		</ul>

	</div>

	<div class="card" style="margin-top:20px;">

		<h2>

			<?php esc_html_e(
				'Export',
				'newsblenda-accounts'
			); ?>

		</h2>

		<p>

			<?php esc_html_e(
				'Developers can add CSV, Excel or PDF export functionality using the hook below.',
				'newsblenda-accounts'
			); ?>

		</p>

		<?php
		/**
		 * Fires inside the payout export section.
		 *
		 * Plugins can add export buttons,
		 * reporting tools or accounting integrations.
		 */
		do_action(
			'nb_accounts_payout_export'
		);
		?>

	</div>

	<?php
	/**
	 * Fires at the bottom of the payout management page.
	 *
	 * Developers can inject additional reports,
	 * payment gateways or reconciliation tools.
	 */
	do_action(
		'nb_accounts_payouts_footer',
		$authors
	);
	?>

</div>