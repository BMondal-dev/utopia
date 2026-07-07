<?php

namespace Clarus\Platform\Modules\Account\Http\Account\Jwt;

use Clarus\Auth\Jwt;
use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Utopia\Response;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createAccountJwt';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/account/jwt')
            ->desc('Create a short-lived JWT for the current user, scoped to the active tenant (`X-Tenant-Id`).')
            ->groups(['api', 'account'])
            ->label('auth', true)
            ->label('roles', MembershipRole::all())
            ->inject('response')
            ->inject('user')
            ->inject('tenantContext')
            ->inject('jwt')
            ->callback($this->action(...));
    }

    public function action(
        Response $response,
        Document $user,
        TenantContext $tenantContext,
        Jwt $jwt,
    ): void {
        $token = $jwt->encode([
            'jti' => ID::unique(),
            'userId' => $user->getId(),
            'tenantId' => $tenantContext->tenant->getId(),
            'role' => $tenantContext->getRole(),
        ]);

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic(new Document(['jwt' => $token]), Response::MODEL_JWT);
    }
}
