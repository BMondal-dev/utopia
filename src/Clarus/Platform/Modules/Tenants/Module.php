<?php

namespace Clarus\Platform\Modules\Tenants;

use Clarus\Platform\Modules\Tenants\Services\Http;
use Utopia\Platform;

class Module extends Platform\Module
{
    public function __construct()
    {
        $this->addService('http', new Http());
    }
}
