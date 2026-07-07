<?php

namespace Clarus\Auth;

use Psr\Http\Message\ServerRequestInterface;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Helpers\Role as DBRole;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;

/**
 * Resolves authentication (who is calling) and tenant context (which
 * organization they are acting in, and with which role) for a single
 * request, and wires the result into the shared {@see Authorization}
 * validator so that document-level permission checks "just work" for the
 * rest of the request lifecycle.
 *
 * All lookups needed to *establish* identity (finding a session/user/
 * membership by id) intentionally bypass document permissions via
 * `Authorization::skip()` - a session secret or valid JWT signature is
 * itself the proof of access, not the row-level ACL.
 */
final class RequestAuthenticator
{
    public const SESSION_COOKIE = 'clarus_session';

    public const TENANT_HEADER = 'x-tenant-id';

    public static function resolveSession(ServerRequestInterface $request, Database $db, Authorization $authorization): Document
    {
        $raw = $request->getCookieParams()[self::SESSION_COOKIE] ?? '';

        if (!\is_string($raw) || !\str_contains($raw, '_')) {
            return new Document();
        }

        [$sessionId, $secret] = \explode('_', $raw, 2);

        if ($sessionId === '' || $secret === '') {
            return new Document();
        }

        $session = $authorization->skip(fn () => $db->getDocument('sessions', $sessionId));

        if ($session->isEmpty()) {
            return new Document();
        }

        if (!Secret::verify($secret, (string) $session->getAttribute('secret', ''))) {
            return new Document();
        }

        if (self::isExpired((string) $session->getAttribute('expire', ''))) {
            return new Document();
        }

        return $session;
    }

    public static function resolveUser(
        Document $session,
        ServerRequestInterface $request,
        Database $db,
        Authorization $authorization,
        Jwt $jwt,
    ): Document {
        $user = new Document();

        if (!$session->isEmpty()) {
            $user = $authorization->skip(fn () => $db->getDocument('users', (string) $session->getAttribute('userId', '')));
        } else {
            $bearer = self::bearerToken($request);

            if ($bearer !== null) {
                $claims = $jwt->decode($bearer);
                $userId = \is_array($claims) ? (string) ($claims['userId'] ?? '') : '';

                if ($userId !== '') {
                    $user = $authorization->skip(fn () => $db->getDocument('users', $userId));
                }
            }
        }

        if ($user->isEmpty() || $user->getAttribute('status') !== 'active') {
            return new Document();
        }

        $authorization->addRole(DBRole::user($user->getId())->toString());
        $authorization->addRole(DBRole::users()->toString());

        return $user;
    }

    public static function resolveTenantContext(
        ServerRequestInterface $request,
        Document $user,
        Database $db,
        Database $dbForTenant,
        Authorization $authorization,
    ): TenantContext {
        if ($user->isEmpty()) {
            return new TenantContext(new Document(), new Document());
        }

        $tenantId = \trim($request->getHeaderLine(self::TENANT_HEADER));

        if ($tenantId === '') {
            return new TenantContext(new Document(), new Document());
        }

        $membership = $authorization->skip(fn () => $db->findOne('memberships', [
            Query::equal('tenantId', [$tenantId]),
            Query::equal('userId', [$user->getId()]),
            Query::equal('status', ['active']),
        ]));

        if ($membership->isEmpty()) {
            return new TenantContext(new Document(), new Document());
        }

        $tenant = $authorization->skip(fn () => $db->getDocument('tenants', $tenantId));

        if ($tenant->isEmpty() || $tenant->getAttribute('status') !== 'active') {
            return new TenantContext(new Document(), new Document());
        }

        $role = (string) $membership->getAttribute('role', '');

        $authorization->addRole(DBRole::team($tenant->getId())->toString());

        if ($role !== '') {
            $authorization->addRole(DBRole::team($tenant->getId(), $role)->toString());
        }

        $dbForTenant->setTenant($tenant->getSequence());

        return new TenantContext($tenant, $membership);
    }

    public static function isExpired(string $expire): bool
    {
        if ($expire === '') {
            return true;
        }

        return $expire < DateTime::now();
    }

    private static function bearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('authorization');

        if ($header === '' || !\preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return \trim($matches[1]);
    }
}
