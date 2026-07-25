<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

if (! is_user_logged_in()) {
	wp_safe_redirect(home_url('/login/'));
	exit;
}

$user = wp_get_current_user();

$categories = get_categories(
	[
		'hide_empty' => false,
	]
);

$success = isset($_GET['submitted']);

$error = isset($_GET['error']);

?>

<div class="nba-submit-page">

	<header class="nba-page-header">

		<h1>

			<?php esc_html_e(
				'Submit Article',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Submit your article for editorial review.',
				'newsblenda-accounts'
			); ?>

		</p>

	</header>

	<?php if ($success) : ?>

		<div class="nba-message nba-message-success">

			<?php esc_html_e(
				'Your article has been submitted successfully.',
				'newsblenda-accounts'
			); ?>

		</div>

	<?php endif; ?>

	<?php if ($error) : ?>

		<div class="nba-message nba-message-error">

			<?php esc_html_e(
				'Please correct the highlighted errors and try again.',
				'newsblenda-accounts'
			); ?>

		</div>

	<?php endif; ?>

	<form
		method="post"
		enctype="multipart/form-data"
		class="nba-submit-form"
	>

		<?php wp_nonce_field('nb_submit_article'); ?>

		<input
			type="hidden"
			name="nb_submit_article"
			value="1"
		>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Article Information',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<label for="post_title">

					<?php esc_html_e(
						'Article Title',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="post_title"
					type="text"
					name="post_title"
					required
					maxlength="200"
				>

			</p>

			<p>

				<label for="seo_title">

					<?php esc_html_e(
						'SEO Title',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="seo_title"
					type="text"
					name="seo_title"
					maxlength="60"
				>

			</p>

			<p>

				<label for="meta_description">

					<?php esc_html_e(
						'Meta Description',
						'newsblenda-accounts'
					); ?>

				</label>

				<textarea
					id="meta_description"
					name="meta_description"
					rows="4"
					maxlength="160"
				></textarea>

			</p>

			<p>

				<label for="category">

					<?php esc_html_e(
						'Category',
						'newsblenda-accounts'
					); ?>

				</label>

				<select
					id="category"
					name="category"
					required
				>

					<option value="">

						<?php esc_html_e(
							'Select Category',
							'newsblenda-accounts'
						); ?>

					</option>

					<?php foreach ($categories as $category) : ?>

						<option
							value="<?php echo esc_attr($category->term_id); ?>"
						>

							<?php echo esc_html($category->name); ?>

						</option>

					<?php endforeach; ?>

				</select>

			</p>

			<p>

				<label for="tags">

					<?php esc_html_e(
						'Tags',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="tags"
					type="text"
					name="tags"
					placeholder="<?php esc_attr_e('Separate tags with commas', 'newsblenda-accounts'); ?>"
				>

			</p>

		</div>
        
        		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Article Content',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<label for="featured_image">

					<?php esc_html_e(
						'Featured Image',
						'newsblenda-accounts'
					); ?>

				</label>

				<input
					id="featured_image"
					type="file"
					name="featured_image"
					accept="image/*"
				>

			</p>

			<p class="description">

				<?php esc_html_e(
					'Upload a high-quality featured image. JPG, PNG and WebP formats are recommended.',
					'newsblenda-accounts'
				); ?>

			</p>

			<p>

				<label>

					<?php esc_html_e(
						'Article Content',
						'newsblenda-accounts'
					); ?>

				</label>

			</p>

			<?php

			wp_editor(

				'',

				'nb_article_content',

				[
					'textarea_name' => 'article_content',

					'textarea_rows' => 20,

					'media_buttons' => true,

					'teeny' => false,

				]

			);

			?>

		</div>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Sources',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Provide at least one reliable source for your article. Add one URL per line.',
					'newsblenda-accounts'
				); ?>

			</p>

			<textarea
				name="sources"
				rows="6"
				placeholder="https://example.com&#10;https://another-source.com"
			></textarea>

		</div>

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Editorial Checklist',
					'newsblenda-accounts'
				); ?>

			</h2>

			<ul class="nba-editorial-checklist">

				<li>

					<?php esc_html_e(
						'Article should contain between 900 and 1,500 words.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Include at least three internal Newsblenda links.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Ensure your article is original and factually accurate.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Upload a featured image before submitting.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Include reliable references and proper attribution where required.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Review grammar, spelling and formatting before submission.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>
        
        		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Author Declaration',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<label>

					<input
						type="checkbox"
						name="author_declaration"
						value="1"
						required
					>

					<?php esc_html_e(
						'I confirm that this article is original, complies with the Newsblenda Editorial Guidelines, contains accurate information, and does not infringe any copyright or intellectual property rights.',
						'newsblenda-accounts'
					); ?>

				</label>

			</p>

			<p>

				<label>

					<input
						type="checkbox"
						name="source_confirmation"
						value="1"
						required
					>

					<?php esc_html_e(
						'I confirm that all sources used in this article have been properly referenced.',
						'newsblenda-accounts'
					); ?>

				</label>

			</p>

		</div>

		<div class="nba-form-actions">

			<button
				type="submit"
				name="save_draft"
				value="1"
				class="button"
			>

				<?php esc_html_e(
					'Save Draft',
					'newsblenda-accounts'
				); ?>

			</button>

			<button
				type="submit"
				name="submit_review"
				value="1"
				class="button button-primary button-large"
			>

				<?php esc_html_e(
					'Submit for Review',
					'newsblenda-accounts'
				); ?>

			</button>

		</div>

	</form>

	<section class="nba-submit-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Before You Submit',
					'newsblenda-accounts'
				); ?>

			</h2>

			<p>

				<?php esc_html_e(
					'Every submitted article is reviewed by the Newsblenda editorial team before publication. Articles that do not meet the editorial guidelines may be returned for revision or rejected.',
					'newsblenda-accounts'
				); ?>

			</p>

			<ul>

				<li>

					<?php esc_html_e(
						'Use clear headings and proper formatting.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Provide accurate and verifiable information.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Avoid duplicate or previously published content.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Your submission history may affect your author rating.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>

	</section>

	<?php
	/**
	 * Fires after the article submission form.
	 *
	 * Developers can use this hook to add
	 * additional validation messages, fields,
	 * integrations or widgets.
	 */
	do_action(
		'nb_accounts_submit_footer',
		$user
	);
	?>

</div>