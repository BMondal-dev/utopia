<?php

use Utopia\Http\Http;
use Utopia\System\System;

Http::setMode(System::getEnv('_APP_ENV', Http::MODE_TYPE_PRODUCTION));
