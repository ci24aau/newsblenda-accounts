<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Migrations;

defined('ABSPATH') || exit;

final class Migrator
{
    private const OPTION_NAME = 'nb_accounts_migration_version';

    /**
     * Run all pending migrations.
     */
    public static function run_migrations(): void
    {
        $current = (int) get_option(self::OPTION_NAME, 0);

        $migrations = [
            1 => Migration001AddIndexes::class,
        ];

        foreach ($migrations as $version => $migration) {
            if ($version <= $current || ! method_exists($migration, 'run')) {
                continue;
            }

            $migration::run();
            update_option(self::OPTION_NAME, $version, false);
        }
    }
}
