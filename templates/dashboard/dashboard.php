<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use Newsblenda\Accounts\Workflow\WorkflowManager;

$user = wp_get_current_user();

/*
|--------------------------------------------------------------------------
| Stats
|--------------------------------------------------------------------------
*/
$cache_version = (string) get_option('nb_dashboard_cache_version', '1');
$cache_key     = 'nb_dashboard_author_' . $user->ID . '_' . md5($cache_version);
$cached_data   = get_transient($cache_key);

\Newsblenda\Accounts\Dashboard\Dashboard::track_cache_event(
	'author_dashboard',
	$cached_data !== false
);

if ($cached_data === false) {
	global $wpdb;

	$stats = (array) $wpdb->get_row(
		$wpdb->prepare(
			"SELECT
				COUNT(CASE WHEN p.post_status = 'publish' THEN 1 END) AS published_count,
				COUNT(CASE WHEN p.post_status = 'pending' THEN 1 END) AS pending_count,
				COUNT(CASE WHEN p.post_status = 'draft' AND (pm.meta_value IS NULL OR pm.meta_value = %s) THEN 1 END) AS draft_count,
				COUNT(CASE WHEN p.post_status = 'draft' AND pm.meta_value = %s THEN 1 END) AS rejected_count,
				COUNT(CASE WHEN p.post_status = 'draft' AND pm.meta_value = %s THEN 1 END) AS revision_count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm
				ON pm.post_id = p.ID
				AND pm.meta_key = 'nb_workflow_status'
			WHERE p.post_type = 'post'
				AND p.post_author = %d",
			WorkflowManager::STATUS_DRAFT,
			WorkflowManager::STATUS_REJECTED,
			WorkflowManager::STATUS_REVISION_REQUESTED,
			$user->ID
		),
		ARRAY_A
	);

	$published_count = (int) ($stats['published_count'] ?? 0);
	$pending_count   = (int) ($stats['pending_count'] ?? 0);
	$draft_count     = (int) ($stats['draft_count'] ?? 0);
	$rejected_count  = (int) ($stats['rejected_count'] ?? 0);
	$revision_count  = (int) ($stats['revision_count'] ?? 0);

	$total_submissions = $published_count
		+ $pending_count
		+ $draft_count
		+ $rejected_count
		+ $revision_count;

	$total_views = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(pm.meta_value), 0)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_author = %d
				AND pm.meta_key = 'nb_post_views'",
			$user->ID
		)
	);

	$total_earnings = (float) get_user_meta($user->ID, 'nb_total_earnings', true);

	$unpaid_balance = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(amount), 0)
			FROM {$wpdb->prefix}nb_earnings
			WHERE user_id = %d AND status = 'unpaid'",
			$user->ID
		)
	);

	$base_post_args = [
		'post_type'              => 'post',
		'author'                 => $user->ID,
		'posts_per_page'         => 5,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'cache_results'          => true,
	];

	$recent_posts = get_posts(array_merge($base_post_args, [
		'post_status' => ['publish', 'pending', 'draft', 'future'],
		'orderby'     => 'modified',
		'order'       => 'DESC',
	]));

	$pending_posts = get_posts(array_merge($base_post_args, [
		'post_status' => 'pending',
		'orderby'     => 'date',
		'order'       => 'ASC',
	]));

	$revision_posts = get_posts(array_merge($base_post_args, [
		'post_status' => 'draft',
		'meta_key'    => 'nb_workflow_status',
		'meta_value'  => WorkflowManager::STATUS_REVISION_REQUESTED,
		'orderby'     => 'date',
		'order'       => 'DESC',
	]));

	$rejected_posts = get_posts(array_merge($base_post_args, [
		'post_status' => 'draft',
		'meta_key'    => 'nb_workflow_status',
		'meta_value'  => WorkflowManager::STATUS_REJECTED,
		'orderby'     => 'date',
		'order'       => 'DESC',
	]));

	$draft_posts = get_posts(array_merge($base_post_args, [
		'post_status' => 'draft',
		'meta_query'  => [
			'relation' => 'OR',
			[
				'key'     => 'nb_workflow_status',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'   => 'nb_workflow_status',
				'value' => WorkflowManager::STATUS_DRAFT,
			],
		],
		'orderby'     => 'modified',
		'order'       => 'DESC',
	]));

	$profile_fields = [
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

	$profile_completed = 0;
	foreach ($profile_fields as $field) {
		if (! empty(get_user_meta($user->ID, $field, true))) {
			$profile_completed++;
		}
	}

	$profile_percent = (int) round(
		($profile_completed / count($profile_fields)) * 100
	);

	$notifications = \Newsblenda\Accounts\Notifications\Notifications::get_latest($user->ID, 4);
	$unread_count  = \Newsblenda\Accounts\Notifications\Notifications::get_unread_count($user->ID);

	$cached_data = compact(
		'total_submissions',
		'published_count',
		'pending_count',
		'draft_count',
		'rejected_count',
		'revision_count',
		'total_views',
		'total_earnings',
		'unpaid_balance',
		'recent_posts',
		'pending_posts',
		'revision_posts',
		'rejected_posts',
		'draft_posts',
		'profile_percent',
		'notifications',
		'unread_count'
	);

	$post_sets = array_merge(
		$recent_posts,
		$pending_posts,
		$revision_posts,
		$rejected_posts,
		$draft_posts
	);

	$post_ids = array_values(array_unique(array_map(
		static fn($post) => (int) $post->ID,
		$post_sets
	)));

	if (! empty($post_ids)) {
		update_meta_cache('post', $post_ids);
		if (function_exists('update_post_author_caches')) {
			update_post_author_caches($post_sets);
		}
	}

	$author_ids = array_values(array_unique(array_map(
		static fn($post) => (int) $post->post_author,
		$post_sets
	)));

	if (! empty($author_ids)) {
		cache_users($author_ids);
	}

	set_transient($cache_key, $cached_data, HOUR_IN_SECONDS);
}

extract($cached_data, EXTR_SKIP);

/*
|--------------------------------------------------------------------------
| Helper: Status Badge
|--------------------------------------------------------------------------
*/

$badge = static function (string $status): string {
	$label = WorkflowManager::status_label($status);
	$class = WorkflowManager::status_badge_class($status);
	return '<span class="nba-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
};
?>

<div class="nba-dashboard">

	<header class="nba-dashboard-header">

		<div class="nba-dashboard-header-info">

			<h1>
				<?php
				printf(
					/* translators: %s: author display name */
					esc_html__('Welcome, %s', 'newsblenda-accounts'),
					esc_html($user->display_name)
				);
				?>
			</h1>

			<p>
				<span class="nba-badge nba-badge-info"><?php esc_html_e('Author', 'newsblenda-accounts'); ?></span>
				<?php esc_html_e('Manage your submissions and track your earnings from one place.', 'newsblenda-accounts'); ?>
			</p>

		</div>

		<div class="nba-dashboard-header-actions">

			<?php if (current_user_can('nb_submit_articles')) : ?>
				<a class="nba-btn nba-btn-primary"
				   href="<?php echo esc_url(home_url('/submit/')); ?>">
					<?php esc_html_e('New Submission', 'newsblenda-accounts'); ?>
				</a>
			<?php endif; ?>

			<a class="nba-btn nba-btn-secondary"
			   href="<?php echo esc_url(home_url('/profile/')); ?>">
				<?php esc_html_e('Edit Profile', 'newsblenda-accounts'); ?>
			</a>

		</div>

	</header>

	<?php if (isset($_GET['nb_action_success'])) : ?>
		<div class="nba-notice nba-notice-success">
			<?php esc_html_e('Action completed successfully.', 'newsblenda-accounts'); ?>
		</div>
	<?php endif; ?>

	<?php if (isset($_GET['nb_action_error'])) : ?>
		<div class="nba-notice nba-notice-error">
			<?php esc_html_e('An error occurred. Please try again.', 'newsblenda-accounts'); ?>
		</div>
	<?php endif; ?>

	<!-- Stats Grid -->
	<div class="nba-stat-grid">

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Total Submissions', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value"><?php echo esc_html((string) $total_submissions); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Published', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-success"><?php echo esc_html((string) $published_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-warning"><?php echo esc_html((string) $pending_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Revision Requested', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-warning"><?php echo esc_html((string) $revision_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Rejected', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-danger"><?php echo esc_html((string) $rejected_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Drafts', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value"><?php echo esc_html((string) $draft_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Total Views', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value"><?php echo esc_html(number_format($total_views)); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Total Earnings', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-success"><?php echo esc_html('£' . number_format($total_earnings, 2)); ?></div>
		</div>

	</div>

	<div class="nba-dashboard-columns">

		<!-- Main Content -->
		<div class="nba-dashboard-main">

			<!-- Recent Submissions -->
			<div class="nba-card">

				<div class="nba-card-header">
					<h2><?php esc_html_e('Recent Submissions', 'newsblenda-accounts'); ?></h2>
					<?php if (current_user_can('nb_submit_articles')) : ?>
						<a href="<?php echo esc_url(home_url('/submit/')); ?>" class="nba-btn nba-btn-sm nba-btn-primary">
							<?php esc_html_e('New Submission', 'newsblenda-accounts'); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php if (empty($recent_posts)) : ?>
					<div class="nba-empty">
						<p><?php esc_html_e('You have not submitted any articles yet.', 'newsblenda-accounts'); ?></p>
						<?php if (current_user_can('nb_submit_articles')) : ?>
							<a href="<?php echo esc_url(home_url('/submit/')); ?>" class="nba-btn nba-btn-primary">
								<?php esc_html_e('Submit Your First Article', 'newsblenda-accounts'); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="nba-table-wrap">
						<table class="nba-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($recent_posts as $post) :
									$wf_status = WorkflowManager::status($post->ID);
									?>
									<tr>
										<td class="nba-col-title">
											<?php if ($post->post_status === 'publish') : ?>
												<a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a>
											<?php else : ?>
												<?php echo esc_html($post->post_title); ?>
											<?php endif; ?>
										</td>
										<td><?php echo wp_kses_post($badge($wf_status)); ?></td>
										<td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
										<td class="nba-col-actions">
											<?php if ($post->post_status === 'publish') : ?>
												<a href="<?php echo esc_url(get_permalink($post)); ?>" class="nba-btn nba-btn-sm">
													<?php esc_html_e('View', 'newsblenda-accounts'); ?>
												</a>
											<?php endif; ?>
											<?php if (
												$post->post_status === 'draft'
												&& in_array($wf_status, [WorkflowManager::STATUS_DRAFT, WorkflowManager::STATUS_REJECTED, WorkflowManager::STATUS_REVISION_REQUESTED], true)
												&& current_user_can('edit_post', $post->ID)
											) : ?>
												<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="nba-btn nba-btn-sm">
													<?php esc_html_e('Edit', 'newsblenda-accounts'); ?>
												</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

			</div>

			<!-- Revision Requested -->
			<?php if (! empty($revision_posts)) : ?>
			<div class="nba-card nba-card-warning">

				<div class="nba-card-header">
					<h2><?php esc_html_e('Revision Requested', 'newsblenda-accounts'); ?></h2>
					<span class="nba-badge nba-badge-warning"><?php echo esc_html((string) count($revision_posts)); ?></span>
				</div>

				<p class="nba-card-desc">
					<?php esc_html_e('The following articles have been returned for revision. Review the editor feedback and resubmit when ready.', 'newsblenda-accounts'); ?>
				</p>

				<div class="nba-table-wrap">
					<table class="nba-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Editor Feedback', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($revision_posts as $post) :
								$feedback = WorkflowManager::revision_requests($post->ID);
								?>
								<tr>
									<td><?php echo esc_html($post->post_title); ?></td>
									<td class="nba-col-feedback">
										<?php echo $feedback ? '<em>' . esc_html($feedback) . '</em>' : '<span class="nba-muted">' . esc_html__('No feedback provided.', 'newsblenda-accounts') . '</span>'; ?>
									</td>
									<td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
									<td class="nba-col-actions">
										<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="nba-btn nba-btn-sm">
											<?php esc_html_e('Edit', 'newsblenda-accounts'); ?>
										</a>
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-primary nba-resubmit-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
											<?php esc_html_e('Resubmit', 'newsblenda-accounts'); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
			<?php endif; ?>

			<!-- Rejected Articles -->
			<?php if (! empty($rejected_posts)) : ?>
			<div class="nba-card nba-card-danger">

				<div class="nba-card-header">
					<h2><?php esc_html_e('Rejected Articles', 'newsblenda-accounts'); ?></h2>
					<span class="nba-badge nba-badge-danger"><?php echo esc_html((string) count($rejected_posts)); ?></span>
				</div>

				<p class="nba-card-desc">
					<?php esc_html_e('These articles were not accepted. Edit and resubmit them for another review.', 'newsblenda-accounts'); ?>
				</p>

				<div class="nba-table-wrap">
					<table class="nba-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Rejection Reason', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rejected_posts as $post) :
								$reason = WorkflowManager::rejection_reason($post->ID);
								?>
								<tr>
									<td><?php echo esc_html($post->post_title); ?></td>
									<td class="nba-col-feedback">
										<?php echo $reason ? '<em>' . esc_html($reason) . '</em>' : '<span class="nba-muted">' . esc_html__('No reason provided.', 'newsblenda-accounts') . '</span>'; ?>
									</td>
									<td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
									<td class="nba-col-actions">
										<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="nba-btn nba-btn-sm">
											<?php esc_html_e('Edit', 'newsblenda-accounts'); ?>
										</a>
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-primary nba-resubmit-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
											<?php esc_html_e('Resubmit', 'newsblenda-accounts'); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
			<?php endif; ?>

			<!-- Pending Review -->
			<?php if (! empty($pending_posts)) : ?>
			<div class="nba-card">

				<div class="nba-card-header">
					<h2><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></h2>
					<span class="nba-badge nba-badge-warning"><?php echo esc_html((string) count($pending_posts)); ?></span>
				</div>

				<p class="nba-card-desc">
					<?php esc_html_e('These articles are awaiting review by an editor.', 'newsblenda-accounts'); ?>
				</p>

				<div class="nba-table-wrap">
					<table class="nba-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Submitted', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Time Pending', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($pending_posts as $post) :
								$submitted = get_post_meta($post->ID, 'nb_workflow_changed_at', true);
								$pending_days = $submitted
									? floor((time() - strtotime($submitted)) / DAY_IN_SECONDS)
									: '';
								?>
								<tr>
									<td><?php echo esc_html($post->post_title); ?></td>
									<td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
									<td>
										<?php if ($pending_days !== '') : ?>
											<?php echo esc_html(sprintf(
												/* translators: %d: number of days */
												_n('%d day', '%d days', (int) $pending_days, 'newsblenda-accounts'),
												(int) $pending_days
											)); ?>
										<?php else : ?>
											<?php esc_html_e('Today', 'newsblenda-accounts'); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
			<?php endif; ?>

			<!-- Drafts -->
			<?php if (! empty($draft_posts)) : ?>
			<div class="nba-card">

				<div class="nba-card-header">
					<h2><?php esc_html_e('Drafts', 'newsblenda-accounts'); ?></h2>
					<span class="nba-badge nba-badge-info"><?php echo esc_html((string) count($draft_posts)); ?></span>
				</div>

				<div class="nba-table-wrap">
					<table class="nba-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Last Edited', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($draft_posts as $post) : ?>
								<tr data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
									<td><?php echo esc_html($post->post_title ?: __('(Untitled)', 'newsblenda-accounts')); ?></td>
									<td><?php echo esc_html(
										mysql2date(
											get_option('date_format'),
											$post->post_modified
										)
									); ?></td>
									<td class="nba-col-actions">
										<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="nba-btn nba-btn-sm">
											<?php esc_html_e('Edit', 'newsblenda-accounts'); ?>
										</a>
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-danger nba-delete-draft-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
											<?php esc_html_e('Delete', 'newsblenda-accounts'); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
			<?php endif; ?>

		</div>

		<!-- Sidebar -->
		<div class="nba-dashboard-sidebar-col">

			<!-- Earnings Summary -->
			<div class="nba-card">
				<h3><?php esc_html_e('Earnings', 'newsblenda-accounts'); ?></h3>
				<div class="nba-earnings-summary">
					<div class="nba-earnings-row">
						<span><?php esc_html_e('Total Earned', 'newsblenda-accounts'); ?></span>
						<strong><?php echo esc_html('£' . number_format($total_earnings, 2)); ?></strong>
					</div>
					<div class="nba-earnings-row">
						<span><?php esc_html_e('Unpaid Balance', 'newsblenda-accounts'); ?></span>
						<strong class="nba-stat-success"><?php echo esc_html('£' . number_format($unpaid_balance, 2)); ?></strong>
					</div>
				</div>
				<a href="<?php echo esc_url(home_url('/earnings/')); ?>" class="nba-btn nba-btn-block">
					<?php esc_html_e('View Earnings', 'newsblenda-accounts'); ?>
				</a>
			</div>

			<!-- Profile Completion -->
			<div class="nba-card">
				<h3><?php esc_html_e('Profile Completion', 'newsblenda-accounts'); ?></h3>
				<div class="nba-progress-wrap">
					<div class="nba-progress-bar" role="progressbar"
					     aria-valuenow="<?php echo esc_attr((string) $profile_percent); ?>"
					     aria-valuemin="0" aria-valuemax="100">
						<div class="nba-progress-fill" style="width:<?php echo esc_attr((string) $profile_percent); ?>%"></div>
					</div>
					<span class="nba-progress-label"><?php echo esc_html((string) $profile_percent); ?>%</span>
				</div>
				<?php if ($profile_percent < 100) : ?>
					<p class="nba-card-desc">
						<?php esc_html_e('Complete your profile to increase visibility and improve your chances of approval.', 'newsblenda-accounts'); ?>
					</p>
					<a href="<?php echo esc_url(home_url('/profile/')); ?>" class="nba-btn nba-btn-block">
						<?php esc_html_e('Complete Profile', 'newsblenda-accounts'); ?>
					</a>
				<?php endif; ?>
			</div>

			<!-- Notifications -->
			<div class="nba-card">
				<div class="nba-card-header">
					<h3><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h3>
					<?php if ($unread_count > 0) : ?>
						<span class="nba-badge nba-badge-danger"><?php echo esc_html((string) $unread_count); ?></span>
					<?php endif; ?>
				</div>

				<?php if (empty($notifications)) : ?>
					<p class="nba-muted"><?php esc_html_e('No notifications.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<ul class="nba-notification-list">
						<?php foreach ($notifications as $notification) : ?>
							<li class="<?php echo $notification->is_read ? '' : 'nba-unread'; ?>">
								<strong><?php echo esc_html($notification->title); ?></strong>
								<p><?php echo esc_html(wp_trim_words($notification->message, 12)); ?></p>
								<span class="nba-muted nba-text-sm">
									<?php echo esc_html(
										wp_date(
											get_option('date_format'),
											strtotime($notification->created_at)
										)
									); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url(home_url('/notifications/')); ?>" class="nba-btn nba-btn-block">
						<?php esc_html_e('View All Notifications', 'newsblenda-accounts'); ?>
					</a>
				<?php endif; ?>
			</div>

			<!-- Quick Links -->
			<div class="nba-card">
				<h3><?php esc_html_e('Quick Links', 'newsblenda-accounts'); ?></h3>
				<ul class="nba-quick-links">
					<?php if (current_user_can('nb_submit_articles')) : ?>
						<li><a href="<?php echo esc_url(home_url('/submit/')); ?>">&#43; <?php esc_html_e('Submit New Article', 'newsblenda-accounts'); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url(home_url('/profile/')); ?>"><?php esc_html_e('Edit Profile', 'newsblenda-accounts'); ?></a></li>
					<li><a href="<?php echo esc_url(home_url('/earnings/')); ?>"><?php esc_html_e('View Earnings', 'newsblenda-accounts'); ?></a></li>
					<li><a href="<?php echo esc_url(home_url('/notifications/')); ?>"><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></a></li>
				</ul>
			</div>

		</div>

	</div>

	<?php do_action('nb_accounts_dashboard_footer'); ?>

</div>
