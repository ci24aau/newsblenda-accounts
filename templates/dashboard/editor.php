<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

if (! current_user_can('nb_review_articles')) {

	wp_safe_redirect(home_url('/dashboard/'));

	exit;

}

$pending_posts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'pending',
		'posts_per_page' => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
	]
);

$published_today = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'date_query'     => [
			[
				'after' => 'today',
			],
		],
	]
);

$drafts = get_posts(
	[
		'post_type'      => 'post',
		'post_status'    => 'draft',
		'posts_per_page' => 10,
	]
);

?>

<div class="nba-editor-dashboard">

	<header class="nba-page-header">

		<h1>

			<?php esc_html_e(
				'Editor Dashboard',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Review, approve and manage submitted Newsblenda articles.',
				'newsblenda-accounts'
			); ?>

		</p>

	</header>

	<div class="nba-dashboard-grid">

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Pending Review',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html((string) count($pending_posts)); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Published Today',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html((string) count($published_today)); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3>

				<?php esc_html_e(
					'Draft Articles',
					'newsblenda-accounts'
				); ?>

			</h3>

			<div class="nba-card-number">

				<?php echo esc_html((string) count($drafts)); ?>

			</div>

		</div>

	</div>

	<section class="nba-editor-section">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Pending Articles',
					'newsblenda-accounts'
				); ?>

			</h2>

			<?php if (empty($pending_posts)) : ?>

				<p>

					<?php esc_html_e(
						'There are no articles awaiting review.',
						'newsblenda-accounts'
					); ?>

				</p>

			<?php else : ?>

				<table class="nba-table">

					<thead>

						<tr>

							<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>

							<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>

							<th><?php esc_html_e('Submitted', 'newsblenda-accounts'); ?></th>

							<th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th>

						</tr>

					</thead>

					<tbody>

						<?php foreach ($pending_posts as $post) : ?>

							<tr>

								<td>

									<?php echo esc_html($post->post_title); ?>

								</td>

								<td>

									<?php
									echo esc_html(
										get_the_author_meta(
											'display_name',
											$post->post_author
										)
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

								<td>

									<a
										class="button button-small"
										href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"
									>

										<?php esc_html_e(
											'Review',
											'newsblenda-accounts'
										); ?>

									</a>

								</td>

							</tr>

						<?php endforeach; ?>

					</tbody>

				</table>

			<?php endif; ?>

		</div>

	</section>
    
    	<section class="nba-editor-section">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Recently Published',
					'newsblenda-accounts'
				); ?>

			</h2>

			<?php if (empty($published_today)) : ?>

				<p>

					<?php esc_html_e(
						'No articles have been published today.',
						'newsblenda-accounts'
					); ?>

				</p>

			<?php else : ?>

				<table class="nba-table">

					<thead>

						<tr>

							<th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>

							<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>

							<th><?php esc_html_e('Published', 'newsblenda-accounts'); ?></th>

						</tr>

					</thead>

					<tbody>

						<?php foreach ($published_today as $post) : ?>

							<tr>

								<td>

									<a href="<?php echo esc_url(get_permalink($post)); ?>">

										<?php echo esc_html($post->post_title); ?>

									</a>

								</td>

								<td>

									<?php
									echo esc_html(
										get_the_author_meta(
											'display_name',
											$post->post_author
										)
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

			<?php endif; ?>

		</div>

	</section>

	<div class="nba-dashboard-columns">

		<div class="nba-dashboard-column">

			<div class="nba-card">

				<h2>

					<?php esc_html_e(
						'Editorial Statistics',
						'newsblenda-accounts'
					); ?>

				</h2>

				<table class="nba-summary-table">

					<tr>

						<th><?php esc_html_e('Pending Articles', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html((string) count($pending_posts)); ?></td>

					</tr>

					<tr>

						<th><?php esc_html_e('Published Today', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html((string) count($published_today)); ?></td>

					</tr>

					<tr>

						<th><?php esc_html_e('Draft Articles', 'newsblenda-accounts'); ?></th>

						<td><?php echo esc_html((string) count($drafts)); ?></td>

					</tr>

				</table>

			</div>

		</div>

		<div class="nba-dashboard-column">

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
						href="<?php echo esc_url(admin_url('edit.php?post_status=pending&post_type=post')); ?>"
					>

						<?php esc_html_e(
							'Review Pending Articles',
							'newsblenda-accounts'
						); ?>

					</a>

				</p>

				<p>

					<a
						class="button"
						href="<?php echo esc_url(admin_url('post-new.php')); ?>"
					>

						<?php esc_html_e(
							'Create New Article',
							'newsblenda-accounts'
						); ?>

					</a>

				</p>

				<p>

					<a
						class="button"
						href="<?php echo esc_url(admin_url('edit.php')); ?>"
					>

						<?php esc_html_e(
							'View All Articles',
							'newsblenda-accounts'
						); ?>

					</a>

				</p>

				<p>

					<a
						class="button"
						href="<?php echo esc_url(admin_url('users.php')); ?>"
					>

						<?php esc_html_e(
							'Manage Authors',
							'newsblenda-accounts'
						); ?>

					</a>

				</p>

			</div>

		</div>

	</div>
    
    	<section class="nba-editor-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Editorial Guidelines',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Editors are responsible for maintaining the quality, accuracy and consistency of all published Newsblenda content.',
					'newsblenda-accounts'
				); ?>

			</p>

			<ul>

				<li>

					<?php esc_html_e(
						'Verify factual accuracy before approving any article.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Ensure articles follow Newsblenda editorial standards.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Reject duplicate, plagiarised or misleading submissions.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Request revisions whenever improvements are required.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Confirm SEO title, meta description, featured image and sources are provided before publication.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Maintain fairness and consistency across all editorial decisions.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Editorial Workflow',
					'newsblenda-accounts'
				); ?>

			</h2>

			<ol>

				<li>

					<?php esc_html_e(
						'Review submitted article.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Check grammar, formatting and originality.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Verify references and supporting sources.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Approve, reject or request revisions.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Notify the author of the editorial decision.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ol>

		</div>

	</section>

	<?php
	/**
	 * Fires after the editor dashboard.
	 *
	 * Developers can use this hook to add
	 * moderation tools, reports, analytics,
	 * approval queues or custom widgets.
	 */
	do_action(
		'nb_accounts_editor_dashboard_footer'
	);
	?>

</div>