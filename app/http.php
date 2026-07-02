<?php

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/controllers/general.php';

use Clarus\Utopia\Server;
use Utopia\Http\Http;
use Utopia\System\System;

global $container;

$server = new Server(
    host: '0.0.0.0',
    port: System::getEnv('PORT', '8080'),
    resources: $container,
);

$http = new Http($server, 'UTC');
$http->start();
