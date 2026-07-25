<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Submission;

use Newsblenda\Accounts\Validation\Validator;
use Newsblenda\Accounts\Workflow\Workflow;

defined('ABSPATH') || exit;

class Submission
{
    public function __construct()
    {
        add_action('init', [$this, 'handle']);
        add_action('init', [$this, 'handle_cancel_submission']);

        if (!has_action('wp_ajax_nb_autosave_article')) {
            add_action('wp_ajax_nb_autosave_article', [$this, 'handle_autosave']);
        }
    }

    public function handle(): void
    {
        if (!is_user_logged_in() || empty($_POST['nb_submit_article'])) {
            return;
        }

        check_admin_referer('nb_submit_article');

        $user_id = get_current_user_id();

        if (!user_can($user_id, 'nb_submit_articles')) {
            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        $existing_post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        $existing_post = $existing_post_id > 0 ? get_post($existing_post_id) : null;

        if ($existing_post_id > 0 && (!$existing_post instanceof \WP_Post || (int) $existing_post->post_author !== $user_id)) {
            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        $is_draft = isset($_POST['save_draft']);
        $is_resubmit = isset($_POST['resubmit_post_id']) && absint(wp_unslash($_POST['resubmit_post_id'])) > 0;
        $validator = new Validator();

        if (!$is_draft && !$validator->validate($_POST)) {
            set_transient('nb_submission_errors_' . $user_id, $validator->report(), 300);
            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        $post_id = $this->save_post($user_id, $existing_post_id, 'draft');

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        $this->save_taxonomies((int) $post_id);
        $this->save_meta((int) $post_id);
        $this->handle_featured_image((int) $post_id);

        if (!$is_draft) {
            $validator->save_report((int) $post_id);
            $workflow_success = $is_resubmit ? Workflow::resubmit((int) $post_id, $user_id) : Workflow::submit((int) $post_id, $user_id);

            if (!$workflow_success) {
                wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
                exit;
            }
        } else {
            $current_status = get_post_meta((int) $post_id, 'nb_article_status', true);
            if ($current_status !== 'revision-requested') {
                update_post_meta((int) $post_id, 'nb_article_status', 'draft');
            }
        }

        $query_key = $is_draft ? 'draft_saved' : 'submitted';
        wp_safe_redirect(add_query_arg($query_key, '1', home_url('/submit/')));
        exit;
    }

    public function handle_cancel_submission(): void
    {
        if (!is_user_logged_in() || empty($_POST['nb_cancel_submission'])) {
            return;
        }

        check_admin_referer('nb_cancel_submission_action');

        $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        $post = $post_id > 0 ? get_post($post_id) : null;
        $user_id = get_current_user_id();

        if (!$post instanceof \WP_Post || (int) $post->post_author !== $user_id) {
            wp_safe_redirect(home_url('/dashboard/?error=1'));
            exit;
        }

        $result = wp_update_post(['ID' => $post_id, 'post_status' => 'draft'], true);

        if (!is_wp_error($result)) {
            update_post_meta($post_id, 'nb_article_status', 'draft');
        }

        wp_safe_redirect(home_url('/dashboard/?cancelled=1'));
        exit;
    }

    public function handle_autosave(): void
    {
        check_ajax_referer('nb_workflow_nonce', 'security');

        $result = self::autosave_post(get_current_user_id());

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success($result);
    }

    public static function autosave_post(int $user_id)
    {
        if (!user_can($user_id, 'nb_submit_articles')) {
            return new \WP_Error('forbidden', __('You are not allowed to save drafts.', 'newsblenda-accounts'));
        }

        $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        $post = $post_id > 0 ? get_post($post_id) : null;

        if ($post_id > 0 && (!$post instanceof \WP_Post || (int) $post->post_author !== $user_id)) {
            return new \WP_Error('forbidden', __('You can only autosave your own articles.', 'newsblenda-accounts'));
        }

        $payload = [
            'post_title'      => sanitize_text_field(wp_unslash($_POST['post_title'] ?? '')),
            'post_content'    => wp_kses_post(wp_unslash($_POST['article_content'] ?? '')),
            'post_excerpt'    => sanitize_textarea_field(wp_unslash($_POST['meta_description'] ?? '')),
            'post_status'     => 'draft',
            'post_type'       => 'post',
            'post_author'     => $user_id,
        ];

        if ($post_id > 0) {
            $payload['ID'] = $post_id;
            $saved_post_id = wp_update_post($payload, true);
        } else {
            $saved_post_id = wp_insert_post($payload, true);
        }

        if (is_wp_error($saved_post_id)) {
            return $saved_post_id;
        }

        update_post_meta((int) $saved_post_id, 'nb_seo_title', sanitize_text_field(wp_unslash($_POST['seo_title'] ?? '')));
        update_post_meta((int) $saved_post_id, 'nb_meta_description', sanitize_textarea_field(wp_unslash($_POST['meta_description'] ?? '')));
        update_post_meta((int) $saved_post_id, 'nb_sources', sanitize_textarea_field(wp_unslash($_POST['sources'] ?? '')));
        update_post_meta((int) $saved_post_id, 'nb_content_type', sanitize_text_field(wp_unslash($_POST['content_type'] ?? 'article')));

        $current_status = get_post_meta((int) $saved_post_id, 'nb_article_status', true);
        if ($current_status !== 'revision-requested') {
            update_post_meta((int) $saved_post_id, 'nb_article_status', 'draft');
        }

        return [
            'post_id'  => (int) $saved_post_id,
            'message'  => __('Draft saved successfully.', 'newsblenda-accounts'),
        ];
    }

    private function save_post(int $user_id, int $post_id, string $post_status)
    {
        $payload = [
            'post_title'   => sanitize_text_field(wp_unslash($_POST['post_title'] ?? '')),
            'post_content' => wp_kses_post(wp_unslash($_POST['article_content'] ?? '')),
            'post_excerpt' => sanitize_textarea_field(wp_unslash($_POST['meta_description'] ?? '')),
            'post_status'  => $post_status,
            'post_type'    => 'post',
            'post_author'  => $user_id,
        ];

        if ($post_id > 0) {
            $payload['ID'] = $post_id;
            return wp_update_post($payload, true);
        }

        return wp_insert_post($payload, true);
    }

    private function save_taxonomies(int $post_id): void
    {
        if (!empty($_POST['category'])) {
            wp_set_post_categories($post_id, [(int) wp_unslash($_POST['category'])]);
        }

        if (!empty($_POST['tags'])) {
            wp_set_post_tags($post_id, sanitize_text_field(wp_unslash($_POST['tags'])));
        }
    }

    private function save_meta(int $post_id): void
    {
        update_post_meta($post_id, 'nb_seo_title', sanitize_text_field(wp_unslash($_POST['seo_title'] ?? '')));
        update_post_meta($post_id, 'nb_meta_description', sanitize_textarea_field(wp_unslash($_POST['meta_description'] ?? '')));
        update_post_meta($post_id, 'nb_sources', sanitize_textarea_field(wp_unslash($_POST['sources'] ?? '')));
        update_post_meta($post_id, 'nb_content_type', sanitize_text_field(wp_unslash($_POST['content_type'] ?? 'article')));
    }

    private function handle_featured_image(int $post_id): void
    {
        if (empty($_FILES['featured_image']['name'])) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('featured_image', $post_id);

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
}
