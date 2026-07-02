<?php

namespace Clarus\Platform\Modules\Todos\Http\Todos;

use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\Nullable;
use Utopia\Validator\Text;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'updateTodo';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/todos/:todoId')
            ->desc('Update todo')
            ->groups(['api', 'todos'])
            ->param('todoId', '', fn (Database $db) => new UID($db->getAdapter()->getMaxUIDLength()), 'Todo ID.', false, ['db'])
            ->param('title', null, new Nullable(new Text(APP_LIMIT_TODO_TITLE)), 'Todo title.', true)
            ->param('description', null, new Nullable(new Text(APP_LIMIT_TODO_DESCRIPTION)), 'Todo description.', true)
            ->param('completed', null, new Nullable(new Boolean()), 'Whether the todo is completed.', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        string $todoId,
        ?string $title,
        ?string $description,
        ?bool $completed,
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

        $updates = new Document([]);

        if ($title !== null) {
            if ($title === '') {
                throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'Param "title" cannot be empty.');
            }
            $updates->setAttribute('title', $title);
        }

        if ($description !== null) {
            $updates->setAttribute('description', $description);
        }

        if ($completed !== null) {
            $updates->setAttribute('completed', $completed);
        }

        if ($updates->isEmpty()) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'At least one field must be provided to update.');
        }

        $todo = $authorization->skip(fn () => $db->updateDocument('todos', $todo->getId(), $updates));

        $response->dynamic($todo, Response::MODEL_TODO);
    }
}
