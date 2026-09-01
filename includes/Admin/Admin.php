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
     * Settings page slug.
     */
    private const SETTINGS_SLUG = 'newsblenda-accounts-settings';

    /**
     * Available settings tabs.
     */
    private const TABS = [
        'general'       => 'General',
        'registration'  => 'Registration',
        'security'      => 'Security',
        'workflow'      => 'Workflow',
        'email'         => 'Email',
        'earnings'      => 'Earnings',
        'seo'           => 'SEO',
        'notifications' => 'Notifications',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    /**
     * Admin notice.
     */
    public function admin_notices(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (empty($_GET['nba_notice'])) {
            return;
        }

        $notice = sanitize_text_field(wp_unslash($_GET['nba_notice']));
        $type   = 'success';

        if (! empty($_GET['nba_notice_type'])) {
            $type = sanitize_html_class(wp_unslash($_GET['nba_notice_type']));
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
            self::SETTINGS_SLUG,
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
        if (class_exists('\Newsblenda\Accounts\Classes\AssetManager')) {
            \Newsblenda\Accounts\Classes\AssetManager::enqueue_admin_assets($hook);
        }
    }

    /**
     * Determine whether the current admin page is the settings page.
     */
    private function is_settings_page(string $hook): bool
    {
        return strpos($hook, self::SETTINGS_SLUG) !== false;
    }

    // =========================================================================
    // Dashboard page
    // =========================================================================

    /**
     * Dashboard page.
     */
    public function dashboard_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__(
                'You do not have permission to access this page.',
                'newsblenda-accounts'
            ));
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

                <?php
                $stats = [
                    ['Authors',          $authors],
                    ['Editors',          $editors],
                    ['Published Posts',  $published],
                    ['Pending Posts',    $pending],
                    ['Draft Posts',      $drafts],
                    ['Notifications',    $notifications],
                    ['Activity Entries', $activity],
                ];

                foreach ($stats as [$label, $count]) :
                ?>
                <div class="nba-stat-card">
                    <h3><?php echo esc_html__($label, 'newsblenda-accounts'); ?></h3>
                    <span><?php echo esc_html((string) $count); ?></span>
                </div>
                <?php endforeach; ?>

            </div>

            <div class="nba-admin-card">
                <h2><?php esc_html_e('Quick Actions', 'newsblenda-accounts'); ?></h2>
                <p>
                    <a class="button button-primary"
                       href="<?php echo esc_url(admin_url('admin.php?page=' . self::SETTINGS_SLUG)); ?>">
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

    // =========================================================================
    // Settings page
    // =========================================================================

    /**
     * Settings page with tabbed interface.
     */
    public function settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__(
                'You do not have permission to access this page.',
                'newsblenda-accounts'
            ));
        }

        $active_tab = sanitize_key($_GET['tab'] ?? 'general');

        if (! array_key_exists($active_tab, self::TABS)) {
            $active_tab = 'general';
        }

        $option_groups = [
            'general'       => 'newsblenda_general',
            'registration'  => 'newsblenda_registration',
            'security'      => 'newsblenda_security',
            'workflow'      => 'newsblenda_workflow',
            'email'         => 'newsblenda_email',
            'earnings'      => 'newsblenda_earnings',
            'seo'           => 'newsblenda_seo',
            'notifications' => 'newsblenda_notifications',
        ];

        ?>
        <div class="wrap nba-settings-wrap">

            <div class="nba-settings-header">
                <h1 class="nba-settings-title">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Newsblenda Accounts — Settings', 'newsblenda-accounts'); ?>
                </h1>
                <p class="nba-settings-subtitle">
                    <?php esc_html_e('Configure all aspects of the Newsblenda Accounts plugin.', 'newsblenda-accounts'); ?>
                </p>
            </div>

            <?php
            // Display success/error from options.php redirect.
            if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                ?>
                <div class="notice notice-success is-dismissible nba-save-notice">
                    <p><?php esc_html_e('Settings saved.', 'newsblenda-accounts'); ?></p>
                </div>
                <?php
            }
            ?>

            <div class="nba-settings-layout">

                <!-- Tab navigation -->
                <nav class="nba-settings-tabs" aria-label="<?php esc_attr_e('Settings tabs', 'newsblenda-accounts'); ?>">
                    <?php foreach (self::TABS as $slug => $label) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::SETTINGS_SLUG . '&tab=' . $slug)); ?>"
                           class="nba-settings-tab <?php echo $active_tab === $slug ? 'active' : ''; ?>">
                            <?php echo esc_html__($label, 'newsblenda-accounts'); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Settings form -->
                <div class="nba-settings-content">

                    <form method="post"
                          action="options.php"
                          class="nba-settings-form"
                          id="nba-settings-form-<?php echo esc_attr($active_tab); ?>">

                        <?php settings_fields($option_groups[$active_tab]); ?>

                        <div class="nba-settings-panel">

                            <?php
                            // Security tab: display environment info card above settings.
                            if ($active_tab === 'security') {
                                $this->render_security_info();
                            }

                            // Earnings tab: display platform totals (display-only).
                            if ($active_tab === 'earnings') {
                                $this->render_earnings_summary();
                            }

                            do_settings_sections('newsblenda-accounts-settings-' . $active_tab);
                            ?>

                        </div>

                        <div class="nba-settings-footer">

                            <?php submit_button(
                                __('Save Settings', 'newsblenda-accounts'),
                                'primary large',
                                'submit',
                                false
                            ); ?>

                            <button type="button"
                                    class="button button-secondary nba-reset-section"
                                    data-section="<?php echo esc_attr($active_tab); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('nba_reset_settings')); ?>">
                                <?php esc_html_e('Reset to Defaults', 'newsblenda-accounts'); ?>
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>
        <?php
    }

    // =========================================================================
    // Supplementary settings panels (display-only)
    // =========================================================================

    /**
     * Display environment information on the Security tab.
     */
    private function render_security_info(): void
    {
        $ssl = is_ssl();

        ?>
        <div class="nba-info-card nba-security-info">

            <h3 class="nba-info-card-title">
                <span class="dashicons dashicons-shield-alt"></span>
                <?php esc_html_e('Environment Status', 'newsblenda-accounts'); ?>
            </h3>

            <table class="widefat striped">
                <tbody>

                    <tr>
                        <th><?php esc_html_e('WordPress Version', 'newsblenda-accounts'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        <td>
                            <?php if (version_compare(get_bloginfo('version'), '6.4', '>=')) : ?>
                                <span class="nba-badge nba-badge-success"><?php esc_html_e('Current', 'newsblenda-accounts'); ?></span>
                            <?php else : ?>
                                <span class="nba-badge nba-badge-warning"><?php esc_html_e('Update Available', 'newsblenda-accounts'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('PHP Version', 'newsblenda-accounts'); ?></th>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                        <td>
                            <?php if (version_compare(PHP_VERSION, '8.1', '>=')) : ?>
                                <span class="nba-badge nba-badge-success"><?php esc_html_e('Current', 'newsblenda-accounts'); ?></span>
                            <?php elseif (version_compare(PHP_VERSION, '8.0', '>=')) : ?>
                                <span class="nba-badge nba-badge-info"><?php esc_html_e('Supported', 'newsblenda-accounts'); ?></span>
                            <?php else : ?>
                                <span class="nba-badge nba-badge-danger"><?php esc_html_e('Outdated', 'newsblenda-accounts'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('SSL Status', 'newsblenda-accounts'); ?></th>
                        <td>
                            <?php echo esc_html($ssl ? __('Active', 'newsblenda-accounts') : __('Not detected', 'newsblenda-accounts')); ?>
                        </td>
                        <td>
                            <?php if ($ssl) : ?>
                                <span class="nba-badge nba-badge-success"><?php esc_html_e('Secure', 'newsblenda-accounts'); ?></span>
                            <?php else : ?>
                                <span class="nba-badge nba-badge-warning"><?php esc_html_e('Not Secure', 'newsblenda-accounts'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('Debug Mode', 'newsblenda-accounts'); ?></th>
                        <td>
                            <?php echo esc_html(defined('WP_DEBUG') && WP_DEBUG
                                ? __('On', 'newsblenda-accounts')
                                : __('Off', 'newsblenda-accounts')
                            ); ?>
                        </td>
                        <td>
                            <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
                                <span class="nba-badge nba-badge-warning"><?php esc_html_e('Disable in production', 'newsblenda-accounts'); ?></span>
                            <?php else : ?>
                                <span class="nba-badge nba-badge-success"><?php esc_html_e('OK', 'newsblenda-accounts'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>
        <?php
    }

    /**
     * Display platform earnings summary on the Earnings tab.
     */
    private function render_earnings_summary(): void
    {
        global $wpdb;

        $table  = $wpdb->prefix . 'nb_earnings';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;

        $total_ytd   = 0.0;
        $payouts_ytd = 0.0;
        $pending     = 0.0;

        if ($exists) {
            $year = gmdate('Y');

            $total_ytd = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE YEAR(calculated_at) = %d",
                $year
            ));

            $paid_table = $wpdb->prefix . 'nb_payouts';

            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $paid_table)) === $paid_table) {
                $payouts_ytd = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(amount),0) FROM {$paid_table} WHERE status='paid' AND YEAR(created_at) = %d",
                    $year
                ));

                $pending = (float) $wpdb->get_var(
                    "SELECT COALESCE(SUM(amount),0) FROM {$paid_table} WHERE status='pending'"
                );
            }
        }

        $currency = SettingsManager::get('earnings', 'currency', 'USD');

        ?>
        <div class="nba-info-card nba-earnings-summary">

            <h3 class="nba-info-card-title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Platform Earnings Overview', 'newsblenda-accounts'); ?>
            </h3>

            <div class="nba-summary-grid">

                <div class="nba-summary-stat">
                    <span class="nba-summary-label"><?php esc_html_e('Total Earnings (YTD)', 'newsblenda-accounts'); ?></span>
                    <span class="nba-summary-value">
                        <?php echo esc_html($currency . ' ' . number_format($total_ytd, 2)); ?>
                    </span>
                </div>

                <div class="nba-summary-stat">
                    <span class="nba-summary-label"><?php esc_html_e('Author Payouts (YTD)', 'newsblenda-accounts'); ?></span>
                    <span class="nba-summary-value">
                        <?php echo esc_html($currency . ' ' . number_format($payouts_ytd, 2)); ?>
                    </span>
                </div>

                <div class="nba-summary-stat">
                    <span class="nba-summary-label"><?php esc_html_e('Pending Payouts', 'newsblenda-accounts'); ?></span>
                    <span class="nba-summary-value">
                        <?php echo esc_html($currency . ' ' . number_format($pending, 2)); ?>
                    </span>
                </div>

            </div>

        </div>
        <?php
    }

    // =========================================================================
    // Other admin pages
    // =========================================================================

    /**
     * Authors page.
     */
    public function authors_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__(
                'You do not have permission to access this page.',
                'newsblenda-accounts'
            ));
        }

        $authors = get_users([
            'role__in' => ['nbe_author', 'author'],
            'orderby'  => 'registered',
            'order'    => 'DESC',
        ]);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Authors', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($authors)) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No authors were found.', 'newsblenda-accounts'); ?></p>
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
                                <td><?php echo esc_html((string) $author->ID); ?></td>
                                <td><?php echo esc_html($author->display_name); ?></td>
                                <td><?php echo esc_html($author->user_login); ?></td>
                                <td><?php echo esc_html($author->user_email); ?></td>
                                <td><?php echo esc_html(implode(', ', $author->roles)); ?></td>
                                <td><?php echo esc_html(
                                    mysql2date(
                                        get_option('date_format'),
                                        $author->user_registered
                                    )
                                ); ?></td>
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
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__(
                'You do not have permission to access this page.',
                'newsblenda-accounts'
            ));
        }

        global $wpdb;

        $table         = $wpdb->prefix . 'nb_notifications';
        $notifications = [];

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            $notifications = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100"
            );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Notifications', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($notifications)) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No notifications available.', 'newsblenda-accounts'); ?></p>
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
            wp_die(esc_html__(
                'You do not have permission to access this page.',
                'newsblenda-accounts'
            ));
        }

        global $wpdb;

        $table      = $wpdb->prefix . 'nb_activity';
        $activities = [];

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            $activities = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100"
            );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Activity Log', 'newsblenda-accounts'); ?></h1>

            <?php if (empty($activities)) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No activity has been recorded yet.', 'newsblenda-accounts'); ?></p>
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
                                <td><?php echo esc_html((string) $activity->id); ?></td>
                                <td><?php echo esc_html((string) $activity->user_id); ?></td>
                                <td><?php echo esc_html($activity->action ?? ''); ?></td>
                                <td><?php echo esc_html($activity->ip_address ?? ''); ?></td>
                                <td><?php echo esc_html($activity->created_at ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function count_users_by_role(string $role): int
    {
        return count(get_users(['role' => $role, 'fields' => 'ID']));
    }

    private function count_posts(string $status): int
    {
        $counts = wp_count_posts('post');

        return isset($counts->{$status}) ? (int) $counts->{$status} : 0;
    }

    private function notification_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nb_notifications';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    private function activity_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'nb_activity';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
}
