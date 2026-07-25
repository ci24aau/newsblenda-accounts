<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

class Admin
{
    /**
     * Menu slug.
     */
    private const MENU_SLUG = 'newsblenda-accounts';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'admin_menu',
            [$this, 'register_menu']
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_assets']
        );

        add_action(
            'admin_notices',
            [$this, 'admin_notices']
        );
    }

    /**
     * Admin notice.
     */
    public function admin_notices(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (empty($_GET['nba_notice'])) {
            return;
        }

        $notice = sanitize_text_field(
            wp_unslash($_GET['nba_notice'])
        );

        $type = 'success';

        if (!empty($_GET['nba_notice_type'])) {
            $type = sanitize_html_class(
                wp_unslash($_GET['nba_notice_type'])
            );
        }

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($type),
            esc_html($notice)
        );
    }

    /**
     * Register admin menu.
     */
    public function register_menu(): void
    {
        add_menu_page(
            __('Newsblenda Accounts', 'newsblenda-accounts'),
            __('Newsblenda', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'dashboard_page'],
            'dashicons-groups',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'newsblenda-accounts'),
            __('Dashboard', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'dashboard_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'newsblenda-accounts'),
            __('Settings', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG . '-settings',
            [$this, 'settings_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Authors', 'newsblenda-accounts'),
            __('Authors', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG . '-authors',
            [$this, 'authors_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Notifications', 'newsblenda-accounts'),
            __('Notifications', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG . '-notifications',
            [$this, 'notifications_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Activity Log', 'newsblenda-accounts'),
            __('Activity Log', 'newsblenda-accounts'),
            'manage_options',
            self::MENU_SLUG . '-activity',
            [$this, 'activity_page']
        );
    }

    /**
     * Enqueue assets.
     */
    public function enqueue_assets(string $hook): void
    {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'nb-accounts-admin',
            NB_ACCOUNTS_URL . 'assets/css/admin.css',
            [],
            NB_ACCOUNTS_VERSION
        );

        wp_enqueue_script(
            'nb-accounts-admin',
            NB_ACCOUNTS_URL . 'assets/js/admin.js',
            ['jquery'],
            NB_ACCOUNTS_VERSION,
            true
        );

        wp_localize_script(
            'nb-accounts-admin',
            'nbaAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('nba_admin'),
            ]
        );
    }

    /**
     * Count users by role.
     */
    private function count_users_by_role(string $role): int
    {
        $users = get_users(
            [
                'role'   => $role,
                'fields' => 'ID',
            ]
        );

        return count($users);
    }

    /**
     * Count posts.
     */
    private function count_posts(string $status): int
    {
        $counts = wp_count_posts('post');

        return isset($counts->{$status})
            ? (int) $counts->{$status}
            : 0;
    }

    /**
     * Notification count.
     */
    private function notification_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nb_notifications';

        if ($wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        )) !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );
    }

    /**
     * Activity count.
     */
    private function activity_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nb_activity';

        if ($wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        )) !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );
    }
    
        /**
     * Dashboard page.
     */
    public function dashboard_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'newsblenda-accounts'
                )
            );
        }

        $authors       = $this->count_users_by_role('nbe_author');
        $editors       = $this->count_users_by_role('editor');
        $published     = $this->count_posts('publish');
        $pending       = $this->count_posts('pending');
        $drafts        = $this->count_posts('draft');
        $notifications = $this->notification_count();
        $activity      = $this->activity_count();

        ?>

        <div class="wrap nba-admin">

            <h1><?php esc_html_e('Newsblenda Accounts', 'newsblenda-accounts'); ?></h1>

            <div class="nba-admin-grid">

                <div class="nba-admin-card">

                    <h2><?php esc_html_e('Plugin Information', 'newsblenda-accounts'); ?></h2>

                    <table class="widefat striped">

                        <tbody>

                            <tr>
                                <th><?php esc_html_e('Plugin Version', 'newsblenda-accounts'); ?></th>
                                <td><?php echo esc_html(NB_ACCOUNTS_VERSION); ?></td>
                            </tr>

                            <tr>
                                <th><?php esc_html_e('WordPress', 'newsblenda-accounts'); ?></th>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>

                            <tr>
                                <th><?php esc_html_e('PHP', 'newsblenda-accounts'); ?></th>
                                <td><?php echo esc_html(PHP_VERSION); ?></td>
                            </tr>

                            <tr>
                                <th><?php esc_html_e('Site URL', 'newsblenda-accounts'); ?></th>
                                <td><?php echo esc_html(home_url()); ?></td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="nba-admin-stats">

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Authors', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $authors); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Editors', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $editors); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Published Posts', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $published); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Pending Posts', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $pending); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Draft Posts', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $drafts); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $notifications); ?></span>
                </div>

                <div class="nba-stat-card">
                    <h3><?php esc_html_e('Activity Entries', 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $activity); ?></span>
                </div>

            </div>

            <div class="nba-admin-card">

                <h2><?php esc_html_e('Quick Actions', 'newsblenda-accounts'); ?></h2>

                <p>

                    <a class="button button-primary"
                       href="<?php echo esc_url(admin_url('admin.php?page=newsblenda-accounts-settings')); ?>">
                        <?php esc_html_e('Plugin Settings', 'newsblenda-accounts'); ?>
                    </a>

                    <a class="button"
                       href="<?php echo esc_url(admin_url('users.php')); ?>">
                        <?php esc_html_e('Manage Users', 'newsblenda-accounts'); ?>
                    </a>

                    <a class="button"
                       href="<?php echo esc_url(admin_url('edit.php')); ?>">
                        <?php esc_html_e('Manage Posts', 'newsblenda-accounts'); ?>
                    </a>

                </p>

            </div>

        </div>

        <?php
    }

    /**
     * Settings page.
     */
    public function settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'newsblenda-accounts'
                )
            );
        }

        ?>

        <div class="wrap">

            <h1><?php esc_html_e('Newsblenda Accounts Settings', 'newsblenda-accounts'); ?></h1>

            <form
                class="nba-settings-form"
                method="post"
                action="options.php"
            >

                <?php

                settings_fields('nb_accounts_group');

                do_settings_sections('newsblenda-accounts-settings');

                submit_button();

                ?>

            </form>

        </div>

        <?php
    }
    
        /**
     * Authors page.
     */
    public function authors_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'newsblenda-accounts'
                )
            );
        }

        $authors = get_users(
            [
                'role__in' => [
                    'nbe_author',
                    'author',
                ],
                'orderby' => 'registered',
                'order'   => 'DESC',
            ]
        );

        ?>

        <div class="wrap">

            <h1><?php esc_html_e('Authors', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($authors)) : ?>

                <div class="notice notice-info">

                    <p>

                        <?php esc_html_e(
                            'No authors were found.',
                            'newsblenda-accounts'
                        ); ?>

                    </p>

                </div>

            <?php else : ?>

                <table class="widefat striped">

                    <thead>

                        <tr>

                            <th><?php esc_html_e('ID', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Name', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Username', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Email', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Role', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Registered', 'newsblenda-accounts'); ?></th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($authors as $author) : ?>

                            <tr>

                                <td>
                                    <?php echo esc_html((string) $author->ID); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($author->display_name); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($author->user_login); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($author->user_email); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        implode(', ', $author->roles)
                                    ); ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        mysql2date(
                                            get_option('date_format'),
                                            $author->user_registered
                                        )
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <?php
    }

    /**
     * Notifications page.
     */
    public function notifications_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'newsblenda-accounts'
                )
            );
        }

        global $wpdb;

        $table = $wpdb->prefix . 'nb_notifications';

        $notifications = [];

        if (
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table
                )
            ) === $table
        ) {

            $notifications = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100"
            );

        }

        ?>

        <div class="wrap">

            <h1><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($notifications)) : ?>

                <div class="notice notice-info">

                    <p>

                        <?php esc_html_e(
                            'No notifications available.',
                            'newsblenda-accounts'
                        ); ?>

                    </p>

                </div>

            <?php else : ?>

                <table class="widefat striped">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th><?php esc_html_e('User', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Title', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Status', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($notifications as $notification) : ?>

                            <tr>

                                <td><?php echo esc_html((string) $notification->id); ?></td>

                                <td><?php echo esc_html((string) $notification->user_id); ?></td>

                                <td><?php echo esc_html($notification->title ?? ''); ?></td>

                                <td><?php echo esc_html($notification->status ?? ''); ?></td>

                                <td><?php echo esc_html($notification->created_at ?? ''); ?></td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <?php
    }
    
        /**
     * Activity log page.
     */
    public function activity_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'newsblenda-accounts'
                )
            );
        }

        global $wpdb;

        $table = $wpdb->prefix . 'nb_activity';

        $activities = [];

        if (
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table
                )
            ) === $table
        ) {

            $activities = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100"
            );

        }

        ?>

        <div class="wrap">

            <h1><?php esc_html_e('Activity Log', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($activities)) : ?>

                <div class="notice notice-info">

                    <p>

                        <?php esc_html_e(
                            'No activity has been recorded yet.',
                            'newsblenda-accounts'
                        ); ?>

                    </p>

                </div>

            <?php else : ?>

                <table class="widefat striped">

                    <thead>

                        <tr>

                            <th><?php esc_html_e('ID', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('User', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Action', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('IP Address', 'newsblenda-accounts'); ?></th>

                            <th><?php esc_html_e('Date', 'newsblenda-accounts'); ?></th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($activities as $activity) : ?>

                            <tr>

                                <td>
                                    <?php echo esc_html((string) $activity->id); ?>
                                </td>

                                <td>
                                    <?php echo esc_html((string) $activity->user_id); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($activity->action ?? ''); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($activity->ip_address ?? ''); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($activity->created_at ?? ''); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <?php
    }
}