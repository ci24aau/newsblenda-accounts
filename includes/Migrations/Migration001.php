<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Migrations;

use Newsblenda\Accounts\Database\Database;

defined('ABSPATH') || exit;

class Migration001
{
    public static function run(): void
    {
        global $wpdb;

        $workflow_table  = Database::table('workflow_history');
        $notifications   = Database::table('notifications');
        $password_tokens = Database::table('password_reset_tokens');
        $earnings_table  = Database::table('earnings');

        $migrations = [
            [$wpdb->posts, 'idx_author_status', "ALTER TABLE {$wpdb->posts} ADD INDEX idx_author_status (post_author, post_status)"],
            [$wpdb->posts, 'idx_type_date', "ALTER TABLE {$wpdb->posts} ADD INDEX idx_type_date (post_type, post_date)"],
            [$wpdb->postmeta, 'idx_meta_key_value', "ALTER TABLE {$wpdb->postmeta} ADD INDEX idx_meta_key_value (meta_key(32), meta_value(32))"],
            [$notifications, 'idx_user_read', "ALTER TABLE {$notifications} ADD INDEX idx_user_read (user_id, is_read)"],
            [$notifications, 'idx_created', "ALTER TABLE {$notifications} ADD INDEX idx_created (created_at)"],
            [$workflow_table, 'idx_post_status', "ALTER TABLE {$workflow_table} ADD INDEX idx_post_status (post_id, current_status)"],
            [$workflow_table, 'idx_author', "ALTER TABLE {$workflow_table} ADD INDEX idx_author (author_id)"],
            [$password_tokens, 'idx_email_expires', "ALTER TABLE {$password_tokens} ADD INDEX idx_email_expires (email, expires_at)"],
            [$earnings_table, 'idx_user_status_calculated', "ALTER TABLE {$earnings_table} ADD INDEX idx_user_status_calculated (user_id, status, calculated_at)"],
        ];

        foreach ($migrations as [$table, $index, $sql]) {
            self::add_index_if_missing((string) $table, (string) $index, (string) $sql);
        }

        update_option('nb_accounts_db_version', '1.4.0');
        update_option('nb_accounts_phase6_migration', '001');
    }

    private static function add_index_if_missing(
        string $table,
        string $index,
        string $sql
    ): void {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW INDEX FROM {$table} WHERE Key_name = %s",
                $index
            )
        );

        if ($exists === $index) {
            return;
        }

        $wpdb->query($sql);
    }
}
