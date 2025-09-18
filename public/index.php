<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// JSON helper
$jsend = static function (Response $res, array $data, int $status = 200): Response {
    $res->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
};

// base routes
$app->get('/', fn(Request $q, Response $r) => $jsend($r, ['status' => 'ok', 'service' => 'php-postgres-api']));
$app->get('/health', fn(Request $q, Response $r) => $jsend($r, ['status' => 'ok']));
$app->map(
    ['GET', 'POST', 'PUT', 'DELETE'],
    '/_echo',
    fn(Request $q, Response $r)
    => $jsend($r, ['method' => $q->getMethod(), 'query' => $q->getQueryParams(), 'body' => (array)$q->getParsedBody()])
);

// tasks routes
(require __DIR__ . '/../src/Routes/tasks.php')($app, $jsend);

$app->run();
