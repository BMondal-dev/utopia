<?php

namespace Clarus\Auth;

/**
 * The set of roles a user can hold inside a tenant (organization).
 *
 * Roles are intentionally flat (no automatic inheritance at the database
 * permission level): each action explicitly declares which roles may
 * perform it. The numeric rank is only a convenience for UI ordering and
 * for the few places we need "at least as privileged as" checks (e.g. only
 * an owner may grant/revoke the owner role).
 */
final class MembershipRole
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const AUDITOR = 'auditor';

    public const EMPLOYEE = 'employee';

    public const CONTRACTOR = 'contractor';

    /**
     * @var array<string, int>
     */
    private const RANK = [
        self::OWNER => 50,
        self::ADMIN => 40,
        self::AUDITOR => 30,
        self::EMPLOYEE => 20,
        self::CONTRACTOR => 10,
    ];

    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return \array_keys(self::RANK);
    }

    public static function isValid(string $role): bool
    {
        return \array_key_exists($role, self::RANK);
    }

    public static function rank(string $role): int
    {
        return self::RANK[$role] ?? 0;
    }

    public static function atLeast(string $role, string $minimum): bool
    {
        return self::rank($role) >= self::rank($minimum);
    }

    /**
     * Roles allowed to manage members (invite, change roles, remove).
     *
     * @return list<string>
     */
    public static function managers(): array
    {
        return [self::OWNER, self::ADMIN];
    }
}
