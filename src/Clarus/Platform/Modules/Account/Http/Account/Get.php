<?php

namespace Clarus\Platform\Modules\Account\Http\Account;

use Clarus\Utopia\Response;
use Utopia\Database\Document;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getAccount';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/account')
            ->desc('Get the currently authenticated account.')
            ->groups(['api', 'account'])
            ->label('auth', true)
            ->inject('response')
            ->inject('user')
            ->callback($this->action(...));
    }

    public function action(Response $response, Document $user): void
    {
        $response->dynamic($user, Response::MODEL_USER);
    }
}
