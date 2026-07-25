<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

global $wpdb;

$table = $wpdb->prefix . 'nb_notifications';

$user_id = get_current_user_id();

$notifications = $wpdb->get_results(

	$wpdb->prepare(

		"
		SELECT *
		FROM {$table}
		WHERE user_id=%d
		ORDER BY created_at DESC
		",

		$user_id

	)

);

$unread = (int) $wpdb->get_var(

	$wpdb->prepare(

		"
		SELECT COUNT(*)
		FROM {$table}
		WHERE user_id=%d
		AND is_read=0
		",

		$user_id

	)

);

$total = count($notifications);

?>

<div class="nba-notifications-page">

	<header class="nba-page-header">

		<h1>

			<?php esc_html_e(
				'Notifications',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Stay up to date with your account, editorial reviews and earnings.',
				'newsblenda-accounts'
			); ?>

		</p>

	</header>

	<div class="nba-dashboard-grid">

		<div class="nba-card">

			<h3><?php esc_html_e('Total', 'newsblenda-accounts'); ?></h3>

			<div class="nba-card-number">

				<?php echo esc_html((string) $total); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3><?php esc_html_e('Unread', 'newsblenda-accounts'); ?></h3>

			<div class="nba-card-number">

				<?php echo esc_html((string) $unread); ?>

			</div>

		</div>

	</div>

	<div class="nba-notification-actions">

		<a
			class="button"
			href="<?php echo esc_url(add_query_arg('mark_all', '1')); ?>"
		>

			<?php esc_html_e(
				'Mark All Read',
				'newsblenda-accounts'
			); ?>

		</a>

	</div>
    
    	<?php if (empty($notifications)) : ?>

		<div class="nba-empty-state">

			<div class="nba-card">

				<h2>

					<?php esc_html_e(
						'No Notifications',
						'newsblenda-accounts'
					); ?>

				</h2>

				<p>

					<?php esc_html_e(
						'You do not have any notifications yet.',
						'newsblenda-accounts'
					); ?>

				</p>

			</div>

		</div>

	<?php else : ?>

		<table class="nba-table">

			<thead>

				<tr>

					<th>

						<?php esc_html_e(
							'Title',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Message',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Date',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Status',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Actions',
							'newsblenda-accounts'
						); ?>

					</th>

				</tr>

			</thead>

			<tbody>

			<?php foreach ($notifications as $notification) : ?>

				<tr class="<?php echo $notification->is_read ? 'nba-read' : 'nba-unread'; ?>">

					<td>

						<?php
						echo esc_html(
							$notification->title ?: __('Notification', 'newsblenda-accounts')
						);
						?>

					</td>

					<td>

						<?php echo wp_kses_post($notification->message); ?>

						<?php if (! empty($notification->action_url)) : ?>

							<br><br>

							<a href="<?php echo esc_url($notification->action_url); ?>">

								<?php esc_html_e(
									'Open',
									'newsblenda-accounts'
								); ?>

							</a>

						<?php endif; ?>

					</td>

					<td>

						<?php

						echo esc_html(

							wp_date(

								get_option('date_format') . ' ' .

								get_option('time_format'),

								strtotime($notification->created_at)

							)

						);

						?>

					</td>

					<td>

						<?php

						echo $notification->is_read

							? esc_html__(
								'Read',
								'newsblenda-accounts'
							)

							: esc_html__(
								'Unread',
								'newsblenda-accounts'
							);

						?>

					</td>

					<td>

						<?php if (! $notification->is_read) : ?>

							<a
								class="button"
								href="<?php echo esc_url(add_query_arg([
									'mark_read' => (int) $notification->id,
								])); ?>"
							>

								<?php esc_html_e(
									'Mark Read',
									'newsblenda-accounts'
								); ?>

							</a>

						<?php endif; ?>

						<a
							class="button"
							href="<?php echo esc_url(add_query_arg([
								'delete_notification' => (int) $notification->id,
							])); ?>"
						>

							<?php esc_html_e(
								'Delete',
								'newsblenda-accounts'
							); ?>

						</a>

					</td>

				</tr>

			<?php endforeach; ?>

			</tbody>

		</table>

	<?php endif; ?>
    
    	<section class="nba-notifications-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Notification Centre',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Notifications keep you informed about article reviews, editorial decisions, account updates, earnings, payouts and other important Newsblenda activities.',
					'newsblenda-accounts'
				); ?>

			</p>

			<ul>

				<li>

					<?php esc_html_e(
						'Review new notifications regularly.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Complete any requested article revisions promptly.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Keep your profile and payment information up to date.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Important account announcements will always appear here.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>

	</section>

	<?php if ($total > 20) : ?>

		<div class="nba-pagination">

			<?php
			/**
			 * Placeholder for future pagination support.
			 */
			do_action(
				'nb_accounts_notifications_pagination',
				$total
			);
			?>

		</div>

	<?php endif; ?>

	<?php
	/**
	 * Fires after the notifications table.
	 *
	 * Developers can use this hook to add custom
	 * notification widgets or extensions.
	 */
	do_action(
		'nb_accounts_notifications_footer',
		$notifications
	);
	?>

</div>