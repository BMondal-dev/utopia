<?php

namespace Clarus\Platform\Modules\Account\Http\Account\OAuth2;

use Clarus\Auth\Jwt;
use Clarus\Auth\OAuth2Factory;
use Clarus\Utopia\Response;
use Psr\Http\Message\ServerRequestInterface;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\System\System;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class CreateSession extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createOAuth2Session';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/account/sessions/oauth2/:provider')
            ->desc('Redirect the user to an OAuth2 provider to start the login flow.')
            ->groups(['api', 'account'])
            ->param('provider', '', new WhiteList(OAuth2Factory::providers()), 'OAuth2 provider name.')
            ->param('success', '/', new Text(2048), 'Relative path to redirect to after a successful login.', true)
            ->param('failure', '/', new Text(2048), 'Relative path to redirect to if the login fails.', true)
            ->inject('request')
            ->inject('response')
            ->callback($this->action(...));
    }

    public function action(
        string $provider,
        string $success,
        string $failure,
        ServerRequestInterface $request,
        Response $response,
    ): void {
        $success = self::sanitizeRedirect($success);
        $failure = self::sanitizeRedirect($failure);

        $state = (new Jwt(System::getEnv('APP_SECRET', ''), APP_AUTH_OAUTH2_STATE_TTL_SECONDS))->encode([
            'success' => $success,
            'failure' => $failure,
        ]);

        $oauth2 = OAuth2Factory::create($provider, self::callbackUrl($request, $provider), ['token' => $state]);

        $response->redirect($oauth2->getLoginURL(), Response::STATUS_CODE_FOUND);
    }

    public static function callbackUrl(ServerRequestInterface $request, string $provider): string
    {
        $uri = $request->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority() . '/v1/account/sessions/oauth2/callback/' . $provider;
    }

    /**
     * Only ever allow same-origin, relative redirects to avoid turning this
     * endpoint into an open redirector.
     */
    private static function sanitizeRedirect(string $path): string
    {
        if ($path === '' || $path[0] !== '/' || \str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }
}
