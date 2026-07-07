<?php

namespace Clarus\Platform\Modules\Tenants\Http\Memberships;

use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Integer;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listMemberships';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/memberships')
            ->desc('List members of the active tenant.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->label('roles', MembershipRole::all())
            ->param('limit', 25, new Integer(true), 'Maximum number of memberships to return.', true)
            ->param('offset', 0, new Integer(true), 'Number of memberships to skip.', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('tenantContext')
            ->callback($this->action(...));
    }

    public function action(
        int $limit,
        int $offset,
        Response $response,
        Database $db,
        Authorization $authorization,
        TenantContext $tenantContext,
    ): void {
        $tenantId = $tenantContext->tenant->getId();

        $queries = [
            Query::equal('tenantId', [$tenantId]),
            Query::orderDesc('$createdAt'),
        ];

        $memberships = $authorization->skip(fn () => $db->find('memberships', \array_merge($queries, [
            Query::limit($limit),
            Query::offset($offset),
        ])));

        $total = $authorization->skip(fn () => $db->count('memberships', $queries));

        $response->dynamic(new Document([
            'memberships' => $memberships,
            'total' => $total,
        ]), Response::MODEL_MEMBERSHIP_LIST);
    }
}
