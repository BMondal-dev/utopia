<?php

namespace Clarus\Auth;

use Clarus\Extend\Exception as ClarusException;
use Utopia\System\System;

/**
 * Builds an OAuth2 provider client from environment configuration.
 *
 * Supported providers and their required env vars:
 * - google: _APP_OAUTH_GOOGLE_CLIENT_ID, _APP_OAUTH_GOOGLE_CLIENT_SECRET
 * - microsoft: _APP_OAUTH_MICROSOFT_CLIENT_ID, _APP_OAUTH_MICROSOFT_CLIENT_SECRET, _APP_OAUTH_MICROSOFT_TENANT_ID (optional, defaults to "common")
 */
final class OAuth2Factory
{
    /**
     * @return list<string>
     */
    public static function providers(): array
    {
        return ['google', 'microsoft'];
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function create(string $provider, string $callback, array $state = []): OAuth2
    {
        return match ($provider) {
            'google' => new OAuth2\Google(
                self::requireEnv('_APP_OAUTH_GOOGLE_CLIENT_ID', $provider),
                self::requireEnv('_APP_OAUTH_GOOGLE_CLIENT_SECRET', $provider),
                $callback,
                $state,
            ),
            'microsoft' => new OAuth2\Microsoft(
                self::requireEnv('_APP_OAUTH_MICROSOFT_CLIENT_ID', $provider),
                self::requireEnv('_APP_OAUTH_MICROSOFT_CLIENT_SECRET', $provider),
                $callback,
                $state,
                System::getEnv('_APP_OAUTH_MICROSOFT_TENANT_ID', 'common'),
            ),
            default => throw new ClarusException(ClarusException::OAUTH2_PROVIDER_UNSUPPORTED, "OAuth2 provider \"{$provider}\" is not supported."),
        };
    }

    private static function requireEnv(string $key, string $provider): string
    {
        $value = System::getEnv($key, '');

        if ($value === '') {
            throw new ClarusException(ClarusException::OAUTH2_PROVIDER_DISABLED, "OAuth2 provider \"{$provider}\" is not configured.");
        }

        return $value;
    }
}
