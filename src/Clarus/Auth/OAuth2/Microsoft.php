<?php

namespace Clarus\Auth\OAuth2;

use Clarus\Auth\OAuth2;

class Microsoft extends OAuth2
{
    protected array $scopes = [
        'offline_access',
        'user.read',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $user = [];

    /**
     * @var array<string, mixed>
     */
    protected array $tokens = [];

    public function __construct(
        string $appId,
        string $appSecret,
        string $callback,
        array $state = [],
        private readonly string $tenantId = 'common',
    ) {
        parent::__construct($appId, $appSecret, $callback, $state);
    }

    public function getName(): string
    {
        return 'microsoft';
    }

    public function getLoginURL(): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/authorize?' . \http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $this->callback,
            'state' => \json_encode($this->state),
            'scope' => \implode(' ', $this->getScopes()),
            'response_type' => 'code',
            'response_mode' => 'query',
        ]);
    }

    protected function getTokens(string $code): array
    {
        if (empty($this->tokens)) {
            $headers = ['Content-Type: application/x-www-form-urlencoded'];

            $this->tokens = $this->decodeJson($this->request(
                'POST',
                'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token',
                $headers,
                \http_build_query([
                    'code' => $code,
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'redirect_uri' => $this->callback,
                    'scope' => \implode(' ', $this->getScopes()),
                    'grant_type' => 'authorization_code',
                ])
            ));
        }

        return $this->tokens;
    }

    public function getUserId(string $accessToken): string
    {
        return (string) ($this->getUser($accessToken)['id'] ?? '');
    }

    public function getUserEmail(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        return (string) ($user['mail'] ?? $user['userPrincipalName'] ?? '');
    }

    public function isEmailVerified(string $accessToken): bool
    {
        return $this->getUserEmail($accessToken) !== '';
    }

    public function getUserName(string $accessToken): string
    {
        return (string) ($this->getUser($accessToken)['displayName'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUser(string $accessToken): array
    {
        if (empty($this->user)) {
            $headers = ['Authorization: Bearer ' . $accessToken];
            $this->user = $this->decodeJson($this->request('GET', 'https://graph.microsoft.com/v1.0/me', $headers));
        }

        return $this->user;
    }
}
