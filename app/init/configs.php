<?php

use Utopia\Config\Adapters\PHP;
use Utopia\Config\Config;

$configAdapter = new PHP();

Config::load('errors', __DIR__ . '/../config/errors.php', $configAdapter);
Config::load('collections', __DIR__ . '/../config/collections.php', $configAdapter);
