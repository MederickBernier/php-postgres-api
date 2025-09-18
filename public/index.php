<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// JSON helper
$jsend = static function (Response $res, array $data, int $status = 200): Response {
    $res->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
};

// Routes
$app->get('/', function (Request $req, Response $res) use ($jsend): Response {
    return $jsend($res, ['status' => 'ok', 'service' => 'php-postgres-api']);
});

$app->get('/health', function (Request $req, Response $res) use ($jsend): Response {
    return $jsend($res, ['status' => 'ok']);
});

$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/_echo', function (Request $req, Response $res) use ($jsend): Response {
    return $jsend($res, [
        'method' => $req->getMethod(),
        'query'  => $req->getQueryParams(),
        'body'   => (array)$req->getParsedBody(),
    ]);
});

$app->run();
