<?php

namespace Clarus\Auth\OAuth2;

use Clarus\Auth\OAuth2;

class Google extends OAuth2
{
    protected array $scopes = [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'openid',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $user = [];

    /**
     * @var array<string, mixed>
     */
    protected array $tokens = [];

    public function getName(): string
    {
        return 'google';
    }

    public function getLoginURL(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . \http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $this->callback,
            'scope' => \implode(' ', $this->getScopes()),
            'state' => \json_encode($this->state),
            'response_type' => 'code',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);
    }

    protected function getTokens(string $code): array
    {
        if (empty($this->tokens)) {
            $this->tokens = $this->decodeJson($this->request(
                'POST',
                'https://oauth2.googleapis.com/token?' . \http_build_query([
                    'code' => $code,
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'redirect_uri' => $this->callback,
                    'grant_type' => 'authorization_code',
                ])
            ));
        }

        return $this->tokens;
    }

    public function getUserId(string $accessToken): string
    {
        return (string) ($this->getUser($accessToken)['sub'] ?? '');
    }

    public function getUserEmail(string $accessToken): string
    {
        return (string) ($this->getUser($accessToken)['email'] ?? '');
    }

    public function isEmailVerified(string $accessToken): bool
    {
        return (bool) ($this->getUser($accessToken)['email_verified'] ?? false);
    }

    public function getUserName(string $accessToken): string
    {
        return (string) ($this->getUser($accessToken)['name'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUser(string $accessToken): array
    {
        if (empty($this->user)) {
            $this->user = $this->decodeJson($this->request(
                'GET',
                'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . \urlencode($accessToken)
            ));
        }

        return $this->user;
    }
}
