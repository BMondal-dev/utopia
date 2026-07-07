<?php

namespace Clarus\Platform\Modules\Account\Services;

use Clarus\Platform\Modules\Account\Http\Account\Create as CreateAccount;
use Clarus\Platform\Modules\Account\Http\Account\Get as GetAccount;
use Clarus\Platform\Modules\Account\Http\Account\Jwt\Create as CreateAccountJwt;
use Clarus\Platform\Modules\Account\Http\Account\OAuth2\Callback as OAuth2Callback;
use Clarus\Platform\Modules\Account\Http\Account\OAuth2\CreateSession as CreateOAuth2Session;
use Clarus\Platform\Modules\Account\Http\Account\Sessions\CreateEmailSession;
use Clarus\Platform\Modules\Account\Http\Account\Sessions\Delete as DeleteSession;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;

        $this->addAction(CreateAccount::getName(), new CreateAccount());
        $this->addAction(GetAccount::getName(), new GetAccount());
        $this->addAction(CreateEmailSession::getName(), new CreateEmailSession());
        $this->addAction(DeleteSession::getName(), new DeleteSession());
        $this->addAction(CreateAccountJwt::getName(), new CreateAccountJwt());
        $this->addAction(CreateOAuth2Session::getName(), new CreateOAuth2Session());
        $this->addAction(OAuth2Callback::getName(), new OAuth2Callback());
    }
}
