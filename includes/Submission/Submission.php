<?php
declare(strict_types=1);

namespace Newsblenda\Accounts\Submission;

use Newsblenda\Accounts\Validation\Validator;

defined('ABSPATH') || exit;

class Submission
{
    public function __construct()
    {
        add_action('init', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (
            !is_user_logged_in() ||
            empty($_POST['nb_submit_article'])
        ) {
            return;
        }

        check_admin_referer('nb_submit_article');

        $user_id = get_current_user_id();

        $validator = new Validator();

        if (!$validator->validate($_POST)) {
            set_transient(
                'nb_submission_errors_' . $user_id,
                $validator->report(),
                300
            );

            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        $status = isset($_POST['save_draft']) ? 'draft' : 'pending';

        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field($_POST['post_title'] ?? ''),
            'post_content' => wp_kses_post($_POST['article_content'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($_POST['meta_description'] ?? ''),
            'post_status'  => $status,
            'post_type'    => 'post',
            'post_author'  => $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('error', '1', wp_get_referer()));
            exit;
        }

        if (!empty($_POST['category'])) {
            wp_set_post_categories($post_id, [(int) $_POST['category']]);
        }

        if (!empty($_POST['tags'])) {
            wp_set_post_tags($post_id, sanitize_text_field($_POST['tags']));
        }

        update_post_meta($post_id, 'nb_seo_title', sanitize_text_field($_POST['seo_title'] ?? ''));
        update_post_meta($post_id, 'nb_meta_description', sanitize_textarea_field($_POST['meta_description'] ?? ''));
        update_post_meta($post_id, 'nb_sources', sanitize_textarea_field($_POST['sources'] ?? ''));

        if (!empty($_FILES['featured_image']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload('featured_image', $post_id);

            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        $validator->save_report($post_id);

        do_action('nb_accounts_article_submitted', $post_id, $user_id);

        wp_safe_redirect(add_query_arg('submitted', '1', home_url('/submit/')));
        exit;
    }
}
