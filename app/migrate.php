<?php

require_once __DIR__ . '/init.php';

use Clarus\Database\MigrationRegistry;
use Clarus\Database\Migrator;
use Clarus\Database\Setup;
use Utopia\Database\Database;
use Utopia\Database\Validator\Authorization;
use Utopia\Http\Http;
use Utopia\System\System;

global $container;

$command = $argv[1] ?? 'run';

try {
    /** @var Database $db */
    $db = $container->get('db');

    /** @var Authorization $authorization */
    $authorization = $container->get('authorization');

    $authorization->skip(function () use ($db, $command): void {
        Setup::run($db);

        $migrator = new Migrator(MigrationRegistry::all());

        switch ($command) {
            case 'run':
                \fwrite(STDOUT, 'Starting migrations...' . PHP_EOL);
                $migrator->run($db);
                \fwrite(STDOUT, 'Migration completed.' . PHP_EOL);
                break;

            case 'status':
                $migrator->status($db);
                break;

            default:
                throw new \InvalidArgumentException("Unknown migration command '{$command}'. Supported commands: run, status.");
        }
    });
    exit(0);
} catch (\Throwable $error) {
    \fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . PHP_EOL);

    if (System::getEnv('_APP_ENV', Http::MODE_TYPE_PRODUCTION) !== Http::MODE_TYPE_PRODUCTION) {
        \fwrite(STDERR, $error->getTraceAsString() . PHP_EOL);
    }

    exit(1);
}
