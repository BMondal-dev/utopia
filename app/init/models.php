<?php

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model\BaseList;
use Clarus\Utopia\Response\Model\Error;
use Clarus\Utopia\Response\Model\Health;
use Clarus\Utopia\Response\Model\Todo;

Response::setModel(new Error());
Response::setModel(new Health());
Response::setModel(new Todo());
Response::setModel(new BaseList('Todos List', Response::MODEL_TODO_LIST, 'todos', Response::MODEL_TODO));
