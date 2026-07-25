<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in() || !current_user_can('nb_review_articles')) {
    echo '<div class="nba-message nba-message-error">' . esc_html__('You do not have permission to access the editor dashboard.', 'newsblenda-accounts') . '</div>';
    return;
}

if (isset($_GET['review']) && absint(wp_unslash($_GET['review'])) > 0) {
    $post_id = absint(wp_unslash($_GET['review']));
    include NB_ACCOUNTS_PATH . 'templates/dashboard/review.php';
    return;
}

$user = $user ?? wp_get_current_user();
$pending_posts = $pending_posts ?? get_posts([
    'post_type'      => 'post',
    'post_status'    => 'pending',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
$scheduled_posts = $scheduled_posts ?? get_posts([
    'post_type'      => 'post',
    'post_status'    => 'future',
    'posts_per_page' => 8,
    'orderby'        => 'date',
    'order'          => 'ASC',
]);
$published_posts = $published_posts ?? get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
$authors = $authors ?? get_users(['role' => 'nb_author', 'number' => 10, 'orderby' => 'registered', 'order' => 'DESC']);
$revision_posts = $revision_posts ?? get_posts([
    'post_type'      => 'post',
    'post_status'    => 'draft',
    'posts_per_page' => 8,
    'meta_key'       => 'nb_editor_status',
    'meta_value'     => 'revision_requested',
    'orderby'        => 'modified',
    'order'          => 'DESC',
]);
$rejected_posts = $rejected_posts ?? get_posts([
    'post_type'      => 'post',
    'post_status'    => 'draft',
    'posts_per_page' => 5,
    'meta_key'       => 'nb_editor_status',
    'meta_value'     => 'rejected',
    'orderby'        => 'modified',
    'order'          => 'DESC',
]);
$pending_count = $pending_count ?? count($pending_posts);
$in_review_count = $in_review_count ?? 0;
$published_this_month = $published_this_month ?? count($published_posts);
$authors_managed = $authors_managed ?? count($authors);
$awaiting_revision = $awaiting_revision ?? count($revision_posts);
$notification_count = $notification_count ?? 0;

$status_class = static function (string $status): string {
    return match ($status) {
        'pending' => 'nba-badge-pending nba-badge-warning',
        'revision-submitted' => 'nba-badge-revision nba-badge-info',
        'approved' => 'nba-badge-approved nba-badge-success',
        'scheduled' => 'nba-badge-scheduled',
        'rejected' => 'nba-badge-rejected',
        default => 'nba-badge-draft',
    };
};
?>
<div class="nba-dashboard-wrap">
    <nav class="nba-sidebar">
        <div class="nba-sidebar-header">
            <a class="nba-logo" href="<?php echo esc_url(home_url('/editor-dashboard/')); ?>"><?php esc_html_e('Newsblenda', 'newsblenda-accounts'); ?></a>
        </div>
        <div class="nba-sidebar-section"><?php esc_html_e('Editor', 'newsblenda-accounts'); ?></div>
        <ul class="nba-sidebar-nav">
            <li><a class="active" href="<?php echo esc_url(home_url('/editor-dashboard/')); ?>"><?php esc_html_e('Dashboard', 'newsblenda-accounts'); ?></a></li>
            <li><a href="#review-queue"><?php esc_html_e('Review Queue', 'newsblenda-accounts'); ?><span class="nba-badge-count"><?php echo esc_html((string) $pending_count); ?></span></a></li>
            <li><a href="#articles"><?php esc_html_e('Articles', 'newsblenda-accounts'); ?></a></li>
            <li><a href="#authors"><?php esc_html_e('Authors', 'newsblenda-accounts'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/notifications/')); ?>"><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?><?php if ($notification_count > 0) : ?><span class="nba-badge-count"><?php echo esc_html((string) $notification_count); ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/profile/')); ?>"><?php esc_html_e('Profile', 'newsblenda-accounts'); ?></a></li>
            <?php if (current_user_can('manage_options')) : ?>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=newsblenda-accounts')); ?>"><?php esc_html_e('Settings', 'newsblenda-accounts'); ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo esc_url(home_url('/logout/')); ?>"><?php esc_html_e('Logout', 'newsblenda-accounts'); ?></a></li>
        </ul>
    </nav>

    <main class="nba-dashboard-main">
        <header class="nba-dash-header">
            <h1><?php printf(esc_html__('Welcome back, %s.', 'newsblenda-accounts'), esc_html($user->display_name)); ?></h1>
            <p><?php esc_html_e('Editor Dashboard.', 'newsblenda-accounts'); ?></p>
        </header>

        <section class="nba-stat-cards">
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $pending_count); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('In Review', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $in_review_count); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Published This Month', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $published_this_month); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Authors Managed', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $authors_managed); ?></div></div>
        </section>

        <section class="nba-section" id="review-queue">
            <div class="nba-section-header"><h2><?php esc_html_e('Review Queue', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <?php if (empty($pending_posts)) : ?>
                    <p><?php esc_html_e('No pending articles right now.', 'newsblenda-accounts'); ?></p>
                <?php else : ?>
                    <table class="nba-table">
                        <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Submitted', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($pending_posts as $post) : $article_status = (string) get_post_meta($post->ID, 'nb_article_status', true) ?: 'pending'; ?>
                                <tr>
                                    <td><a href="<?php echo esc_url(home_url('/editor-dashboard/?review=' . $post->ID)); ?>"><?php echo esc_html($post->post_title); ?></a></td>
                                    <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                                    <td><?php echo esc_html(get_post_meta($post->ID, 'nb_submitted_at', true) ?: get_the_date(get_option('date_format'), $post)); ?></td>
                                    <td><span class="nba-badge <?php echo esc_attr($status_class($article_status)); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $article_status))); ?></span></td>
                                    <td class="nba-inline-actions">
                                        <a class="button button-small" href="<?php echo esc_url(home_url('/editor-dashboard/?review=' . $post->ID)); ?>"><?php esc_html_e('Review', 'newsblenda-accounts'); ?></a>
                                        <button type="button" class="button button-small" data-nba-modal="nba-approve-modal" data-post-id="<?php echo esc_attr((string) $post->ID); ?>" data-action="approve"><?php esc_html_e('Approve', 'newsblenda-accounts'); ?></button>
                                        <button type="button" class="button button-small" data-nba-modal="nba-revision-modal" data-post-id="<?php echo esc_attr((string) $post->ID); ?>" data-action="revision"><?php esc_html_e('Request Revision', 'newsblenda-accounts'); ?></button>
                                        <button type="button" class="button button-small nba-btn-danger" data-nba-modal="nba-reject-modal" data-post-id="<?php echo esc_attr((string) $post->ID); ?>" data-action="reject"><?php esc_html_e('Reject', 'newsblenda-accounts'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="nba-section">
            <div class="nba-section-header"><h2><?php esc_html_e('Revision Requests', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <?php if (empty($revision_posts)) : ?>
                    <p><?php esc_html_e('No articles are waiting on author revisions.', 'newsblenda-accounts'); ?></p>
                <?php else : ?>
                    <table class="nba-table">
                        <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Requested Date', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Feedback', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th></tr></thead>
                        <tbody>
                            <?php global $wpdb; foreach ($revision_posts as $post) :
                                $revision = $wpdb->get_row($wpdb->prepare('SELECT feedback, severity, requested_at FROM ' . $wpdb->prefix . 'nb_article_revisions WHERE article_id = %d ORDER BY requested_at DESC LIMIT 1', $post->ID));
                                ?>
                                <tr>
                                    <td><a href="<?php echo esc_url(home_url('/editor-dashboard/?review=' . $post->ID)); ?>"><?php echo esc_html($post->post_title); ?></a></td>
                                    <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                                    <td><?php echo esc_html($revision?->requested_at ?: get_the_modified_date(get_option('date_format'), $post)); ?></td>
                                    <td><?php echo esc_html(wp_trim_words((string) ($revision->feedback ?? ''), 14)); ?></td>
                                    <td><span class="nba-badge nba-badge-revision"><?php echo esc_html(ucfirst((string) ($revision->severity ?? 'minor'))); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <div class="nba-two-column" id="articles">
            <section class="nba-section"><div class="nba-section-header"><h2><?php esc_html_e('Scheduled Articles', 'newsblenda-accounts'); ?></h2></div><div class="nba-card"><ul class="nba-editor-list"><?php if (empty($scheduled_posts)) : ?><li><?php esc_html_e('No scheduled articles.', 'newsblenda-accounts'); ?></li><?php else : foreach ($scheduled_posts as $post) : ?><li><a href="<?php echo esc_url(home_url('/editor-dashboard/?review=' . $post->ID)); ?>"><?php echo esc_html($post->post_title); ?></a><span><?php echo esc_html(get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $post)); ?></span></li><?php endforeach; endif; ?></ul></div></section>
            <section class="nba-section"><div class="nba-section-header"><h2><?php esc_html_e('Recently Published', 'newsblenda-accounts'); ?></h2></div><div class="nba-card"><ul class="nba-editor-list"><?php if (empty($published_posts)) : ?><li><?php esc_html_e('No published articles found.', 'newsblenda-accounts'); ?></li><?php else : foreach ($published_posts as $post) : ?><li><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a><span><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></span></li><?php endforeach; endif; ?></ul></div></section>
        </div>

        <section class="nba-section" id="authors">
            <div class="nba-section-header"><h2><?php esc_html_e('Author Profiles', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <table class="nba-table">
                    <thead><tr><th><?php esc_html_e('Author', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Articles Published', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Pending', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Joined', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($authors)) : ?>
                            <tr><td colspan="5"><?php esc_html_e('No authors found.', 'newsblenda-accounts'); ?></td></tr>
                        <?php else : foreach ($authors as $author) : ?>
                            <tr>
                                <td><?php echo esc_html($author->display_name); ?></td>
                                <td><?php echo esc_html((string) count_user_posts($author->ID, 'post', true)); ?></td>
                                <td><?php echo esc_html((string) count(get_posts(['post_type' => 'post', 'post_status' => 'pending', 'author' => $author->ID, 'posts_per_page' => -1]))); ?></td>
                                <td><?php echo esc_html(mysql2date(get_option('date_format'), $author->user_registered)); ?></td>
                                <td><a class="button button-small" href="<?php echo esc_url(home_url('/profile/?user_id=' . $author->ID)); ?>"><?php esc_html_e('View Profile', 'newsblenda-accounts'); ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div id="nba-approve-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Approve Article', 'newsblenda-accounts'); ?></h2><form id="nba-approve-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value=""><label for="nba-publish-now"><?php esc_html_e('Publishing Mode', 'newsblenda-accounts'); ?></label><select id="nba-publish-now" name="publish_now"><option value="publish"><?php esc_html_e('Publish now', 'newsblenda-accounts'); ?></option><option value="schedule"><?php esc_html_e('Schedule for later', 'newsblenda-accounts'); ?></option></select><label for="nba-scheduled-at"><?php esc_html_e('Schedule Date', 'newsblenda-accounts'); ?></label><input id="nba-scheduled-at" type="datetime-local" name="scheduled_at" value=""><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button button-primary"><?php esc_html_e('Confirm Approval', 'newsblenda-accounts'); ?></button></div></form></div></div>
<div id="nba-reject-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Reject Article', 'newsblenda-accounts'); ?></h2><form id="nba-reject-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value=""><label for="nba-reject-reason"><?php esc_html_e('Reason', 'newsblenda-accounts'); ?></label><select id="nba-reject-reason" name="reason"><option value="plagiarism"><?php esc_html_e('Plagiarism', 'newsblenda-accounts'); ?></option><option value="low-quality"><?php esc_html_e('Low-quality', 'newsblenda-accounts'); ?></option><option value="off-topic"><?php esc_html_e('Off-topic', 'newsblenda-accounts'); ?></option><option value="other"><?php esc_html_e('Other', 'newsblenda-accounts'); ?></option></select><label for="nba-reject-comments"><?php esc_html_e('Comments', 'newsblenda-accounts'); ?></label><textarea id="nba-reject-comments" name="comments"></textarea><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button nba-btn-danger"><?php esc_html_e('Reject Article', 'newsblenda-accounts'); ?></button></div></form></div></div>
<div id="nba-revision-modal" class="nba-modal-overlay" aria-hidden="true"><div class="nba-modal"><button type="button" class="nba-modal-close" aria-label="<?php esc_attr_e('Close', 'newsblenda-accounts'); ?>">×</button><h2><?php esc_html_e('Request Revisions', 'newsblenda-accounts'); ?></h2><form id="nba-revision-form"><?php wp_nonce_field('nb_workflow_nonce', 'security'); ?><input type="hidden" name="post_id" value=""><label for="nba-revision-severity"><?php esc_html_e('Severity', 'newsblenda-accounts'); ?></label><select id="nba-revision-severity" name="severity"><option value="minor"><?php esc_html_e('Minor', 'newsblenda-accounts'); ?></option><option value="major"><?php esc_html_e('Major', 'newsblenda-accounts'); ?></option></select><label for="nba-revision-feedback"><?php esc_html_e('Feedback', 'newsblenda-accounts'); ?></label><textarea id="nba-revision-feedback" name="feedback"></textarea><div class="nba-modal-actions"><button type="button" class="button nba-modal-cancel"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button><button type="submit" class="button button-primary"><?php esc_html_e('Send Request', 'newsblenda-accounts'); ?></button></div></form></div></div>
