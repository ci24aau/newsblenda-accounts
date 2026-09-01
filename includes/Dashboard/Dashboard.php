<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Dashboard;

use Newsblenda\Accounts\Classes\CacheManager;

defined('ABSPATH') || exit;

class Dashboard
{
    /**
     * Buffered cache metric deltas.
     *
     * @var array<string, array{hits:int, misses:int}>
     */
    private static array $cache_metric_buffer = [];

    /**
     * Whether shutdown flush has been registered.
     */
    private static bool $cache_metric_flush_hooked = false;

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

        add_action(
            'save_post',
            [$this, 'invalidate_cache_on_save'],
            10,
            3
        );

        add_action(
            'delete_post',
            [$this, 'invalidate_cache_on_delete']
        );

        add_action(
            'transition_post_status',
            [$this, 'invalidate_cache_on_status_change'],
            10,
            3
        );

        add_action(
            'profile_update',
            [$this, 'invalidate_cache_on_user_update']
        );

        add_action(
            'added_user_meta',
            [$this, 'invalidate_cache_on_user_meta_change'],
            10,
            4
        );

        add_action(
            'updated_user_meta',
            [$this, 'invalidate_cache_on_user_meta_change'],
            10,
            4
        );

        add_action(
            'deleted_user_meta',
            [$this, 'invalidate_cache_on_user_meta_change'],
            10,
            4
        );
    }

    /**
     * Invalidate dashboard caches when content changes.
     */
    public function invalidate_cache_on_save(
        int $post_id,
        \WP_Post $post,
        bool $update
    ): void {
        if ($post->post_type !== 'post') {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        $this->invalidate_cache_for_author((int) $post->post_author);
    }

    /**
     * Invalidate dashboard caches when post is deleted.
     */
    public function invalidate_cache_on_delete(
        int $post_id
    ): void {
        $post = get_post($post_id);
        if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
            return;
        }

        $this->invalidate_cache_for_author((int) $post->post_author);
    }

    /**
     * Invalidate dashboard caches when status transitions.
     */
    public function invalidate_cache_on_status_change(
        string $new_status,
        string $old_status,
        \WP_Post $post
    ): void {
        if ($post->post_type !== 'post') {
            return;
        }

        if ($new_status === $old_status) {
            return;
        }

        $this->invalidate_cache_for_author((int) $post->post_author);
    }

    /**
     * Invalidate author/editor dashboard cache keys.
     */
    private function invalidate_cache_for_author(
        int $author_id
    ): void {
        if ($author_id <= 0) {
            return;
        }

        delete_transient('nb_dashboard_author_' . $author_id);

        update_option(
            'nb_dashboard_cache_version',
            (string) time(),
            false
        );
    }

    /**
     * Invalidate dashboard caches when user profile is updated.
     */
    public function invalidate_cache_on_user_update(
        int $user_id
    ): void {
        $this->invalidate_cache_for_author($user_id);
    }

    /**
     * Invalidate dashboard caches when user metadata changes.
     *
     * @param int $meta_id
     * @param int $user_id
     * @param string $meta_key
     * @param mixed $meta_value
     */
    public function invalidate_cache_on_user_meta_change(
        $meta_id,
        int $user_id,
        string $meta_key,
        $meta_value
    ): void {
        unset($meta_id, $meta_key, $meta_value);
        $this->invalidate_cache_for_author($user_id);
    }

    /**
     * Record transient cache metrics.
     */
    public static function track_cache_event(
        string $segment,
        bool $hit
    ): void {
        $enabled = (bool) apply_filters(
            'nb_accounts_enable_cache_metrics',
            defined('WP_DEBUG') && WP_DEBUG
        );

        if (! $enabled) {
            return;
        }

        if (! isset(self::$cache_metric_buffer[$segment])) {
            self::$cache_metric_buffer[$segment] = [
                'hits' => 0,
                'misses' => 0,
            ];
        }

        if ($hit) {
            self::$cache_metric_buffer[$segment]['hits']++;
        } else {
            self::$cache_metric_buffer[$segment]['misses']++;
        }

        if (! self::$cache_metric_flush_hooked) {
            add_action(
                'shutdown',
                [self::class, 'flush_cache_metrics'],
                99
            );
            self::$cache_metric_flush_hooked = true;
        }
    }

    /**
     * Flush buffered cache metrics to persistent option storage.
     */
    public static function flush_cache_metrics(): void
    {
        if (empty(self::$cache_metric_buffer)) {
            return;
        }

        $metrics = get_option('nb_dashboard_cache_metrics', []);
        if (! is_array($metrics)) {
            $metrics = [];
        }

        foreach (self::$cache_metric_buffer as $segment => $delta) {
            if (! isset($metrics[$segment]) || ! is_array($metrics[$segment])) {
                $metrics[$segment] = [
                    'hits' => 0,
                    'misses' => 0,
                    'updated_at' => '',
                ];
            }

            $metrics[$segment]['hits'] = (int) $metrics[$segment]['hits'] + (int) $delta['hits'];
            $metrics[$segment]['misses'] = (int) $metrics[$segment]['misses'] + (int) $delta['misses'];
            $metrics[$segment]['updated_at'] = current_time('mysql');
        }

        update_option('nb_dashboard_cache_metrics', $metrics, false);
        self::$cache_metric_buffer = [];
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
        $stats = CacheManager::get_author_stats($user_id);

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
                'value' => (int) ($stats->published_count ?? 0),
            ],

            [
                'title' => __('Pending Review', 'newsblenda-accounts'),
                'value' => (int) ($stats->pending_count ?? 0),
            ],

            [
                'title' => __('Drafts', 'newsblenda-accounts'),
                'value' => (int) ($stats->draft_count ?? 0),
            ],

            [
                'title' => __('Rejected', 'newsblenda-accounts'),
                'value' => (int) ($stats->rejected_count ?? 0),
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
        if (class_exists('\Newsblenda\Accounts\Classes\CacheManager')) {
            return \Newsblenda\Accounts\Classes\CacheManager::get_author_stats($user_id);
        }

        return $this->get_dashboard_data($user_id);
    }

    /**
     * Dashboard summary.
     */
    public function summary(
        int $user_id
    ): array
    {
        $stats = CacheManager::get_author_stats($user_id);

        return [

            'posts' => (int) ($stats->published_count ?? 0),

            'views' => (int) ($stats->total_views ?? 0),

            'completion' => $this->profile_completion($user_id),

            'earnings' => (float) get_user_meta(
                $user_id,
                'nb_total_earnings',
                true
            ),

        ];
    }
}