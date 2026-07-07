<?php

namespace Clarus\Platform\Modules\Todos\Http\Todos;

use Clarus\Auth\MembershipRole;
use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Query;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'deleteTodo';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath('/v1/todos/:todoId')
            ->desc('Delete todo')
            ->groups(['api', 'todos'])
            ->label('auth', true)
            ->label('roles', MembershipRole::all())
            ->param('todoId', '', fn (Database $dbForTenant) => new UID($dbForTenant->getAdapter()->getMaxUIDLength()), 'Todo ID.', false, ['dbForTenant'])
            ->inject('response')
            ->inject('dbForTenant')
            ->callback($this->action(...));
    }

    public function action(
        string $todoId,
        Response $response,
        Database $dbForTenant,
    ): void {
        $todo = $dbForTenant->findOne('todos', [
            Query::equal('$id', [$todoId]),
        ]);

        if ($todo->isEmpty()) {
            throw new Exception(Exception::TODO_NOT_FOUND);
        }

        if (!$dbForTenant->deleteDocument('todos', $todo->getId())) {
            throw new Exception(Exception::GENERAL_SERVER_ERROR, 'Failed to remove todo from database.');
        }

        $response->noContent();
    }
}
