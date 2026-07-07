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

try {
    /** @var Database $db */
    $db = $container->get('db');

    /** @var Authorization $authorization */
    $authorization = $container->get('authorization');

    \fwrite(STDOUT, 'Starting migrations...' . PHP_EOL);

    $authorization->skip(function () use ($db): void {
        Setup::run($db);

        $migrator = new Migrator(MigrationRegistry::all());
        $migrator->run($db);
    });

    \fwrite(STDOUT, 'Migration completed.' . PHP_EOL);
    exit(0);
} catch (\Throwable $error) {
    \fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . PHP_EOL);

    if (System::getEnv('_APP_ENV', Http::MODE_TYPE_PRODUCTION) !== Http::MODE_TYPE_PRODUCTION) {
        \fwrite(STDERR, $error->getTraceAsString() . PHP_EOL);
    }

    exit(1);
}
