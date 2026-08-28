<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Migrations;

defined('ABSPATH') || exit;

/**
 * Migration 001 – Add performance indexes.
 *
 * Uses IF NOT EXISTS guards where the DB engine supports them so that
 * re-running the migration is safe.
 */
class Migration001AddIndexes
{
    /**
     * Apply the migration.
     */
    public function up(): void
    {
        global $wpdb;

        $queries = [
            // wp_posts – common filters used by dashboards and listings.
            "ALTER TABLE {$wpdb->posts}
                ADD INDEX IF NOT EXISTS idx_nb_author_status (post_author, post_status)",

            "ALTER TABLE {$wpdb->posts}
                ADD INDEX IF NOT EXISTS idx_nb_type_date (post_type, post_date)",

            // wp_postmeta – speeds up meta_key lookups with value filtering.
            "ALTER TABLE {$wpdb->postmeta}
                ADD INDEX IF NOT EXISTS idx_nb_meta_key_value (meta_key(20), meta_value(20))",

            // Notifications table.
            "ALTER TABLE {$wpdb->prefix}newsblenda_notifications
                ADD INDEX IF NOT EXISTS idx_nb_user_read (user_id, is_read)",

            "ALTER TABLE {$wpdb->prefix}newsblenda_notifications
                ADD INDEX IF NOT EXISTS idx_nb_created (created_at)",

            // Workflow history table.
            "ALTER TABLE {$wpdb->prefix}newsblenda_workflow_history
                ADD INDEX IF NOT EXISTS idx_nb_post_status (post_id, current_status)",

            "ALTER TABLE {$wpdb->prefix}newsblenda_workflow_history
                ADD INDEX IF NOT EXISTS idx_nb_author (author_id)",

            // Token tables – used for expiry look-ups.
            "ALTER TABLE {$wpdb->prefix}newsblenda_password_reset_tokens
                ADD INDEX IF NOT EXISTS idx_nb_email_expires (email, expires_at)",

            "ALTER TABLE {$wpdb->prefix}newsblenda_verification_tokens
                ADD INDEX IF NOT EXISTS idx_nb_email_expires (email, expires_at)",
        ];

        // Suppress errors for tables that do not exist yet (they will be
        // created by the main Database installer on first activation).
        $suppress = $wpdb->suppress_errors(true);

        foreach ($queries as $query) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query($query);
        }

        $wpdb->suppress_errors($suppress);

        update_option(\Newsblenda\Accounts\Database\Migrator::VERSION_OPTION, '001');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $drop_queries = [
            "ALTER TABLE {$wpdb->posts} DROP INDEX IF EXISTS idx_nb_author_status",
            "ALTER TABLE {$wpdb->posts} DROP INDEX IF EXISTS idx_nb_type_date",
            "ALTER TABLE {$wpdb->postmeta} DROP INDEX IF EXISTS idx_nb_meta_key_value",
            "ALTER TABLE {$wpdb->prefix}newsblenda_notifications DROP INDEX IF EXISTS idx_nb_user_read",
            "ALTER TABLE {$wpdb->prefix}newsblenda_notifications DROP INDEX IF EXISTS idx_nb_created",
            "ALTER TABLE {$wpdb->prefix}newsblenda_workflow_history DROP INDEX IF EXISTS idx_nb_post_status",
            "ALTER TABLE {$wpdb->prefix}newsblenda_workflow_history DROP INDEX IF EXISTS idx_nb_author",
            "ALTER TABLE {$wpdb->prefix}newsblenda_password_reset_tokens DROP INDEX IF EXISTS idx_nb_email_expires",
            "ALTER TABLE {$wpdb->prefix}newsblenda_verification_tokens DROP INDEX IF EXISTS idx_nb_email_expires",
        ];

        $suppress = $wpdb->suppress_errors(true);

        foreach ($drop_queries as $query) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query($query);
        }

        $wpdb->suppress_errors($suppress);

        delete_option(\Newsblenda\Accounts\Database\Migrator::VERSION_OPTION);
    }
}
