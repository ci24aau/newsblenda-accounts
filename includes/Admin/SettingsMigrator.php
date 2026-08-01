<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Admin;

defined('ABSPATH') || exit;

/**
 * Settings migrator.
 *
 * Handles safe migrations of stored settings when the plugin is updated.
 * Runs on the `nb_accounts_upgraded` action and on explicit calls from the
 * Activator. Old values are preserved; new keys receive their defaults.
 */
class SettingsMigrator
{
    /**
     * Register the migration hook.
     */
    public function __construct()
    {
        add_action(
            'nb_accounts_upgraded',
            [$this, 'run']
        );
    }

    /**
     * Execute all pending migrations.
     */
    public function run(): void
    {
        $current_schema = (string) get_option(
            SettingsManager::SCHEMA_VERSION_OPTION,
            '0.0.0'
        );

        if (version_compare($current_schema, '1.0.0', '<')) {
            $this->migrate_to_1_0_0();
        }

        update_option(
            SettingsManager::SCHEMA_VERSION_OPTION,
            SettingsManager::SCHEMA_VERSION
        );

        // Flush after migration.
        SettingsManager::flush_cache();
    }

    // -------------------------------------------------------------------------
    // Migrations
    // -------------------------------------------------------------------------

    /**
     * Migrate legacy `nb_accounts_settings` flat array → per-section options.
     *
     * Values already saved in the new option keys are preserved.
     */
    private function migrate_to_1_0_0(): void
    {
        $legacy = (array) get_option('nb_accounts_settings', []);

        // -----------------------------------------------------------------
        // Registration
        // -----------------------------------------------------------------

        $this->migrate_section(
            'registration',
            [
                'enable_registration'          => $legacy['allow_author_registration']   ?? null,
                'email_verification_required'  => $legacy['require_email_verification']  ?? null,
                'password_min_length'          => $legacy['password_min_length']          ?? null,
                'password_require_uppercase'   => $legacy['require_strong_passwords']     ?? null,
                'password_require_numbers'     => $legacy['require_strong_passwords']     ?? null,
                'password_require_special'     => $legacy['require_strong_passwords']     ?? null,
            ]
        );

        // -----------------------------------------------------------------
        // Security
        // -----------------------------------------------------------------

        $this->migrate_section(
            'security',
            [
                'login_failure_limit' => $legacy['max_login_attempts'] ?? null,
                'lockout_duration'    => $legacy['lockout_minutes']    ?? null,
            ]
        );

        // -----------------------------------------------------------------
        // Email
        // -----------------------------------------------------------------

        $this->migrate_section(
            'email',
            [
                'from_name'    => $legacy['sender_name']  ?? null,
                'from_address' => $legacy['sender_email'] ?? null,
                'footer_text'  => $legacy['email_footer'] ?? null,
            ]
        );

        // -----------------------------------------------------------------
        // Notifications
        // -----------------------------------------------------------------

        $this->migrate_section(
            'notifications',
            [
                'dashboard_notifications_enabled' => $legacy['enable_notifications']  ?? null,
                'email_notifications_enabled'     => $legacy['email_notifications']   ?? null,
            ]
        );

        // Initialize every section that has not yet been created.
        SettingsManager::initialize();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Merge legacy values into a settings section, skipping null or already-
     * present values.
     *
     * @param string                    $section Section slug.
     * @param array<string,mixed|null>  $map     Key → legacy value (null = skip).
     */
    private function migrate_section(string $section, array $map): void
    {
        $option = SettingsManager::OPTION_KEYS[$section] ?? '';

        if (empty($option)) {
            return;
        }

        // If the new option doesn't exist yet, start from defaults.
        $existing = get_option($option);

        if ($existing === false) {
            $existing = SettingsManager::defaults()[$section] ?? [];
        } else {
            $existing = (array) $existing;
        }

        $changed = false;

        foreach ($map as $new_key => $legacy_value) {

            // Skip if the new key is already explicitly set.
            if (array_key_exists($new_key, $existing)) {
                continue;
            }

            // Skip null / missing legacy values.
            if ($legacy_value === null) {
                continue;
            }

            $existing[$new_key] = $legacy_value;
            $changed             = true;
        }

        if ($changed) {
            update_option($option, $existing);
        }
    }
}
