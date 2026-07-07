<?php

namespace Clarus\Platform\Modules\Tenants\Http\Tenants;

use Clarus\Auth\MembershipRole;
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
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createTenant';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/tenants')
            ->desc('Create a new tenant (organization). The caller becomes its owner.')
            ->groups(['api', 'tenants'])
            ->label('auth', true)
            ->param('name', '', new Text(APP_LIMIT_TENANT_NAME), 'Tenant name.')
            ->param('slug', '', new Text(APP_LIMIT_TENANT_SLUG), 'Unique, URL-friendly identifier. Derived from the name when omitted.', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('user')
            ->callback($this->action(...));
    }

    public function action(
        string $name,
        string $slug,
        Response $response,
        Database $db,
        Authorization $authorization,
        Document $user,
    ): void {
        $slug = self::slugify($slug !== '' ? $slug : $name);

        if ($slug === '') {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'Could not derive a valid slug from the tenant name. Please provide one explicitly.');
        }

        $existing = $authorization->skip(fn () => $db->findOne('tenants', [
            Query::equal('slug', [$slug]),
        ]));

        if (!$existing->isEmpty()) {
            throw new Exception(Exception::TENANT_SLUG_ALREADY_EXISTS);
        }

        $tenantId = ID::unique();

        $tenant = new Document([
            '$id' => $tenantId,
            '$permissions' => [
                Permission::read(Role::team($tenantId)),
                Permission::update(Role::team($tenantId, MembershipRole::OWNER)),
                Permission::delete(Role::team($tenantId, MembershipRole::OWNER)),
            ],
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'ownerId' => $user->getId(),
        ]);

        try {
            $tenant = $authorization->skip(fn () => $db->createDocument('tenants', $tenant));
        } catch (DuplicateException) {
            throw new Exception(Exception::TENANT_SLUG_ALREADY_EXISTS);
        }

        $membership = new Document([
            '$id' => ID::unique(),
            '$permissions' => [
                Permission::read(Role::team($tenantId)),
                Permission::update(Role::team($tenantId, MembershipRole::OWNER)),
                Permission::delete(Role::team($tenantId, MembershipRole::OWNER)),
            ],
            'tenantId' => $tenantId,
            'userId' => $user->getId(),
            'role' => MembershipRole::OWNER,
            'status' => 'active',
            'invitedBy' => $user->getId(),
        ]);

        $authorization->skip(fn () => $db->createDocument('memberships', $membership));

        $tenant->setAttribute('role', MembershipRole::OWNER);

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($tenant, Response::MODEL_TENANT);
    }

    private static function slugify(string $value): string
    {
        $value = \mb_strtolower(\trim($value));
        $value = \preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return \trim($value, '-');
    }
}
