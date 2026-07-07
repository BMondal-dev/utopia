<?php

namespace Clarus\Platform\Modules\Account\Http\Account;

use Clarus\Auth\Validator\EmailAddress;
use Clarus\Auth\Validator\Password as PasswordValidator;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Auth\Hash;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createAccount';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/account')
            ->desc('Register a new account with an email and password.')
            ->groups(['api', 'account'])
            ->param('email', '', new EmailAddress(), 'Account email address.')
            ->param('password', '', new PasswordValidator(), 'Account password. Must be between 8 and 256 characters.')
            ->param('name', '', new Text(APP_LIMIT_USER_NAME), 'Account name.')
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->inject('hash')
            ->callback($this->action(...));
    }

    public function action(
        string $email,
        string $password,
        string $name,
        Response $response,
        Database $db,
        Authorization $authorization,
        Hash $hash,
    ): void {
        $email = \mb_strtolower($email);

        $existing = $authorization->skip(fn () => $db->findOne('users', [
            Query::equal('email', [$email]),
        ]));

        if (!$existing->isEmpty()) {
            throw new Exception(Exception::USER_ALREADY_EXISTS);
        }

        $userId = ID::unique();

        $user = new Document([
            '$id' => $userId,
            '$permissions' => [
                Permission::read(Role::user($userId)),
                Permission::update(Role::user($userId)),
            ],
            'email' => $email,
            'emailVerified' => false,
            'name' => $name,
            'password' => $hash->hash($password),
            'status' => 'active',
        ]);

        try {
            $user = $authorization->skip(fn () => $db->createDocument('users', $user));
        } catch (DuplicateException) {
            throw new Exception(Exception::USER_ALREADY_EXISTS);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($user, Response::MODEL_USER);
    }
}
