<?php

namespace Clarus\Platform\Modules\Tenants\Http\Memberships;

use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Auth\Validator\EmailAddress;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createMembership';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/memberships')
            ->desc('Add an existing account to the active tenant with a given role.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->label('roles', MembershipRole::managers())
            ->param('email', '', new EmailAddress(), 'Email address of the account to add. The account must already exist.')
            ->param('role', '', new WhiteList(MembershipRole::all(), true), 'Role to grant: ' . \implode(', ', MembershipRole::all()) . '.')
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('user')
            ->inject('tenantContext')
            ->callback($this->action(...));
    }

    public function action(
        string $email,
        string $role,
        Response $response,
        Database $db,
        Authorization $authorization,
        Document $user,
        TenantContext $tenantContext,
    ): void {
        $tenantId = $tenantContext->tenant->getId();

        if ($role === MembershipRole::OWNER && $tenantContext->getRole() !== MembershipRole::OWNER) {
            throw new Exception(Exception::GENERAL_FORBIDDEN, 'Only an owner can grant the owner role.');
        }

        $email = \mb_strtolower($email);

        $target = $authorization->skip(fn () => $db->findOne('users', [
            Query::equal('email', [$email]),
        ]));

        if ($target->isEmpty()) {
            throw new Exception(Exception::USER_NOT_FOUND, 'No account with this email exists yet. Ask them to register first.');
        }

        $existing = $authorization->skip(fn () => $db->findOne('memberships', [
            Query::equal('tenantId', [$tenantId]),
            Query::equal('userId', [$target->getId()]),
        ]));

        if (!$existing->isEmpty()) {
            throw new Exception(Exception::MEMBERSHIP_ALREADY_EXISTS);
        }

        $membership = new Document([
            '$id' => ID::unique(),
            '$permissions' => [
                Permission::read(Role::team($tenantId)),
                Permission::update(Role::team($tenantId, MembershipRole::OWNER)),
                Permission::update(Role::team($tenantId, MembershipRole::ADMIN)),
                Permission::delete(Role::team($tenantId, MembershipRole::OWNER)),
                Permission::delete(Role::team($tenantId, MembershipRole::ADMIN)),
            ],
            'tenantId' => $tenantId,
            'userId' => $target->getId(),
            'role' => $role,
            'status' => 'active',
            'invitedBy' => $user->getId(),
        ]);

        try {
            $membership = $authorization->skip(fn () => $db->createDocument('memberships', $membership));
        } catch (DuplicateException) {
            throw new Exception(Exception::MEMBERSHIP_ALREADY_EXISTS);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($membership, Response::MODEL_MEMBERSHIP);
    }
}
