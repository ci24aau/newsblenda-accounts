<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

/**
 * WordPress Settings API registration for all settings groups.
 *
 * Each tab maps to its own option group and wp_options key.  The
 * settings page template in Admin::settings_page() renders tabs and
 * calls settings_fields() / do_settings_sections() per tab.
 */
class Settings
{
    /**
     * Settings page slug – must match what Admin registers.
     */
    private const PAGE_SLUG = 'newsblenda-accounts-settings';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'register']);
        add_action('wp_ajax_nba_send_test_email', [$this, 'ajax_send_test_email']);
        add_action('wp_ajax_nba_smtp_test', [$this, 'ajax_smtp_test']);
        add_action('wp_ajax_nba_reset_settings', [$this, 'ajax_reset_settings']);
    }

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register all settings sections and fields.
     */
    public function register(): void
    {
        $this->register_general();
        $this->register_registration();
        $this->register_security();
        $this->register_workflow();
        $this->register_email();
        $this->register_earnings();
        $this->register_seo();
        $this->register_notifications();
    }

    // -------------------------------------------------------------------------
    // General
    // -------------------------------------------------------------------------

    private function register_general(): void
    {
        register_setting(
            'newsblenda_general',
            'newsblenda_general_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_general'],
                'default'           => SettingsManager::defaults()['general'],
                'capability'        => 'manage_options',
            ]
        );

        add_settings_section(
            'nba_general_site',
            __('Site Identity', 'newsblenda-accounts'),
            '__return_false',
            self::PAGE_SLUG . '-general'
        );

        $fields = [
            ['site_name',                 __('Site Name', 'newsblenda-accounts'),                      'text'],
            ['site_logo',                 __('Site Logo URL', 'newsblenda-accounts'),                  'url'],
            ['primary_color',             __('Primary Color', 'newsblenda-accounts'),                  'color'],
            ['secondary_color',           __('Secondary Color', 'newsblenda-accounts'),                'color'],
            ['site_url',                  __('Frontend Site URL', 'newsblenda-accounts'),              'url'],
            ['platform_description',      __('Platform Description', 'newsblenda-accounts'),           'textarea'],
            ['author_profile_visibility', __('Author Profile Visibility', 'newsblenda-accounts'),      'select_visibility'],
            ['date_format',               __('Date Format', 'newsblenda-accounts'),                    'text'],
        ];

        foreach ($fields as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_general_site', 'general', self::PAGE_SLUG . '-general');
        }
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    private function register_registration(): void
    {
        register_setting(
            'newsblenda_registration',
            'newsblenda_registration_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_registration'],
                'default'           => SettingsManager::defaults()['registration'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_reg_general', __('General Registration', 'newsblenda-accounts'), 'registration');
        $this->add_section('nba_reg_password', __('Password Requirements', 'newsblenda-accounts'), 'registration');
        $this->add_section('nba_reg_protection', __('Spam Protection', 'newsblenda-accounts'), 'registration');

        $general = [
            ['enable_registration',          __('Enable Registration', 'newsblenda-accounts'),            'checkbox'],
            ['auto_assign_role',              __('Auto-Assign Role', 'newsblenda-accounts'),               'select_role'],
            ['email_verification_required',  __('Require Email Verification', 'newsblenda-accounts'),     'checkbox'],
            ['verification_expiration',      __('Verification Expiration (hours)', 'newsblenda-accounts'), 'number'],
            ['duplicate_username_detection', __('Detect Duplicate Usernames', 'newsblenda-accounts'),     'checkbox'],
            ['duplicate_email_detection',    __('Detect Duplicate Emails', 'newsblenda-accounts'),        'checkbox'],
        ];

        foreach ($general as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_reg_general', 'registration', self::PAGE_SLUG . '-registration');
        }

        $password = [
            ['password_min_length',         __('Minimum Password Length', 'newsblenda-accounts'),        'number'],
            ['password_require_uppercase',  __('Require Uppercase Letter', 'newsblenda-accounts'),       'checkbox'],
            ['password_require_numbers',    __('Require Number', 'newsblenda-accounts'),                  'checkbox'],
            ['password_require_special',    __('Require Special Character', 'newsblenda-accounts'),      'checkbox'],
        ];

        foreach ($password as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_reg_password', 'registration', self::PAGE_SLUG . '-registration');
        }

        $protection = [
            ['captcha_enabled',      __('Enable CAPTCHA', 'newsblenda-accounts'),                'checkbox'],
            ['honeypot_enabled',     __('Enable Honeypot Protection', 'newsblenda-accounts'),    'checkbox'],
            ['rate_limiting_enabled', __('Enable Rate Limiting', 'newsblenda-accounts'),         'checkbox'],
            ['rate_limit_threshold', __('Rate Limit Threshold (per hour)', 'newsblenda-accounts'), 'number'],
        ];

        foreach ($protection as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_reg_protection', 'registration', self::PAGE_SLUG . '-registration');
        }
    }

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------

    private function register_security(): void
    {
        register_setting(
            'newsblenda_security',
            'newsblenda_security_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_security'],
                'default'           => SettingsManager::defaults()['security'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_sec_login', __('Login Security', 'newsblenda-accounts'), 'security');
        $this->add_section('nba_sec_session', __('Session & Cookies', 'newsblenda-accounts'), 'security');
        $this->add_section('nba_sec_advanced', __('Advanced Security', 'newsblenda-accounts'), 'security');

        $login = [
            ['password_reset_expiration', __('Password Reset Expiration (hours)', 'newsblenda-accounts'), 'number'],
            ['login_failure_limit',       __('Login Failure Limit', 'newsblenda-accounts'),               'number'],
            ['lockout_duration',          __('Lockout Duration (minutes)', 'newsblenda-accounts'),        'number'],
            ['rate_limit_logins',         __('Rate Limit Login Attempts', 'newsblenda-accounts'),         'checkbox'],
        ];

        foreach ($login as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_sec_login', 'security', self::PAGE_SLUG . '-security');
        }

        $session = [
            ['session_timeout', __('Session Timeout (minutes)', 'newsblenda-accounts'), 'number'],
            ['force_https',     __('Force HTTPS', 'newsblenda-accounts'),               'checkbox'],
            ['cookie_secure',   __('Cookie Secure Flag', 'newsblenda-accounts'),        'checkbox'],
            ['cookie_httponly', __('Cookie HttpOnly Flag', 'newsblenda-accounts'),      'checkbox'],
        ];

        foreach ($session as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_sec_session', 'security', self::PAGE_SLUG . '-security');
        }

        $advanced = [
            ['enable_2fa',              __('Enable Two-Factor Authentication', 'newsblenda-accounts'), 'checkbox'],
            ['security_headers_enabled', __('Enable Security Headers', 'newsblenda-accounts'),          'checkbox'],
        ];

        foreach ($advanced as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_sec_advanced', 'security', self::PAGE_SLUG . '-security');
        }
    }

    // -------------------------------------------------------------------------
    // Workflow
    // -------------------------------------------------------------------------

    private function register_workflow(): void
    {
        register_setting(
            'newsblenda_workflow',
            'newsblenda_workflow_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_workflow'],
                'default'           => SettingsManager::defaults()['workflow'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_wf_publishing', __('Publishing', 'newsblenda-accounts'), 'workflow');
        $this->add_section('nba_wf_articles', __('Article Requirements', 'newsblenda-accounts'), 'workflow');
        $this->add_section('nba_wf_revisions', __('Revisions', 'newsblenda-accounts'), 'workflow');

        $publishing = [
            ['auto_publish_approved',       __('Auto-Publish Approved Articles', 'newsblenda-accounts'), 'checkbox'],
            ['auto_publish_delay',          __('Auto-Publish Delay (hours)', 'newsblenda-accounts'),     'number'],
            ['allow_author_delete',         __('Authors Can Delete Own Articles', 'newsblenda-accounts'), 'checkbox'],
            ['allow_author_edit_published', __('Authors Can Edit Published Articles', 'newsblenda-accounts'), 'checkbox'],
            ['editor_assignment',           __('Editor Assignment', 'newsblenda-accounts'),              'select_editor_assignment'],
            ['enable_scheduling',           __('Enable Article Scheduling', 'newsblenda-accounts'),      'checkbox'],
        ];

        foreach ($publishing as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_wf_publishing', 'workflow', self::PAGE_SLUG . '-workflow');
        }

        $articles = [
            ['require_article_category', __('Require Article Category', 'newsblenda-accounts'), 'checkbox'],
            ['require_article_tags',     __('Require Article Tags', 'newsblenda-accounts'),     'checkbox'],
            ['require_featured_image',   __('Require Featured Image', 'newsblenda-accounts'),   'checkbox'],
            ['min_word_count',           __('Minimum Word Count', 'newsblenda-accounts'),        'number'],
            ['max_word_count',           __('Maximum Word Count (0 = unlimited)', 'newsblenda-accounts'), 'number'],
        ];

        foreach ($articles as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_wf_articles', 'workflow', self::PAGE_SLUG . '-workflow');
        }

        $revisions = [
            ['revision_request_minimum', __('Minimum Days Before Resubmission', 'newsblenda-accounts'), 'number'],
            ['max_revision_requests',    __('Maximum Revision Requests per Article', 'newsblenda-accounts'), 'number'],
        ];

        foreach ($revisions as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_wf_revisions', 'workflow', self::PAGE_SLUG . '-workflow');
        }
    }

    // -------------------------------------------------------------------------
    // Email
    // -------------------------------------------------------------------------

    private function register_email(): void
    {
        register_setting(
            'newsblenda_email',
            'newsblenda_email_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_email'],
                'default'           => SettingsManager::defaults()['email'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_email_sender', __('Sender Settings', 'newsblenda-accounts'), 'email');
        $this->add_section('nba_email_smtp', __('SMTP Configuration', 'newsblenda-accounts'), 'email');
        $this->add_section('nba_email_branding', __('Email Branding', 'newsblenda-accounts'), 'email');
        $this->add_section('nba_email_notifications', __('Email Notification Types', 'newsblenda-accounts'), 'email');

        $sender = [
            ['from_name',    __('From Name', 'newsblenda-accounts'),    'text'],
            ['from_address', __('From Address', 'newsblenda-accounts'), 'email'],
        ];

        foreach ($sender as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_email_sender', 'email', self::PAGE_SLUG . '-email');
        }

        $smtp = [
            ['smtp_enabled',       __('Enable SMTP', 'newsblenda-accounts'),       'checkbox'],
            ['smtp_host',          __('SMTP Host', 'newsblenda-accounts'),          'text'],
            ['smtp_port',          __('SMTP Port', 'newsblenda-accounts'),          'number'],
            ['smtp_username',      __('SMTP Username', 'newsblenda-accounts'),      'text'],
            ['smtp_password',      __('SMTP Password', 'newsblenda-accounts'),      'password'],
            ['smtp_encryption',    __('SMTP Encryption', 'newsblenda-accounts'),    'select_smtp_encryption'],
            ['smtp_test',          __('Test Connection', 'newsblenda-accounts'),    'smtp_test_button'],
            ['test_email_address', __('Test Email Address', 'newsblenda-accounts'), 'email_test'],
        ];

        foreach ($smtp as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_email_smtp', 'email', self::PAGE_SLUG . '-email');
        }

        $branding = [
            ['header_logo',  __('Header Logo URL', 'newsblenda-accounts'), 'url'],
            ['footer_text',  __('Footer Text', 'newsblenda-accounts'),     'textarea'],
            ['brand_color',  __('Brand Color', 'newsblenda-accounts'),     'color'],
        ];

        foreach ($branding as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_email_branding', 'email', self::PAGE_SLUG . '-email');
        }

        $notifications = [
            ['notify_new_article',      __('New Article Submissions to Editors', 'newsblenda-accounts'), 'checkbox'],
            ['notify_approval',         __('Approval Notifications to Authors', 'newsblenda-accounts'),  'checkbox'],
            ['notify_rejection',        __('Rejection Notifications to Authors', 'newsblenda-accounts'), 'checkbox'],
            ['notify_revision_request', __('Revision Request Notifications', 'newsblenda-accounts'),     'checkbox'],
            ['notify_payout',           __('Payout Notifications', 'newsblenda-accounts'),               'checkbox'],
            ['notify_welcome',          __('New User Welcome Emails', 'newsblenda-accounts'),             'checkbox'],
            ['notify_verification',     __('Email Verification Emails', 'newsblenda-accounts'),          'checkbox'],
            ['notify_password_reset',   __('Password Reset Emails', 'newsblenda-accounts'),              'checkbox'],
        ];

        foreach ($notifications as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_email_notifications', 'email', self::PAGE_SLUG . '-email');
        }
    }

    // -------------------------------------------------------------------------
    // Earnings
    // -------------------------------------------------------------------------

    private function register_earnings(): void
    {
        register_setting(
            'newsblenda_earnings',
            'newsblenda_earnings_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_earnings'],
                'default'           => SettingsManager::defaults()['earnings'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_earn_model', __('Earnings Model', 'newsblenda-accounts'), 'earnings');
        $this->add_section('nba_earn_payout', __('Payout Configuration', 'newsblenda-accounts'), 'earnings');

        $model = [
            ['enable_earnings',        __('Enable Earnings Tracking', 'newsblenda-accounts'),   'checkbox'],
            ['earnings_model',         __('Earnings Model', 'newsblenda-accounts'),              'select_earnings_model'],
            ['price_per_view',         __('Price Per View ($)', 'newsblenda-accounts'),          'decimal'],
            ['fixed_rate_per_article', __('Fixed Rate Per Article ($)', 'newsblenda-accounts'), 'decimal'],
            ['currency',               __('Currency', 'newsblenda-accounts'),                    'select_currency'],
            ['tax_rate',               __('Tax Rate (%)', 'newsblenda-accounts'),                'decimal'],
            ['top_articles_threshold', __('Top Articles View Threshold', 'newsblenda-accounts'), 'number'],
        ];

        foreach ($model as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_earn_model', 'earnings', self::PAGE_SLUG . '-earnings');
        }

        $payout = [
            ['min_payout_amount',     __('Minimum Payout Amount ($)', 'newsblenda-accounts'),       'decimal'],
            ['payout_frequency',      __('Payout Frequency', 'newsblenda-accounts'),                'select_payout_frequency'],
            ['payout_methods_bank',   __('Bank Transfer', 'newsblenda-accounts'),                   'checkbox'],
            ['payout_methods_paypal', __('PayPal', 'newsblenda-accounts'),                          'checkbox'],
            ['payout_methods_stripe', __('Stripe', 'newsblenda-accounts'),                          'checkbox'],
            ['auto_payout',           __('Automatic Payout Processing', 'newsblenda-accounts'),     'checkbox'],
            ['payout_pending_period', __('Payout Pending Period (days)', 'newsblenda-accounts'),    'number'],
        ];

        foreach ($payout as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_earn_payout', 'earnings', self::PAGE_SLUG . '-earnings');
        }
    }

    // -------------------------------------------------------------------------
    // SEO
    // -------------------------------------------------------------------------

    private function register_seo(): void
    {
        register_setting(
            'newsblenda_seo',
            'newsblenda_seo_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_seo'],
                'default'           => SettingsManager::defaults()['seo'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_seo_meta', __('Meta Tags', 'newsblenda-accounts'), 'seo');
        $this->add_section('nba_seo_schema', __('Schema & Structured Data', 'newsblenda-accounts'), 'seo');
        $this->add_section('nba_seo_social', __('Social Media Profiles', 'newsblenda-accounts'), 'seo');

        $meta = [
            ['seo_title_template',        __('SEO Title Template', 'newsblenda-accounts'),        'text'],
            ['meta_description_template', __('Meta Description Template', 'newsblenda-accounts'), 'textarea'],
            ['open_graph_enabled',        __('Enable Open Graph', 'newsblenda-accounts'),          'checkbox'],
            ['twitter_card_enabled',      __('Enable Twitter Cards', 'newsblenda-accounts'),       'checkbox'],
            ['canonical_urls_enabled',    __('Enable Canonical URLs', 'newsblenda-accounts'),      'checkbox'],
            ['xml_sitemap_enabled',       __('Enable XML Sitemap', 'newsblenda-accounts'),         'checkbox'],
        ];

        foreach ($meta as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_seo_meta', 'seo', self::PAGE_SLUG . '-seo');
        }

        $schema = [
            ['schema_markup_enabled', __('Enable Schema Markup', 'newsblenda-accounts'),         'checkbox'],
            ['author_schema',         __('Author Schema in Articles', 'newsblenda-accounts'),    'checkbox'],
            ['org_schema_enabled',    __('Enable Organization Schema', 'newsblenda-accounts'),   'checkbox'],
            ['org_name',              __('Organization Name', 'newsblenda-accounts'),             'text'],
            ['org_logo',              __('Organization Logo URL', 'newsblenda-accounts'),         'url'],
        ];

        foreach ($schema as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_seo_schema', 'seo', self::PAGE_SLUG . '-seo');
        }

        $social = [
            ['social_facebook', __('Facebook Profile URL', 'newsblenda-accounts'),  'url'],
            ['social_twitter',  __('Twitter Profile URL', 'newsblenda-accounts'),   'url'],
            ['social_linkedin', __('LinkedIn Profile URL', 'newsblenda-accounts'),  'url'],
        ];

        foreach ($social as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_seo_social', 'seo', self::PAGE_SLUG . '-seo');
        }
    }

    // -------------------------------------------------------------------------
    // Notifications
    // -------------------------------------------------------------------------

    private function register_notifications(): void
    {
        register_setting(
            'newsblenda_notifications',
            'newsblenda_notifications_settings',
            [
                'sanitize_callback' => [SettingsValidator::class, 'sanitize_notifications'],
                'default'           => SettingsManager::defaults()['notifications'],
                'capability'        => 'manage_options',
            ]
        );

        $this->add_section('nba_notif_general', __('General Notifications', 'newsblenda-accounts'), 'notifications');
        $this->add_section('nba_notif_categories', __('Notification Categories', 'newsblenda-accounts'), 'notifications');
        $this->add_section('nba_notif_email', __('Email Delivery', 'newsblenda-accounts'), 'notifications');

        $general = [
            ['dashboard_notifications_enabled', __('Enable Dashboard Notifications', 'newsblenda-accounts'), 'checkbox'],
            ['email_notifications_enabled',     __('Enable Email Notifications', 'newsblenda-accounts'),     'checkbox'],
            ['notification_badge_enabled',      __('Enable Notification Badge', 'newsblenda-accounts'),      'checkbox'],
            ['notification_retention',          __('Notification Retention (days)', 'newsblenda-accounts'),  'number'],
        ];

        foreach ($general as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_notif_general', 'notifications', self::PAGE_SLUG . '-notifications');
        }

        $categories = [
            ['notify_article_submissions', __('Article Submissions', 'newsblenda-accounts'), 'checkbox'],
            ['notify_article_approvals',   __('Article Approvals', 'newsblenda-accounts'),   'checkbox'],
            ['notify_article_rejections',  __('Article Rejections', 'newsblenda-accounts'),  'checkbox'],
            ['notify_revision_requests',   __('Revision Requests', 'newsblenda-accounts'),   'checkbox'],
            ['notify_payouts',             __('Payouts', 'newsblenda-accounts'),              'checkbox'],
            ['notify_system_alerts',       __('System Alerts', 'newsblenda-accounts'),        'checkbox'],
        ];

        foreach ($categories as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_notif_categories', 'notifications', self::PAGE_SLUG . '-notifications');
        }

        $email = [
            ['email_frequency',           __('Email Frequency', 'newsblenda-accounts'),             'select_email_frequency'],
            ['admin_notification_email',  __('Admin Notification Email', 'newsblenda-accounts'),    'email'],
            ['editor_notification_email', __('Editor Notification Email', 'newsblenda-accounts'),   'email'],
        ];

        foreach ($email as [$key, $label, $type]) {
            $this->add_field($key, $label, $type, 'nba_notif_email', 'notifications', self::PAGE_SLUG . '-notifications');
        }
    }

    // =========================================================================
    // Field registration helpers
    // =========================================================================

    /**
     * Register a settings section for a given tab page.
     */
    private function add_section(string $id, string $title, string $tab): void
    {
        add_settings_section(
            $id,
            $title,
            '__return_false',
            self::PAGE_SLUG . '-' . $tab
        );
    }

    /**
     * Register a single settings field.
     */
    private function add_field(
        string $key,
        string $label,
        string $type,
        string $section,
        string $settings_section,
        string $page
    ): void {
        add_settings_field(
            $key,
            $label,
            [$this, 'render_field'],
            $page,
            $section,
            [
                'key'              => $key,
                'type'             => $type,
                'settings_section' => $settings_section,
                'label_for'        => 'nba_' . $key,
            ]
        );
    }

    // =========================================================================
    // Field renderer
    // =========================================================================

    /**
     * Render a single settings field.
     *
     * @param array<string,mixed> $args
     */
    public function render_field(array $args): void
    {
        $key              = $args['key'];
        $type             = $args['type'];
        $settings_section = $args['settings_section'];
        $option_name      = SettingsManager::OPTION_KEYS[$settings_section] ?? '';
        $value            = SettingsManager::get($settings_section, $key);
        $field_id         = 'nba_' . $key;
        $field_name       = $option_name . '[' . $key . ']';

        switch ($type) {

            case 'checkbox':
                printf(
                    '<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    checked((int) $value, 1, false),
                    esc_html__('Enabled', 'newsblenda-accounts')
                );
                break;

            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
                break;

            case 'decimal':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0" step="0.001">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
                break;

            case 'email':
                printf(
                    '<input type="email" id="%s" name="%s" value="%s" class="regular-text">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
                break;

            case 'email_test':
                printf(
                    '<input type="email" id="%s" name="%s" value="%s" class="regular-text">
                     <button type="button" class="button nba-send-test-email" data-nonce="%s">%s</button>
                     <span class="nba-test-email-result" style="display:none;margin-left:8px;"></span>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value),
                    esc_attr(wp_create_nonce('nba_send_test_email')),
                    esc_html__('Send Test Email', 'newsblenda-accounts')
                );
                break;

            case 'url':
                printf(
                    '<input type="url" id="%s" name="%s" value="%s" class="regular-text" placeholder="https://">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
                break;

            case 'password':
                printf(
                    '<input type="password" id="%s" name="%s" value="%s" class="regular-text" autocomplete="new-password" placeholder="%s">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value),
                    esc_attr__('Leave blank to keep current', 'newsblenda-accounts')
                );
                break;

            case 'color':
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" class="nba-color-picker" data-default-color="%s">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value),
                    esc_attr((string) $value)
                );
                break;

            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="4" class="large-text">%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_textarea((string) $value)
                );
                break;

            case 'select_visibility':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'public'  => __('Public', 'newsblenda-accounts'),
                    'private' => __('Private', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_role':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'nbe_author' => __('Newsblenda Author', 'newsblenda-accounts'),
                    'author'     => __('WordPress Author', 'newsblenda-accounts'),
                    'editor'     => __('Editor', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_editor_assignment':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'manual' => __('Manual (admin assigns)', 'newsblenda-accounts'),
                    'auto'   => __('Auto (round-robin)', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_smtp_encryption':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'none' => __('None', 'newsblenda-accounts'),
                    'tls'  => __('TLS (recommended)', 'newsblenda-accounts'),
                    'ssl'  => __('SSL', 'newsblenda-accounts'),
                ]);
                break;

            case 'smtp_test_button':
                printf(
                    '<button type="button" class="button nba-smtp-test" data-nonce="%s">%s</button>
                     <span id="nba-smtp-test-result" style="display:none;margin-left:8px;"></span>',
                    esc_attr(wp_create_nonce('nba_smtp_test')),
                    esc_html__('Test Connection', 'newsblenda-accounts')
                );
                break;

            case 'select_earnings_model':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'per-view'   => __('Per View', 'newsblenda-accounts'),
                    'fixed-rate' => __('Fixed Rate per Article', 'newsblenda-accounts'),
                    'hybrid'     => __('Hybrid (per-view + fixed)', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_payout_frequency':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'weekly'    => __('Weekly', 'newsblenda-accounts'),
                    'bi-weekly' => __('Bi-Weekly', 'newsblenda-accounts'),
                    'monthly'   => __('Monthly', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_currency':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'USD' => __('USD – US Dollar', 'newsblenda-accounts'),
                    'EUR' => __('EUR – Euro', 'newsblenda-accounts'),
                    'GBP' => __('GBP – British Pound', 'newsblenda-accounts'),
                    'CAD' => __('CAD – Canadian Dollar', 'newsblenda-accounts'),
                    'AUD' => __('AUD – Australian Dollar', 'newsblenda-accounts'),
                    'JPY' => __('JPY – Japanese Yen', 'newsblenda-accounts'),
                    'CHF' => __('CHF – Swiss Franc', 'newsblenda-accounts'),
                    'INR' => __('INR – Indian Rupee', 'newsblenda-accounts'),
                ]);
                break;

            case 'select_email_frequency':
                $this->render_select($field_id, $field_name, (string) $value, [
                    'immediate' => __('Immediate', 'newsblenda-accounts'),
                    'daily'     => __('Daily Digest', 'newsblenda-accounts'),
                    'weekly'    => __('Weekly Digest', 'newsblenda-accounts'),
                ]);
                break;

            default: // text
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" class="regular-text">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
                break;
        }

        $this->render_description($key, $settings_section);
    }

    /**
     * Render a <select> element.
     *
     * @param array<string,string> $options
     */
    private function render_select(
        string $id,
        string $name,
        string $current,
        array $options
    ): void {
        printf('<select id="%s" name="%s">', esc_attr($id), esc_attr($name));

        foreach ($options as $val => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($val),
                selected($current, $val, false),
                esc_html($label)
            );
        }

        echo '</select>';
    }

    /**
     * Render field description / help text.
     */
    private function render_description(string $key, string $section): void
    {
        $descriptions = $this->descriptions();
        $text         = $descriptions[$section][$key] ?? '';

        if ($text === '') {
            return;
        }

        printf(
            '<p class="description">%s</p>',
            esc_html($text)
        );
    }

    /**
     * Field descriptions keyed by [section][field].
     *
     * @return array<string,array<string,string>>
     */
    private function descriptions(): array
    {
        return [

            'general' => [
                'site_name'                  => __('Brand name shown in emails and UI.', 'newsblenda-accounts'),
                'site_logo'                  => __('Full URL to your site logo image.', 'newsblenda-accounts'),
                'primary_color'              => __('Brand primary colour used in emails and UI components.', 'newsblenda-accounts'),
                'secondary_color'            => __('Accent colour for buttons and highlights.', 'newsblenda-accounts'),
                'site_url'                   => __('Frontend base URL (not the WordPress admin URL).', 'newsblenda-accounts'),
                'platform_description'       => __('Short tagline or description shown on the site.', 'newsblenda-accounts'),
                'author_profile_visibility'  => __('Whether author profiles are publicly visible.', 'newsblenda-accounts'),
                'date_format'                => __('PHP date format string used across the plugin.', 'newsblenda-accounts'),
            ],

            'registration' => [
                'enable_registration'           => __('Allow new users to register on the platform.', 'newsblenda-accounts'),
                'auto_assign_role'              => __('Role automatically assigned to new registrants.', 'newsblenda-accounts'),
                'email_verification_required'   => __('Registrants must verify their email before they can log in.', 'newsblenda-accounts'),
                'verification_expiration'       => __('Hours before an email verification token expires.', 'newsblenda-accounts'),
                'duplicate_username_detection'  => __('Prevent registrations with a username already in use.', 'newsblenda-accounts'),
                'duplicate_email_detection'     => __('Prevent registrations with an email address already in use.', 'newsblenda-accounts'),
                'password_min_length'           => __('Minimum number of characters required in a password.', 'newsblenda-accounts'),
                'password_require_uppercase'    => __('Password must contain at least one uppercase letter.', 'newsblenda-accounts'),
                'password_require_numbers'      => __('Password must contain at least one number.', 'newsblenda-accounts'),
                'password_require_special'      => __('Password must contain at least one special character.', 'newsblenda-accounts'),
                'captcha_enabled'               => __('Enable CAPTCHA challenge on the registration form.', 'newsblenda-accounts'),
                'honeypot_enabled'              => __('Add a hidden honeypot field to catch bots.', 'newsblenda-accounts'),
                'rate_limiting_enabled'         => __('Limit the number of registrations per IP address per hour.', 'newsblenda-accounts'),
                'rate_limit_threshold'          => __('Maximum registrations allowed per IP per hour.', 'newsblenda-accounts'),
            ],

            'security' => [
                'password_reset_expiration' => __('Hours before a password reset link expires.', 'newsblenda-accounts'),
                'login_failure_limit'       => __('Number of failed login attempts before lockout.', 'newsblenda-accounts'),
                'lockout_duration'          => __('Minutes an IP is locked out after too many failed logins.', 'newsblenda-accounts'),
                'rate_limit_logins'         => __('Enable rate limiting for login attempts.', 'newsblenda-accounts'),
                'session_timeout'           => __('Minutes of inactivity before a session is invalidated.', 'newsblenda-accounts'),
                'force_https'               => __('Redirect all frontend pages to HTTPS.', 'newsblenda-accounts'),
                'cookie_secure'             => __('Only transmit auth cookies over HTTPS connections.', 'newsblenda-accounts'),
                'cookie_httponly'           => __('Prevent JavaScript from accessing auth cookies.', 'newsblenda-accounts'),
                'enable_2fa'                => __('Future-proof toggle — enables two-factor authentication when implemented.', 'newsblenda-accounts'),
                'security_headers_enabled'  => __('Output X-Content-Type-Options, X-Frame-Options, and Referrer-Policy headers.', 'newsblenda-accounts'),
            ],

            'workflow' => [
                'auto_publish_approved'       => __('Automatically publish articles immediately after approval.', 'newsblenda-accounts'),
                'auto_publish_delay'          => __('Hours to delay auto-publishing after approval (0 = immediate).', 'newsblenda-accounts'),
                'allow_author_delete'         => __('Allow authors to permanently delete their own draft articles.', 'newsblenda-accounts'),
                'allow_author_edit_published' => __('Allow authors to edit articles after they have been published.', 'newsblenda-accounts'),
                'revision_request_minimum'    => __('Minimum days an author must wait before resubmitting a revised article.', 'newsblenda-accounts'),
                'max_revision_requests'       => __('Maximum number of revision requests allowed per article.', 'newsblenda-accounts'),
                'require_article_category'    => __('Authors must assign at least one category before submitting.', 'newsblenda-accounts'),
                'require_article_tags'        => __('Authors must add at least one tag before submitting.', 'newsblenda-accounts'),
                'require_featured_image'      => __('Articles must have a featured image to be submitted.', 'newsblenda-accounts'),
                'min_word_count'              => __('Minimum word count required for an article submission (0 = no limit).', 'newsblenda-accounts'),
                'max_word_count'              => __('Maximum word count allowed per article (0 = no limit).', 'newsblenda-accounts'),
                'editor_assignment'           => __('How editors are assigned to incoming article submissions.', 'newsblenda-accounts'),
                'enable_scheduling'           => __('Allow editors to schedule articles for future publication.', 'newsblenda-accounts'),
            ],

            'email' => [
                'from_name'               => __('Name shown as the email sender.', 'newsblenda-accounts'),
                'from_address'            => __('Email address used for outgoing messages.', 'newsblenda-accounts'),
                'smtp_enabled'            => __('Send emails via your own SMTP server instead of PHP mail().', 'newsblenda-accounts'),
                'smtp_host'               => __('SMTP server hostname (e.g. smtp.gmail.com).', 'newsblenda-accounts'),
                'smtp_port'               => __('SMTP server port. Typical values: 587 (TLS), 465 (SSL), 25 (none).', 'newsblenda-accounts'),
                'smtp_username'           => __('SMTP authentication username.', 'newsblenda-accounts'),
                'smtp_password'           => __('SMTP authentication password. Leave blank to keep the current value.', 'newsblenda-accounts'),
                'smtp_encryption'         => __('Encryption method for the SMTP connection.', 'newsblenda-accounts'),
                'test_email_address'      => __('Send a test email to this address to verify your settings.', 'newsblenda-accounts'),
                'header_logo'             => __('Logo URL shown at the top of HTML emails.', 'newsblenda-accounts'),
                'footer_text'             => __('Text displayed in the footer of every outgoing email.', 'newsblenda-accounts'),
                'brand_color'             => __('Colour used in email headers and call-to-action buttons.', 'newsblenda-accounts'),
            ],

            'earnings' => [
                'enable_earnings'        => __('Enable the earnings and payouts system.', 'newsblenda-accounts'),
                'earnings_model'         => __('How author earnings are calculated.', 'newsblenda-accounts'),
                'price_per_view'         => __('Amount (in the selected currency) earned per article view.', 'newsblenda-accounts'),
                'fixed_rate_per_article' => __('Fixed payment per published article.', 'newsblenda-accounts'),
                'currency'               => __('Currency used for all earnings and payouts.', 'newsblenda-accounts'),
                'tax_rate'               => __('Tax rate deducted from gross earnings before payout (0–100%).', 'newsblenda-accounts'),
                'top_articles_threshold' => __('Minimum view count for an article to be considered "top performing".', 'newsblenda-accounts'),
                'min_payout_amount'      => __('Minimum balance required before a payout is processed.', 'newsblenda-accounts'),
                'payout_frequency'       => __('How often payouts are processed.', 'newsblenda-accounts'),
                'auto_payout'            => __('Automatically process payouts on schedule without manual approval.', 'newsblenda-accounts'),
                'payout_pending_period'  => __('Days after the period end before earnings become available for payout.', 'newsblenda-accounts'),
            ],

            'seo' => [
                'seo_title_template'        => __('Template for the SEO title tag. Use %title%, %author%, %site%.', 'newsblenda-accounts'),
                'meta_description_template' => __('Default meta description template. Leave blank to use excerpt.', 'newsblenda-accounts'),
                'open_graph_enabled'        => __('Output Open Graph meta tags for richer social sharing previews.', 'newsblenda-accounts'),
                'twitter_card_enabled'      => __('Output Twitter Card meta tags.', 'newsblenda-accounts'),
                'canonical_urls_enabled'    => __('Output canonical link tags to prevent duplicate content.', 'newsblenda-accounts'),
                'xml_sitemap_enabled'       => __('Generate an XML sitemap for author profile pages.', 'newsblenda-accounts'),
                'schema_markup_enabled'     => __('Output JSON-LD schema markup on article pages.', 'newsblenda-accounts'),
                'author_schema'             => __('Include author schema data inside article schema.', 'newsblenda-accounts'),
                'org_schema_enabled'        => __('Output Organization schema on the homepage.', 'newsblenda-accounts'),
                'org_name'                  => __('Organisation name used in schema markup.', 'newsblenda-accounts'),
                'org_logo'                  => __('Organisation logo URL used in schema markup.', 'newsblenda-accounts'),
            ],

            'notifications' => [
                'dashboard_notifications_enabled' => __('Show in-app notification alerts in the frontend dashboard.', 'newsblenda-accounts'),
                'email_notifications_enabled'     => __('Send email notifications in addition to dashboard alerts.', 'newsblenda-accounts'),
                'notification_badge_enabled'      => __('Show a badge counter on the notifications menu item.', 'newsblenda-accounts'),
                'notification_retention'          => __('Days to keep read notifications before automatic deletion.', 'newsblenda-accounts'),
                'email_frequency'                 => __('How often email notification digests are sent.', 'newsblenda-accounts'),
                'admin_notification_email'        => __('Email address for administrator-level notifications.', 'newsblenda-accounts'),
                'editor_notification_email'       => __('Notification emails for editors. Leave blank to use each editor\'s account email.', 'newsblenda-accounts'),
            ],

        ];
    }

    // =========================================================================
    // AJAX handlers
    // =========================================================================

    /**
     * AJAX: send a test email.
     */
    public function ajax_send_test_email(): void
    {
        check_ajax_referer('nba_send_test_email', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('Permission denied.', 'newsblenda-accounts')]
            );
        }

        $to = sanitize_email(
            wp_unslash($_POST['email'] ?? '')
        );

        if (! is_email($to)) {
            wp_send_json_error(
                ['message' => __('Please enter a valid email address.', 'newsblenda-accounts')]
            );
        }

        $from_name    = SettingsManager::get('email', 'from_name', get_bloginfo('name'));
        $from_address = SettingsManager::get('email', 'from_address', get_option('admin_email'));

        add_filter(
            'wp_mail_from',
            static fn () => $from_address
        );

        add_filter(
            'wp_mail_from_name',
            static fn () => $from_name
        );

        $sent = wp_mail(
            $to,
            sprintf(
                /* translators: %s: site name */
                __('[%s] Test Email', 'newsblenda-accounts'),
                get_bloginfo('name')
            ),
            sprintf(
                /* translators: %s: recipient email */
                __('This is a test email from Newsblenda Accounts. Your email settings are working correctly. Sent to: %s', 'newsblenda-accounts'),
                $to
            ),
            [
                'Content-Type: text/plain; charset=UTF-8',
                sprintf('From: %s <%s>', $from_name, $from_address),
            ]
        );

        if ($sent) {
            wp_send_json_success(
                [
                    'message' => sprintf(
                        /* translators: %s: email address */
                        __('Test email sent successfully to %s.', 'newsblenda-accounts'),
                        $to
                    ),
                ]
            );
        } else {
            wp_send_json_error(
                ['message' => __('Failed to send test email. Check your email configuration.', 'newsblenda-accounts')]
            );
        }
    }

    /**
     * AJAX: test SMTP connection.
     */
    public function ajax_smtp_test(): void
    {
        check_ajax_referer('nba_smtp_test', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('Permission denied.', 'newsblenda-accounts')]
            );
        }

        $host       = sanitize_text_field(wp_unslash($_POST['host'] ?? ''));
        $port       = absint($_POST['port'] ?? 587);
        $encryption = sanitize_text_field(wp_unslash($_POST['encryption'] ?? 'tls'));

        if (empty($host)) {
            wp_send_json_error(
                ['message' => __('SMTP host is required.', 'newsblenda-accounts')]
            );
        }

        $result = $this->test_smtp_connection($host, $port, $encryption);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX: reset a settings section to defaults.
     */
    public function ajax_reset_settings(): void
    {
        check_ajax_referer('nba_reset_settings', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('Permission denied.', 'newsblenda-accounts')]
            );
        }

        $section = sanitize_key(wp_unslash($_POST['section'] ?? ''));

        if (! array_key_exists($section, SettingsManager::OPTION_KEYS)) {
            wp_send_json_error(
                ['message' => __('Invalid settings section.', 'newsblenda-accounts')]
            );
        }

        SettingsManager::reset($section);

        wp_send_json_success(
            ['message' => __('Settings reset to defaults.', 'newsblenda-accounts')]
        );
    }

    // =========================================================================
    // SMTP connection test
    // =========================================================================

    /**
     * Attempt a basic TCP connection to the SMTP server.
     *
     * @return array{success:bool,message:string}
     */
    private function test_smtp_connection(
        string $host,
        int $port,
        string $encryption
    ): array {
        $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $target = $prefix . $host;

        set_error_handler(static function (): bool {
            return true;
        });

        $socket = @fsockopen($target, $port, $errno, $errstr, 10);

        restore_error_handler();

        if (is_resource($socket)) {
            fclose($socket);

            return [
                'success' => true,
                'message' => sprintf(
                    /* translators: 1: host 2: port */
                    __('Connected successfully to %1$s:%2$d.', 'newsblenda-accounts'),
                    $host,
                    $port
                ),
            ];
        }

        return [
            'success' => false,
            'message' => sprintf(
                /* translators: 1: host 2: port 3: error */
                __('Could not connect to %1$s:%2$d — %3$s', 'newsblenda-accounts'),
                $host,
                $port,
                $errstr
            ),
        ];
    }

    // =========================================================================
    // Backward-compatible access
    // =========================================================================

    /**
     * Get a single setting (legacy compatibility shim).
     *
     * @param string $key     Setting key; uses first section found that contains it.
     * @param mixed  $default Fallback value.
     * @return mixed
     */
    public function get_setting(string $key, $default = null)
    {
        return SettingsManager::legacy_get($key, $default);
    }

    /**
     * Get all settings merged for a specific section.
     *
     * @return array<string,mixed>
     */
    public function get(string $section = 'general'): array
    {
        return SettingsManager::get_all($section);
    }
}
