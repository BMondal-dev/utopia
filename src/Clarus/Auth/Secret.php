<?php

namespace Clarus\Auth;

use Utopia\Auth\Hashes\Sha;

/**
 * Generates and verifies high-entropy opaque secrets used for session and
 * one-time-use tokens (email verification, password recovery, invites).
 *
 * These are *not* user-chosen passwords, so a fast cryptographic hash
 * (SHA-256, via utopia-php/auth) is appropriate here. Passwords are hashed
 * separately with Argon2 (see {@see \Utopia\Auth\Hashes\Argon2}).
 */
final class Secret
{
    private function __construct()
    {
    }

    public static function generate(int $bytes = 32): string
    {
        return \bin2hex(\random_bytes($bytes));
    }

    public static function hash(string $secret): string
    {
        return (new Sha())->hash($secret);
    }

    public static function verify(string $secret, string $hash): bool
    {
        if ($secret === '' || $hash === '') {
            return false;
        }

        return (new Sha())->verify($secret, $hash);
    }
}
