<?php

namespace Clarus\Platform\Modules\Tenants\Http\Tenants;

use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listTenants';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/tenants')
            ->desc('List the tenants the current user is a member of.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('user')
            ->callback($this->action(...));
    }

    public function action(
        Response $response,
        Database $db,
        Authorization $authorization,
        Document $user,
    ): void {
        $memberships = $authorization->skip(fn () => $db->find('memberships', [
            Query::equal('userId', [$user->getId()]),
            Query::equal('status', ['active']),
            Query::limit(APP_LIMIT_ARRAY_PARAMS_SIZE),
        ]));

        $rolesByTenant = [];
        $tenantIds = [];

        foreach ($memberships as $membership) {
            $tenantId = (string) $membership->getAttribute('tenantId');
            $tenantIds[] = $tenantId;
            $rolesByTenant[$tenantId] = (string) $membership->getAttribute('role');
        }

        $tenants = [];

        if ($tenantIds !== []) {
            $tenants = $authorization->skip(fn () => $db->find('tenants', [
                Query::equal('$id', $tenantIds),
                Query::limit(\count($tenantIds)),
            ]));

            foreach ($tenants as $tenant) {
                $tenant->setAttribute('role', $rolesByTenant[$tenant->getId()] ?? '');
            }
        }

        $response->dynamic(new Document([
            'tenants' => $tenants,
            'total' => \count($tenants),
        ]), Response::MODEL_TENANT_LIST);
    }
}
