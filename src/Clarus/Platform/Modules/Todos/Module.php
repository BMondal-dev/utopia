<?php

namespace Clarus\Platform\Modules\Todos;

use Clarus\Platform\Modules\Todos\Services\Http;
use Utopia\Platform;

class Module extends Platform\Module
{
    public function __construct()
    {
        $this->addService('http', new Http());
    }
}
