<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Dashboard;

use Newsblenda\Accounts\Database\Database;
use Newsblenda\Accounts\Notifications\Notifications;
use Newsblenda\Accounts\Workflow\Workflow;

defined('ABSPATH') || exit;

class EditorDashboard
{
    public function __construct()
    {
        add_shortcode('nb_editor_dashboard', [$this, 'render']);
        add_shortcode('nb_article_review', [$this, 'render_review']);
        add_action('init', [$this, 'handle_actions']);
    }

    public function render(): string
    {
        if (!is_user_logged_in() || !current_user_can('nb_review_articles')) {
            return $this->message(__('You do not have permission to access the editor dashboard.', 'newsblenda-accounts'));
        }

        if (isset($_GET['review']) && absint(wp_unslash($_GET['review'])) > 0) {
            return $this->render_review();
        }

        $user = wp_get_current_user();
        $data = $this->get_dashboard_data();

        $user              = $data['user'];
        $pending_posts     = $data['pending_posts'];
        $scheduled_posts   = $data['scheduled_posts'];
        $published_posts   = $data['published_posts'];
        $authors           = $data['authors'];
        $revision_posts    = $data['revision_posts'];
        $rejected_posts    = $data['rejected_posts'];
        $pending_count     = $data['pending_count'];
        $in_review_count   = $data['in_review_count'];
        $published_this_month = $data['published_this_month'];
        $authors_managed   = $data['authors_managed'];
        $awaiting_revision = $data['awaiting_revision'];
        $notification_count = $data['notification_count'];

        ob_start();
        include NB_ACCOUNTS_PATH . 'templates/dashboard/editor.php';

        return (string) ob_get_clean();
    }

    public function handle_actions(): void
    {
        if (!is_user_logged_in() || !current_user_can('nb_review_articles') || empty($_GET['nb_editor_action'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_GET['nb_editor_action']));
        $notification_id = isset($_GET['notification']) ? absint(wp_unslash($_GET['notification'])) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if ($action !== 'mark_notification_read' || $notification_id < 1 || !wp_verify_nonce($nonce, 'nb_editor_action')) {
            return;
        }

        $notifications = new Notifications();
        $notifications->mark_read($notification_id, get_current_user_id());

        wp_safe_redirect(remove_query_arg(['nb_editor_action', 'notification', '_wpnonce']));
        exit;
    }

    public function get_dashboard_data(): array
    {
        global $wpdb;

        $user = wp_get_current_user();
        $pending_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'pending',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $scheduled_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'future',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ]);

        $published_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'date_query'     => [
                [
                    'year'  => (int) gmdate('Y'),
                    'month' => (int) gmdate('n'),
                ],
            ],
        ]);

        $revision_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'draft',
            'posts_per_page' => 8,
            'meta_key'       => 'nb_editor_status',
            'meta_value'     => 'revision_requested',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $rejected_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'draft',
            'posts_per_page' => 5,
            'meta_key'       => 'nb_editor_status',
            'meta_value'     => 'rejected',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $authors = get_users([
            'role'    => 'nb_author',
            'number'  => 10,
            'orderby' => 'registered',
            'order'   => 'DESC',
        ]);

        $pending_count = (int) wp_count_posts('post')->pending;
        $in_review_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                'nb_article_status',
                'in-review'
            )
        );
        $published_this_month = count($published_posts);
        $authors_managed = count($authors);
        $awaiting_revision = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                'nb_editor_status',
                'revision_requested'
            )
        );
        $notification_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . Database::table('notifications') . ' WHERE user_id = %d AND is_read = 0',
                $user->ID
            )
        );

        return compact(
            'user',
            'pending_posts',
            'scheduled_posts',
            'published_posts',
            'authors',
            'revision_posts',
            'rejected_posts',
            'pending_count',
            'in_review_count',
            'published_this_month',
            'authors_managed',
            'awaiting_revision',
            'notification_count'
        );
    }

    public function render_review(): string
    {
        if (!is_user_logged_in() || !current_user_can('nb_review_articles')) {
            return $this->message(__('You do not have permission to review this article.', 'newsblenda-accounts'));
        }

        $post_id = isset($_GET['review']) ? absint(wp_unslash($_GET['review'])) : (isset($_GET['post_id']) ? absint(wp_unslash($_GET['post_id'])) : 0);
        $post = $post_id > 0 ? get_post($post_id) : null;

        if (!$post instanceof \WP_Post || $post->post_type !== 'post') {
            return $this->message(__('The requested article could not be found.', 'newsblenda-accounts'));
        }

        global $wpdb;

        $author = get_userdata((int) $post->post_author);
        $revisions = Workflow::get_revisions($post_id);
        $workflow_log = Workflow::get_workflow_log($post_id);
        $feedback_history = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . Database::table('article_feedback') . ' WHERE article_id = %d ORDER BY created_at DESC',
                $post_id
            ),
            ARRAY_A
        ) ?: [];

        ob_start();
        include NB_ACCOUNTS_PATH . 'templates/dashboard/review.php';

        return (string) ob_get_clean();
    }

    private function message(string $message): string
    {
        return '<div class="nba-message nba-message-error">' . esc_html($message) . '</div>';
    }
}
