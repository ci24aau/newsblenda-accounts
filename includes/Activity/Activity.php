<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Activity;

defined('ABSPATH') || exit;

class Activity
{
    /**
     * Activity table.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->table = $wpdb->prefix . 'nb_activity';

        add_action(
            'wp_login',
            [$this, 'login'],
            10,
            2
        );

        add_action(
            'wp_logout',
            [$this, 'logout']
        );

        add_action(
            'user_register',
            [$this, 'registration']
        );

        add_action(
            'profile_update',
            [$this, 'profile_updated'],
            10,
            2
        );

        add_action(
            'transition_post_status',
            [$this, 'post_status_changed'],
            10,
            3
        );

        add_action(
            'nb_accounts_payout_recorded',
            [$this, 'payout'],
            10,
            2
        );
    }

    /**
     * Write activity entry.
     */
    public function log(
        int $user_id,
        string $action,
        string $description = '',
        array $context = []
    ): void {

        global $wpdb;

        $wpdb->insert(

            $this->table,

            [

                'user_id' => $user_id,

                'action' => sanitize_key($action),

                'description' => sanitize_text_field(
                    $description
                ),

                'context' => wp_json_encode($context),

                'ip_address' => $this->ip(),

                'user_agent' => $this->agent(),

                'created_at' => current_time('mysql'),

            ],

            [

                '%d',

                '%s',

                '%s',

                '%s',

                '%s',

                '%s',

                '%s',

            ]

        );

    }

    /**
     * User logged in.
     */
    public function login(
        string $user_login,
        \WP_User $user
    ): void {

        $this->log(

            $user->ID,

            'login',

            'User logged in.',

            [

                'username' => $user_login,

            ]

        );

    }

    /**
     * User logged out.
     */
    public function logout(): void
    {
        $user = wp_get_current_user();

        if (! $user->exists()) {
            return;
        }

        $this->log(

            $user->ID,

            'logout',

            'User logged out.'

        );

    }

    /**
     * New account registered.
     */
    public function registration(
        int $user_id
    ): void {

        $this->log(

            $user_id,

            'registration',

            'New account registered.'

        );

    }
    
        /**
     * User profile updated.
     */
    public function profile_updated(
        int $user_id,
        \WP_User $old_user_data
    ): void {

        $this->log(

            $user_id,

            'profile_updated',

            'User updated profile.'

        );

    }

    /**
     * Track post workflow.
     */
    public function post_status_changed(
        string $new_status,
        string $old_status,
        \WP_Post $post
    ): void {

        if ($post->post_type !== 'post') {
            return;
        }

        switch ($new_status) {

            case 'pending':

                $this->log(

                    (int) $post->post_author,

                    'article_submitted',

                    'Article submitted for editorial review.',

                    [

                        'post_id' => $post->ID,

                        'title'   => $post->post_title,

                    ]

                );

                break;

            case 'publish':

                $this->log(

                    (int) $post->post_author,

                    'article_published',

                    'Article published.',

                    [

                        'post_id' => $post->ID,

                        'title'   => $post->post_title,

                    ]

                );

                break;

            case 'draft':

                if ($old_status === 'pending') {

                    $this->log(

                        (int) $post->post_author,

                        'revision_requested',

                        'Revision requested by editor.',

                        [

                            'post_id' => $post->ID,

                            'title'   => $post->post_title,

                        ]

                    );

                }

                break;

            case 'trash':

                $this->log(

                    (int) $post->post_author,

                    'article_deleted',

                    'Article deleted.',

                    [

                        'post_id' => $post->ID,

                        'title'   => $post->post_title,

                    ]

                );

                break;

        }

    }

    /**
     * Record payout.
     */
    public function payout(
        int $user_id,
        float $amount
    ): void {

        $this->log(

            $user_id,

            'payout',

            'Author payout recorded.',

            [

                'amount' => $amount,

            ]

        );

    }

    /**
     * Record account approval.
     */
    public function account_approved(
        int $user_id
    ): void {

        $this->log(

            $user_id,

            'account_approved',

            'Author account approved.'

        );

    }

    /**
     * Record account restriction.
     */
    public function account_restricted(
        int $user_id
    ): void {

        $this->log(

            $user_id,

            'account_restricted',

            'Author account restricted.'

        );

    }

    /**
     * Record account restoration.
     */
    public function account_restored(
        int $user_id
    ): void {

        $this->log(

            $user_id,

            'account_restored',

            'Author account restored.'

        );

    }
    
        /**
     * Get latest activity.
     */
    public function latest(
        int $limit = 20
    ): array {

        global $wpdb;

        return $wpdb->get_results(

            $wpdb->prepare(

                "
                SELECT *
                FROM {$this->table}
                ORDER BY created_at DESC
                LIMIT %d
                ",

                $limit

            )

        ) ?: [];

    }

    /**
     * Get activity for a user.
     */
    public function by_user(
        int $user_id,
        int $limit = 20
    ): array {

        global $wpdb;

        return $wpdb->get_results(

            $wpdb->prepare(

                "
                SELECT *
                FROM {$this->table}
                WHERE user_id=%d
                ORDER BY created_at DESC
                LIMIT %d
                ",

                $user_id,

                $limit

            )

        ) ?: [];

    }

    /**
     * Delete activity older than a number of days.
     */
    public function delete_old(
        int $days = 365
    ): void {

        global $wpdb;

        $wpdb->query(

            $wpdb->prepare(

                "
                DELETE FROM {$this->table}
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                ",

                $days

            )

        );

    }

    /**
     * Get client IP address.
     */
    private function ip(): string
    {
        $keys = [

            'HTTP_CF_CONNECTING_IP',

            'HTTP_CLIENT_IP',

            'HTTP_X_FORWARDED_FOR',

            'REMOTE_ADDR',

        ];

        foreach ($keys as $key) {

            if (empty($_SERVER[$key])) {
                continue;
            }

            $parts = explode(
                ',',
                (string) $_SERVER[$key]
            );

            return sanitize_text_field(
                trim($parts[0])
            );

        }

        return '';

    }

    /**
     * Get browser user agent.
     */
    private function agent(): string
    {
        return sanitize_text_field(
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }

    /**
     * Clear all activity for a user.
     */
    public function clear_user(
        int $user_id
    ): void {

        global $wpdb;

        $wpdb->delete(

            $this->table,

            [
                'user_id' => $user_id,
            ],

            [
                '%d',
            ]

        );

    }

    /**
     * Total activity count.
     */
    public function count(): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(

            "SELECT COUNT(*) FROM {$this->table}"

        );

    }

    /**
     * Fires after an activity record is written.
     */
    public function after_log(
        int $user_id,
        string $action
    ): void {

        do_action(

            'nb_accounts_activity_logged',

            $user_id,

            $action

        );

    }
}