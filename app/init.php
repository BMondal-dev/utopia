<?php

/**
 * Init
 *
 * Bootstraps configuration, registers, and DI resources for HTTP entry points.
 */

use Utopia\System\System;

if (\file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

\ini_set('memory_limit', '256M');
\ini_set('display_errors', '1');
\ini_set('display_startup_errors', '1');
\error_reporting(E_ALL);

require_once __DIR__ . '/init/constants.php';
require_once __DIR__ . '/init/configs.php';
require_once __DIR__ . '/init/models.php';
require_once __DIR__ . '/init/registers.php';
require_once __DIR__ . '/init/resources.php';
