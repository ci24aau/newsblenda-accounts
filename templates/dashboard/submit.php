<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

if (!current_user_can('nb_submit_articles')) {
    echo '<div class="nba-message nba-message-error">' . esc_html__('You do not have permission to submit articles.', 'newsblenda-accounts') . '</div>';
    return;
}

$user = wp_get_current_user();
$categories = get_categories(['hide_empty' => false]);
$success = isset($_GET['submitted']);
$error = isset($_GET['error']);
$draft_saved = isset($_GET['draft_saved']);
$resubmit_post_id = isset($_GET['resubmit']) ? absint(wp_unslash($_GET['resubmit'])) : 0;
$editing_post_id = 0;
$editing_post = null;

if ($resubmit_post_id > 0) {
    $candidate = get_post($resubmit_post_id);
    if ($candidate instanceof WP_Post && (int) $candidate->post_author === (int) $user->ID) {
        $editing_post = $candidate;
        $editing_post_id = (int) $candidate->ID;
    }
} elseif (!empty($_GET['post_id'])) {
    $candidate = get_post(absint(wp_unslash($_GET['post_id'])));
    if ($candidate instanceof WP_Post && (int) $candidate->post_author === (int) $user->ID) {
        $editing_post = $candidate;
        $editing_post_id = (int) $candidate->ID;
    }
}

$post_title = $editing_post instanceof WP_Post ? $editing_post->post_title : '';
$seo_title = $editing_post_id > 0 ? (string) get_post_meta($editing_post_id, 'nb_seo_title', true) : '';
$meta_description = $editing_post_id > 0 ? (string) get_post_meta($editing_post_id, 'nb_meta_description', true) : '';
$sources = $editing_post_id > 0 ? (string) get_post_meta($editing_post_id, 'nb_sources', true) : '';
$content_type = $editing_post_id > 0 ? (string) get_post_meta($editing_post_id, 'nb_content_type', true) : 'article';
$content = $editing_post instanceof WP_Post ? $editing_post->post_content : '';
$selected_category = 0;
$selected_tags = '';

if ($editing_post instanceof WP_Post) {
    $post_categories = wp_get_post_categories($editing_post_id);
    $selected_category = !empty($post_categories) ? (int) $post_categories[0] : 0;
    $selected_tags = implode(', ', wp_get_post_tags($editing_post_id, ['fields' => 'names']));
}
?>
<div class="nba-submit-page">
    <header class="nba-page-header">
        <h1><?php echo esc_html($editing_post_id > 0 ? __('Update Article', 'newsblenda-accounts') : __('Submit Article', 'newsblenda-accounts')); ?></h1>
        <p><?php echo esc_html($resubmit_post_id > 0 ? __('Revise your draft and resubmit it for editorial review.', 'newsblenda-accounts') : __('Submit your article for editorial review.', 'newsblenda-accounts')); ?></p>
    </header>

    <?php if ($success) : ?>
        <div class="nba-message nba-message-success"><?php esc_html_e('Your article has been submitted successfully.', 'newsblenda-accounts'); ?></div>
    <?php endif; ?>

    <?php if ($draft_saved) : ?>
        <div class="nba-message nba-message-success"><?php esc_html_e('Your draft has been saved.', 'newsblenda-accounts'); ?></div>
    <?php endif; ?>

    <?php if ($error) : ?>
        <div class="nba-message nba-message-error"><?php esc_html_e('Please correct the highlighted errors and try again.', 'newsblenda-accounts'); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="nba-submit-form" id="nba-submit-form">
        <?php wp_nonce_field('nb_submit_article'); ?>
        <input type="hidden" name="nb_submit_article" value="1">
        <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $editing_post_id); ?>">
        <input type="hidden" name="resubmit_post_id" value="<?php echo esc_attr((string) $resubmit_post_id); ?>">

        <div class="nba-card">
            <h2><?php esc_html_e('Article Information', 'newsblenda-accounts'); ?></h2>
            <p>
                <label for="post_title"><?php esc_html_e('Article Title', 'newsblenda-accounts'); ?></label>
                <input id="post_title" type="text" name="post_title" required maxlength="200" value="<?php echo esc_attr($post_title); ?>">
            </p>
            <p>
                <label for="seo_title"><?php esc_html_e('SEO Title', 'newsblenda-accounts'); ?></label>
                <input id="seo_title" type="text" name="seo_title" maxlength="60" value="<?php echo esc_attr($seo_title); ?>">
            </p>
            <p>
                <label for="meta_description"><?php esc_html_e('Meta Description', 'newsblenda-accounts'); ?></label>
                <textarea id="meta_description" name="meta_description" rows="4" maxlength="160"><?php echo esc_textarea($meta_description); ?></textarea>
            </p>
            <p>
                <label for="content_type"><?php esc_html_e('Content Type', 'newsblenda-accounts'); ?></label>
                <select id="content_type" name="content_type">
                    <option value="article" <?php selected($content_type, 'article'); ?>><?php esc_html_e('Article', 'newsblenda-accounts'); ?></option>
                    <option value="opinion" <?php selected($content_type, 'opinion'); ?>><?php esc_html_e('Opinion', 'newsblenda-accounts'); ?></option>
                    <option value="analysis" <?php selected($content_type, 'analysis'); ?>><?php esc_html_e('Analysis', 'newsblenda-accounts'); ?></option>
                    <option value="feature" <?php selected($content_type, 'feature'); ?>><?php esc_html_e('Feature', 'newsblenda-accounts'); ?></option>
                </select>
            </p>
            <p>
                <label for="category"><?php esc_html_e('Category', 'newsblenda-accounts'); ?></label>
                <select id="category" name="category" required>
                    <option value=""><?php esc_html_e('Select Category', 'newsblenda-accounts'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr((string) $category->term_id); ?>" <?php selected($selected_category, (int) $category->term_id); ?>><?php echo esc_html($category->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="tags"><?php esc_html_e('Tags', 'newsblenda-accounts'); ?></label>
                <input id="tags" type="text" name="tags" placeholder="<?php esc_attr_e('Separate tags with commas', 'newsblenda-accounts'); ?>" value="<?php echo esc_attr($selected_tags); ?>">
            </p>
        </div>

        <div class="nba-card">
            <div class="nba-section-header">
                <h2><?php esc_html_e('Article Content', 'newsblenda-accounts'); ?></h2>
                <span class="nba-autosave-status"><?php esc_html_e('Not saved yet', 'newsblenda-accounts'); ?></span>
            </div>
            <p>
                <label for="featured_image"><?php esc_html_e('Featured Image', 'newsblenda-accounts'); ?></label>
                <input id="featured_image" type="file" name="featured_image" accept="image/*">
            </p>
            <p class="description"><?php esc_html_e('Upload a high-quality featured image. JPG, PNG and WebP formats are recommended.', 'newsblenda-accounts'); ?></p>
            <p><label><?php esc_html_e('Article Content', 'newsblenda-accounts'); ?></label></p>
            <?php
            wp_editor(
                $content,
                'nb_article_content',
                [
                    'textarea_name' => 'article_content',
                    'textarea_rows' => 20,
                    'media_buttons' => true,
                    'teeny'         => false,
                ]
            );
            ?>
            <div class="nba-word-counter"></div>
        </div>

        <div class="nba-card">
            <h2><?php esc_html_e('Sources', 'newsblenda-accounts'); ?></h2>
            <p><?php esc_html_e('Provide at least one reliable source for your article. Add one URL per line.', 'newsblenda-accounts'); ?></p>
            <textarea id="sources" name="sources" rows="6" placeholder="https://example.com&#10;https://another-source.com"><?php echo esc_textarea($sources); ?></textarea>
        </div>

        <div class="nba-card">
            <h2><?php esc_html_e('Submission Checklist', 'newsblenda-accounts'); ?></h2>
            <ul class="nba-submission-checklist">
                <li id="check-title"><span class="nba-check-icon fail">✗</span><span><?php esc_html_e('Add a clear article title.', 'newsblenda-accounts'); ?></span></li>
                <li id="check-category"><span class="nba-check-icon fail">✗</span><span><?php esc_html_e('Choose the most relevant category.', 'newsblenda-accounts'); ?></span></li>
                <li id="check-meta"><span class="nba-check-icon fail">✗</span><span><?php esc_html_e('Write a useful meta description.', 'newsblenda-accounts'); ?></span></li>
            </ul>
        </div>

        <div class="nba-card">
            <h2><?php esc_html_e('Author Declaration', 'newsblenda-accounts'); ?></h2>
            <p><label><input type="checkbox" name="author_declaration" value="1" required> <?php esc_html_e('I confirm that this article is original, accurate, and complies with the Newsblenda Editorial Guidelines.', 'newsblenda-accounts'); ?></label></p>
            <p><label><input type="checkbox" name="source_confirmation" value="1" required> <?php esc_html_e('I confirm that all sources used in this article have been properly referenced.', 'newsblenda-accounts'); ?></label></p>
        </div>

        <div class="nba-form-actions">
            <button type="submit" name="save_draft" value="1" class="button"><?php esc_html_e('Save Draft', 'newsblenda-accounts'); ?></button>
            <button type="submit" name="submit_review" value="1" class="button button-primary button-large"><?php echo esc_html($resubmit_post_id > 0 ? __('Resubmit for Review', 'newsblenda-accounts') : __('Submit for Review', 'newsblenda-accounts')); ?></button>
        </div>
    </form>
</div>
