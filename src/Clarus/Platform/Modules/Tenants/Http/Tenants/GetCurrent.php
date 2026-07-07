<?php

namespace Clarus\Platform\Modules\Tenants\Http\Tenants;

use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Utopia\Response;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class GetCurrent extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getCurrentTenant';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/tenants/current')
            ->desc('Get the active tenant, resolved from the "X-Tenant-Id" header.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->label('roles', MembershipRole::all())
            ->inject('response')
            ->inject('tenantContext')
            ->callback($this->action(...));
    }

    public function action(Response $response, TenantContext $tenantContext): void
    {
        $tenant = $tenantContext->tenant;
        $tenant->setAttribute('role', $tenantContext->getRole());

        $response->dynamic($tenant, Response::MODEL_TENANT);
    }
}
