<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Reports;

defined('ABSPATH') || exit;

class Reports
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'admin_menu',
            [$this, 'register_menu']
        );
    }

    /**
     * Register Reports page.
     */
    public function register_menu(): void
    {
        add_submenu_page(

            'newsblenda-accounts',

            __('Reports', 'newsblenda-accounts'),

            __('Reports', 'newsblenda-accounts'),

            'nb_manage_settings',

            'nb-reports',

            [$this, 'render']

        );
    }

    /**
     * Render reports page.
     */
    public function render(): void
    {
        $stats = [

            'authors' => $this->author_count(),

            'published' => $this->published_posts(),

            'pending' => $this->pending_posts(),

            'drafts' => $this->draft_posts(),

            'total_views' => $this->total_views(),

            'earnings' => $this->total_earnings(),

        ];

        include NB_ACCOUNTS_PATH .
            'templates/admin/reports.php';
    }

    /**
     * Total authors.
     */
    public function author_count(): int
    {
        return count(

            get_users(

                [

                    'role__in' => [

                        'nb_author',

                        'nb_author_restricted',

                    ],

                ]

            )

        );
    }

    /**
     * Published posts.
     */
    public function published_posts(): int
    {
        return (int) wp_count_posts(
            'post'
        )->publish;
    }

    /**
     * Pending posts.
     */
    public function pending_posts(): int
    {
        return (int) wp_count_posts(
            'post'
        )->pending;
    }

    /**
     * Draft posts.
     */
    public function draft_posts(): int
    {
        return (int) wp_count_posts(
            'post'
        )->draft;
    }
    
        /**
     * Total earnings.
     */
    public function total_earnings(): float
    {
        $users = get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],
            ]
        );

        $total = 0;

        foreach ($users as $user) {

            $total += (float) get_user_meta(
                $user->ID,
                'nb_total_earnings',
                true
            );

        }

        return round($total, 2);
    }

    /**
     * Total article views.
     */
    public function total_views(): int
    {
        $users = get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],
            ]
        );

        $views = 0;

        foreach ($users as $user) {

            $views += (int) get_user_meta(
                $user->ID,
                'nb_total_views',
                true
            );

        }

        return $views;
    }

    /**
     * Top earning authors.
     */
    public function top_authors(
        int $limit = 10
    ): array {

        $users = get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],
            ]
        );

        usort(

            $users,

            static function ($a, $b) {

                return

                    (float) get_user_meta(
                        $b->ID,
                        'nb_total_earnings',
                        true
                    )

                    <=>

                    (float) get_user_meta(
                        $a->ID,
                        'nb_total_earnings',
                        true
                    );

            }

        );

        return array_slice(
            $users,
            0,
            $limit
        );

    }

    /**
     * Most viewed articles.
     */
    public function top_articles(
        int $limit = 10
    ): array {

        return get_posts(
            [
                'post_type' => 'post',

                'post_status' => 'publish',

                'posts_per_page' => $limit,

                'meta_key' => 'nb_valid_views',

                'orderby' => 'meta_value_num',

                'order' => 'DESC',

            ]
        );

    }

    /**
     * Recently registered authors.
     */
    public function recent_authors(
        int $limit = 10
    ): array {

        return get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],

                'orderby' => 'registered',

                'order' => 'DESC',

                'number' => $limit,

            ]
        );

    }

    /**
     * Recently submitted articles.
     */
    public function recent_submissions(
        int $limit = 10
    ): array {

        return get_posts(
            [
                'post_type' => 'post',

                'post_status' => [
                    'pending',
                    'draft',
                    'publish',
                ],

                'posts_per_page' => $limit,

                'orderby' => 'date',

                'order' => 'DESC',

            ]
        );

    }
    
        /**
     * Total approved articles.
     */
    public function approved_articles(): int
    {
        return (int) wp_count_posts(
            'post'
        )->publish;
    }

    /**
     * Total rejected articles.
     *
     * Rejected articles are stored in trash.
     */
    public function rejected_articles(): int
    {
        return (int) wp_count_posts(
            'post'
        )->trash;
    }

    /**
     * Average earnings per author.
     */
    public function average_earnings(): float
    {
        $authors = max(
            1,
            $this->author_count()
        );

        return round(
            $this->total_earnings() / $authors,
            2
        );
    }

    /**
     * Dashboard summary.
     */
    public function summary(): array
    {
        return [

            'authors' => $this->author_count(),

            'published' => $this->published_posts(),

            'pending' => $this->pending_posts(),

            'drafts' => $this->draft_posts(),

            'approved' => $this->approved_articles(),

            'rejected' => $this->rejected_articles(),

            'views' => $this->total_views(),

            'earnings' => $this->total_earnings(),

            'average_earnings' => $this->average_earnings(),

        ];
    }

    /**
     * Export report data.
     */
    public function export(): array
    {
        return [

            'generated_at' => current_time('mysql'),

            'summary' => $this->summary(),

            'top_authors' => $this->top_authors(),

            'top_articles' => $this->top_articles(),

            'recent_authors' => $this->recent_authors(),

            'recent_submissions' => $this->recent_submissions(),

        ];
    }

    /**
     * Refresh reports.
     */
    public function refresh(): void
    {
        do_action(
            'nb_accounts_reports_refresh'
        );
    }

    /**
     * Fires after reports are generated.
     */
    public function after_generate(): void
    {
        do_action(
            'nb_accounts_reports_generated',
            $this->summary()
        );
    }
}