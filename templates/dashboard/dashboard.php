<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in() || !current_user_can('nb_access_dashboard')) {
    echo '<div class="nba-message nba-message-error">' . esc_html__('You do not have permission to access this dashboard.', 'newsblenda-accounts') . '</div>';
    return;
}

$user = $user ?? wp_get_current_user();
global $wpdb;

$published_posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'author' => $user->ID, 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC']);
$pending_posts = get_posts(['post_type' => 'post', 'post_status' => 'pending', 'author' => $user->ID, 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC']);
$draft_posts = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'draft',
    'author'         => $user->ID,
    'posts_per_page' => 10,
    'orderby'        => 'modified',
    'order'          => 'DESC',
    'meta_query'     => [
        'relation' => 'OR',
        ['key' => 'nb_editor_status', 'compare' => 'NOT EXISTS'],
        ['key' => 'nb_editor_status', 'value' => 'revision_requested', 'compare' => '!='],
    ],
]);
$revision_posts = get_posts(['post_type' => 'post', 'post_status' => 'draft', 'author' => $user->ID, 'posts_per_page' => 10, 'meta_key' => 'nb_editor_status', 'meta_value' => 'revision_requested', 'orderby' => 'modified', 'order' => 'DESC']);
$notifications = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'nb_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 5', $user->ID));
$recent_activity = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'nb_activity WHERE user_id = %d ORDER BY created_at DESC LIMIT 10', $user->ID));
$published_count = $published_count ?? count_user_posts($user->ID, 'post', true);
$pending_count = $pending_count ?? count($pending_posts);
$drafts_count = $drafts_count ?? count($draft_posts);
$revision_count = $revision_count ?? count($revision_posts);
$earnings = $earnings ?? (float) get_user_meta($user->ID, 'nb_total_earnings', true);
$profile_completion = $profile_completion ?? 0;
if ($profile_completion === 0) {
    $fields = ['first_name', 'last_name', 'description', 'nb_phone', 'nb_country', 'nb_state', 'nb_city', 'nb_niche', 'nb_payment_method', 'nb_whatsapp', 'nb_gender'];
    $complete = 0;
    foreach ($fields as $field) {
        if (!empty(get_user_meta($user->ID, $field, true))) {
            $complete++;
        }
    }
    $profile_completion = (int) round(($complete / count($fields)) * 100);
}
$unread_notifications = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'nb_notifications WHERE user_id = %d AND is_read = 0', $user->ID));
$status_class = static function (string $status): string {
    return match ($status) {
        'submitted', 'pending' => 'nba-badge-submitted',
        'revision-requested', 'revision-submitted' => 'nba-badge-revision',
        'approved' => 'nba-badge-approved',
        'rejected' => 'nba-badge-rejected',
        'publish', 'published' => 'nba-badge-published',
        default => 'nba-badge-draft',
    };
};
?>
<div class="nba-dashboard-wrap">
    <nav class="nba-sidebar">
        <div class="nba-sidebar-header"><a class="nba-logo" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php esc_html_e('Newsblenda', 'newsblenda-accounts'); ?></a></div>
        <div class="nba-sidebar-section"><?php esc_html_e('Author', 'newsblenda-accounts'); ?></div>
        <ul class="nba-sidebar-nav">
            <li><a class="active" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php esc_html_e('Dashboard', 'newsblenda-accounts'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/submit/')); ?>"><?php esc_html_e('Submit Article', 'newsblenda-accounts'); ?></a></li>
            <li><a href="#my-articles"><?php esc_html_e('My Articles', 'newsblenda-accounts'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/earnings/')); ?>"><?php esc_html_e('Earnings', 'newsblenda-accounts'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/notifications/')); ?>"><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?><?php if ($unread_notifications > 0) : ?><span class="nba-badge-count"><?php echo esc_html((string) $unread_notifications); ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/profile/')); ?>"><?php esc_html_e('Profile', 'newsblenda-accounts'); ?></a></li>
            <li><a href="<?php echo esc_url(home_url('/logout/')); ?>"><?php esc_html_e('Logout', 'newsblenda-accounts'); ?></a></li>
        </ul>
    </nav>

    <main class="nba-dashboard-main">
        <header class="nba-dash-header">
            <h1><?php printf(esc_html__('Welcome back, %s.', 'newsblenda-accounts'), esc_html($user->display_name)); ?></h1>
            <p><?php esc_html_e('Author Dashboard.', 'newsblenda-accounts'); ?></p>
        </header>

        <section class="nba-stat-cards">
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Published Articles', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $published_count); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Pending Review', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $pending_count); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Awaiting Revision', 'newsblenda-accounts'); ?></div><div class="nba-stat-value"><?php echo esc_html((string) $revision_count); ?></div></div>
            <div class="nba-stat-card"><div class="nba-stat-label"><?php esc_html_e('Total Earnings', 'newsblenda-accounts'); ?></div><div class="nba-stat-value">£<?php echo esc_html(number_format($earnings, 2)); ?></div></div>
        </section>

        <section class="nba-section">
            <div class="nba-section-header"><h2><?php esc_html_e('Pending Submissions', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <table class="nba-table">
                    <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Submitted Date', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Action', 'newsblenda-accounts'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($pending_posts)) : ?>
                            <tr><td colspan="4"><?php esc_html_e('No pending submissions.', 'newsblenda-accounts'); ?></td></tr>
                        <?php else : foreach ($pending_posts as $post) : $article_status = (string) get_post_meta($post->ID, 'nb_article_status', true) ?: 'submitted'; ?>
                            <tr>
                                <td><?php echo esc_html($post->post_title); ?></td>
                                <td><?php echo esc_html(get_post_meta($post->ID, 'nb_submitted_at', true) ?: get_the_date(get_option('date_format'), $post)); ?></td>
                                <td><span class="nba-badge <?php echo esc_attr($status_class($article_status)); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $article_status))); ?></span></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(home_url('/dashboard/')); ?>">
                                        <?php wp_nonce_field('nb_cancel_submission_action'); ?>
                                        <input type="hidden" name="nb_cancel_submission" value="1">
                                        <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                                        <button type="submit" class="button button-small"><?php esc_html_e('Cancel', 'newsblenda-accounts'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nba-section">
            <div class="nba-section-header"><h2><?php esc_html_e('Revision Requests', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <table class="nba-table">
                    <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Requested Date', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Editor Feedback', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Action', 'newsblenda-accounts'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($revision_posts)) : ?>
                            <tr><td colspan="4"><?php esc_html_e('No revision requests right now.', 'newsblenda-accounts'); ?></td></tr>
                        <?php else : foreach ($revision_posts as $post) : $revision = $wpdb->get_row($wpdb->prepare('SELECT feedback, requested_at FROM ' . $wpdb->prefix . 'nb_article_revisions WHERE article_id = %d ORDER BY requested_at DESC LIMIT 1', $post->ID)); ?>
                            <tr>
                                <td><?php echo esc_html($post->post_title); ?></td>
                                <td><?php echo esc_html($revision?->requested_at ?: get_the_modified_date(get_option('date_format'), $post)); ?></td>
                                <td><?php echo esc_html(wp_trim_words((string) ($revision->feedback ?? ''), 16)); ?></td>
                                <td><a class="button button-small" href="<?php echo esc_url(home_url('/submit/?resubmit=' . $post->ID)); ?>"><?php esc_html_e('Resubmit', 'newsblenda-accounts'); ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nba-section" id="my-articles">
            <div class="nba-section-header"><h2><?php esc_html_e('Published Articles', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <table class="nba-table">
                    <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Published Date', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Views', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($published_posts)) : ?>
                            <tr><td colspan="4"><?php esc_html_e('No published articles yet.', 'newsblenda-accounts'); ?></td></tr>
                        <?php else : foreach ($published_posts as $post) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a></td>
                                <td><?php echo esc_html(get_the_date(get_option('date_format'), $post)); ?></td>
                                <td><?php echo esc_html((string) ((int) get_post_meta($post->ID, 'nb_total_views', true))); ?></td>
                                <td><span class="nba-badge nba-badge-published"><?php esc_html_e('Published', 'newsblenda-accounts'); ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nba-section">
            <div class="nba-section-header"><h2><?php esc_html_e('Draft Articles', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <table class="nba-table">
                    <thead><tr><th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Last Saved', 'newsblenda-accounts'); ?></th><th><?php esc_html_e('Actions', 'newsblenda-accounts'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($draft_posts)) : ?>
                            <tr><td colspan="3"><?php esc_html_e('No drafts found.', 'newsblenda-accounts'); ?></td></tr>
                        <?php else : foreach ($draft_posts as $post) : ?>
                            <tr>
                                <td><?php echo esc_html($post->post_title); ?></td>
                                <td><?php echo esc_html(get_the_modified_date(get_option('date_format') . ' ' . get_option('time_format'), $post)); ?></td>
                                <td class="nba-inline-actions"><a class="button button-small" href="<?php echo esc_url(home_url('/submit/?post_id=' . $post->ID)); ?>"><?php esc_html_e('Edit', 'newsblenda-accounts'); ?></a><a class="button button-small nba-btn-danger" href="<?php echo esc_url(get_delete_post_link($post->ID, '', true)); ?>"><?php esc_html_e('Delete', 'newsblenda-accounts'); ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="nba-section">
            <div class="nba-section-header"><h2><?php esc_html_e('Recent Activity', 'newsblenda-accounts'); ?></h2></div>
            <div class="nba-card">
                <ul class="nba-workflow-log">
                    <?php if (empty($recent_activity)) : ?>
                        <li><?php esc_html_e('No recent activity found.', 'newsblenda-accounts'); ?></li>
                    <?php else : foreach ($recent_activity as $activity) : ?>
                        <li><span class="log-action"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $activity->action))); ?></span><div class="log-meta"><?php echo esc_html((string) $activity->description); ?> · <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime((string) $activity->created_at))); ?></div></li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </section>
    </main>
</div>
