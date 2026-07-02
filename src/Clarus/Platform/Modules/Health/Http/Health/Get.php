<?php

namespace Clarus\Platform\Modules\Health\Http\Health;

use Clarus\Utopia\Response;
use Utopia\Database\Document;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getHealth';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/health')
            ->desc('Get health')
            ->groups(['api', 'health'])
            ->inject('response')
            ->callback($this->action(...));
    }

    public function action(Response $response): void
    {
        $response->dynamic(new Document([
            'name' => APP_NAME,
            'version' => APP_VERSION,
            'status' => 'pass',
            'ping' => 0,
        ]), Response::MODEL_HEALTH);
    }
}
