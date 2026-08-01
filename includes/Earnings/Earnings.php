<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Earnings;

defined('ABSPATH') || exit;

class Earnings
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_shortcode(
            'newsblenda_earnings',
            [$this, 'render']
        );

        add_shortcode(
            'nbe_earnings',
            [$this, 'render']
        );

        add_shortcode(
            'nb_earnings',
            [$this, 'render']
        );

        add_action(
            'nb_accounts_daily_event',
            [$this, 'sync']
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
    }

    /**
     * Render earnings page.
     */
    public function render(): string
    {
        if (! is_user_logged_in()) {

            return '<div class="nba-message nba-message-error">' .
                esc_html__(
                    'You must be logged in to view your earnings.',
                    'newsblenda-accounts'
                ) .
                '</div>';

        }

        $user_id = get_current_user_id();

        $data = [

            'total_earnings' => $this->total($user_id),

            'paid_amount' => $this->paid($user_id),

            'unpaid_balance' => $this->unpaid($user_id),

            'total_views' => $this->views($user_id),

            'top_article' => $this->top_article($user_id),

            'last_update' => get_user_meta(
                $user_id,
                'nb_last_earnings_update',
                true
            ),

        ];

        ob_start();

        extract(
            $data,
            EXTR_SKIP
        );

        include NB_ACCOUNTS_PATH .
            'templates/earnings/index.php';

        return (string) ob_get_clean();
    }

    /**
     * Daily earnings synchronisation.
     */
    public function sync(): void
    {
        $page = 1;

        do {
            $users = get_users(
                [
                    'role__in' => [
                        'nb_author',
                        'nb_author_restricted',
                    ],
                    'fields' => 'ID',
                    'number' => 500,
                    'paged'  => $page,
                ]
            );

            foreach ($users as $user_id) {
                $this->calculate((int) $user_id);
            }

            $page++;
        } while (! empty($users));
    }

    /**
     * Calculate earnings.
     */
    public function calculate(
        int $user_id
    ): void
    {
        global $wpdb;

        $rate = (float) get_option(
            'nb_rate_per_1000_views',
            2
        );

        $cache_version = (string) get_option('nb_dashboard_cache_version', '1');
        $cache_key     = 'nb_earnings_summary_' . $user_id . '_' . md5($cache_version);
        $summary       = get_transient($cache_key);

        if ($summary === false) {
            $summary = (array) $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0) AS total_views,
                        COALESCE(MAX(CAST(pm.meta_value AS UNSIGNED)), 0) AS max_views
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm
                        ON pm.post_id = p.ID
                        AND pm.meta_key = 'nb_valid_views'
                    WHERE p.post_type = 'post'
                        AND p.post_status = 'publish'
                        AND p.post_author = %d",
                    $user_id
                ),
                ARRAY_A
            );

            $top_article = (string) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT p.post_title
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm
                        ON pm.post_id = p.ID
                        AND pm.meta_key = 'nb_valid_views'
                    WHERE p.post_type = 'post'
                        AND p.post_status = 'publish'
                        AND p.post_author = %d
                    ORDER BY CAST(COALESCE(pm.meta_value, 0) AS UNSIGNED) DESC, p.ID DESC
                    LIMIT 1",
                    $user_id
                )
            );

            $summary['top_article'] = $top_article;

            set_transient($cache_key, $summary, DAY_IN_SECONDS);
        }

        $views      = (int) ($summary['total_views'] ?? 0);
        $earnings   = ($views / 1000) * $rate;
        $top_article = (string) ($summary['top_article'] ?? '');

        update_user_meta(
            $user_id,
            'nb_total_views',
            $views
        );

        update_user_meta(
            $user_id,
            'nb_total_earnings',
            round($earnings, 2)
        );

        update_user_meta(
            $user_id,
            'nb_top_article',
            $top_article
        );

        update_user_meta(
            $user_id,
            'nb_last_earnings_update',
            current_time('mysql')
        );
    }

    /**
     * Invalidate earnings cache on post save.
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

        $this->invalidate_author_cache((int) $post->post_author);
    }

    /**
     * Invalidate earnings cache on post deletion.
     */
    public function invalidate_cache_on_delete(
        int $post_id
    ): void {
        $post = get_post($post_id);
        if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
            return;
        }

        $this->invalidate_author_cache((int) $post->post_author);
    }

    /**
     * Invalidate earnings cache on status transition.
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

        $this->invalidate_author_cache((int) $post->post_author);
    }

    /**
     * Invalidate transient cache for author earnings summary.
     */
    private function invalidate_author_cache(
        int $user_id
    ): void {
        if ($user_id <= 0) {
            return;
        }

        $cache_version = (string) get_option('nb_dashboard_cache_version', '1');
        $cache_key     = 'nb_earnings_summary_' . $user_id . '_' . md5($cache_version);
        delete_transient($cache_key);
    }
    
        /**
     * Get total earnings.
     */
    public function total(
        int $user_id
    ): float {

        return (float) get_user_meta(
            $user_id,
            'nb_total_earnings',
            true
        );

    }

    /**
     * Get paid amount.
     */
    public function paid(
        int $user_id
    ): float {

        return (float) get_user_meta(
            $user_id,
            'nb_paid_amount',
            true
        );

    }

    /**
     * Get unpaid balance.
     */
    public function unpaid(
        int $user_id
    ): float {

        $stored = get_user_meta(
            $user_id,
            'nb_unpaid_balance',
            true
        );

        if ($stored !== '') {

            return (float) $stored;

        }

        return max(
            0,
            $this->total($user_id) - $this->paid($user_id)
        );

    }

    /**
     * Get total valid views.
     */
    public function views(
        int $user_id
    ): int {

        return (int) get_user_meta(
            $user_id,
            'nb_total_views',
            true
        );

    }

    /**
     * Get top earning article.
     */
    public function top_article(
        int $user_id
    ): string {

        return (string) get_user_meta(
            $user_id,
            'nb_top_article',
            true
        );

    }

    /**
     * Record a payout.
     */
    public function payout(
        int $user_id,
        float $amount
    ): bool {

        if ($amount <= 0) {
            return false;
        }

        $paid = $this->paid($user_id);

        update_user_meta(
            $user_id,
            'nb_paid_amount',
            round(
                $paid + $amount,
                2
            )
        );

        update_user_meta(
            $user_id,
            'nb_unpaid_balance',
            round(
                max(
                    0,
                    $this->total($user_id) - ($paid + $amount)
                ),
                2
            )
        );

        do_action(
            'nb_accounts_payout_recorded',
            $user_id,
            $amount
        );

        return true;

    }

    /**
     * Refresh unpaid balance.
     */
    public function refresh_balance(
        int $user_id
    ): void {

        update_user_meta(
            $user_id,
            'nb_unpaid_balance',
            round(
                max(
                    0,
                    $this->total($user_id) - $this->paid($user_id)
                ),
                2
            )
        );

    }
    
        /**
     * Is the author eligible for payout?
     */
    public function eligible(
        int $user_id,
        float $minimum = 10
    ): bool {

        return $this->unpaid($user_id) >= $minimum;

    }

    /**
     * Get earnings summary.
     */
    public function summary(
        int $user_id
    ): array {

        return [

            'total_earnings' => $this->total($user_id),

            'paid_amount'    => $this->paid($user_id),

            'unpaid_balance' => $this->unpaid($user_id),

            'total_views'    => $this->views($user_id),

            'top_article'    => $this->top_article($user_id),

            'last_update'    => get_user_meta(
                $user_id,
                'nb_last_earnings_update',
                true
            ),

        ];

    }

    /**
     * Format a monetary value.
     */
    public static function money(
        float $amount
    ): string {

        return '£' . number_format(
            $amount,
            2
        );

    }

    /**
     * Reset an author's earnings.
     */
    public function reset(
        int $user_id
    ): void {

        update_user_meta(
            $user_id,
            'nb_total_earnings',
            0
        );

        update_user_meta(
            $user_id,
            'nb_paid_amount',
            0
        );

        update_user_meta(
            $user_id,
            'nb_unpaid_balance',
            0
        );

        update_user_meta(
            $user_id,
            'nb_total_views',
            0
        );

        update_user_meta(
            $user_id,
            'nb_top_article',
            ''
        );

        update_user_meta(
            $user_id,
            'nb_last_earnings_update',
            current_time('mysql')
        );

    }

    /**
     * Export an author's earnings.
     */
    public function export(
        int $user_id
    ): array {

        return [

            'user_id'         => $user_id,

            'total_earnings'  => $this->total($user_id),

            'paid_amount'     => $this->paid($user_id),

            'unpaid_balance'  => $this->unpaid($user_id),

            'total_views'     => $this->views($user_id),

            'top_article'     => $this->top_article($user_id),

            'last_update'     => get_user_meta(
                $user_id,
                'nb_last_earnings_update',
                true
            ),

        ];

    }

    /**
     * Refresh earnings for a single author.
     */
    public function recalculate(
        int $user_id
    ): void {

        $this->calculate($user_id);

        $this->refresh_balance($user_id);

    }
}