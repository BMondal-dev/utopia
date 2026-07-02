<?php

namespace Clarus\Platform\Modules\Health\Services;

use Clarus\Platform\Modules\Health\Http\Health\Get as GetHealth;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;
        $this->addAction(GetHealth::getName(), new GetHealth());
    }
}
