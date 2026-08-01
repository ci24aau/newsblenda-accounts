<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

/**
 * Central settings manager.
 *
 * Provides a unified static API for reading and writing all Newsblenda
 * settings groups. Each group is stored as a serialized array in wp_options.
 */
class SettingsManager
{
    /**
     * Settings schema version stored in wp_options.
     */
    public const SCHEMA_VERSION = '1.0.0';

    /**
     * Option name for the schema version.
     */
    public const SCHEMA_VERSION_OPTION = 'newsblenda_settings_schema_version';

    /**
     * Map of section slug → wp_options key.
     */
    public const OPTION_KEYS = [
        'general'       => 'newsblenda_general_settings',
        'registration'  => 'newsblenda_registration_settings',
        'security'      => 'newsblenda_security_settings',
        'workflow'      => 'newsblenda_workflow_settings',
        'email'         => 'newsblenda_email_settings',
        'earnings'      => 'newsblenda_earnings_settings',
        'seo'           => 'newsblenda_seo_settings',
        'notifications' => 'newsblenda_notifications_settings',
    ];

    /**
     * In-memory cache: section → array of settings.
     *
     * @var array<string,array<string,mixed>>
     */
    private static array $cache = [];

    // -------------------------------------------------------------------------
    // Default values
    // -------------------------------------------------------------------------

    /**
     * Default values for every settings section.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function defaults(): array
    {
        return [

            'general' => [
                'site_name'                  => get_bloginfo('name'),
                'site_logo'                  => '',
                'primary_color'              => '#2271b1',
                'secondary_color'            => '#135e96',
                'site_url'                   => home_url(),
                'platform_description'       => '',
                'author_profile_visibility'  => 'public',
                'date_format'                => get_option('date_format', 'F j, Y'),
            ],

            'registration' => [
                'enable_registration'           => 1,
                'auto_assign_role'              => 'nbe_author',
                'email_verification_required'   => 1,
                'verification_expiration'       => 48,
                'duplicate_username_detection'  => 1,
                'duplicate_email_detection'     => 1,
                'password_min_length'           => 12,
                'password_require_uppercase'    => 1,
                'password_require_numbers'      => 1,
                'password_require_special'      => 1,
                'captcha_enabled'               => 0,
                'honeypot_enabled'              => 1,
                'rate_limiting_enabled'         => 1,
                'rate_limit_threshold'          => 5,
            ],

            'security' => [
                'password_reset_expiration' => 24,
                'login_failure_limit'       => 5,
                'lockout_duration'          => 15,
                'rate_limit_logins'         => 1,
                'session_timeout'           => 60,
                'force_https'               => 0,
                'cookie_secure'             => 0,
                'cookie_httponly'           => 1,
                'enable_2fa'                => 0,
                'security_headers_enabled'  => 1,
            ],

            'workflow' => [
                'auto_publish_approved'       => 0,
                'auto_publish_delay'          => 0,
                'allow_author_delete'         => 0,
                'allow_author_edit_published' => 0,
                'revision_request_minimum'    => 0,
                'max_revision_requests'       => 3,
                'require_article_category'    => 1,
                'require_article_tags'        => 0,
                'require_featured_image'      => 0,
                'min_word_count'              => 500,
                'max_word_count'              => 0,
                'editor_assignment'           => 'manual',
                'enable_scheduling'           => 1,
            ],

            'email' => [
                'from_name'                       => get_bloginfo('name'),
                'from_address'                    => get_option('admin_email', ''),
                'smtp_enabled'                    => 0,
                'smtp_host'                       => '',
                'smtp_port'                       => 587,
                'smtp_username'                   => '',
                'smtp_password'                   => '',
                'smtp_encryption'                 => 'tls',
                'test_email_address'              => get_option('admin_email', ''),
                'header_logo'                     => '',
                'footer_text'                     => __('Thank you for using Newsblenda.', 'newsblenda-accounts'),
                'brand_color'                     => '#2271b1',
                'notify_new_article'              => 1,
                'notify_approval'                 => 1,
                'notify_rejection'                => 1,
                'notify_revision_request'         => 1,
                'notify_payout'                   => 1,
                'notify_welcome'                  => 1,
                'notify_verification'             => 1,
                'notify_password_reset'           => 1,
            ],

            'earnings' => [
                'enable_earnings'         => 1,
                'earnings_model'          => 'per-view',
                'price_per_view'          => 0.001,
                'fixed_rate_per_article'  => 10.00,
                'min_payout_amount'       => 50.00,
                'payout_frequency'        => 'monthly',
                'payout_methods_bank'     => 1,
                'payout_methods_paypal'   => 1,
                'payout_methods_stripe'   => 0,
                'currency'                => 'USD',
                'tax_rate'                => 0.00,
                'auto_payout'             => 0,
                'payout_pending_period'   => 15,
                'top_articles_threshold'  => 1000,
            ],

            'seo' => [
                'seo_title_template'        => '%title% — %author% | %site%',
                'meta_description_template' => '',
                'open_graph_enabled'        => 1,
                'twitter_card_enabled'      => 1,
                'canonical_urls_enabled'    => 1,
                'xml_sitemap_enabled'       => 0,
                'schema_markup_enabled'     => 1,
                'author_schema'             => 1,
                'org_schema_enabled'        => 0,
                'org_name'                  => get_bloginfo('name'),
                'org_logo'                  => '',
                'social_facebook'           => '',
                'social_twitter'            => '',
                'social_linkedin'           => '',
            ],

            'notifications' => [
                'dashboard_notifications_enabled' => 1,
                'email_notifications_enabled'     => 1,
                'notify_article_submissions'      => 1,
                'notify_article_approvals'        => 1,
                'notify_article_rejections'       => 1,
                'notify_revision_requests'        => 1,
                'notify_payouts'                  => 1,
                'notify_system_alerts'            => 1,
                'email_frequency'                 => 'immediate',
                'admin_notification_email'        => get_option('admin_email', ''),
                'editor_notification_email'       => '',
                'notification_badge_enabled'      => 1,
                'notification_retention'          => 30,
            ],

        ];
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Get a single setting value.
     *
     * @param string $section  Section slug, e.g. "general".
     * @param string $key      Setting key within the section.
     * @param mixed  $default  Fallback value when key is absent.
     * @return mixed
     */
    public static function get(string $section, string $key, $default = null)
    {
        $all = self::get_all($section);

        return $all[$key] ?? $default;
    }

    /**
     * Get all settings for a section, merged with defaults.
     *
     * @param string $section Section slug.
     * @return array<string,mixed>
     */
    public static function get_all(string $section): array
    {
        if (isset(self::$cache[$section])) {
            return self::$cache[$section];
        }

        $option  = self::OPTION_KEYS[$section] ?? '';
        $stored  = $option ? (array) get_option($option, []) : [];
        $section_defaults = self::defaults()[$section] ?? [];

        self::$cache[$section] = wp_parse_args($stored, $section_defaults);

        return self::$cache[$section];
    }

    /**
     * Update a single setting value.
     *
     * @param string $section  Section slug.
     * @param string $key      Setting key.
     * @param mixed  $value    New value.
     * @return bool
     */
    public static function update(string $section, string $key, $value): bool
    {
        $all       = self::get_all($section);
        $all[$key] = $value;

        return self::save_all($section, $all);
    }

    /**
     * Save a full settings array for a section.
     *
     * @param string               $section  Section slug.
     * @param array<string,mixed>  $values   Full settings array.
     * @return bool
     */
    public static function save_all(string $section, array $values): bool
    {
        $option = self::OPTION_KEYS[$section] ?? '';

        if (empty($option)) {
            return false;
        }

        unset(self::$cache[$section]);

        return update_option($option, $values);
    }

    /**
     * Reset a section to its defaults.
     *
     * @param string $section Section slug, or "all" to reset every section.
     * @return bool
     */
    public static function reset(string $section): bool
    {
        if ($section === 'all') {
            $success = true;

            foreach (array_keys(self::OPTION_KEYS) as $s) {
                if (! self::reset($s)) {
                    $success = false;
                }
            }

            return $success;
        }

        $option = self::OPTION_KEYS[$section] ?? '';

        if (empty($option)) {
            return false;
        }

        unset(self::$cache[$section]);

        $section_defaults = self::defaults()[$section] ?? [];

        return update_option($option, $section_defaults);
    }

    /**
     * Initialise all settings on activation (only sets defaults if not yet saved).
     */
    public static function initialize(): void
    {
        $defaults = self::defaults();

        foreach (self::OPTION_KEYS as $section => $option) {

            if (get_option($option) === false) {
                add_option($option, $defaults[$section] ?? [], '', 'yes');
            }
        }

        if (get_option(self::SCHEMA_VERSION_OPTION) === false) {
            add_option(
                self::SCHEMA_VERSION_OPTION,
                self::SCHEMA_VERSION
            );
        }
    }

    /**
     * Flush the in-memory cache for one or all sections.
     *
     * @param string|null $section  Section slug, or null to flush all.
     */
    public static function flush_cache(?string $section = null): void
    {
        if ($section !== null) {
            unset(self::$cache[$section]);
        } else {
            self::$cache = [];
        }
    }

    /**
     * Backward-compatible helper: read a key from the legacy
     * `nb_accounts_settings` flat option, then fall through to the new
     * section-based storage.
     *
     * @param string $key     Legacy setting key.
     * @param mixed  $default Fallback.
     * @return mixed
     */
    public static function legacy_get(string $key, $default = null)
    {
        static $map = [
            // Registration section
            'allow_author_registration'     => ['registration', 'enable_registration'],
            'allow_subscriber_registration' => ['registration', 'enable_registration'],
            'require_email_verification'    => ['registration', 'email_verification_required'],
            'require_admin_approval'        => ['registration', 'email_verification_required'],
            'password_min_length'           => ['registration', 'password_min_length'],
            'require_strong_passwords'      => ['registration', 'password_require_uppercase'],
            // Security section
            'max_login_attempts'            => ['security', 'login_failure_limit'],
            'lockout_minutes'               => ['security', 'lockout_duration'],
            // Email section
            'sender_name'                   => ['email', 'from_name'],
            'sender_email'                  => ['email', 'from_address'],
            'email_footer'                  => ['email', 'footer_text'],
            // Notifications section
            'enable_notifications'          => ['notifications', 'dashboard_notifications_enabled'],
            'email_notifications'           => ['notifications', 'email_notifications_enabled'],
        ];

        if (isset($map[$key])) {
            [$section, $new_key] = $map[$key];

            return self::get($section, $new_key, $default);
        }

        return $default;
    }
}
