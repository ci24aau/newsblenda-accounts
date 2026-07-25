<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$user = wp_get_current_user();

$published = (int) count_user_posts(
	$user->ID,
	'post',
	true
);

$pending = new WP_Query(
	[
		'post_type'      => 'post',
		'post_status'    => 'pending',
		'author'         => $user->ID,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	]
);

$drafts = new WP_Query(
	[
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'author'         => $user->ID,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	]
);

$status = get_user_meta(
	$user->ID,
	'nb_account_status',
	true
);

$status = $status ?: __('Pending', 'newsblenda-accounts');

$earnings = (float) get_user_meta(
	$user->ID,
	'nb_total_earnings',
	true
);

$profile_completion = 0;

$fields = [
	'first_name',
	'last_name',
	'description',
	'nb_phone',
	'nb_country',
	'nb_state',
	'nb_city',
	'nb_niche',
	'nb_payment_method',
	'nb_whatsapp',
	'nb_gender',
];

foreach ($fields as $field) {

	if (! empty(get_user_meta($user->ID, $field, true))) {

		$profile_completion++;

	}

}

$profile_completion = (int) round(
	($profile_completion / count($fields)) * 100
);

$cards = [

	[
		'title' => __('Published Articles', 'newsblenda-accounts'),
		'value' => $published,
	],

	[
		'title' => __('Pending Review', 'newsblenda-accounts'),
		'value' => (int) $pending->found_posts,
	],

	[
		'title' => __('Draft Articles', 'newsblenda-accounts'),
		'value' => (int) $drafts->found_posts,
	],

	[
		'title' => __('Account Status', 'newsblenda-accounts'),
		'value' => ucfirst((string) $status),
	],

	[
		'title' => __('Profile Completion', 'newsblenda-accounts'),
		'value' => $profile_completion . '%',
	],

	[
		'title' => __('Total Earnings', 'newsblenda-accounts'),
		'value' => '£' . number_format($earnings, 2),
	],

];
?>

<div class="nba-dashboard">

	<header class="nba-dashboard-header">

		<div>

			<h1>

				<?php
				printf(
					esc_html__('Welcome, %s', 'newsblenda-accounts'),
					esc_html($user->display_name)
				);
				?>

			</h1>

			<p>

				<?php esc_html_e(
					'Manage your Newsblenda author account from one place.',
					'newsblenda-accounts'
				); ?>

			</p>

		</div>

		<div class="nba-dashboard-buttons">

			<a class="button button-primary"
				href="<?php echo esc_url(home_url('/submit/')); ?>">

				<?php esc_html_e(
					'Submit Article',
					'newsblenda-accounts'
				); ?>

			</a>

			<a class="button"
				href="<?php echo esc_url(home_url('/profile/')); ?>">

				<?php esc_html_e(
					'Edit Profile',
					'newsblenda-accounts'
				); ?>

			</a>

		</div>

	</header>

	<div class="nba-dashboard-grid">

		<?php foreach ($cards as $card) : ?>

			<div class="nba-card">

				<h3><?php echo esc_html($card['title']); ?></h3>

				<div class="nba-card-number">

					<?php echo esc_html((string) $card['value']); ?>

				</div>

			</div>

		<?php endforeach; ?>

	</div>
    
    	<section class="nba-dashboard-actions">

		<h2>

			<?php esc_html_e(
				'Quick Actions',
				'newsblenda-accounts'
			); ?>

		</h2>

		<div class="nba-action-buttons">

			<a class="button button-primary"
				href="<?php echo esc_url(home_url('/submit/')); ?>">

				<?php esc_html_e(
					'Submit New Article',
					'newsblenda-accounts'
				); ?>

			</a>

			<a class="button"
				href="<?php echo esc_url(home_url('/profile/')); ?>">

				<?php esc_html_e(
					'Edit Profile',
					'newsblenda-accounts'
				); ?>

			</a>

			<a class="button"
				href="<?php echo esc_url(home_url('/notifications/')); ?>">

				<?php esc_html_e(
					'Notifications',
					'newsblenda-accounts'
				); ?>

			</a>

			<a class="button"
				href="<?php echo esc_url(home_url('/earnings/')); ?>">

				<?php esc_html_e(
					'Earnings',
					'newsblenda-accounts'
				); ?>

			</a>

		</div>

	</section>

	<div class="nba-dashboard-columns">

		<div class="nba-dashboard-column">

			<div class="nba-card">

				<h2>

					<?php esc_html_e(
						'Account Summary',
						'newsblenda-accounts'
					); ?>

				</h2>

				<table class="nba-summary-table">

					<tr>

						<th><?php esc_html_e('Username', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html($user->user_login); ?></td>

					</tr>

					<tr>

						<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html($user->user_email); ?></td>

					</tr>

					<tr>

						<th><?php esc_html_e('Role', 'newsblenda-accounts'); ?></th>

						<td>

							<?php
							echo esc_html(
								implode(', ', $user->roles)
							);
							?>

						</td>

					</tr>

					<tr>

						<th><?php esc_html_e('Member Since', 'newsblenda-accounts'); ?></th>

						<td>

							<?php
							echo esc_html(
								mysql2date(
									get_option('date_format'),
									$user->user_registered
								)
							);
							?>

						</td>

					</tr>

					<tr>

						<th><?php esc_html_e('Account Status', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html(ucfirst((string) $status)); ?></td>

					</tr>

				</table>

			</div>

		</div>

		<div class="nba-dashboard-column">

			<div class="nba-card">

				<h2>

					<?php esc_html_e(
						'Latest Notifications',
						'newsblenda-accounts'
					); ?>

				</h2>

				<?php
				$notifications = get_posts([
					'post_type'      => 'notification',
					'post_status'    => 'publish',
					'posts_per_page' => 3,
				]);

				if (! empty($notifications)) :
					foreach ($notifications as $notification) :
						?>
						<div class="nba-notification-item">
							<strong><?php echo esc_html($notification->post_title); ?></strong>
							<p><?php echo esc_html(wp_trim_words($notification->post_content, 18)); ?></p>
						</div>
					<?php
					endforeach;
				else :
					?><p><?php esc_html_e('You have no new notifications.', 'newsblenda-accounts'); ?></p><?php
				endif;
				?>

			</div>

		</div>

	</div>
    
    	<section class="nba-dashboard-recent">

		<h2>

			<?php esc_html_e(
				'Recent Articles',
				'newsblenda-accounts'
			); ?>

		</h2>

		<?php

		$recent_posts = get_posts(
			[
				'post_type'      => 'post',
				'author'         => $user->ID,
				'posts_per_page' => 5,
				'post_status'    => [
					'publish',
					'pending',
					'draft',
				],
			]
		);

		if (! empty($recent_posts)) :
		?>

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
								'Status',
								'newsblenda-accounts'
							); ?>

						</th>

						<th>

							<?php esc_html_e(
								'Date',
								'newsblenda-accounts'
							); ?>

						</th>

					</tr>

				</thead>

				<tbody>

					<?php foreach ($recent_posts as $post) : ?>

						<tr>

							<td>

								<a href="<?php echo esc_url(get_permalink($post)); ?>">

									<?php echo esc_html($post->post_title); ?>

								</a>

							</td>

							<td>

								<?php
								echo esc_html(
									ucfirst($post->post_status)
								);
								?>

							</td>

							<td>

								<?php
								echo esc_html(
									get_the_date(
										get_option('date_format'),
										$post
									)
								);
								?>

							</td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		<?php else : ?>

			<p>

				<?php esc_html_e(
					'You have not submitted any articles yet.',
					'newsblenda-accounts'
				); ?>

			</p>

		<?php endif; ?>

	</section>

	<section class="nba-dashboard-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Need Assistance?',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Keep your profile up to date, follow the Newsblenda editorial guidelines and monitor your dashboard regularly for article reviews, notifications and earnings updates.',
					'newsblenda-accounts'
				); ?>

			</p>

		</div>

	</section>

	<?php
	/**
	 * Fires at the bottom of the Newsblenda dashboard.
	 *
	 * Developers can use this hook to add custom dashboard widgets.
	 */
	do_action('nb_accounts_dashboard_footer');
	?>

</div>