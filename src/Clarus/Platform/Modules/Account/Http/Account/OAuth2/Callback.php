<?php

namespace Clarus\Platform\Modules\Account\Http\Account\OAuth2;

use Clarus\Auth\Jwt;
use Clarus\Auth\OAuth2Factory;
use Clarus\Auth\Secret;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Psr\Http\Message\ServerRequestInterface;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\System\System;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Callback extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createOAuth2SessionCallback';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/account/sessions/oauth2/callback/:provider')
            ->desc('Handle the OAuth2 provider redirect back and complete login.')
            ->groups(['api', 'account'])
            ->param('provider', '', new WhiteList(OAuth2Factory::providers()), 'OAuth2 provider name.')
            ->param('code', '', new Text(2048), 'Authorization code returned by the provider.', true)
            ->param('state', '', new Text(4096), 'Signed state returned by the provider.', true)
            ->inject('request')
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        string $provider,
        string $code,
        string $state,
        ServerRequestInterface $request,
        Response $response,
        Database $db,
        Authorization $authorization,
    ): void {
        $claims = (new Jwt(System::getEnv('APP_SECRET', ''), APP_AUTH_OAUTH2_STATE_TTL_SECONDS))->decode($state);

        if ($claims === null) {
            // We cannot trust any redirect target without a validated state.
            throw new Exception(Exception::OAUTH2_STATE_INVALID);
        }

        $success = (string) ($claims['success'] ?? '/');
        $failure = (string) ($claims['failure'] ?? '/');

        try {
            if ($code === '') {
                throw new Exception(Exception::OAUTH2_MISSING_CODE);
            }

            $oauth2 = OAuth2Factory::create($provider, CreateSession::callbackUrl($request, $provider));
            $accessToken = $oauth2->getAccessToken($code);

            if ($accessToken === '') {
                throw new Exception(Exception::OAUTH2_MISSING_CODE);
            }

            $providerUserId = $oauth2->getUserId($accessToken);
            $email = \mb_strtolower($oauth2->getUserEmail($accessToken));
            $name = $oauth2->getUserName($accessToken);
            $verified = $oauth2->isEmailVerified($accessToken);

            if ($providerUserId === '' || $email === '') {
                throw new Exception(Exception::OAUTH2_PROVIDER_DISABLED, 'The provider did not return enough profile information to sign in.');
            }

            $user = $this->findOrCreateUser($db, $authorization, $provider, $providerUserId, $email, $name, $verified);

            if ($user->getAttribute('status') !== 'active') {
                throw new Exception(Exception::USER_BLOCKED);
            }
        } catch (\Throwable) {
            $response->redirect(self::withQuery($failure, ['error' => 'oauth2_failed']), Response::STATUS_CODE_FOUND);

            return;
        }

        $secret = Secret::generate();

        $session = new Document([
            '$id' => ID::unique(),
            'userId' => $user->getId(),
            'secret' => Secret::hash($secret),
            'provider' => $provider,
            'userAgent' => \mb_substr($request->getHeaderLine('user-agent'), 0, 512),
            'ip' => $request->getServerParams()['remote_addr'] ?? '',
            'expire' => DateTime::addSeconds(new \DateTime(), APP_AUTH_SESSION_DURATION_SECONDS),
        ]);

        $session = $authorization->skip(fn () => $db->createDocument('sessions', $session));

        $response->addCookie(
            name: APP_AUTH_SESSION_COOKIE,
            value: $session->getId() . '_' . $secret,
            expire: \time() + APP_AUTH_SESSION_DURATION_SECONDS,
            path: '/',
            httponly: true,
            sameSite: 'Lax',
        );

        $response->redirect($success, Response::STATUS_CODE_FOUND);
    }

    private function findOrCreateUser(
        Database $db,
        Authorization $authorization,
        string $provider,
        string $providerUserId,
        string $email,
        string $name,
        bool $verified,
    ): Document {
        $identity = $authorization->skip(fn () => $db->findOne('identities', [
            Query::equal('provider', [$provider]),
            Query::equal('providerUserId', [$providerUserId]),
        ]));

        if (!$identity->isEmpty()) {
            return $authorization->skip(fn () => $db->getDocument('users', (string) $identity->getAttribute('userId', '')));
        }

        $user = $authorization->skip(fn () => $db->findOne('users', [
            Query::equal('email', [$email]),
        ]));

        if ($user->isEmpty()) {
            $userId = ID::unique();

            $user = new Document([
                '$id' => $userId,
                '$permissions' => [
                    Permission::read(Role::user($userId)),
                    Permission::update(Role::user($userId)),
                ],
                'email' => $email,
                'emailVerified' => $verified,
                'name' => $name !== '' ? $name : $email,
                'password' => '',
                'status' => 'active',
            ]);

            $user = $authorization->skip(fn () => $db->createDocument('users', $user));
        }

        $identity = new Document([
            '$id' => ID::unique(),
            '$permissions' => [
                Permission::read(Role::user($user->getId())),
                Permission::update(Role::user($user->getId())),
            ],
            'userId' => $user->getId(),
            'provider' => $provider,
            'providerUserId' => $providerUserId,
            'providerEmail' => $email,
        ]);

        $authorization->skip(fn () => $db->createDocument('identities', $identity));

        return $user;
    }

    private static function withQuery(string $path, array $query): string
    {
        $separator = \str_contains($path, '?') ? '&' : '?';

        return $path . $separator . \http_build_query($query);
    }
}
