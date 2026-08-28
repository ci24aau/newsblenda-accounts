<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Database;

defined('ABSPATH') || exit;

/**
 * Runs database migrations in order, tracking the applied version in options.
 */
class Migrator
{
    /**
     * Option key that stores the current database schema version.
     */
    const VERSION_OPTION = 'nb_accounts_db_version';

    /**
     * Run all pending migrations.
     */
    public static function run_migrations(): void
    {
        $db_version = (string) get_option(self::VERSION_OPTION, '0');

        if (version_compare($db_version, '001', '<')) {
            $migration = new \Newsblenda\Accounts\Migrations\Migration001AddIndexes();
            $migration->up();
        }
    }
}
