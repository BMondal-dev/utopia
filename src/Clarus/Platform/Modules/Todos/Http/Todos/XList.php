<?php

namespace Clarus\Platform\Modules\Todos\Http\Todos;

use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\Integer;
use Utopia\Validator\Nullable;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listTodos';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/todos')
            ->desc('List todos')
            ->groups(['api', 'todos'])
            ->param('completed', null, new Nullable(new Boolean()), 'Filter by completed status.', true)
            ->param('limit', 25, new Integer(true), 'Maximum number of todos to return.', true)
            ->param('offset', 0, new Integer(true), 'Number of todos to skip.', true)
            ->param('total', true, new Boolean(true), 'When set to false, total count is omitted.', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        ?bool $completed,
        int $limit,
        int $offset,
        bool $total,
        Response $response,
        Database $db,
        Authorization $authorization,
    ): void {
        $queries = [
            Query::limit($limit),
            Query::offset($offset),
            Query::orderDesc('$createdAt'),
        ];

        if ($completed !== null) {
            $queries[] = Query::equal('completed', [$completed]);
        }

        $filterQueries = Query::groupByType($queries)['filters'];

        $todos = $authorization->skip(fn () => $db->find('todos', $queries));
        $totalCount = $total ? $authorization->skip(fn () => $db->count('todos', $filterQueries)) : 0;

        $response->dynamic(new Document([
            'todos' => $todos,
            'total' => $totalCount,
        ]), Response::MODEL_TODO_LIST);
    }
}
