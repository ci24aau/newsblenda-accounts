<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Database;

defined('ABSPATH') || exit;

class Database
{
    public const VERSION = '1.3.0';

    public function __construct()
    {
        add_action('admin_init', [$this, 'maybe_upgrade']);
    }

    public function maybe_upgrade(): void
    {
        if (self::needs_upgrade()) {
            self::install();
        }
    }

    public static function install(): void
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $notifications = $wpdb->prefix . 'nb_notifications';
        $activity = $wpdb->prefix . 'nb_activity';
        $tokens = $wpdb->prefix . 'nb_email_tokens';
        $password_tokens = $wpdb->prefix . 'nb_password_reset_tokens';
        $login_log = $wpdb->prefix . 'nb_login_log';
        $earnings = $wpdb->prefix . 'nb_earnings';
        $payouts = $wpdb->prefix . 'nb_payouts';
        $article_revisions = $wpdb->prefix . 'nb_article_revisions';
        $article_feedback = $wpdb->prefix . 'nb_article_feedback';
        $workflow_log = $wpdb->prefix . 'nb_workflow_log';

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
                KEY status (status)
            ) {$charset};"
        );

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

        $wpdb->query(
            "CREATE TABLE {$tokens} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY token (token),
                KEY user_id (user_id)
            ) {$charset};"
        );

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
                KEY expires_at (expires_at)
            ) {$charset};"
        );

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

        $wpdb->query(
            "CREATE TABLE {$article_revisions} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                article_id BIGINT UNSIGNED NOT NULL,
                revision_number INT UNSIGNED NOT NULL DEFAULT 1,
                editor_id BIGINT UNSIGNED NOT NULL,
                feedback LONGTEXT NOT NULL,
                severity VARCHAR(20) DEFAULT 'minor',
                requested_at DATETIME NOT NULL,
                resubmitted_at DATETIME NULL,
                status VARCHAR(20) DEFAULT 'pending',
                PRIMARY KEY (id),
                KEY article_id (article_id),
                KEY editor_id (editor_id),
                KEY status (status)
            ) {$charset};"
        );

        $wpdb->query(
            "CREATE TABLE {$article_feedback} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                article_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                feedback_type VARCHAR(30) NOT NULL,
                feedback_text LONGTEXT NOT NULL,
                severity VARCHAR(20) DEFAULT '',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY article_id (article_id),
                KEY user_id (user_id),
                KEY feedback_type (feedback_type)
            ) {$charset};"
        );

        $wpdb->query(
            "CREATE TABLE {$workflow_log} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                article_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                action VARCHAR(50) NOT NULL,
                previous_status VARCHAR(30) NOT NULL DEFAULT '',
                new_status VARCHAR(30) NOT NULL,
                metadata LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY article_id (article_id),
                KEY user_id (user_id),
                KEY action (action)
            ) {$charset};"
        );

        update_option('nb_accounts_db_version', self::VERSION);

        do_action('nb_accounts_database_installed');
    }

    public static function table(string $table): string
    {
        global $wpdb;

        return $wpdb->prefix . 'nb_' . $table;
    }

    public static function exists(string $table): bool
    {
        global $wpdb;

        $name = self::table($table);

        return $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $name)
        ) === $name;
    }

    public static function version(): string
    {
        return (string) get_option('nb_accounts_db_version', '0');
    }

    public static function needs_upgrade(): bool
    {
        return version_compare(self::version(), self::VERSION, '<');
    }

    public static function upgrade(): void
    {
        if (!self::needs_upgrade()) {
            return;
        }

        self::install();
    }

    public static function repair(): void
    {
        $required = [
            'notifications',
            'activity',
            'email_tokens',
            'password_reset_tokens',
            'login_log',
            'earnings',
            'payouts',
            'article_revisions',
            'article_feedback',
            'workflow_log',
        ];

        foreach ($required as $table) {
            if (!self::exists($table)) {
                self::install();
                break;
            }
        }
    }

    public static function tables(): array
    {
        return [
            self::table('notifications'),
            self::table('activity'),
            self::table('email_tokens'),
            self::table('password_reset_tokens'),
            self::table('login_log'),
            self::table('earnings'),
            self::table('payouts'),
            self::table('article_revisions'),
            self::table('article_feedback'),
            self::table('workflow_log'),
        ];
    }

    public static function healthy(): bool
    {
        global $wpdb;

        foreach (self::tables() as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
                return false;
            }
        }

        return true;
    }

    public static function uninstall(): void
    {
        global $wpdb;

        foreach (self::tables() as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('nb_accounts_db_version');
    }
}
