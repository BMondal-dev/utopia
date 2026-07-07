<?php

namespace Clarus\Auth;

use Ahc\Jwt\JWT as AdhocJWT;
use Ahc\Jwt\JWTException;

/**
 * Thin wrapper around adhocore/jwt used to issue and verify short-lived,
 * stateless bearer tokens (`POST /v1/account/jwt`) and to sign the OAuth2
 * `state` parameter (CSRF protection for the OAuth2 redirect flow).
 */
final class Jwt
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 900,
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function encode(array $claims): string
    {
        return $this->client()->encode($claims);
    }

    /**
     * @return array<string, mixed>|null Null when the token is missing, malformed, expired or has an invalid signature.
     */
    public function decode(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        try {
            return $this->client()->decode($token);
        } catch (JWTException) {
            return null;
        }
    }

    private function client(): AdhocJWT
    {
        return new AdhocJWT($this->secret, 'HS256', $this->ttl);
    }
}
