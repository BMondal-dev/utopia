<?php

namespace Clarus\Platform\Modules\Todos\Services;

use Clarus\Platform\Modules\Todos\Http\Todos\Create as CreateTodo;
use Clarus\Platform\Modules\Todos\Http\Todos\Delete as DeleteTodo;
use Clarus\Platform\Modules\Todos\Http\Todos\Get as GetTodo;
use Clarus\Platform\Modules\Todos\Http\Todos\Update as UpdateTodo;
use Clarus\Platform\Modules\Todos\Http\Todos\XList as ListTodos;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;

        $this->addAction(CreateTodo::getName(), new CreateTodo());
        $this->addAction(ListTodos::getName(), new ListTodos());
        $this->addAction(GetTodo::getName(), new GetTodo());
        $this->addAction(UpdateTodo::getName(), new UpdateTodo());
        $this->addAction(DeleteTodo::getName(), new DeleteTodo());
    }
}
