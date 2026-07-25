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
            'nb_accounts_daily_sync',
            [$this, 'sync']
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
        $users = get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],
            ]
        );

        foreach ($users as $user) {

            $this->calculate(
                $user->ID
            );

        }
    }

    /**
     * Calculate earnings.
     */
    public function calculate(
        int $user_id
    ): void
    {
        $posts = get_posts(
            [
                'post_type' => 'post',
                'post_status' => 'publish',
                'author' => $user_id,
                'posts_per_page' => -1,
            ]
        );

        $rate = (float) get_option(
            'nb_rate_per_1000_views',
            2
        );

        $views = 0;

        $earnings = 0;

        $top_views = 0;

        $top_article = '';

        foreach ($posts as $post) {

            $count = (int) get_post_meta(
                $post->ID,
                'nb_valid_views',
                true
            );

            $views += $count;

            $earnings += ($count / 1000) * $rate;

            if ($count > $top_views) {

                $top_views = $count;

                $top_article = $post->post_title;

            }

        }

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