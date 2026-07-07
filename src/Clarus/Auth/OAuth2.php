<?php

namespace Clarus\Auth;

use Clarus\Auth\OAuth2\Exception;

/**
 * Minimal OAuth2 "authorization code" flow client, adapted from Appwrite's
 * `Appwrite\Auth\OAuth2` base class. Kept intentionally small: only what's
 * needed to redirect the user, exchange a code for an access token, and
 * read back an identity (id/email/name/verified) from the provider.
 */
abstract class OAuth2
{
    /**
     * @var list<string>
     */
    protected array $scopes = [];

    public function __construct(
        protected string $appId,
        protected string $appSecret,
        protected string $callback,
        protected array $state = [],
    ) {
    }

    abstract public function getName(): string;

    abstract public function getLoginURL(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getTokens(string $code): array;

    abstract public function getUserId(string $accessToken): string;

    abstract public function getUserEmail(string $accessToken): string;

    abstract public function getUserName(string $accessToken): string;

    abstract public function isEmailVerified(string $accessToken): bool;

    public function getAccessToken(string $code): string
    {
        $tokens = $this->getTokens($code);

        return (string) ($tokens['access_token'] ?? '');
    }

    protected function addScope(string $scope): static
    {
        if (!\in_array($scope, $this->scopes, true)) {
            $this->scopes[] = $scope;
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    protected function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param array<int, string> $headers
     */
    protected function request(string $method, string $url, array $headers = [], string $payload = ''): string
    {
        $ch = \curl_init($url);

        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 15);
        \curl_setopt($ch, \CURLOPT_USERAGENT, 'Clarus OAuth2');

        if ($payload !== '') {
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Length: ' . \strlen($payload);
        }

        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = \curl_exec($ch);
        $code = (int) \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $error = \curl_error($ch);

        \curl_close($ch);

        if ($response === false) {
            throw new Exception('OAuth2 request failed: ' . $error);
        }

        if ($code >= 400) {
            throw new Exception('OAuth2 provider returned an error: ' . $response, $code);
        }

        return (string) $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $body): array
    {
        $decoded = \json_decode($body, true);

        return \is_array($decoded) ? $decoded : [];
    }
}
