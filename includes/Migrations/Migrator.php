<?php

declare(strict_types=1);

namespace Newsblenda\Accounts\Migrations;

defined('ABSPATH') || exit;

final class Migrator
{
    public static function run_migrations(): void
    {
        Migration001::run();
    }
}
