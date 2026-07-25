<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$reports = new \Newsblenda\Accounts\Reports\Reports();

$top_authors = $reports->top_authors();

$top_articles = $reports->top_articles();

$recent_authors = $reports->recent_authors();

$recent_submissions = $reports->recent_submissions();

?>

<div class="wrap">

	<h1>

		<?php esc_html_e(
			'Newsblenda Reports',
			'newsblenda-accounts'
		); ?>

	</h1>

	<p>

		<?php esc_html_e(
			'Overview of authors, articles, earnings and editorial activity.',
			'newsblenda-accounts'
		); ?>

	</p>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Platform Summary',
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

						<?php echo esc_html((string) $stats['authors']); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Published Articles',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php echo esc_html((string) $stats['published']); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Pending Articles',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php echo esc_html((string) $stats['pending']); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Draft Articles',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php echo esc_html((string) $stats['drafts']); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Total Views',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						<?php echo esc_html(number_format($stats['total_views'])); ?>

					</td>

				</tr>

				<tr>

					<th>

						<?php esc_html_e(
							'Total Earnings',
							'newsblenda-accounts'
						); ?>

					</th>

					<td>

						£<?php echo esc_html(number_format((float) $stats['earnings'], 2)); ?>

					</td>

				</tr>

			</tbody>

		</table>

	</div>

	<br>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Top Earning Authors',
				'newsblenda-accounts'
			); ?>

		</h2>

		<table class="widefat striped">

			<thead>

				<tr>

					<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>

					<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>

					<th><?php esc_html_e('Total Earnings', 'newsblenda-accounts'); ?></th>

					<th><?php esc_html_e('Views', 'newsblenda-accounts'); ?></th>

				</tr>

			</thead>

			<tbody>
            
            			<?php foreach ($top_authors as $author) : ?>

				<tr>

					<td>

						<strong>

							<?php echo esc_html($author->display_name); ?>

						</strong>

					</td>

					<td>

						<?php echo esc_html($author->user_email); ?>

					</td>

					<td>

						£<?php

						echo esc_html(

							number_format(

								(float) get_user_meta(

									$author->ID,

									'nb_total_earnings',

									true

								),

								2

							)

						);

						?>

					</td>

					<td>

						<?php

						echo esc_html(

							number_format(

								(int) get_user_meta(

									$author->ID,

									'nb_total_views',

									true

								)

							)

						);

						?>

					</td>

				</tr>

			<?php endforeach; ?>

			</tbody>

		</table>

	</div>

	<br>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Most Viewed Articles',
				'newsblenda-accounts'
			); ?>

		</h2>

		<table class="widefat striped">

			<thead>

				<tr>

					<th>

						<?php esc_html_e(
							'Article',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Author',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Views',
							'newsblenda-accounts'
						); ?>

					</th>

				</tr>

			</thead>

			<tbody>

				<?php foreach ($top_articles as $post) : ?>

					<tr>

						<td>

							<a
								href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"
							>

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

								number_format(

									(int) get_post_meta(

										$post->ID,

										'nb_valid_views',

										true

									)

								)

							);

							?>

						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	</div>

	<br>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Recently Registered Authors',
				'newsblenda-accounts'
			); ?>

		</h2>

		<table class="widefat striped">

			<thead>

				<tr>

					<th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th>

					<th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>

					<th><?php esc_html_e('Registered', 'newsblenda-accounts'); ?></th>

				</tr>

			</thead>

			<tbody>

				<?php foreach ($recent_authors as $author) : ?>

					<tr>

						<td>

							<?php echo esc_html($author->display_name); ?>

						</td>

						<td>

							<?php echo esc_html($author->user_email); ?>

						</td>

						<td>

							<?php

							echo esc_html(

								mysql2date(

									get_option('date_format'),

									$author->user_registered

								)

							);

							?>

						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	</div>
    
    	<br>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Recent Article Submissions',
				'newsblenda-accounts'
			); ?>

		</h2>

		<table class="widefat striped">

			<thead>

				<tr>

					<th>

						<?php esc_html_e(
							'Article',
							'newsblenda-accounts'
						); ?>

					</th>

					<th>

						<?php esc_html_e(
							'Author',
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

				<?php if (! empty($recent_submissions)) : ?>

					<?php foreach ($recent_submissions as $post) : ?>

						<tr>

							<td>

								<a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">

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
									ucfirst($post->post_status)
								);
								?>

							</td>

							<td>

								<?php
								echo esc_html(
									get_the_date(
										get_option('date_format') . ' ' . get_option('time_format'),
										$post
									)
								);
								?>

							</td>

						</tr>

					<?php endforeach; ?>

				<?php else : ?>

					<tr>

						<td colspan="4">

							<?php esc_html_e(
								'No recent article submissions found.',
								'newsblenda-accounts'
							); ?>

						</td>

					</tr>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<br>

	<div class="card">

		<h2>

			<?php esc_html_e(
				'Reports Information',
				'newsblenda-accounts'
			); ?>

		</h2>

		<p>

			<?php esc_html_e(
				'These reports provide a live overview of your editorial platform. Statistics are generated from authors, articles, views and earnings currently stored in WordPress.',
				'newsblenda-accounts'
			); ?>

		</p>

		<ul>

			<li><?php esc_html_e('Monitor author performance.', 'newsblenda-accounts'); ?></li>

			<li><?php esc_html_e('Track article submissions and approvals.', 'newsblenda-accounts'); ?></li>

			<li><?php esc_html_e('Review overall platform earnings and engagement.', 'newsblenda-accounts'); ?></li>

			<li><?php esc_html_e('Identify your highest-performing authors and articles.', 'newsblenda-accounts'); ?></li>

		</ul>

	</div>

	<?php
	/**
	 * Fires at the bottom of the Reports page.
	 *
	 * Developers can use this hook to add custom
	 * analytics, charts, exports, integrations,
	 * or additional reporting widgets.
	 */
	do_action(
		'nb_accounts_reports_footer',
		$stats
	);
	?>

</div>