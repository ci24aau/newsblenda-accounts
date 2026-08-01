<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use Newsblenda\Accounts\Workflow\WorkflowManager;

if (! current_user_can('nb_review_articles') && ! current_user_can('manage_options')) {
	wp_safe_redirect(home_url('/dashboard/'));
	exit;
}

$editor = wp_get_current_user();

$search = isset($_GET['nb_s'])
	? sanitize_text_field(wp_unslash($_GET['nb_s']))
	: '';

$filter_author = isset($_GET['nb_author'])
	? absint($_GET['nb_author'])
	: 0;

/*
|--------------------------------------------------------------------------
| Review Queue (Pending Review)
|--------------------------------------------------------------------------
*/

$queue_args = [
	'post_type'      => 'post',
	'post_status'    => 'pending',
	'posts_per_page' => 20,
	'orderby'        => 'date',
	'order'          => 'ASC',
	's'              => $search,
];

if ($filter_author > 0) {
	$queue_args['author'] = $filter_author;
}

$queue_posts = get_posts($queue_args);

/*
|--------------------------------------------------------------------------
| Revision Requests
|--------------------------------------------------------------------------
*/

$revision_args = [
	'post_type'      => 'post',
	'post_status'    => 'draft',
	'meta_key'       => 'nb_workflow_status',
	'meta_value'     => WorkflowManager::STATUS_REVISION_REQUESTED,
	'posts_per_page' => 10,
	'orderby'        => 'modified',
	'order'          => 'DESC',
];

if ($filter_author > 0) {
	$revision_args['author'] = $filter_author;
}

$revision_posts = get_posts($revision_args);

/*
|--------------------------------------------------------------------------
| Recently Approved
|--------------------------------------------------------------------------
*/

$approved_posts = get_posts([
	'post_type'      => 'post',
	'post_status'    => 'draft',
	'meta_key'       => 'nb_workflow_status',
	'meta_value'     => WorkflowManager::STATUS_APPROVED,
	'posts_per_page' => 8,
	'orderby'        => 'modified',
	'order'          => 'DESC',
]);

/*
|--------------------------------------------------------------------------
| Published Articles
|--------------------------------------------------------------------------
*/

$published_posts = get_posts([
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 8,
	'orderby'        => 'date',
	'order'          => 'DESC',
	's'              => $search,
]);

/*
|--------------------------------------------------------------------------
| Stats
|--------------------------------------------------------------------------
*/

$pending_count  = count($queue_posts);
$revision_count = count($revision_posts);
$approved_count = count($approved_posts);

$published_query = new WP_Query([
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'no_found_rows'  => false,
]);

$total_published = (int) $published_query->found_posts;

/*
|--------------------------------------------------------------------------
| Author List
|--------------------------------------------------------------------------
*/

$authors = get_users([
	'role__in' => ['nb_author', 'nb_author_pending', 'nb_author_restricted'],
	'number'   => 10,
	'orderby'  => 'registered',
	'order'    => 'DESC',
]);

/*
|--------------------------------------------------------------------------
| Notifications for editor
|--------------------------------------------------------------------------
*/

$editor_notifs = \Newsblenda\Accounts\Notifications\Notifications::get_latest($editor->ID, 5);
$unread_count  = \Newsblenda\Accounts\Notifications\Notifications::get_unread_count($editor->ID);

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

<div class="nba-editor-dashboard">

	<!-- Header -->
	<header class="nba-dashboard-header">

		<div class="nba-dashboard-header-info">

			<h1><?php esc_html_e('Editor Dashboard', 'newsblenda-accounts'); ?></h1>

			<p>
				<span class="nba-badge nba-badge-info"><?php esc_html_e('Editor', 'newsblenda-accounts'); ?></span>
				<?php esc_html_e('Review submissions, manage the editorial queue and publish approved articles.', 'newsblenda-accounts'); ?>
			</p>

		</div>

		<div class="nba-dashboard-header-actions">

			<a href="<?php echo esc_url(home_url('/notifications/')); ?>"
			   class="nba-btn nba-btn-secondary nba-notif-btn">
				<?php esc_html_e('Notifications', 'newsblenda-accounts'); ?>
				<?php if ($unread_count > 0) : ?>
					<span class="nba-badge nba-badge-danger nba-badge-sm"><?php echo esc_html((string) $unread_count); ?></span>
				<?php endif; ?>
			</a>

			<a href="<?php echo esc_url(home_url('/profile/')); ?>"
			   class="nba-btn nba-btn-secondary">
				<?php esc_html_e('Profile', 'newsblenda-accounts'); ?>
			</a>

		</div>

	</header>

	<!-- Stats Grid -->
	<div class="nba-stat-grid">

		<div class="nba-stat-card nba-stat-card-highlight">
			<h4><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-warning"><?php echo esc_html((string) $pending_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Revision Requests', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-warning"><?php echo esc_html((string) $revision_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Approved (Unpublished)', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value nba-stat-success"><?php echo esc_html((string) $approved_count); ?></div>
		</div>

		<div class="nba-stat-card">
			<h4><?php esc_html_e('Total Published', 'newsblenda-accounts'); ?></h4>
			<div class="nba-stat-value"><?php echo esc_html((string) $total_published); ?></div>
		</div>

	</div>

	<!-- Search / Filter Toolbar -->
	<div class="nba-card nba-editor-toolbar">

		<form method="get" class="nba-editor-search-form">
			<label for="nb_s" class="screen-reader-text">
				<?php esc_html_e('Search Articles', 'newsblenda-accounts'); ?>
			</label>
			<input id="nb_s" type="search" name="nb_s"
			       value="<?php echo esc_attr($search); ?>"
			       placeholder="<?php esc_attr_e('Search by title…', 'newsblenda-accounts'); ?>">

			<select name="nb_author" id="nb_author_filter">
				<option value=""><?php esc_html_e('All Authors', 'newsblenda-accounts'); ?></option>
				<?php foreach ($authors as $a) : ?>
					<option value="<?php echo esc_attr((string) $a->ID); ?>"
					<?php selected($filter_author, $a->ID); ?>>
						<?php echo esc_html($a->display_name); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="nba-btn nba-btn-primary">
				<?php esc_html_e('Filter', 'newsblenda-accounts'); ?>
			</button>

			<?php if ($search !== '' || $filter_author > 0) : ?>
				<a href="<?php echo esc_url(home_url('/editor-dashboard/')); ?>" class="nba-btn">
					<?php esc_html_e('Clear', 'newsblenda-accounts'); ?>
				</a>
			<?php endif; ?>
		</form>

	</div>

	<!-- Review Queue -->
	<section class="nba-editor-section">

		<div class="nba-card">

			<div class="nba-card-header">
				<h2><?php esc_html_e('Review Queue', 'newsblenda-accounts'); ?></h2>
				<?php if ($pending_count > 0) : ?>
					<span class="nba-badge nba-badge-warning"><?php echo esc_html((string) $pending_count); ?></span>
				<?php endif; ?>
			</div>

			<?php if (empty($queue_posts)) : ?>

				<div class="nba-empty">
					<p><?php esc_html_e('No pending submissions. Check back later.', 'newsblenda-accounts'); ?></p>
				</div>

			<?php else : ?>

				<div class="nba-table-wrap">
					<table class="nba-table nba-review-table" id="nba-review-queue">
						<thead>
							<tr>
								<th><?php esc_html_e('Article Title', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Submitted', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Words', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($queue_posts as $post) :
								$author        = get_userdata($post->post_author);
								$word_count    = str_word_count(wp_strip_all_tags($post->post_content));
								$submitted_at  = get_post_meta($post->ID, 'nb_workflow_changed_at', true);
								?>
								<tr data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
									<td class="nba-col-title">
										<strong><?php echo esc_html($post->post_title); ?></strong>
									</td>
									<td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'newsblenda-accounts')); ?></td>
									<td><?php echo esc_html(
										$submitted_at
											? mysql2date(get_option('date_format'), $submitted_at)
											: get_the_date(get_option('date_format'), $post)
									); ?></td>
									<td><?php echo esc_html(number_format($word_count)); ?></td>
									<td class="nba-col-actions nba-review-actions">
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-success nba-approve-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
										        data-title="<?php echo esc_attr($post->post_title); ?>">
											<?php esc_html_e('Approve', 'newsblenda-accounts'); ?>
										</button>
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-warning nba-revision-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
										        data-title="<?php echo esc_attr($post->post_title); ?>"
										        data-modal="#nba-modal-revision">
											<?php esc_html_e('Request Revision', 'newsblenda-accounts'); ?>
										</button>
										<button type="button"
										        class="nba-btn nba-btn-sm nba-btn-danger nba-reject-btn"
										        data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
										        data-title="<?php echo esc_attr($post->post_title); ?>"
										        data-modal="#nba-modal-reject">
											<?php esc_html_e('Reject', 'newsblenda-accounts'); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			<?php endif; ?>

		</div>

	</section>

	<!-- Revision Requests Pending -->
	<?php if (! empty($revision_posts)) : ?>
	<section class="nba-editor-section">

		<div class="nba-card">

			<div class="nba-card-header">
				<h2><?php esc_html_e('Revision Requests Pending', 'newsblenda-accounts'); ?></h2>
				<span class="nba-badge nba-badge-warning"><?php echo esc_html((string) count($revision_posts)); ?></span>
			</div>

			<p class="nba-card-desc">
				<?php esc_html_e('Authors are currently revising these articles per your feedback.', 'newsblenda-accounts'); ?>
			</p>

			<div class="nba-table-wrap">
				<table class="nba-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Article Title', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Feedback Sent', 'newsblenda-accounts'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($revision_posts as $post) :
							$author   = get_userdata($post->post_author);
							$feedback = WorkflowManager::revision_requests($post->ID);
							?>
							<tr>
								<td><?php echo esc_html($post->post_title); ?></td>
								<td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'newsblenda-accounts')); ?></td>
								<td class="nba-col-feedback">
									<?php echo $feedback ? '<em>' . esc_html(wp_trim_words($feedback, 10)) . '</em>' : '&mdash;'; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

		</div>

	</section>
	<?php endif; ?>

	<!-- Approved: Ready to Publish -->
	<?php if (! empty($approved_posts)) : ?>
	<section class="nba-editor-section">

		<div class="nba-card">

			<div class="nba-card-header">
				<h2><?php esc_html_e('Approved — Ready to Publish', 'newsblenda-accounts'); ?></h2>
				<span class="nba-badge nba-badge-success"><?php echo esc_html((string) count($approved_posts)); ?></span>
			</div>

			<div class="nba-table-wrap">
				<table class="nba-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Article Title', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Approved', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($approved_posts as $post) :
							$author     = get_userdata($post->post_author);
							$changed_at = get_post_meta($post->ID, 'nb_workflow_changed_at', true);
							?>
							<tr data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
								<td><?php echo esc_html($post->post_title); ?></td>
								<td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'newsblenda-accounts')); ?></td>
								<td><?php echo esc_html(
									$changed_at
										? mysql2date(get_option('date_format'), $changed_at)
										: '&mdash;'
								); ?></td>
								<td class="nba-col-actions">
									<button type="button"
									        class="nba-btn nba-btn-sm nba-btn-success nba-publish-btn"
									        data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
									        data-title="<?php echo esc_attr($post->post_title); ?>">
										<?php esc_html_e('Publish Now', 'newsblenda-accounts'); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

		</div>

	</section>
	<?php endif; ?>

	<div class="nba-dashboard-columns">

		<!-- Published Articles -->
		<div class="nba-dashboard-column">
			<div class="nba-card">

				<h2><?php esc_html_e('Recently Published', 'newsblenda-accounts'); ?></h2>

				<?php if (empty($published_posts)) : ?>
					<p class="nba-muted"><?php esc_html_e('No published articles.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<ul class="nba-editor-list">
						<?php foreach ($published_posts as $post) : ?>
							<li>
								<a href="<?php echo esc_url(get_permalink($post)); ?>">
									<?php echo esc_html($post->post_title); ?>
								</a>
								<span class="nba-muted nba-text-sm">
									<?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

			</div>
		</div>

		<!-- Author Profiles -->
		<div class="nba-dashboard-column">
			<div class="nba-card">

				<h2><?php esc_html_e('Author Profiles', 'newsblenda-accounts'); ?></h2>

				<?php if (empty($authors)) : ?>
					<p class="nba-muted"><?php esc_html_e('No authors registered.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<div class="nba-table-wrap">
						<table class="nba-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Role', 'newsblenda-accounts'); ?></th>
									<th><?php esc_html_e('Joined', 'newsblenda-accounts'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($authors as $author) : ?>
									<tr>
										<td><?php echo esc_html($author->display_name); ?></td>
										<td><?php echo esc_html($author->user_email); ?></td>
										<td>
											<?php
											$role_map = [
												'nb_author'            => __('Author', 'newsblenda-accounts'),
												'nb_author_pending'    => __('Pending', 'newsblenda-accounts'),
												'nb_author_restricted' => __('Restricted', 'newsblenda-accounts'),
											];
											$primary_role = '';
											foreach ($role_map as $r => $label) {
												if (in_array($r, (array) $author->roles, true)) {
													$primary_role = $label;
													break;
												}
											}
											echo esc_html($primary_role ?: __('Unknown', 'newsblenda-accounts'));
											?>
										</td>
										<td><?php echo esc_html(
											mysql2date(
												get_option('date_format'),
												$author->user_registered
											)
										); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

			</div>
		</div>

	</div>

	<!-- Notifications -->
	<div class="nba-card">

		<div class="nba-card-header">
			<h2><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h2>
			<?php if ($unread_count > 0) : ?>
				<span class="nba-badge nba-badge-danger"><?php echo esc_html((string) $unread_count); ?></span>
			<?php endif; ?>
			<a href="<?php echo esc_url(home_url('/notifications/')); ?>" class="nba-btn nba-btn-sm">
				<?php esc_html_e('View All', 'newsblenda-accounts'); ?>
			</a>
		</div>

		<?php if (empty($editor_notifs)) : ?>
			<p class="nba-muted"><?php esc_html_e('No notifications.', 'newsblenda-accounts'); ?></p>
		<?php else : ?>
			<ul class="nba-notification-list">
				<?php foreach ($editor_notifs as $notif) : ?>
					<li class="<?php echo $notif->is_read ? '' : 'nba-unread'; ?>">
						<strong><?php echo esc_html($notif->title); ?></strong>
						<p><?php echo esc_html(wp_trim_words($notif->message, 15)); ?></p>
						<span class="nba-muted nba-text-sm">
							<?php echo esc_html(
								wp_date(
									get_option('date_format'),
									strtotime($notif->created_at)
								)
							); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</div>

	<!-- Reject Modal -->
	<div id="nba-modal-reject" class="nba-modal" aria-hidden="true" style="display:none;">
		<div class="nba-modal-box">
			<button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">&times;</button>
			<h3><?php esc_html_e('Reject Article', 'newsblenda-accounts'); ?></h3>
			<p id="nba-modal-reject-title" class="nba-modal-subtitle"></p>
			<label for="nba-reject-reason">
				<?php esc_html_e('Rejection Reason (optional)', 'newsblenda-accounts'); ?>
			</label>
			<textarea id="nba-reject-reason"
			          name="reason"
			          rows="4"
			          placeholder="<?php esc_attr_e('Explain why this article is being rejected…', 'newsblenda-accounts'); ?>"></textarea>
			<div class="nba-modal-footer">
				<button type="button" class="nba-btn nba-btn-danger" id="nba-reject-confirm">
					<?php esc_html_e('Confirm Rejection', 'newsblenda-accounts'); ?>
				</button>
				<button type="button" class="nba-btn nba-modal-close">
					<?php esc_html_e('Cancel', 'newsblenda-accounts'); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Request Revision Modal -->
	<div id="nba-modal-revision" class="nba-modal" aria-hidden="true" style="display:none;">
		<div class="nba-modal-box">
			<button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">&times;</button>
			<h3><?php esc_html_e('Request Revision', 'newsblenda-accounts'); ?></h3>
			<p id="nba-modal-revision-title" class="nba-modal-subtitle"></p>
			<label for="nba-revision-feedback">
				<?php esc_html_e('Revision Feedback (required)', 'newsblenda-accounts'); ?>
			</label>
			<textarea id="nba-revision-feedback"
			          name="feedback"
			          rows="4"
			          placeholder="<?php esc_attr_e('Describe what changes the author needs to make…', 'newsblenda-accounts'); ?>"></textarea>
			<div class="nba-modal-footer">
				<button type="button" class="nba-btn nba-btn-warning" id="nba-revision-confirm">
					<?php esc_html_e('Send Revision Request', 'newsblenda-accounts'); ?>
				</button>
				<button type="button" class="nba-btn nba-modal-close">
					<?php esc_html_e('Cancel', 'newsblenda-accounts'); ?>
				</button>
			</div>
		</div>
	</div>

	<?php do_action('nb_accounts_editor_dashboard_footer'); ?>

</div>
