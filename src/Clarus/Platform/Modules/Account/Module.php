<?php

namespace Clarus\Platform\Modules\Account;

use Clarus\Platform\Modules\Account\Services\Http;
use Utopia\Platform;

class Module extends Platform\Module
{
    public function __construct()
    {
        $this->addService('http', new Http());
    }
}
