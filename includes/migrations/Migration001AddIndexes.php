<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Migrations;

use Newsblenda\Accounts\Database\Database;

defined('ABSPATH') || exit;

final class Migration001AddIndexes
{
    /**
     * Apply strategic Phase 6 indexes.
     */
    public static function run(): void
    {
        global $wpdb;

        self::add_index($wpdb->posts, 'idx_author_status', '(post_author, post_status)');
        self::add_index($wpdb->posts, 'idx_type_date', '(post_type, post_date)');
        self::add_index($wpdb->postmeta, 'idx_meta_key_value', '(meta_key(20), meta_value(20))');

        self::add_index(Database::table('notifications'), 'idx_user_read', '(user_id, is_read)');
        self::add_index(Database::table('notifications'), 'idx_created', '(created_at)');
        self::add_index(Database::table('workflow_history'), 'idx_post_status', '(post_id, current_status)');
        self::add_index(Database::table('workflow_history'), 'idx_author', '(author_id)');
        self::add_index(Database::table('password_reset_tokens'), 'idx_email_expires', '(email, expires_at)');

        $verification_tables = [
            $wpdb->prefix . 'newsblenda_verification_tokens',
            Database::table('email_tokens'),
        ];

        foreach ($verification_tables as $table) {
            if (! self::table_exists($table)) {
                continue;
            }

            if (self::column_exists($table, 'email')) {
                self::add_index($table, 'idx_email_expires', '(email, expires_at)');
                continue;
            }

            if (self::column_exists($table, 'user_id')) {
                self::add_index($table, 'idx_user_expires', '(user_id, expires_at)');
            }
        }
    }

    private static function add_index(
        string $table,
        string $index_name,
        string $definition
    ): void {
        global $wpdb;

        $table      = self::identifier($table);
        $index_name = self::identifier($index_name);

        if ($table === '' || $index_name === '' || ! self::table_exists($table) || self::index_exists($table, $index_name)) {
            return;
        }

        $wpdb->query(
            "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` {$definition}"
        );
    }

    private static function table_exists(
        string $table
    ): bool {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        ) === $table;
    }

    private static function index_exists(
        string $table,
        string $index_name
    ): bool {
        global $wpdb;

        return ! empty(
            $wpdb->get_results(
                $wpdb->prepare(
                    "SHOW INDEX FROM `{$table}` WHERE Key_name = %s",
                    $index_name
                )
            )
        );
    }

    private static function column_exists(
        string $table,
        string $column_name
    ): bool {
        global $wpdb;

        return ! empty(
            $wpdb->get_results(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM `{$table}` LIKE %s",
                    $column_name
                )
            )
        );
    }

    private static function identifier(
        string $value
    ): string {
        return preg_replace('/[^A-Za-z0-9_]/', '', $value) ?: '';
    }
}
