<?php

namespace Clarus\Platform\Modules\Tenants\Http\Memberships;

use Clarus\Auth\MembershipRole;
use Clarus\Auth\TenantContext;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return "updateMembership";
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath("/v1/memberships/:membershipId")
            ->desc('Change a member\'s role within the active tenant.')
            ->groups(["api", "tenants"])
            ->label("auth", true)
            ->label("roles", MembershipRole::managers())
            ->param("membershipId", "", new Text(36), "Membership ID.")
            ->param(
                "role",
                "",
                new WhiteList(MembershipRole::all(), true),
                "New role: " . \implode(", ", MembershipRole::all()) . ".",
            )
            ->inject("response")
            ->inject("db")
            ->inject("authorization")
            ->inject("tenantContext")
            ->callback($this->action(...));
    }

    public function action(
        string $membershipId,
        string $role,
        Response $response,
        Database $db,
        Authorization $authorization,
        TenantContext $tenantContext,
    ): void {
        $tenantId = $tenantContext->tenant->getId();

        $membership = $authorization->skip(
            fn () => $db->getDocument("memberships", $membershipId),
        );

        if (
            $membership->isEmpty() ||
            $membership->getAttribute("tenantId") !== $tenantId
        ) {
            throw new Exception(Exception::MEMBERSHIP_NOT_FOUND);
        }

        $currentRole = (string) $membership->getAttribute("role");

        $isOwnerChange =
            $currentRole === MembershipRole::OWNER ||
            $role === MembershipRole::OWNER;

        if (
            $isOwnerChange &&
            $tenantContext->getRole() !== MembershipRole::OWNER
        ) {
            throw new Exception(
                Exception::GENERAL_FORBIDDEN,
                "Only an owner can grant or revoke the owner role.",
            );
        }

        if (
            $currentRole === MembershipRole::OWNER &&
            $role !== MembershipRole::OWNER
        ) {
            $owners = $authorization->skip(
                fn () => $db->count(
                    "memberships",
                    [
                        Query::equal("tenantId", [$tenantId]),
                        Query::equal("role", [MembershipRole::OWNER]),
                        Query::equal("status", ["active"]),
                    ],
                    2,
                ),
            );

            if ($owners <= 1) {
                throw new Exception(Exception::MEMBERSHIP_LAST_OWNER);
            }
        }

        $membership->setAttribute("role", $role);
        $membership = $authorization->skip(
            fn () => $db->updateDocument(
                "memberships",
                $membership->getId(),
                $membership,
            ),
        );

        $response->dynamic($membership, Response::MODEL_MEMBERSHIP);
    }
}
