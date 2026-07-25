<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in() || !current_user_can('nb_review_articles')) {
    echo '<div class="nba-message nba-message-error">' . esc_html__('You do not have permission to review this article.', 'newsblenda-accounts') . '</div>';
    return;
}

global $wpdb;

$post_id = isset($post_id) ? (int) $post_id : (isset($_GET['review']) ? absint(wp_unslash($_GET['review'])) : 0);
$post = isset($post) && $post instanceof WP_Post ? $post : get_post($post_id);

if (!$post instanceof WP_Post) {
    echo '<div class="nba-message nba-message-error">' . esc_html__('Article not found.', 'newsblenda-accounts') . '</div>';
    return;
}

$author = isset($author) && $author instanceof WP_User ? $author : get_userdata((int) $post->post_author);
$word_count = str_word_count(wp_strip_all_tags($post->post_content));
$seo_title = (string) get_post_meta($post_id, 'nb_seo_title', true);
$meta_description = (string) get_post_meta($post_id, 'nb_meta_description', true);
$sources = (string) get_post_meta($post_id, 'nb_sources', true);
$categories = get_the_category($post_id);
$tags = get_the_tags($post_id);
$revisions = $revisions ?? ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'nb_article_revisions WHERE article_id = %d ORDER BY requested_at DESC', $post_id), ARRAY_A) ?: []);
$feedback_history = $feedback_history ?? ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'nb_article_feedback WHERE article_id = %d ORDER BY created_at DESC', $post_id), ARRAY_A) ?: []);
$workflow_log = $workflow_log ?? ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'nb_workflow_log WHERE article_id = %d ORDER BY created_at DESC', $post_id), ARRAY_A) ?: []);
$article_status = (string) get_post_meta($post_id, 'nb_article_status', true) ?: $post->post_status;
$status_class = match ($article_status) {
    'submitted', 'pending' => 'nba-badge-submitted',
    'revision-requested', 'revision-submitted' => 'nba-badge-revision',
    'approved' => 'nba-badge-approved',
    'rejected' => 'nba-badge-rejected',
    'scheduled' => 'nba-badge-scheduled',
    default => 'nba-badge-draft',
};
?>
<div class="nba-review-page">
    <header>
        <a href="<?php echo esc_url(home_url('/editor-dashboard/')); ?>">← <?php esc_html_e('Back to Editor Dashboard', 'newsblenda-accounts'); ?></a>
        <h1><?php echo esc_html($post->post_title); ?></h1>
        <div class="nba-review-meta">
            <span class="nba-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $article_status))); ?></span>
            <span><?php printf(esc_html__('Author: %s', 'newsblenda-accounts'), esc_html($author?->display_name ?? __('Unknown', 'newsblenda-accounts'))); ?></span>
            <span><?php printf(esc_html__('Submitted: %s', 'newsblenda-accounts'), esc_html((string) get_post_meta($post_id, 'nb_submitted_at', true) ?: get_the_date(get_option('date_format'), $post))); ?></span>
            <span><?php printf(esc_html__('Word count: %d', 'newsblenda-accounts'), $word_count); ?></span>
        </div>
    </header>

    <div class="nba-review-layout">
        <article class="nba-review-content"><?php echo wp_kses_post(wpautop($post->post_content)); ?></article>
        <aside class="nba-review-sidebar">
            <div class="nba-card">
                <h3><?php esc_html_e('Article Details', 'newsblenda-accounts'); ?></h3>
                <p><strong><?php esc_html_e('Author', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html($author?->display_name ?? __('Unknown', 'newsblenda-accounts')); ?></p>
                <p><strong><?php esc_html_e('Submission Date', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html((string) get_post_meta($post_id, 'nb_submitted_at', true) ?: get_the_date(get_option('date_format'), $post)); ?></p>
                <p><strong><?php esc_html_e('Word Count', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html((string) $word_count); ?></p>
                <p><strong><?php esc_html_e('SEO Title', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html($seo_title); ?></p>
                <p><strong><?php esc_html_e('Meta Description', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html($meta_description); ?></p>
                <p><strong><?php esc_html_e('Categories', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html(implode(', ', wp_list_pluck($categories ?: [], 'name'))); ?></p>
                <p><strong><?php esc_html_e('Tags', 'newsblenda-accounts'); ?>:</strong> <?php echo esc_html(implode(', ', wp_list_pluck($tags ?: [], 'name'))); ?></p>
                <p><strong><?php esc_html_e('Source References', 'newsblenda-accounts'); ?>:</strong><br><?php echo nl2br(esc_html($sources)); ?></p>
            </div>

            <div class="nba-card nba-review-actions">
                <h3><?php esc_html_e('Review Actions', 'newsblenda-accounts'); ?></h3>
                <button class="button button-primary" data-nba-modal="nba-approve-modal" data-action="approve" data-post-id="<?php echo esc_attr((string) $post_id); ?>"><?php esc_html_e('Approve & Publish', 'newsblenda-accounts'); ?></button>
                <button class="button" data-nba-modal="nba-revision-modal" data-action="revision" data-post-id="<?php echo esc_attr((string) $post_id); ?>"><?php esc_html_e('Request Revisions', 'newsblenda-accounts'); ?></button>
                <button class="button nba-btn-danger" data-nba-modal="nba-reject-modal" data-action="reject" data-post-id="<?php echo esc_attr((string) $post_id); ?>"><?php esc_html_e('Reject', 'newsblenda-accounts'); ?></button>
            </div>

            <?php if ($revisions) : ?>
                <div class="nba-card">
                    <h3><?php esc_html_e('Revision History', 'newsblenda-accounts'); ?></h3>
                    <table class="nba-table">
                        <thead><tr><th><?php esc_html_e('Revision', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Editor', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Feedback', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Severity', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Requested', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($revisions as $revision) : $editor = get_userdata((int) $revision['editor_id']); ?>
                                <tr>
                                    <td><?php echo esc_html((string) $revision['revision_number']); ?></td>
                                    <td><?php echo esc_html($editor?->display_name ?? __('Unknown', 'newsblenda-accounts')); ?></td>
                                    <td><?php echo esc_html($revision['feedback']); ?></td>
                                    <td><?php echo esc_html(ucfirst((string) $revision['severity'])); ?></td>
                                    <td><?php echo esc_html((string) $revision['requested_at']); ?></td>
                                    <td><?php echo esc_html(ucfirst((string) $revision['status'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="nba-card">
                <h3><?php esc_html_e('Workflow Log', 'newsblenda-accounts'); ?></h3>
                <ul class="nba-workflow-log">
                    <?php foreach ($workflow_log as $entry) : $entry_user = get_userdata((int) $entry['user_id']); ?>
                        <li>
                            <span class="log-action"><?php echo esc_html(ucwords(str_replace('-', ' ', (string) $entry['action']))); ?></span>
                            <div class="log-meta"><?php echo esc_html(($entry_user?->display_name ?? __('Unknown', 'newsblenda-accounts')) . ' · ' . (string) $entry['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<div id="nba-approve-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Approve Article', 'newsblenda-accounts'); ?></h2><form id="nba-approve-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>"><label for="nba-publish-now-review"><?php esc_html_e('Publishing Mode', 'newsblenda-accounts'); ?></label><select id="nba-publish-now-review" name="publish_now"><option value="publish"><?php esc_html_e('Publish now', 'newsblenda-accounts'); ?></option><option value="schedule"><?php esc_html_e('Schedule for later', 'newsblenda-accounts'); ?></option></select><label for="nba-scheduled-at-review"><?php esc_html_e('Schedule Date', 'newsblenda-accounts'); ?></label><input id="nba-scheduled-at-review" type="datetime-local" name="scheduled_at" value=""><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button button-primary"><?php esc_html_e('Confirm Approval', 'newsblenda-accounts'); ?></button></div></form></div></div>
<div id="nba-reject-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Reject Article', 'newsblenda-accounts'); ?></h2><form id="nba-reject-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>"><label for="nba-reject-reason-review"><?php esc_html_e('Reason', 'newsblenda-accounts'); ?></label><select id="nba-reject-reason-review" name="reason"><option value="plagiarism"><?php esc_html_e('Plagiarism', 'newsblenda-accounts'); ?></option><option value="low-quality"><?php esc_html_e('Low-quality', 'newsblenda-accounts'); ?></option><option value="off-topic"><?php esc_html_e('Off-topic', 'newsblenda-accounts'); ?></option><option value="other"><?php esc_html_e('Other', 'newsblenda-accounts'); ?></option></select><label for="nba-reject-comments-review"><?php esc_html_e('Comments', 'newsblenda-accounts'); ?></label><textarea id="nba-reject-comments-review" name="comments"></textarea><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button nba-btn-danger"><?php esc_html_e('Reject Article', 'newsblenda-accounts'); ?></button></div></form></div></div>
<div id="nba-revision-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Request Revisions', 'newsblenda-accounts'); ?></h2><form id="nba-revision-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>"><label for="nba-revision-severity-review"><?php esc_html_e('Severity', 'newsblenda-accounts'); ?></label><select id="nba-revision-severity-review" name="severity"><option value="minor"><?php esc_html_e('Minor', 'newsblenda-accounts'); ?></option><option value="major"><?php esc_html_e('Major', 'newsblenda-accounts'); ?></option></select><label for="nba-revision-feedback-review"><?php esc_html_e('Feedback', 'newsblenda-accounts'); ?></label><textarea id="nba-revision-feedback-review" name="feedback"></textarea><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button button-primary"><?php esc_html_e('Send Request', 'newsblenda-accounts'); ?></button></div></form></div></div>
