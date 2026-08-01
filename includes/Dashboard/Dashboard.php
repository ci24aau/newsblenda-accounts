<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Dashboard;

defined('ABSPATH') || exit;

class Dashboard
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_shortcode(
            'newsblenda_dashboard',
            [$this, 'render']
        );

        add_shortcode(
            'nbe_dashboard',
            [$this, 'render']
        );

        add_shortcode(
            'nb_dashboard',
            [$this, 'render']
        );
    }

    /**
     * Render dashboard.
     */
    public function render(): string
    {
        if (! is_user_logged_in()) {

            return $this->message(
                __('You must be logged in to view your dashboard.', 'newsblenda-accounts'),
                'error'
            );

        }

        // Route editors to the editor dashboard content.
        if (
            current_user_can('nb_review_articles')
            && ! current_user_can('manage_options')
        ) {
            ob_start();
            $template = NB_ACCOUNTS_PATH . 'templates/dashboard/editor.php';
            if (file_exists($template)) {
                include $template;
            }
            return (string) ob_get_clean();
        }

        ob_start();
        $template = NB_ACCOUNTS_PATH . 'templates/dashboard/dashboard.php';
        if (file_exists($template)) {
            $user = wp_get_current_user();
            include $template;
        }
        return (string) ob_get_clean();
    }

    /**
     * Dashboard statistics.
     */
    private function get_dashboard_data(
        int $user_id
    ): array
    {
        $published = (int) count_user_posts(
            $user_id,
            'post',
            true
        );

        $pending = $this->count_posts_by_status(
            $user_id,
            'pending'
        );

        $drafts = $this->count_posts_by_status(
            $user_id,
            'draft'
        );

        $rejected = $this->count_posts_by_status(
            $user_id,
            'rejected'
        );

        $status = get_user_meta(
            $user_id,
            'nb_account_status',
            true
        );

        if (empty($status)) {
            $status = 'Pending';
        }

        $earnings = (float) get_user_meta(
            $user_id,
            'nb_total_earnings',
            true
        );

        return [

            [
                'title' => __('Published Articles', 'newsblenda-accounts'),
                'value' => $published,
            ],

            [
                'title' => __('Pending Review', 'newsblenda-accounts'),
                'value' => $pending,
            ],

            [
                'title' => __('Drafts', 'newsblenda-accounts'),
                'value' => $drafts,
            ],

            [
                'title' => __('Rejected', 'newsblenda-accounts'),
                'value' => $rejected,
            ],

            [
                'title' => __('Account Status', 'newsblenda-accounts'),
                'value' => ucfirst((string) $status),
            ],

            [
                'title' => __('Total Earnings', 'newsblenda-accounts'),
                'value' => number_format($earnings, 2),
            ],

        ];
    }
    
        /**
     * Count posts by status.
     */
    private function count_posts_by_status(
        int $user_id,
        string $status
    ): int {

        $query = new \WP_Query(
            [
                'post_type'      => 'post',
                'post_status'    => $status,
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => false,
            ]
        );

        return (int) $query->found_posts;
    }

    /**
     * Get total article views.
     */
    private function total_views(
        int $user_id
    ): int {

        global $wpdb;

        $views = (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT SUM(meta_value)
                FROM {$wpdb->postmeta}
                INNER JOIN {$wpdb->posts}
                    ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
                WHERE {$wpdb->posts}.post_author = %d
                AND meta_key = 'nb_post_views'
                ",
                $user_id
            )
        );

        return max(0, $views);
    }

    /**
     * Profile completion.
     */
    private function profile_completion(
        int $user_id
    ): int {

        $fields = [

            'first_name',
            'last_name',
            'description',
            'nb_phone',
            'nb_country',
            'nb_state',
            'nb_city',
            'nb_niche',
            'nb_profile_photo',

        ];

        $completed = 0;

        foreach ($fields as $field) {

            if (! empty(get_user_meta($user_id, $field, true))) {

                $completed++;

            }

        }

        return (int) round(
            ($completed / count($fields)) * 100
        );
    }

    /**
     * Dashboard message.
     */
    private function message(
        string $message,
        string $type = 'info'
    ): string {

        return sprintf(
            '<div class="nba-message nba-message-%1$s">%2$s</div>',
            esc_attr($type),
            esc_html($message)
        );

    }

    /**
     * Dashboard URL.
     */
    public static function url(): string
    {
        return home_url('/dashboard/');
    }

    /**
     * Is dashboard page.
     */
    public static function is_dashboard(): bool
    {
        return is_page('dashboard');
    }

    /**
     * Current dashboard user.
     */
    public static function current_user(): ?\WP_User
    {
        if (! is_user_logged_in()) {
            return null;
        }

        return wp_get_current_user();
    }

    /**
     * Dashboard statistics for external use.
     */
    public function statistics(
        int $user_id
    ): array
    {
        return $this->get_dashboard_data($user_id);
    }

    /**
     * Dashboard summary.
     */
    public function summary(
        int $user_id
    ): array
    {
        return [

            'posts' => (int) count_user_posts(
                $user_id,
                'post',
                true
            ),

            'views' => $this->total_views($user_id),

            'completion' => $this->profile_completion($user_id),

            'earnings' => (float) get_user_meta(
                $user_id,
                'nb_total_earnings',
                true
            ),

        ];
    }
}