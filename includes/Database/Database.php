<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Database;

defined('ABSPATH') || exit;

class Database
{
    /**
     * Database version.
     */
    public const VERSION = '1.3.0';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'maybe_upgrade']);
    }

    /**
     * Run database upgrades for administrators.
     */
    public function maybe_upgrade(): void
    {
        if (self::needs_upgrade()) {
            self::install();
        }
    }

    /**
     * Install database tables.
     */
    public static function install(): void
    {
        global $wpdb;


        $charset = $wpdb->get_charset_collate();

        /*
        |--------------------------------------------------------------------------
        | Workflow History
        |--------------------------------------------------------------------------
        */

        $workflow_history = $wpdb->prefix . 'nb_workflow_history';

        $wpdb->query(

            "CREATE TABLE IF NOT EXISTS {$workflow_history} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                post_id BIGINT UNSIGNED NOT NULL,

                author_id BIGINT UNSIGNED NOT NULL,

                editor_id BIGINT UNSIGNED NULL,

                previous_status VARCHAR(50) NOT NULL,

                current_status VARCHAR(50) NOT NULL,

                editor_comments LONGTEXT NULL,

                revision_requests LONGTEXT NULL,

                rejection_reason TEXT NULL,

                status_changed_at DATETIME NOT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY post_id (post_id),

                KEY author_id (author_id),

                KEY current_status (current_status),

                KEY idx_post_status (post_id, current_status)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        $notifications = $wpdb->prefix . 'nb_notifications';

        $wpdb->query(

            "CREATE TABLE {$notifications} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                title VARCHAR(255) NOT NULL,

                message LONGTEXT NOT NULL,

                type VARCHAR(50) NOT NULL,

                icon VARCHAR(100) DEFAULT '',

                action_url TEXT NULL,

                status VARCHAR(30) DEFAULT 'unread',

                is_read TINYINT(1) DEFAULT 0,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY is_read (is_read),

                KEY status (status),

                KEY idx_user_read (user_id, is_read),

                KEY idx_created (created_at)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Activity
        |--------------------------------------------------------------------------
        */

        $activity = $wpdb->prefix . 'nb_activity';

        $wpdb->query(

            "CREATE TABLE {$activity} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                action VARCHAR(150) NOT NULL,

                description LONGTEXT NULL,

                ip_address VARCHAR(45) NULL,

                user_agent TEXT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY action (action)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Email Verification Tokens
        |--------------------------------------------------------------------------
        */

        $tokens = $wpdb->prefix . 'nb_email_tokens';

        $wpdb->query(

            "CREATE TABLE {$tokens} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                token VARCHAR(255) NOT NULL,

                expires_at DATETIME NOT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                UNIQUE KEY token (token),

                KEY user_id (user_id),

                KEY idx_user_expires (user_id, expires_at)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Password Reset Tokens
        |--------------------------------------------------------------------------
        */

        $password_tokens = $wpdb->prefix . 'nb_password_reset_tokens';

        $wpdb->query(

            "CREATE TABLE {$password_tokens} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                email VARCHAR(190) NOT NULL,

                token_hash VARCHAR(255) NOT NULL,

                expires_at DATETIME NOT NULL,

                consumed_at DATETIME NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY email (email),

                KEY expires_at (expires_at),

                KEY idx_email_expires (email, expires_at)

            ) {$charset};"

        );
        
                /*
        |--------------------------------------------------------------------------
        | Login Log
        |--------------------------------------------------------------------------
        */

        $login_log = $wpdb->prefix . 'nb_login_log';

        $wpdb->query(

            "CREATE TABLE {$login_log} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NULL,

                username VARCHAR(190) NULL,

                email VARCHAR(190) NULL,

                ip_address VARCHAR(45) NULL,

                user_agent TEXT NULL,

                status VARCHAR(30) NOT NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY username (username),

                KEY status (status)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Earnings
        |--------------------------------------------------------------------------
        */

        $earnings = $wpdb->prefix . 'nb_earnings';

        $wpdb->query(

            "CREATE TABLE {$earnings} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                post_id BIGINT UNSIGNED NOT NULL,

                views BIGINT UNSIGNED DEFAULT 0,

                amount DECIMAL(12,2) DEFAULT 0.00,

                status VARCHAR(30) DEFAULT 'unpaid',

                calculated_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY post_id (post_id),

                KEY status (status)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Payouts
        |--------------------------------------------------------------------------
        */

        $payouts = $wpdb->prefix . 'nb_payouts';

        $wpdb->query(

            "CREATE TABLE {$payouts} (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                user_id BIGINT UNSIGNED NOT NULL,

                amount DECIMAL(12,2) NOT NULL,

                payment_method VARCHAR(100) DEFAULT '',

                reference VARCHAR(190) DEFAULT '',

                status VARCHAR(30) DEFAULT 'pending',

                paid_at DATETIME NULL,

                created_at DATETIME NOT NULL,

                PRIMARY KEY (id),

                KEY user_id (user_id),

                KEY status (status)

            ) {$charset};"

        );

        /*
        |--------------------------------------------------------------------------
        | Settings Version
        |--------------------------------------------------------------------------
        */

        update_option(
            'nb_accounts_db_version',
            self::VERSION
        );

        do_action(
            'nb_accounts_database_installed'
        );
    }
    
        /**
     * Get a plugin table name.
     */
    public static function table(
        string $table
    ): string {

        global $wpdb;

        return $wpdb->prefix . 'nb_' . $table;

    }

    /**
     * Determine whether a table exists.
     */
    public static function exists(
        string $table
    ): bool {

        global $wpdb;

        $name = self::table($table);

        return $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $name
            )
        ) === $name;

    }

    /**
     * Current database version.
     */
    public static function version(): string
    {
        return (string) get_option(
            'nb_accounts_db_version',
            '0'
        );
    }

    /**
     * Database upgrade required?
     */
    public static function needs_upgrade(): bool
    {
        return version_compare(
            self::version(),
            self::VERSION,
            '<'
        );
    }

    /**
     * Upgrade database.
     */
    public static function upgrade(): void
    {
        if (! self::needs_upgrade()) {
            return;
        }

        self::install();
    }

    /**
     * Repair missing tables.
     */
    public static function repair(): void
    {
        $required = [

            'workflow_history',

            'notifications',

            'activity',

            'email_tokens',
            'password_reset_tokens',

            'login_log',

            'earnings',

            'payouts',

        ];

        foreach ($required as $table) {

            if (! self::exists($table)) {

                self::install();

                break;

            }

        }
    }

    /**
     * List plugin tables.
     */
    public static function tables(): array
    {
        return [

            self::table('workflow_history'),

            self::table('notifications'),

            self::table('activity'),

            self::table('email_tokens'),

            self::table('password_reset_tokens'),

            self::table('login_log'),

            self::table('earnings'),

            self::table('payouts'),

        ];
    }

    /**
     * Check database health.
     */
    public static function healthy(): bool
    {
        foreach (self::tables() as $table) {

            global $wpdb;

            if (
                $wpdb->get_var(
                    $wpdb->prepare(
                        'SHOW TABLES LIKE %s',
                        $table
                    )
                ) !== $table
            ) {
                return false;
            }

        }

        return true;
    }

    /**
     * Remove all plugin tables.
     *
     * Intended for uninstall only.
     */
    public static function uninstall(): void
    {
        global $wpdb;

        foreach (self::tables() as $table) {

            $wpdb->query(
                "DROP TABLE IF EXISTS {$table}"
            );

        }

        delete_option(
            'nb_accounts_db_version'
        );
    }
}