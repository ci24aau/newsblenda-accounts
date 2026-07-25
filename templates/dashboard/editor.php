<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

if (! current_user_can('nb_review_articles')) {
	wp_safe_redirect(home_url('/dashboard/'));
	exit;
}

$search = isset($_GET['nb_editor_search'])
	? sanitize_text_field(wp_unslash($_GET['nb_editor_search']))
	: '';

$pending_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'pending',
		'posts_per_page' => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
		's'              => $search,
	]
);

$scheduled_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'future',
		'posts_per_page' => 8,
		'orderby'        => 'date',
		'order'          => 'ASC',
		's'              => $search,
	]
);

$published_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'orderby'        => 'date',
		'order'          => 'DESC',
		's'              => $search,
	]
);

$rejected_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'posts_per_page' => 5,
		'meta_key'       => 'nb_editor_status',
		'meta_value'     => 'rejected',
		'orderby'        => 'date',
		'order'          => 'DESC',
	]
);

$revision_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'posts_per_page' => 5,
		'meta_key'       => 'nb_editor_status',
		'meta_value'     => 'revision_requested',
		'orderby'        => 'date',
		'order'          => 'DESC',
	]
);

$authors = get_users(
	[
		'role__in' => ['nb_author', 'nb_author_pending', 'nb_author_restricted'],
		'number'   => 8,
		'orderby'  => 'registered',
		'order'    => 'DESC',
	]
);

$post_counts = wp_count_posts('post');
$pending_count = isset($post_counts->pending)
	? (int) $post_counts->pending
	: 0;
?>

<div class="nba-editor-dashboard">

	<header class="nba-page-header">
		<h1><?php esc_html_e('Editor Dashboard', 'newsblenda-accounts'); ?></h1>
		<p><?php esc_html_e('Moderate submissions, manage review queues and keep editorial workflow moving.', 'newsblenda-accounts'); ?></p>
	</header>

	<div class="nba-dashboard-grid">
		<div class="nba-card">
			<h3><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></h3>
			<div class="nba-card-number"><?php echo esc_html((string) count($pending_posts)); ?></div>
		</div>
		<div class="nba-card">
			<h3><?php esc_html_e('Awaiting Approval', 'newsblenda-accounts'); ?></h3>
			<div class="nba-card-number"><?php echo esc_html((string) $pending_count); ?></div>
		</div>
		<div class="nba-card">
			<h3><?php esc_html_e('Revision Requests', 'newsblenda-accounts'); ?></h3>
			<div class="nba-card-number"><?php echo esc_html((string) count($revision_posts)); ?></div>
		</div>
		<div class="nba-card">
			<h3><?php esc_html_e('Recently Rejected', 'newsblenda-accounts'); ?></h3>
			<div class="nba-card-number"><?php echo esc_html((string) count($rejected_posts)); ?></div>
		</div>
	</div>

	<div class="nba-card nba-editor-toolbar">
		<form method="get" class="nba-editor-search-form">
			<label for="nb_editor_search"><?php esc_html_e('Search Articles', 'newsblenda-accounts'); ?></label>
			<input id="nb_editor_search" type="search" name="nb_editor_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search title or content', 'newsblenda-accounts'); ?>">
			<button type="submit" class="button button-primary"><?php esc_html_e('Search', 'newsblenda-accounts'); ?></button>
		</form>
		<div class="nba-editor-links">
			<a class="button" href="<?php echo esc_url(home_url('/notifications/')); ?>"><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></a>
			<a class="button" href="<?php echo esc_url(home_url('/profile/')); ?>"><?php esc_html_e('Profile', 'newsblenda-accounts'); ?></a>
			<?php if (current_user_can('nb_manage_settings') || current_user_can('manage_options')) : ?>
				<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=newsblenda-accounts')); ?>"><?php esc_html_e('Settings', 'newsblenda-accounts'); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<section class="nba-editor-section">
		<div class="nba-card">
			<h2><?php esc_html_e('Review Queue', 'newsblenda-accounts'); ?></h2>

			<?php if (empty($pending_posts)) : ?>
				<p><?php esc_html_e('No pending submissions right now.', 'newsblenda-accounts'); ?></p>
			<?php else : ?>
				<table class="nba-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Submitted', 'newsblenda-accounts'); ?></th>
							<th><?php esc_html_e('Moderation', 'newsblenda-accounts'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($pending_posts as $post) : ?>
							<tr>
								<td><?php echo esc_html($post->post_title); ?></td>
								<td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
								<td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
								<td class="nba-editor-actions">
									<a class="button button-small" href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php esc_html_e('Approve', 'newsblenda-accounts'); ?></a>
									<a class="button button-small" href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php esc_html_e('Request Revisions', 'newsblenda-accounts'); ?></a>
									<a class="button button-small" href="<?php echo esc_url(get_delete_post_link($post->ID, '', true)); ?>"><?php esc_html_e('Reject', 'newsblenda-accounts'); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</section>

	<div class="nba-dashboard-columns">
		<div class="nba-dashboard-column">
			<div class="nba-card">
				<h2><?php esc_html_e('Scheduled Articles', 'newsblenda-accounts'); ?></h2>
				<?php if (empty($scheduled_posts)) : ?>
					<p><?php esc_html_e('No scheduled articles.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<ul class="nba-editor-list">
						<?php foreach ($scheduled_posts as $post) : ?>
							<li>
								<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php echo esc_html($post->post_title); ?></a>
								<span><?php echo esc_html(get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $post)); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<div class="nba-dashboard-column">
			<div class="nba-card">
				<h2><?php esc_html_e('Published Articles', 'newsblenda-accounts'); ?></h2>
				<?php if (empty($published_posts)) : ?>
					<p><?php esc_html_e('No recent publications found.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<ul class="nba-editor-list">
						<?php foreach ($published_posts as $post) : ?>
							<li>
								<a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a>
								<span><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="nba-dashboard-columns">
		<div class="nba-dashboard-column">
			<div class="nba-card">
				<h2><?php esc_html_e('Author Profiles', 'newsblenda-accounts'); ?></h2>
				<?php if (empty($authors)) : ?>
					<p><?php esc_html_e('No author profiles found.', 'newsblenda-accounts'); ?></p>
				<?php else : ?>
					<table class="nba-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>
								<th><?php esc_html_e('Joined', 'newsblenda-accounts'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($authors as $author) : ?>
								<tr>
									<td><?php echo esc_html($author->display_name); ?></td>
									<td><?php echo esc_html($author->user_email); ?></td>
									<td><?php echo esc_html(mysql2date(get_option('date_format'), $author->user_registered)); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<div class="nba-dashboard-column">
			<div class="nba-card">
				<h2><?php esc_html_e('Quick Moderation Tools', 'newsblenda-accounts'); ?></h2>
				<p><a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_status=pending&post_type=post')); ?>"><?php esc_html_e('Pending Articles', 'newsblenda-accounts'); ?></a></p>
				<p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_status=future&post_type=post')); ?>"><?php esc_html_e('Scheduled Queue', 'newsblenda-accounts'); ?></a></p>
				<p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_status=publish&post_type=post')); ?>"><?php esc_html_e('Published List', 'newsblenda-accounts'); ?></a></p>
				<p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_status=draft&post_type=post')); ?>"><?php esc_html_e('Revision Queue', 'newsblenda-accounts'); ?></a></p>
			</div>
		</div>
	</div>

	<?php do_action('nb_accounts_editor_dashboard_footer'); ?>
</div>
