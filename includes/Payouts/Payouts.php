<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Payouts;

defined('ABSPATH') || exit;

class Payouts
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'admin_post_nb_process_payout',
            [$this, 'process']
        );

        add_action(
            'admin_post_nb_reject_payout',
            [$this, 'reject']
        );

        add_action(
            'admin_menu',
            [$this, 'menu']
        );

        add_action(
            'nb_accounts_payout_recorded',
            [$this, 'invalidate_statistics_cache']
        );

        add_action(
            'profile_update',
            [$this, 'invalidate_statistics_cache']
        );
    }

    /**
     * Register admin menu.
     */
    public function menu(): void
    {
        add_submenu_page(

            'newsblenda-accounts',

            __('Payouts', 'newsblenda-accounts'),

            __('Payouts', 'newsblenda-accounts'),

            'nb_manage_payouts',

            'nb-payouts',

            [$this, 'page']

        );
    }

    /**
     * Render admin page.
     */
    public function page(): void
    {
        $authors = get_users(

            [

                'role__in' => [

                    'nb_author',

                    'nb_author_restricted',

                ],

                'orderby' => 'display_name',

                'order' => 'ASC',

            ]

        );

        include NB_ACCOUNTS_PATH .
            'templates/admin/payouts.php';
    }

    /**
     * Process payout.
     */
    public function process(): void
    {
        if (! current_user_can('nb_manage_payouts')) {
            wp_die(
                esc_html__(
                    'Permission denied.',
                    'newsblenda-accounts'
                )
            );
        }

        check_admin_referer(
            'nb_process_payout'
        );

        $user_id = isset($_POST['user_id'])
            ? (int) $_POST['user_id']
            : 0;

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        if ($user_id <= 0 || $amount <= 0) {

            wp_safe_redirect(
                wp_get_referer()
            );

            exit;
        }

        $paid = (float) get_user_meta(

            $user_id,

            'nb_paid_amount',

            true

        );

        update_user_meta(

            $user_id,

            'nb_paid_amount',

            round(
                $paid + $amount,
                2
            )

        );

        do_action(

            'nb_accounts_payout_recorded',

            $user_id,

            $amount

        );

        wp_safe_redirect(

            add_query_arg(

                'payout',

                'success',

                wp_get_referer()

            )

        );

        exit;
    }
    
        /**
     * Reject payout request.
     */
    public function reject(): void
    {
        if (! current_user_can('nb_manage_payouts')) {

            wp_die(
                esc_html__(
                    'Permission denied.',
                    'newsblenda-accounts'
                )
            );

        }

        check_admin_referer(
            'nb_reject_payout'
        );

        $user_id = isset($_POST['user_id'])
            ? (int) $_POST['user_id']
            : 0;

        do_action(

            'nb_accounts_payout_rejected',

            $user_id

        );

        wp_safe_redirect(

            add_query_arg(

                'payout',

                'rejected',

                wp_get_referer()

            )

        );

        exit;
    }

    /**
     * Is author eligible for payout?
     */
    public function eligible(
        int $user_id
    ): bool {

        $minimum = (float) get_option(
            'nb_minimum_payout',
            10
        );

        $balance = (float) get_user_meta(
            $user_id,
            'nb_unpaid_balance',
            true
        );

        return $balance >= $minimum;

    }

    /**
     * Get unpaid balance.
     */
    public function balance(
        int $user_id
    ): float {

        return (float) get_user_meta(
            $user_id,
            'nb_unpaid_balance',
            true
        );

    }

    /**
     * Get total paid.
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
     * Build payout summary.
     */
    public function summary(
        int $user_id
    ): array {

        return [

            'balance' => $this->balance($user_id),

            'paid' => $this->paid($user_id),

            'eligible' => $this->eligible($user_id),

            'minimum' => (float) get_option(
                'nb_minimum_payout',
                10
            ),

            'method' => get_user_meta(
                $user_id,
                'nb_payment_method',
                true
            ),

            'account_name' => get_user_meta(
                $user_id,
                'nb_account_name',
                true
            ),

            'account_number' => get_user_meta(
                $user_id,
                'nb_account_number',
                true
            ),

            'bank_name' => get_user_meta(
                $user_id,
                'nb_bank_name',
                true
            ),

        ];

    }

    /**
     * Notify author after payout.
     */
    public function notify(
        int $user_id,
        float $amount
    ): void {

        do_action(

            'nb_accounts_send_notification',

            $user_id,

            sprintf(

                __(
                    'Your payout of £%s has been processed.',
                    'newsblenda-accounts'
                ),

                number_format(
                    $amount,
                    2
                )

            )

        );

    }
    
        /**
     * Get payout history.
     */
    public function history(
        int $user_id
    ): array {

        return [

            'paid_amount' => $this->paid($user_id),

            'last_payment' => get_user_meta(
                $user_id,
                'nb_last_payment_date',
                true
            ),

            'payment_method' => get_user_meta(
                $user_id,
                'nb_payment_method',
                true
            ),

        ];

    }

    /**
     * Export payout data.
     */
    public function export(
        int $user_id
    ): array {

        return [

            'user_id' => $user_id,

            'balance' => $this->balance($user_id),

            'paid' => $this->paid($user_id),

            'eligible' => $this->eligible($user_id),

            'account_name' => get_user_meta(
                $user_id,
                'nb_account_name',
                true
            ),

            'account_number' => get_user_meta(
                $user_id,
                'nb_account_number',
                true
            ),

            'bank_name' => get_user_meta(
                $user_id,
                'nb_bank_name',
                true
            ),

            'payment_method' => get_user_meta(
                $user_id,
                'nb_payment_method',
                true
            ),

        ];

    }

    /**
     * Format currency.
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
     * Get payout statistics.
     */
    public function statistics(): array
    {
        $cache_key = 'nb_payouts_statistics';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $authors = get_users(
            [
                'role__in' => [
                    'nb_author',
                    'nb_author_restricted',
                ],
                'fields' => 'ID',
            ]
        );

        $author_ids = array_map('intval', $authors);
        if (! empty($author_ids)) {
            update_meta_cache('user', $author_ids);
        }

        $total_paid = 0.0;

        $total_balance = 0.0;

        foreach ($author_ids as $author_id) {

            $total_paid += $this->paid($author_id);

            $total_balance += $this->balance($author_id);

        }

        $stats = [

            'authors' => count($author_ids),

            'total_paid' => $total_paid,

            'total_balance' => $total_balance,

        ];

        set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);

        return $stats;

    }

    /**
     * Invalidate cached payout statistics.
     *
     * @param mixed ...$args
     */
    public function invalidate_statistics_cache(...$args): void
    {
        unset($args);
        delete_transient('nb_payouts_statistics');
    }

    /**
     * Refresh author's payout balance.
     */
    public function refresh(
        int $user_id
    ): void {

        $total = (float) get_user_meta(
            $user_id,
            'nb_total_earnings',
            true
        );

        $paid = $this->paid($user_id);

        update_user_meta(

            $user_id,

            'nb_unpaid_balance',

            max(
                0,
                round(
                    $total - $paid,
                    2
                )
            )

        );

    }

    /**
     * Fires after a payout has been processed.
     */
    public function after_payout(
        int $user_id,
        float $amount
    ): void {

        do_action(

            'nb_accounts_after_payout',

            $user_id,

            $amount

        );

    }
}