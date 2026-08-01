<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

/**
 * Settings validator and sanitizer.
 *
 * Provides sanitization callbacks for each settings section, used by the
 * WordPress Settings API `register_setting()` call. Also exposes standalone
 * field-level helpers used in the settings page renderer.
 */
class SettingsValidator
{
    // -------------------------------------------------------------------------
    // Section sanitization callbacks
    // -------------------------------------------------------------------------

    /**
     * Sanitize General settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_general($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['general'];

        return [
            'site_name'                 => self::text(
                $input['site_name'] ?? $defaults['site_name']
            ),
            'site_logo'                 => self::url(
                $input['site_logo'] ?? $defaults['site_logo']
            ),
            'primary_color'             => self::hex_color(
                $input['primary_color'] ?? $defaults['primary_color']
            ),
            'secondary_color'           => self::hex_color(
                $input['secondary_color'] ?? $defaults['secondary_color']
            ),
            'site_url'                  => self::url(
                $input['site_url'] ?? $defaults['site_url']
            ),
            'platform_description'      => sanitize_textarea_field(
                $input['platform_description'] ?? ''
            ),
            'author_profile_visibility' => self::select(
                $input['author_profile_visibility'] ?? $defaults['author_profile_visibility'],
                ['public', 'private'],
                $defaults['author_profile_visibility']
            ),
            'date_format'               => self::text(
                $input['date_format'] ?? $defaults['date_format']
            ),
        ];
    }

    /**
     * Sanitize Registration settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_registration($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['registration'];

        return [
            'enable_registration'           => self::bool($input, 'enable_registration'),
            'auto_assign_role'              => self::select(
                $input['auto_assign_role'] ?? $defaults['auto_assign_role'],
                ['nbe_author', 'editor', 'author'],
                $defaults['auto_assign_role']
            ),
            'email_verification_required'   => self::bool($input, 'email_verification_required'),
            'verification_expiration'       => self::absint_min(
                $input['verification_expiration'] ?? $defaults['verification_expiration'],
                1,
                $defaults['verification_expiration']
            ),
            'duplicate_username_detection'  => self::bool($input, 'duplicate_username_detection'),
            'duplicate_email_detection'     => self::bool($input, 'duplicate_email_detection'),
            'password_min_length'           => self::absint_range(
                $input['password_min_length'] ?? $defaults['password_min_length'],
                6,
                128,
                $defaults['password_min_length']
            ),
            'password_require_uppercase'    => self::bool($input, 'password_require_uppercase'),
            'password_require_numbers'      => self::bool($input, 'password_require_numbers'),
            'password_require_special'      => self::bool($input, 'password_require_special'),
            'captcha_enabled'               => self::bool($input, 'captcha_enabled'),
            'honeypot_enabled'              => self::bool($input, 'honeypot_enabled'),
            'rate_limiting_enabled'         => self::bool($input, 'rate_limiting_enabled'),
            'rate_limit_threshold'          => self::absint_range(
                $input['rate_limit_threshold'] ?? $defaults['rate_limit_threshold'],
                1,
                1000,
                $defaults['rate_limit_threshold']
            ),
        ];
    }

    /**
     * Sanitize Security settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_security($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['security'];

        return [
            'password_reset_expiration' => self::absint_range(
                $input['password_reset_expiration'] ?? $defaults['password_reset_expiration'],
                1,
                720,
                $defaults['password_reset_expiration']
            ),
            'login_failure_limit'       => self::absint_range(
                $input['login_failure_limit'] ?? $defaults['login_failure_limit'],
                1,
                100,
                $defaults['login_failure_limit']
            ),
            'lockout_duration'          => self::absint_range(
                $input['lockout_duration'] ?? $defaults['lockout_duration'],
                1,
                10080,
                $defaults['lockout_duration']
            ),
            'rate_limit_logins'         => self::bool($input, 'rate_limit_logins'),
            'session_timeout'           => self::absint_range(
                $input['session_timeout'] ?? $defaults['session_timeout'],
                5,
                10080,
                $defaults['session_timeout']
            ),
            'force_https'               => self::bool($input, 'force_https'),
            'cookie_secure'             => self::bool($input, 'cookie_secure'),
            'cookie_httponly'           => self::bool($input, 'cookie_httponly'),
            'enable_2fa'                => self::bool($input, 'enable_2fa'),
            'security_headers_enabled'  => self::bool($input, 'security_headers_enabled'),
        ];
    }

    /**
     * Sanitize Workflow settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_workflow($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['workflow'];

        return [
            'auto_publish_approved'       => self::bool($input, 'auto_publish_approved'),
            'auto_publish_delay'          => self::absint_min(
                $input['auto_publish_delay'] ?? $defaults['auto_publish_delay'],
                0,
                $defaults['auto_publish_delay']
            ),
            'allow_author_delete'         => self::bool($input, 'allow_author_delete'),
            'allow_author_edit_published' => self::bool($input, 'allow_author_edit_published'),
            'revision_request_minimum'    => self::absint_min(
                $input['revision_request_minimum'] ?? $defaults['revision_request_minimum'],
                0,
                $defaults['revision_request_minimum']
            ),
            'max_revision_requests'       => self::absint_range(
                $input['max_revision_requests'] ?? $defaults['max_revision_requests'],
                0,
                50,
                $defaults['max_revision_requests']
            ),
            'require_article_category'    => self::bool($input, 'require_article_category'),
            'require_article_tags'        => self::bool($input, 'require_article_tags'),
            'require_featured_image'      => self::bool($input, 'require_featured_image'),
            'min_word_count'              => self::absint_min(
                $input['min_word_count'] ?? $defaults['min_word_count'],
                0,
                $defaults['min_word_count']
            ),
            'max_word_count'              => self::absint_min(
                $input['max_word_count'] ?? $defaults['max_word_count'],
                0,
                $defaults['max_word_count']
            ),
            'editor_assignment'           => self::select(
                $input['editor_assignment'] ?? $defaults['editor_assignment'],
                ['auto', 'manual'],
                $defaults['editor_assignment']
            ),
            'enable_scheduling'           => self::bool($input, 'enable_scheduling'),
        ];
    }

    /**
     * Sanitize Email settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_email($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['email'];

        $from_address = self::email_field(
            $input['from_address'] ?? $defaults['from_address'],
            $defaults['from_address']
        );

        $test_email = isset($input['test_email_address'])
            ? self::email_field($input['test_email_address'], '')
            : '';

        $smtp_password = isset($input['smtp_password'])
            ? sanitize_text_field($input['smtp_password'])
            : $defaults['smtp_password'];

        // Preserve existing password if blank is submitted.
        if ($smtp_password === '') {
            $smtp_password = SettingsManager::get('email', 'smtp_password', '');
        }

        return [
            'from_name'               => self::text(
                $input['from_name'] ?? $defaults['from_name']
            ),
            'from_address'            => $from_address,
            'smtp_enabled'            => self::bool($input, 'smtp_enabled'),
            'smtp_host'               => self::text($input['smtp_host'] ?? ''),
            'smtp_port'               => self::absint_range(
                $input['smtp_port'] ?? $defaults['smtp_port'],
                1,
                65535,
                $defaults['smtp_port']
            ),
            'smtp_username'           => self::text($input['smtp_username'] ?? ''),
            'smtp_password'           => $smtp_password,
            'smtp_encryption'         => self::select(
                $input['smtp_encryption'] ?? $defaults['smtp_encryption'],
                ['none', 'ssl', 'tls'],
                $defaults['smtp_encryption']
            ),
            'test_email_address'      => $test_email,
            'header_logo'             => self::url($input['header_logo'] ?? ''),
            'footer_text'             => sanitize_textarea_field(
                $input['footer_text'] ?? $defaults['footer_text']
            ),
            'brand_color'             => self::hex_color(
                $input['brand_color'] ?? $defaults['brand_color']
            ),
            'notify_new_article'      => self::bool($input, 'notify_new_article'),
            'notify_approval'         => self::bool($input, 'notify_approval'),
            'notify_rejection'        => self::bool($input, 'notify_rejection'),
            'notify_revision_request' => self::bool($input, 'notify_revision_request'),
            'notify_payout'           => self::bool($input, 'notify_payout'),
            'notify_welcome'          => self::bool($input, 'notify_welcome'),
            'notify_verification'     => self::bool($input, 'notify_verification'),
            'notify_password_reset'   => self::bool($input, 'notify_password_reset'),
        ];
    }

    /**
     * Sanitize Earnings settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_earnings($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['earnings'];

        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'INR', 'BRL'];
        $models     = ['per-view', 'fixed-rate', 'hybrid'];
        $freqs      = ['weekly', 'bi-weekly', 'monthly'];

        return [
            'enable_earnings'        => self::bool($input, 'enable_earnings'),
            'earnings_model'         => self::select(
                $input['earnings_model'] ?? $defaults['earnings_model'],
                $models,
                $defaults['earnings_model']
            ),
            'price_per_view'         => self::decimal_min(
                $input['price_per_view'] ?? $defaults['price_per_view'],
                0.0,
                $defaults['price_per_view']
            ),
            'fixed_rate_per_article' => self::decimal_min(
                $input['fixed_rate_per_article'] ?? $defaults['fixed_rate_per_article'],
                0.0,
                $defaults['fixed_rate_per_article']
            ),
            'min_payout_amount'      => self::decimal_min(
                $input['min_payout_amount'] ?? $defaults['min_payout_amount'],
                0.0,
                $defaults['min_payout_amount']
            ),
            'payout_frequency'       => self::select(
                $input['payout_frequency'] ?? $defaults['payout_frequency'],
                $freqs,
                $defaults['payout_frequency']
            ),
            'payout_methods_bank'    => self::bool($input, 'payout_methods_bank'),
            'payout_methods_paypal'  => self::bool($input, 'payout_methods_paypal'),
            'payout_methods_stripe'  => self::bool($input, 'payout_methods_stripe'),
            'currency'               => self::select(
                $input['currency'] ?? $defaults['currency'],
                $currencies,
                $defaults['currency']
            ),
            'tax_rate'               => self::decimal_range(
                $input['tax_rate'] ?? $defaults['tax_rate'],
                0.0,
                100.0,
                $defaults['tax_rate']
            ),
            'auto_payout'            => self::bool($input, 'auto_payout'),
            'payout_pending_period'  => self::absint_range(
                $input['payout_pending_period'] ?? $defaults['payout_pending_period'],
                0,
                90,
                $defaults['payout_pending_period']
            ),
            'top_articles_threshold' => self::absint_min(
                $input['top_articles_threshold'] ?? $defaults['top_articles_threshold'],
                1,
                $defaults['top_articles_threshold']
            ),
        ];
    }

    /**
     * Sanitize SEO settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_seo($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['seo'];

        return [
            'seo_title_template'        => self::text(
                $input['seo_title_template'] ?? $defaults['seo_title_template']
            ),
            'meta_description_template' => sanitize_textarea_field(
                $input['meta_description_template'] ?? ''
            ),
            'open_graph_enabled'        => self::bool($input, 'open_graph_enabled'),
            'twitter_card_enabled'      => self::bool($input, 'twitter_card_enabled'),
            'canonical_urls_enabled'    => self::bool($input, 'canonical_urls_enabled'),
            'xml_sitemap_enabled'       => self::bool($input, 'xml_sitemap_enabled'),
            'schema_markup_enabled'     => self::bool($input, 'schema_markup_enabled'),
            'author_schema'             => self::bool($input, 'author_schema'),
            'org_schema_enabled'        => self::bool($input, 'org_schema_enabled'),
            'org_name'                  => self::text($input['org_name'] ?? $defaults['org_name']),
            'org_logo'                  => self::url($input['org_logo'] ?? ''),
            'social_facebook'           => self::url($input['social_facebook'] ?? ''),
            'social_twitter'            => self::url($input['social_twitter'] ?? ''),
            'social_linkedin'           => self::url($input['social_linkedin'] ?? ''),
        ];
    }

    /**
     * Sanitize Notifications settings.
     *
     * @param mixed $input Raw POST data.
     * @return array<string,mixed>
     */
    public static function sanitize_notifications($input): array
    {
        $input    = is_array($input) ? $input : [];
        $defaults = SettingsManager::defaults()['notifications'];

        $admin_email = self::email_field(
            $input['admin_notification_email'] ?? $defaults['admin_notification_email'],
            $defaults['admin_notification_email']
        );

        $editor_email = isset($input['editor_notification_email'])
            ? self::email_field($input['editor_notification_email'], '')
            : '';

        return [
            'dashboard_notifications_enabled' => self::bool($input, 'dashboard_notifications_enabled'),
            'email_notifications_enabled'     => self::bool($input, 'email_notifications_enabled'),
            'notify_article_submissions'      => self::bool($input, 'notify_article_submissions'),
            'notify_article_approvals'        => self::bool($input, 'notify_article_approvals'),
            'notify_article_rejections'       => self::bool($input, 'notify_article_rejections'),
            'notify_revision_requests'        => self::bool($input, 'notify_revision_requests'),
            'notify_payouts'                  => self::bool($input, 'notify_payouts'),
            'notify_system_alerts'            => self::bool($input, 'notify_system_alerts'),
            'email_frequency'                 => self::select(
                $input['email_frequency'] ?? $defaults['email_frequency'],
                ['immediate', 'daily', 'weekly'],
                $defaults['email_frequency']
            ),
            'admin_notification_email'        => $admin_email,
            'editor_notification_email'       => $editor_email,
            'notification_badge_enabled'      => self::bool($input, 'notification_badge_enabled'),
            'notification_retention'          => self::absint_range(
                $input['notification_retention'] ?? $defaults['notification_retention'],
                1,
                365,
                $defaults['notification_retention']
            ),
        ];
    }

    // -------------------------------------------------------------------------
    // Field-level helpers
    // -------------------------------------------------------------------------

    /**
     * Sanitize a plain text field.
     */
    public static function text(string $value): string
    {
        return sanitize_text_field(wp_unslash($value));
    }

    /**
     * Sanitize a URL field.
     */
    public static function url(string $value): string
    {
        return esc_url_raw(trim($value));
    }

    /**
     * Sanitize an email address field, fallback to $default on invalid.
     */
    public static function email_field(string $value, string $default): string
    {
        $clean = sanitize_email($value);

        return is_email($clean) ? $clean : $default;
    }

    /**
     * Validate and return a CSS hex colour (#rrggbb or #rgb).
     */
    public static function hex_color(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $value)) {
            return strtolower($value);
        }

        return '';
    }

    /**
     * Cast a checkbox value to 0 or 1.
     *
     * @param array<string,mixed> $input
     */
    public static function bool(array $input, string $key): int
    {
        return empty($input[$key]) ? 0 : 1;
    }

    /**
     * Validate a select against an allowlist; return $default if not found.
     *
     * @param mixed         $value
     * @param list<string>  $allowed
     */
    public static function select($value, array $allowed, string $default): string
    {
        $value = (string) $value;

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Clamp an integer to a minimum value.
     *
     * @param mixed $value
     */
    public static function absint_min($value, int $min, int $default): int
    {
        $v = absint($value);

        if ($v < $min && $value !== '' && $value !== null) {
            return $min;
        }

        if ($value === '' || $value === null) {
            return $default;
        }

        return $v;
    }

    /**
     * Clamp an integer to a [min, max] range.
     *
     * @param mixed $value
     */
    public static function absint_range($value, int $min, int $max, int $default): int
    {
        if ($value === '' || $value === null) {
            return $default;
        }

        $v = absint($value);

        return max($min, min($max, $v));
    }

    /**
     * Clamp a decimal float to a minimum value.
     *
     * @param mixed $value
     */
    public static function decimal_min($value, float $min, float $default): float
    {
        if ($value === '' || $value === null) {
            return $default;
        }

        $v = (float) $value;

        return max($min, $v);
    }

    /**
     * Clamp a decimal float to a [min, max] range.
     *
     * @param mixed $value
     */
    public static function decimal_range($value, float $min, float $max, float $default): float
    {
        if ($value === '' || $value === null) {
            return $default;
        }

        $v = (float) $value;

        return max($min, min($max, $v));
    }
}
