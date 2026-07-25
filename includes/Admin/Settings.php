<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

class Settings
{
    /**
     * Option name.
     */
    private string $option = 'nb_accounts_settings';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action(
            'admin_init',
            [$this, 'register']
        );
    }

    /**
     * Register settings.
     */
    public function register(): void
    {
        register_setting(
            'nb_accounts_group',
            $this->option,
            [
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => $this->defaults(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | General
        |--------------------------------------------------------------------------
        */

        add_settings_section(
            'nb_general',
            __('General Settings', 'newsblenda-accounts'),
            '__return_false',
            'newsblenda-accounts-settings'
        );

        $this->field(
            'allow_author_registration',
            __('Allow Author Registration', 'newsblenda-accounts'),
            'checkbox',
            'nb_general'
        );

        $this->field(
            'allow_subscriber_registration',
            __('Allow Subscriber Registration', 'newsblenda-accounts'),
            'checkbox',
            'nb_general'
        );

        $this->field(
            'require_email_verification',
            __('Require Email Verification', 'newsblenda-accounts'),
            'checkbox',
            'nb_general'
        );

        $this->field(
            'require_admin_approval',
            __('Require Admin Approval', 'newsblenda-accounts'),
            'checkbox',
            'nb_general'
        );

        /*
        |--------------------------------------------------------------------------
        | Login & Security
        |--------------------------------------------------------------------------
        */

        add_settings_section(
            'nb_security',
            __('Login & Security', 'newsblenda-accounts'),
            '__return_false',
            'newsblenda-accounts-settings'
        );

        $this->field(
            'max_login_attempts',
            __('Maximum Login Attempts', 'newsblenda-accounts'),
            'number',
            'nb_security'
        );

        $this->field(
            'lockout_minutes',
            __('Lockout Time (Minutes)', 'newsblenda-accounts'),
            'number',
            'nb_security'
        );

        $this->field(
            'password_min_length',
            __('Minimum Password Length', 'newsblenda-accounts'),
            'number',
            'nb_security'
        );

        $this->field(
            'require_strong_passwords',
            __('Require Strong Passwords', 'newsblenda-accounts'),
            'checkbox',
            'nb_security'
        );

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        add_settings_section(
            'nb_email',
            __('Email', 'newsblenda-accounts'),
            '__return_false',
            'newsblenda-accounts-settings'
        );

        $this->field(
            'sender_name',
            __('Sender Name', 'newsblenda-accounts'),
            'text',
            'nb_email'
        );

        $this->field(
            'sender_email',
            __('Sender Email', 'newsblenda-accounts'),
            'email',
            'nb_email'
        );

        $this->field(
            'email_footer',
            __('Email Footer', 'newsblenda-accounts'),
            'textarea',
            'nb_email'
        );

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        add_settings_section(
            'nb_dashboard',
            __('Dashboard', 'newsblenda-accounts'),
            '__return_false',
            'newsblenda-accounts-settings'
        );

        $this->field(
            'dashboard_refresh',
            __('Dashboard Refresh (Seconds)', 'newsblenda-accounts'),
            'number',
            'nb_dashboard'
        );

        $this->field(
            'show_statistics',
            __('Show Statistics Cards', 'newsblenda-accounts'),
            'checkbox',
            'nb_dashboard'
        );

        $this->field(
            'show_recent_activity',
            __('Show Recent Activity', 'newsblenda-accounts'),
            'checkbox',
            'nb_dashboard'
        );

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        add_settings_section(
            'nb_notifications',
            __('Notifications', 'newsblenda-accounts'),
            '__return_false',
            'newsblenda-accounts-settings'
        );

        $this->field(
            'enable_notifications',
            __('Enable Notifications', 'newsblenda-accounts'),
            'checkbox',
            'nb_notifications'
        );

        $this->field(
            'notification_refresh',
            __('Notification Refresh (Seconds)', 'newsblenda-accounts'),
            'number',
            'nb_notifications'
        );

        $this->field(
            'email_notifications',
            __('Email Notifications', 'newsblenda-accounts'),
            'checkbox',
            'nb_notifications'
        );
    }

    /**
     * Register field.
     */
    private function field(
        string $key,
        string $label,
        string $type,
        string $section
    ): void {

        add_settings_field(
            $key,
            $label,
            [$this, 'render_field'],
            'newsblenda-accounts-settings',
            $section,
            [
                'key'  => $key,
                'type' => $type,
            ]
        );
    }
    
        /**
     * Render a settings field.
     */
    public function render_field(array $args): void
    {
        $settings = get_option(
            $this->option,
            $this->defaults()
        );

        $key   = $args['key'];
        $type  = $args['type'];
        $value = $settings[$key] ?? '';

        switch ($type) {

            case 'checkbox':
                ?>

                <label>

                    <input
                        type="checkbox"
                        name="<?php echo esc_attr($this->option); ?>[<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((int) $value, 1); ?>
                    >

                </label>

                <?php
                break;

            case 'number':
                ?>

                <input
                    class="regular-text"
                    type="number"
                    min="0"
                    name="<?php echo esc_attr($this->option); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr((string) $value); ?>"
                >

                <?php
                break;

            case 'email':
                ?>

                <input
                    class="regular-text"
                    type="email"
                    name="<?php echo esc_attr($this->option); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr((string) $value); ?>"
                >

                <?php
                break;

            case 'textarea':
                ?>

                <textarea
                    class="large-text"
                    rows="5"
                    name="<?php echo esc_attr($this->option); ?>[<?php echo esc_attr($key); ?>]"
                ><?php echo esc_textarea((string) $value); ?></textarea>

                <?php
                break;

            default:
                ?>

                <input
                    class="regular-text"
                    type="text"
                    name="<?php echo esc_attr($this->option); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr((string) $value); ?>"
                >

                <?php

        }

        $this->description($key);
    }

    /**
     * Field descriptions.
     */
    private function description(string $key): void
    {
        $descriptions = [

            'allow_author_registration' =>
                __('Allow visitors to register as Newsblenda authors.', 'newsblenda-accounts'),

            'allow_subscriber_registration' =>
                __('Allow normal subscriber registrations.', 'newsblenda-accounts'),

            'require_email_verification' =>
                __('Users must verify their email before login.', 'newsblenda-accounts'),

            'require_admin_approval' =>
                __('Accounts require administrator approval.', 'newsblenda-accounts'),

            'max_login_attempts' =>
                __('Maximum failed logins before lockout.', 'newsblenda-accounts'),

            'lockout_minutes' =>
                __('Lockout duration after too many failed logins.', 'newsblenda-accounts'),

            'password_min_length' =>
                __('Minimum password length.', 'newsblenda-accounts'),

            'require_strong_passwords' =>
                __('Require uppercase, lowercase, number and symbol.', 'newsblenda-accounts'),

            'sender_name' =>
                __('Displayed as the sender name.', 'newsblenda-accounts'),

            'sender_email' =>
                __('Email used for outgoing messages.', 'newsblenda-accounts'),

            'email_footer' =>
                __('Footer appended to every outgoing email.', 'newsblenda-accounts'),

            'dashboard_refresh' =>
                __('Automatic dashboard refresh interval.', 'newsblenda-accounts'),

            'show_statistics' =>
                __('Display statistics cards on dashboards.', 'newsblenda-accounts'),

            'show_recent_activity' =>
                __('Display recent activity widgets.', 'newsblenda-accounts'),

            'enable_notifications' =>
                __('Enable the notification system.', 'newsblenda-accounts'),

            'notification_refresh' =>
                __('Notification auto-refresh interval.', 'newsblenda-accounts'),

            'email_notifications' =>
                __('Send notifications by email.', 'newsblenda-accounts'),

        ];

        if (!isset($descriptions[$key])) {
            return;
        }

        printf(
            '<p class="description">%s</p>',
            esc_html($descriptions[$key])
        );
    }

    /**
     * Default option values.
     */
    private function defaults(): array
    {
        return [

            'allow_author_registration'     => 1,
            'allow_subscriber_registration' => 1,
            'require_email_verification'    => 1,
            'require_admin_approval'        => 1,

            'max_login_attempts'            => 5,
            'lockout_minutes'               => 30,
            'password_min_length'           => 8,
            'require_strong_passwords'      => 1,

            'sender_name'                   => get_bloginfo('name'),
            'sender_email'                  => get_option('admin_email'),
            'email_footer'                  => __('Thank you for using Newsblenda.', 'newsblenda-accounts'),

            'dashboard_refresh'             => 60,
            'show_statistics'               => 1,
            'show_recent_activity'          => 1,

            'enable_notifications'          => 1,
            'notification_refresh'          => 60,
            'email_notifications'           => 1,

        ];
    }
    
        /**
     * Sanitize settings.
     */
    public function sanitize(array $input): array
    {
        $defaults = $this->defaults();

        $output = [];

        /*
        |--------------------------------------------------------------------------
        | Checkboxes
        |--------------------------------------------------------------------------
        */

        $checkboxes = [

            'allow_author_registration',
            'allow_subscriber_registration',
            'require_email_verification',
            'require_admin_approval',
            'require_strong_passwords',
            'show_statistics',
            'show_recent_activity',
            'enable_notifications',
            'email_notifications',

        ];

        foreach ($checkboxes as $field) {

            $output[$field] = ! empty($input[$field]) ? 1 : 0;

        }

        /*
        |--------------------------------------------------------------------------
        | Numbers
        |--------------------------------------------------------------------------
        */

        $output['max_login_attempts'] = max(
            1,
            absint(
                $input['max_login_attempts']
                ?? $defaults['max_login_attempts']
            )
        );

        $output['lockout_minutes'] = max(
            1,
            absint(
                $input['lockout_minutes']
                ?? $defaults['lockout_minutes']
            )
        );

        $output['password_min_length'] = max(
            6,
            absint(
                $input['password_min_length']
                ?? $defaults['password_min_length']
            )
        );

        $output['dashboard_refresh'] = max(
            10,
            absint(
                $input['dashboard_refresh']
                ?? $defaults['dashboard_refresh']
            )
        );

        $output['notification_refresh'] = max(
            10,
            absint(
                $input['notification_refresh']
                ?? $defaults['notification_refresh']
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Text
        |--------------------------------------------------------------------------
        */

        $output['sender_name'] = sanitize_text_field(
            $input['sender_name']
            ?? $defaults['sender_name']
        );

        $email = sanitize_email(
            $input['sender_email']
            ?? $defaults['sender_email']
        );

        if (empty($email) || ! is_email($email)) {
            $email = $defaults['sender_email'];
        }

        $output['sender_email'] = $email;

        $output['email_footer'] = wp_kses_post(
            $input['email_footer']
            ?? $defaults['email_footer']
        );

        /**
         * Filter settings before saving.
         */
        return apply_filters(
            'nb_accounts_sanitized_settings',
            $output,
            $input
        );
    }

    /**
     * Get all settings.
     */
    public function get(): array
    {
        return wp_parse_args(
            get_option(
                $this->option,
                []
            ),
            $this->defaults()
        );
    }

    /**
     * Get a single setting.
     */
    public function get_setting(
        string $key,
        $default = null
    ) {

        $settings = $this->get();

        return $settings[$key] ?? $default;

    }

    /**
     * Update one setting.
     */
    public function update_setting(
        string $key,
        $value
    ): bool {

        $settings = $this->get();

        $settings[$key] = $value;

        return update_option(
            $this->option,
            $this->sanitize($settings)
        );

    }

    /**
     * Reset all settings.
     */
    public function reset(): bool
    {
        return update_option(
            $this->option,
            $this->defaults()
        );
    }
}