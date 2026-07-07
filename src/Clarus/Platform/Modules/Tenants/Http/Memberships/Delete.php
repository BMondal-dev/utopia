<?php

namespace Clarus\Platform\Modules\Tenants\Http\Memberships;

use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'deleteMembership';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath('/v1/memberships/:membershipId')
            ->desc('Remove a member from the active tenant.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->label('roles', MembershipRole::managers())
            ->param('membershipId', '', new Text(36), 'Membership ID.')
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('tenantContext')
            ->callback($this->action(...));
    }

    public function action(
        string $membershipId,
        Response $response,
        Database $db,
        Authorization $authorization,
        TenantContext $tenantContext,
    ): void {
        $tenantId = $tenantContext->tenant->getId();

        $membership = $authorization->skip(fn () => $db->getDocument('memberships', $membershipId));

        if ($membership->isEmpty() || $membership->getAttribute('tenantId') !== $tenantId) {
            throw new Exception(Exception::MEMBERSHIP_NOT_FOUND);
        }

        if ((string) $membership->getAttribute('role') === MembershipRole::OWNER) {
            $owners = $authorization->skip(fn () => $db->count('memberships', [
                Query::equal('tenantId', [$tenantId]),
                Query::equal('role', [MembershipRole::OWNER]),
                Query::equal('status', ['active']),
            ], 2));

            if ($owners <= 1) {
                throw new Exception(Exception::MEMBERSHIP_LAST_OWNER);
            }
        }

        $authorization->skip(fn () => $db->deleteDocument('memberships', $membershipId));

        $response->dynamic(new Document(), Response::MODEL_NONE);
    }
}
