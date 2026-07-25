<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Notifications;

defined('ABSPATH') || exit;

class Notifications
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table = $wpdb->prefix . 'nb_notifications';

        add_shortcode('newsblenda_notifications', [$this, 'render']);
        add_shortcode('nbe_notifications', [$this, 'render']);
        add_shortcode('nb_notifications', [$this, 'render']);
        add_action('init', [$this, 'handle_actions']);
    }

    public function render(): string
    {
        if (!is_user_logged_in()) {
            return '<div class="nba-message nba-message-error">' . esc_html__('You must be logged in to view notifications.', 'newsblenda-accounts') . '</div>';
        }

        $notifications = $this->get_notifications(get_current_user_id());

        ob_start();
        ?>
        <div class="nba-notifications">
            <div class="nba-notifications-header">
                <h2><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h2>
                <span class="nba-notification-count"><?php echo esc_html((string) $this->unread_count(get_current_user_id())); ?></span>
            </div>

            <?php if (empty($notifications)) : ?>
                <p><?php esc_html_e('No notifications found.', 'newsblenda-accounts'); ?></p>
            <?php else : ?>
                <table class="nba-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>
                            <th><?php esc_html_e('Message', 'newsblenda-accounts'); ?></th>
                            <th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>
                            <th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th>
                            <th><?php esc_html_e('Action', 'newsblenda-accounts'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification) : ?>
                            <tr class="<?php echo $notification->is_read ? 'nba-read' : 'nba-unread'; ?>">
                                <td><?php echo esc_html($notification->title ?: __('Notification', 'newsblenda-accounts')); ?></td>
                                <td>
                                    <?php echo wp_kses_post($notification->message); ?>
                                    <?php if (!empty($notification->action_url)) : ?>
                                        <br><br>
                                        <a href="<?php echo esc_url($notification->action_url); ?>"><?php esc_html_e('View', 'newsblenda-accounts'); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at))); ?></td>
                                <td><?php echo esc_html($notification->is_read ? __('Read', 'newsblenda-accounts') : __('Unread', 'newsblenda-accounts')); ?></td>
                                <td>
                                    <?php if (!$notification->is_read) : ?>
                                        <a href="<?php echo esc_url($this->mark_read_url((int) $notification->id)); ?>"><?php esc_html_e('Mark Read', 'newsblenda-accounts'); ?></a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url($this->delete_url((int) $notification->id)); ?>"><?php esc_html_e('Delete', 'newsblenda-accounts'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public function handle_actions(): void
    {
        if (!is_user_logged_in() || empty($_GET['nba_action'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_GET['nba_action']));
        $id = isset($_GET['notification']) ? absint(wp_unslash($_GET['notification'])) : 0;

        if ($id < 1) {
            return;
        }

        check_admin_referer('nba_mark_notification');

        global $wpdb;

        if ($action === 'mark_read') {
            $wpdb->update(
                $this->table,
                ['is_read' => 1],
                ['id' => $id, 'user_id' => get_current_user_id()],
                ['%d'],
                ['%d', '%d']
            );
        }

        if ($action === 'delete') {
            $wpdb->delete(
                $this->table,
                ['id' => $id, 'user_id' => get_current_user_id()],
                ['%d', '%d']
            );
        }

        wp_safe_redirect(remove_query_arg(['nba_action', 'notification', '_wpnonce']));
        exit;
    }

    public static function add(int $user_id, string $title, string $message, string $type = 'info', string $action_url = ''): bool
    {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'nb_notifications',
            [
                'user_id'    => $user_id,
                'title'      => sanitize_text_field($title),
                'message'    => wp_kses_post($message),
                'type'       => sanitize_text_field($type),
                'action_url' => esc_url_raw($action_url),
                'is_read'    => 0,
                'status'     => 'unread',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result) {
            do_action('nb_accounts_notification_created', $user_id, $title, $message);
        }

        return (bool) $result;
    }

    private function get_notifications(int $user_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        ) ?: [];
    }

    public function unread_count(int $user_id): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND is_read = 0",
                $user_id
            )
        );
    }

    public function create(int $user_id, string $title, string $message, string $type = 'info', string $action_url = ''): bool
    {
        return self::add($user_id, $title, $message, $type, $action_url);
    }

    public function mark_read(int $notification_id, int $user_id): bool
    {
        global $wpdb;

        return (bool) $wpdb->update(
            $this->table,
            ['is_read' => 1, 'status' => 'read'],
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%d', '%s'],
            ['%d', '%d']
        );
    }

    public function mark_all_read(int $user_id): bool
    {
        global $wpdb;

        return (bool) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table} SET is_read = 1, status = %s WHERE user_id = %d",
                'read',
                $user_id
            )
        );
    }

    public function delete(int $notification_id, int $user_id): bool
    {
        global $wpdb;

        return (bool) $wpdb->delete(
            $this->table,
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%d', '%d']
        );
    }

    public function latest(int $user_id, int $limit = 5): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
                $user_id,
                $limit
            )
        ) ?: [];
    }

    private function delete_url(int $notification_id): string
    {
        return wp_nonce_url(
            add_query_arg(['nba_action' => 'delete', 'notification' => $notification_id]),
            'nba_mark_notification'
        );
    }

    private function mark_read_url(int $notification_id): string
    {
        return wp_nonce_url(
            add_query_arg(['nba_action' => 'mark_read', 'notification' => $notification_id]),
            'nba_mark_notification'
        );
    }

    public function exists(int $notification_id): bool
    {
        global $wpdb;

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE id = %d",
                $notification_id
            )
        );
    }
}
