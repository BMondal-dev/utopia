<?php

namespace Clarus\Platform\Modules\Todos\Http\Todos;

use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
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
            ->param('todoId', '', fn (Database $db) => new UID($db->getAdapter()->getMaxUIDLength()), 'Todo ID.', false, ['db'])
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        string $todoId,
        Response $response,
        Database $db,
        Authorization $authorization,
    ): void {
        $todo = $authorization->skip(fn () => $db->findOne('todos', [
            Query::equal('$id', [$todoId]),
        ]));

        if ($todo->isEmpty()) {
            throw new Exception(Exception::TODO_NOT_FOUND);
        }

        if (!$authorization->skip(fn () => $db->deleteDocument('todos', $todo->getId()))) {
            throw new Exception(Exception::GENERAL_SERVER_ERROR, 'Failed to remove todo from database.');
        }

        $response->noContent();
    }
}
