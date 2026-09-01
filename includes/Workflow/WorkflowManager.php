<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Workflow;

use Newsblenda\Accounts\Database\Database;
use Newsblenda\Accounts\Notifications\Notifications;
use Newsblenda\Accounts\Email\Mailer;

defined('ABSPATH') || exit;

class WorkflowManager
{
    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT               = 'draft';
    public const STATUS_PENDING_REVIEW      = 'pending_review';
    public const STATUS_REVISION_REQUESTED  = 'revision_requested';
    public const STATUS_APPROVED            = 'approved';
    public const STATUS_REJECTED            = 'rejected';
    public const STATUS_PUBLISHED           = 'published';
    public const STATUS_SCHEDULED           = 'scheduled';
    public const STATUS_ARCHIVED            = 'archived';

    /**
     * Valid status transitions: from_status => [allowed to_statuses]
     */
    private const TRANSITIONS = [
        self::STATUS_DRAFT              => [self::STATUS_PENDING_REVIEW],
        self::STATUS_PENDING_REVIEW     => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_REVISION_REQUESTED,
        ],
        self::STATUS_REVISION_REQUESTED => [self::STATUS_PENDING_REVIEW],
        self::STATUS_REJECTED           => [self::STATUS_PENDING_REVIEW],
        self::STATUS_APPROVED           => [
            self::STATUS_PUBLISHED,
            self::STATUS_SCHEDULED,
        ],
        self::STATUS_PUBLISHED          => [
            self::STATUS_ARCHIVED,
            self::STATUS_DRAFT,
        ],
        self::STATUS_SCHEDULED          => [
            self::STATUS_PUBLISHED,
            self::STATUS_DRAFT,
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_nb_approve_article',    [$this, 'ajax_approve']);
        add_action('wp_ajax_nb_reject_article',     [$this, 'ajax_reject']);
        add_action('wp_ajax_nb_request_revision',   [$this, 'ajax_request_revision']);
        add_action('wp_ajax_nb_resubmit_article',   [$this, 'ajax_resubmit']);
        add_action('wp_ajax_nb_publish_article',    [$this, 'ajax_publish']);
        add_action('wp_ajax_nb_delete_draft',       [$this, 'ajax_delete_draft']);

        add_action(
            'nb_accounts_article_submitted',
            [$this, 'on_article_submitted'],
            10,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Editor Actions
    |--------------------------------------------------------------------------
    */

    /**
     * AJAX: Editor approves an article.
     */
    public function ajax_approve(): void
    {
        check_ajax_referer('nb_editor_action', 'nonce');

        if (
            ! current_user_can('nb_approve_articles')
            && ! current_user_can('manage_options')
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id  = absint($_POST['post_id'] ?? 0);
        $comments = sanitize_textarea_field(
            wp_unslash($_POST['comments'] ?? '')
        );

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (! $post) {
            wp_send_json_error(
                ['message' => __('Article not found.', 'newsblenda-accounts')]
            );
        }

        if (
            ! $this->transition(
                $post_id,
                self::STATUS_APPROVED,
                get_current_user_id(),
                $comments
            )
        ) {
            wp_send_json_error(
                ['message' => __('Could not approve article. Check that the article is in the review queue.', 'newsblenda-accounts')]
            );
        }

        $notifications = new Notifications();
        $notifications->create(
            (int) $post->post_author,
            __('Article Approved', 'newsblenda-accounts'),
            sprintf(
                /* translators: %s: article title */
                __('Your article "%s" has been approved and is ready for publishing.', 'newsblenda-accounts'),
                $post->post_title
            ),
            'approval',
            home_url('/dashboard/')
        );

        Mailer::send_article_approved(
            (int) $post->post_author,
            $post->post_title
        );

        do_action(
            'nb_accounts_article_approved',
            $post_id,
            get_current_user_id()
        );

        wp_send_json_success(
            ['message' => __('Article approved successfully.', 'newsblenda-accounts')]
        );
    }

    /**
     * AJAX: Editor rejects an article.
     */
    public function ajax_reject(): void
    {
        check_ajax_referer('nb_editor_action', 'nonce');

        if (
            ! current_user_can('nb_reject_articles')
            && ! current_user_can('manage_options')
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $reason  = sanitize_textarea_field(
            wp_unslash($_POST['reason'] ?? '')
        );

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (! $post) {
            wp_send_json_error(
                ['message' => __('Article not found.', 'newsblenda-accounts')]
            );
        }

        if (
            ! $this->transition(
                $post_id,
                self::STATUS_REJECTED,
                get_current_user_id(),
                '',
                $reason
            )
        ) {
            wp_send_json_error(
                ['message' => __('Could not reject article.', 'newsblenda-accounts')]
            );
        }

        $notifications = new Notifications();
        $notifications->create(
            (int) $post->post_author,
            __('Article Rejected', 'newsblenda-accounts'),
            sprintf(
                /* translators: 1: article title 2: reason */
                __('Your article "%1$s" was not accepted. Reason: %2$s', 'newsblenda-accounts'),
                $post->post_title,
                $reason ?: __('No reason provided.', 'newsblenda-accounts')
            ),
            'rejection',
            home_url('/dashboard/')
        );

        Mailer::send_article_rejected(
            (int) $post->post_author,
            $post->post_title,
            $reason
        );

        do_action(
            'nb_accounts_article_rejected',
            $post_id,
            get_current_user_id(),
            $reason
        );

        wp_send_json_success(
            ['message' => __('Article rejected.', 'newsblenda-accounts')]
        );
    }

    /**
     * AJAX: Editor requests a revision.
     */
    public function ajax_request_revision(): void
    {
        check_ajax_referer('nb_editor_action', 'nonce');

        if (
            ! current_user_can('nb_request_revision')
            && ! current_user_can('manage_options')
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id  = absint($_POST['post_id'] ?? 0);
        $feedback = sanitize_textarea_field(
            wp_unslash($_POST['feedback'] ?? '')
        );

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        if (empty($feedback)) {
            wp_send_json_error(
                ['message' => __('Revision feedback is required.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (! $post) {
            wp_send_json_error(
                ['message' => __('Article not found.', 'newsblenda-accounts')]
            );
        }

        if (
            ! $this->transition(
                $post_id,
                self::STATUS_REVISION_REQUESTED,
                get_current_user_id(),
                '',
                '',
                $feedback
            )
        ) {
            wp_send_json_error(
                ['message' => __('Could not request revision.', 'newsblenda-accounts')]
            );
        }

        $notifications = new Notifications();
        $notifications->create(
            (int) $post->post_author,
            __('Revision Requested', 'newsblenda-accounts'),
            sprintf(
                /* translators: 1: article title 2: feedback */
                __('Your article "%1$s" needs revision. Editor feedback: %2$s', 'newsblenda-accounts'),
                $post->post_title,
                $feedback
            ),
            'revision',
            home_url('/dashboard/')
        );

        Mailer::send_revision_requested(
            (int) $post->post_author,
            $post->post_title,
            $feedback
        );

        do_action(
            'nb_accounts_revision_requested',
            $post_id,
            get_current_user_id(),
            $feedback
        );

        wp_send_json_success(
            ['message' => __('Revision requested. Author has been notified.', 'newsblenda-accounts')]
        );
    }

    /**
     * AJAX: Editor publishes an approved article.
     */
    public function ajax_publish(): void
    {
        check_ajax_referer('nb_editor_action', 'nonce');

        if (
            ! current_user_can('publish_posts')
            && ! current_user_can('manage_options')
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id = absint($_POST['post_id'] ?? 0);

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (! $post) {
            wp_send_json_error(
                ['message' => __('Article not found.', 'newsblenda-accounts')]
            );
        }

        if (
            ! $this->transition(
                $post_id,
                self::STATUS_PUBLISHED,
                get_current_user_id()
            )
        ) {
            wp_send_json_error(
                ['message' => __('Could not publish article. Ensure it has been approved first.', 'newsblenda-accounts')]
            );
        }

        $permalink = (string) get_permalink($post_id);

        $notifications = new Notifications();
        $notifications->create(
            (int) $post->post_author,
            __('Article Published', 'newsblenda-accounts'),
            sprintf(
                /* translators: %s: article title */
                __('Your article "%s" is now live on the site.', 'newsblenda-accounts'),
                $post->post_title
            ),
            'approval',
            $permalink ?: home_url('/dashboard/')
        );

        do_action(
            'nb_accounts_article_published',
            $post_id,
            get_current_user_id()
        );

        wp_send_json_success([
            'message' => __('Article published successfully.', 'newsblenda-accounts'),
            'url'     => esc_url($permalink),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Author Actions
    |--------------------------------------------------------------------------
    */

    /**
     * AJAX: Author resubmits an article after rejection or revision request.
     */
    public function ajax_resubmit(): void
    {
        check_ajax_referer('nb_author_action', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id = absint($_POST['post_id'] ?? 0);

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (
            ! $post
            || (int) $post->post_author !== get_current_user_id()
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $current = self::status($post_id);

        if (
            ! in_array(
                $current,
                [self::STATUS_REJECTED, self::STATUS_REVISION_REQUESTED],
                true
            )
        ) {
            wp_send_json_error(
                ['message' => __('This article cannot be resubmitted in its current state.', 'newsblenda-accounts')]
            );
        }

        if (
            ! $this->transition(
                $post_id,
                self::STATUS_PENDING_REVIEW,
                get_current_user_id()
            )
        ) {
            wp_send_json_error(
                ['message' => __('Could not resubmit article.', 'newsblenda-accounts')]
            );
        }

        // Notify editors of the resubmission.
        $editors       = get_users(['role' => 'nb_editor', 'fields' => ['ID']]);
        $notifications = new Notifications();

        foreach ($editors as $editor) {
            $notifications->create(
                (int) $editor->ID,
                __('Article Resubmitted', 'newsblenda-accounts'),
                sprintf(
                    /* translators: %s: article title */
                    __('"%s" has been resubmitted for review.', 'newsblenda-accounts'),
                    $post->post_title
                ),
                'submission',
                home_url('/editor-dashboard/')
            );
        }

        do_action(
            'nb_accounts_article_resubmitted',
            $post_id,
            get_current_user_id()
        );

        wp_send_json_success(
            ['message' => __('Article resubmitted for review.', 'newsblenda-accounts')]
        );
    }

    /**
     * AJAX: Author deletes a draft article.
     */
    public function ajax_delete_draft(): void
    {
        check_ajax_referer('nb_author_action', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $post_id = absint($_POST['post_id'] ?? 0);

        if ($post_id < 1) {
            wp_send_json_error(
                ['message' => __('Invalid article.', 'newsblenda-accounts')]
            );
        }

        $post = get_post($post_id);

        if (
            ! $post
            || (int) $post->post_author !== get_current_user_id()
        ) {
            wp_send_json_error(
                ['message' => __('Access denied.', 'newsblenda-accounts')]
            );
        }

        $workflow_status = self::status($post_id);

        if ($workflow_status !== self::STATUS_DRAFT) {
            wp_send_json_error(
                ['message' => __('Only drafts can be deleted.', 'newsblenda-accounts')]
            );
        }

        $deleted = wp_delete_post($post_id, true);

        if (! $deleted) {
            wp_send_json_error(
                ['message' => __('Could not delete article.', 'newsblenda-accounts')]
            );
        }

        wp_send_json_success(
            ['message' => __('Draft deleted.', 'newsblenda-accounts')]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Respond to a new article submission.
     */
    public function on_article_submitted(int $post_id, int $author_id): void
    {
        // Only initialise if the workflow meta has not already been set.
        if (get_post_meta($post_id, 'nb_workflow_status', true) !== '') {
            return;
        }

        update_post_meta(
            $post_id,
            'nb_workflow_status',
            self::STATUS_PENDING_REVIEW
        );

        update_post_meta(
            $post_id,
            'nb_workflow_changed_at',
            current_time('mysql')
        );

        update_post_meta(
            $post_id,
            'nb_workflow_changed_by',
            $author_id
        );

        $this->record_history(
            $post_id,
            $author_id,
            null,
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REVIEW
        );

        $post = get_post($post_id);

        if (! $post) {
            return;
        }

        $editors       = get_users(['role' => 'nb_editor', 'fields' => ['ID']]);
        $notifications = new Notifications();

        foreach ($editors as $editor) {
            $notifications->create(
                (int) $editor->ID,
                __('New Article Submitted', 'newsblenda-accounts'),
                sprintf(
                    /* translators: %s: article title */
                    __('A new article "%s" has been submitted for review.', 'newsblenda-accounts'),
                    $post->post_title
                ),
                'submission',
                home_url('/editor-dashboard/')
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | State Machine
    |--------------------------------------------------------------------------
    */

    /**
     * Perform a status transition.
     */
    public function transition(
        int    $post_id,
        string $new_status,
        int    $actor_id,
        string $comments          = '',
        string $rejection_reason  = '',
        string $revision_requests = ''
    ): bool {

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        $current = (string) (
            get_post_meta($post_id, 'nb_workflow_status', true)
            ?: self::STATUS_DRAFT
        );

        if (! $this->is_valid_transition($current, $new_status)) {
            return false;
        }

        $this->record_history(
            $post_id,
            (int) $post->post_author,
            $actor_id,
            $current,
            $new_status,
            $comments,
            $revision_requests,
            $rejection_reason
        );

        update_post_meta($post_id, 'nb_workflow_status',          $new_status);
        update_post_meta($post_id, 'nb_workflow_previous_status', $current);
        update_post_meta($post_id, 'nb_workflow_changed_at',      current_time('mysql'));
        update_post_meta($post_id, 'nb_workflow_changed_by',      $actor_id);

        if ($rejection_reason !== '') {
            update_post_meta($post_id, 'nb_rejection_reason', $rejection_reason);
        }

        if ($revision_requests !== '') {
            update_post_meta($post_id, 'nb_revision_requests', $revision_requests);
        }

        if ($comments !== '') {
            update_post_meta($post_id, 'nb_editor_comments', $comments);
        }

        $wp_status = $this->wp_status($new_status);

        if ($wp_status !== null && $post->post_status !== $wp_status) {
            wp_update_post([
                'ID'          => $post_id,
                'post_status' => $wp_status,
            ]);
        }

        do_action(
            'nb_accounts_workflow_transition',
            $post_id,
            $current,
            $new_status,
            $actor_id
        );

        do_action(
            'newsblenda_workflow_status_changed',
            $post_id,
            $current,
            $new_status,
            $actor_id
        );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the workflow status of a post.
     */
    public static function status(int $post_id): string
    {
        $status = (string) get_post_meta(
            $post_id,
            'nb_workflow_status',
            true
        );

        if ($status !== '') {
            return $status;
        }

        // Fall back to mapping from WP post status for backward compatibility.
        $post = get_post($post_id);

        if (! $post) {
            return self::STATUS_DRAFT;
        }

        $map = [
            'publish' => self::STATUS_PUBLISHED,
            'pending' => self::STATUS_PENDING_REVIEW,
            'future'  => self::STATUS_SCHEDULED,
            'draft'   => self::STATUS_DRAFT,
        ];

        return $map[$post->post_status] ?? self::STATUS_DRAFT;
    }

    /**
     * Rejection reason for a post.
     */
    public static function rejection_reason(int $post_id): string
    {
        return (string) get_post_meta(
            $post_id,
            'nb_rejection_reason',
            true
        );
    }

    /**
     * Revision requests for a post.
     */
    public static function revision_requests(int $post_id): string
    {
        return (string) get_post_meta(
            $post_id,
            'nb_revision_requests',
            true
        );
    }

    /**
     * Editor comments for a post.
     */
    public static function editor_comments(int $post_id): string
    {
        return (string) get_post_meta(
            $post_id,
            'nb_editor_comments',
            true
        );
    }

    /**
     * Full workflow history for a post.
     */
    public static function history(
        int $post_id,
        int $limit = 20
    ): array {

        global $wpdb;

        $table = Database::table('workflow_history');

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE post_id = %d
                 ORDER BY created_at DESC
                 LIMIT %d",
                $post_id,
                $limit
            )
        );
    }

    /**
     * Human-readable label for a workflow status.
     */
    public static function status_label(string $status): string
    {
        $labels = [
            self::STATUS_DRAFT              => __('Draft', 'newsblenda-accounts'),
            self::STATUS_PENDING_REVIEW     => __('Pending Review', 'newsblenda-accounts'),
            self::STATUS_REVISION_REQUESTED => __('Revision Requested', 'newsblenda-accounts'),
            self::STATUS_APPROVED           => __('Approved', 'newsblenda-accounts'),
            self::STATUS_REJECTED           => __('Rejected', 'newsblenda-accounts'),
            self::STATUS_PUBLISHED          => __('Published', 'newsblenda-accounts'),
            self::STATUS_SCHEDULED          => __('Scheduled', 'newsblenda-accounts'),
            self::STATUS_ARCHIVED           => __('Archived', 'newsblenda-accounts'),
        ];

        return $labels[$status]
            ?? ucwords(str_replace('_', ' ', $status));
    }

    /**
     * CSS badge class for a workflow status.
     */
    public static function status_badge_class(string $status): string
    {
        $classes = [
            self::STATUS_DRAFT              => 'nba-badge-info',
            self::STATUS_PENDING_REVIEW     => 'nba-badge-warning',
            self::STATUS_REVISION_REQUESTED => 'nba-badge-warning',
            self::STATUS_APPROVED           => 'nba-badge-success',
            self::STATUS_REJECTED           => 'nba-badge-danger',
            self::STATUS_PUBLISHED          => 'nba-badge-success',
            self::STATUS_SCHEDULED          => 'nba-badge-info',
            self::STATUS_ARCHIVED           => 'nba-badge-info',
        ];

        return $classes[$status] ?? 'nba-badge-info';
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a transition between two statuses is valid.
     */
    private function is_valid_transition(string $from, string $to): bool
    {
        return isset(self::TRANSITIONS[$from])
            && in_array($to, self::TRANSITIONS[$from], true);
    }

    /**
     * Map a workflow status to the equivalent WordPress post status.
     */
    private function wp_status(string $workflow_status): ?string
    {
        $map = [
            self::STATUS_DRAFT              => 'draft',
            self::STATUS_PENDING_REVIEW     => 'pending',
            self::STATUS_REVISION_REQUESTED => 'draft',
            self::STATUS_APPROVED           => 'draft',
            self::STATUS_REJECTED           => 'draft',
            self::STATUS_PUBLISHED          => 'publish',
            self::STATUS_SCHEDULED          => 'future',
            self::STATUS_ARCHIVED           => 'draft',
        ];

        return $map[$workflow_status] ?? null;
    }

    /**
     * Insert a workflow history record.
     */
    private function record_history(
        int    $post_id,
        int    $author_id,
        ?int   $editor_id,
        string $previous_status,
        string $current_status,
        string $editor_comments   = '',
        string $revision_requests = '',
        string $rejection_reason  = ''
    ): void {

        global $wpdb;

        $table = Database::table('workflow_history');

        $wpdb->insert(
            $table,
            [
                'post_id'           => $post_id,
                'author_id'         => $author_id,
                'editor_id'         => $editor_id,
                'previous_status'   => $previous_status,
                'current_status'    => $current_status,
                'editor_comments'   => $editor_comments,
                'revision_requests' => $revision_requests,
                'rejection_reason'  => $rejection_reason,
                'status_changed_at' => current_time('mysql'),
                'created_at'        => current_time('mysql'),
            ],
            [
                '%d',
                '%d',
                $editor_id !== null ? '%d' : '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
}
