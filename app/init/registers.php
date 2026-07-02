<?php

use Utopia\Http\Http;
use Utopia\Registry\Registry;
use Utopia\System\System;

global $register;

$register = new Registry();

Http::setMode(System::getEnv('_APP_ENV', Http::MODE_TYPE_PRODUCTION));
