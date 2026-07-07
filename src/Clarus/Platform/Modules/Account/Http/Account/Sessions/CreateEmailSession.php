<?php

namespace Clarus\Platform\Modules\Account\Http\Account\Sessions;

use Clarus\Auth\Secret;
use Clarus\Auth\Validator\EmailAddress;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Psr\Http\Message\ServerRequestInterface;
use Utopia\Auth\Hash;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;

class CreateEmailSession extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createEmailSession';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/account/sessions/email')
            ->desc('Create a session using an email and password.')
            ->groups(['api', 'account'])
            ->param('email', '', new EmailAddress(), 'Account email address.')
            ->param('password', '', new Text(256), 'Account password.')
            ->inject('request')
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('hash')
            ->callback($this->action(...));
    }

    public function action(
        string $email,
        string $password,
        ServerRequestInterface $request,
        Response $response,
        Database $db,
        Authorization $authorization,
        Hash $hash,
    ): void {
        $email = \mb_strtolower($email);

        $user = $authorization->skip(fn () => $db->findOne('users', [
            Query::equal('email', [$email]),
        ]));

        if ($user->isEmpty() || $user->getAttribute('password', '') === '') {
            throw new Exception(Exception::USER_INVALID_CREDENTIALS);
        }

        if (!$hash->verify($password, $user->getAttribute('password'))) {
            throw new Exception(Exception::USER_INVALID_CREDENTIALS);
        }

        if ($user->getAttribute('status') !== 'active') {
            throw new Exception(Exception::USER_BLOCKED);
        }

        $secret = Secret::generate();

        $session = new Document([
            '$id' => ID::unique(),
            'userId' => $user->getId(),
            'secret' => Secret::hash($secret),
            'provider' => 'email',
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

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($session, Response::MODEL_SESSION);
    }
}
