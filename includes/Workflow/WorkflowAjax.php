<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Workflow;

use Newsblenda\Accounts\Submission\Submission;

defined('ABSPATH') || exit;

class WorkflowAjax
{
    public function __construct()
    {
        add_action('wp_ajax_nb_approve_article', [$this, 'handle_approve']);
        add_action('wp_ajax_nb_reject_article', [$this, 'handle_reject']);
        add_action('wp_ajax_nb_request_revision', [$this, 'handle_revision']);
        add_action('wp_ajax_nb_resubmit_article', [$this, 'handle_resubmit']);
        add_action('wp_ajax_nb_autosave_article', [$this, 'handle_autosave']);
    }

    public function handle_approve(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $editor_id = get_current_user_id();

        if (!user_can($editor_id, 'nb_approve_articles')) {
            wp_send_json_error(['message' => __('You are not allowed to approve articles.', 'newsblenda-accounts')], 403);
        }

        $post = $this->validated_post();
        $publish_mode = isset($_POST['publish_now']) ? sanitize_text_field(wp_unslash($_POST['publish_now'])) : 'publish';
        $scheduled_at = isset($_POST['scheduled_at']) ? sanitize_text_field(wp_unslash($_POST['scheduled_at'])) : '';

        $success = ($publish_mode === 'schedule' && $scheduled_at !== '')
            ? Workflow::schedule((int) $post->ID, $editor_id, $scheduled_at)
            : Workflow::approve((int) $post->ID, $editor_id);

        if (!$success) {
            wp_send_json_error(['message' => __('Unable to process this approval request.', 'newsblenda-accounts')], 400);
        }

        wp_send_json_success([
            'message' => $publish_mode === 'schedule'
                ? __('Article scheduled successfully.', 'newsblenda-accounts')
                : __('Article approved and published.', 'newsblenda-accounts'),
        ]);
    }

    public function handle_reject(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $editor_id = get_current_user_id();

        if (!user_can($editor_id, 'nb_reject_articles')) {
            wp_send_json_error(['message' => __('You are not allowed to reject articles.', 'newsblenda-accounts')], 403);
        }

        $post = $this->validated_post();
        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
        $comments = isset($_POST['comments']) ? sanitize_textarea_field(wp_unslash($_POST['comments'])) : '';
        $message = trim($reason . ($comments !== '' ? ': ' . $comments : ''));

        if ($message === '') {
            wp_send_json_error(['message' => __('A rejection reason is required.', 'newsblenda-accounts')], 400);
        }

        if (!Workflow::reject((int) $post->ID, $editor_id, $message)) {
            wp_send_json_error(['message' => __('Unable to reject this article.', 'newsblenda-accounts')], 400);
        }

        wp_send_json_success(['message' => __('Article rejected.', 'newsblenda-accounts')]);
    }

    public function handle_revision(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $editor_id = get_current_user_id();

        if (!user_can($editor_id, 'nb_request_revision')) {
            wp_send_json_error(['message' => __('You are not allowed to request revisions.', 'newsblenda-accounts')], 403);
        }

        $post = $this->validated_post();
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';
        $severity = isset($_POST['severity']) ? sanitize_text_field(wp_unslash($_POST['severity'])) : 'minor';

        if ($feedback === '') {
            wp_send_json_error(['message' => __('Revision feedback is required.', 'newsblenda-accounts')], 400);
        }

        if (!Workflow::request_revision((int) $post->ID, $editor_id, $feedback, $severity)) {
            wp_send_json_error(['message' => __('Unable to request revisions for this article.', 'newsblenda-accounts')], 400);
        }

        wp_send_json_success(['message' => __('Revision request sent to the author.', 'newsblenda-accounts')]);
    }

    public function handle_resubmit(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $user_id = get_current_user_id();

        if (!user_can($user_id, 'nb_submit_articles')) {
            wp_send_json_error(['message' => __('You are not allowed to resubmit articles.', 'newsblenda-accounts')], 403);
        }

        $post = $this->validated_post();

        if ((int) $post->post_author !== $user_id) {
            wp_send_json_error(['message' => __('You can only resubmit your own articles.', 'newsblenda-accounts')], 403);
        }

        if (!Workflow::resubmit((int) $post->ID, $user_id)) {
            wp_send_json_error(['message' => __('Unable to resubmit this article.', 'newsblenda-accounts')], 400);
        }

        wp_send_json_success(['message' => __('Article resubmitted for review.', 'newsblenda-accounts')]);
    }

    public function handle_autosave(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $user_id = get_current_user_id();

        if (!user_can($user_id, 'nb_submit_articles')) {
            wp_send_json_error(['message' => __('You are not allowed to save drafts.', 'newsblenda-accounts')], 403);
        }

        $result = Submission::autosave_post($user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success($result);
    }

    private function validated_post(): \WP_Post
    {
        $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        $post = $post_id > 0 ? get_post($post_id) : null;

        if (!$post instanceof \WP_Post) {
            wp_send_json_error(['message' => __('Invalid article selected.', 'newsblenda-accounts')], 404);
        }

        return $post;
    }
}
