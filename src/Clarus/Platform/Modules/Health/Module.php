<?php

namespace Clarus\Platform\Modules\Health;

use Clarus\Platform\Modules\Health\Services\Http;
use Utopia\Platform;

class Module extends Platform\Module
{
    public function __construct()
    {
        $this->addService('http', new Http());
    }
}
