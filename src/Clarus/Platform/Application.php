<?php

namespace Clarus\Platform;

use Clarus\Platform\Modules\Core;
use Clarus\Platform\Modules\Health;
use Clarus\Platform\Modules\Todos;
use Utopia\Platform\Platform;

class Application extends Platform
{
    public function __construct()
    {
        parent::__construct(new Core());
        $this->addModule(new Health\Module());
        $this->addModule(new Todos\Module());
    }
}
