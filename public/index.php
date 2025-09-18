<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Middleware/RequestLogger.php';

$app = AppFactory::create();

// JSON helper
$jsend = static function (Response $res, array $data, int $status = 200): Response {
    $res->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
};

// Logger: stdout always; file only if writable
$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://stdout'));

$logPath = getenv('LOG_PATH') ?: __DIR__ . '/../logs/app.log';
try {
    @mkdir(dirname($logPath), 0777, true);
    if (is_writable(dirname($logPath))) {
        $logger->pushHandler(new StreamHandler($logPath));
    }
} catch (\Throwable $e) {
    // ignore file logging if not writable
}

// Middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new RequestLogger($logger));

// Error middleware with JSON renderer
$error = $app->addErrorMiddleware(true, true, true);
$error->setDefaultErrorHandler(function (Request $request, \Throwable $e, bool $display, bool $logErrors, bool $logDetails)
use ($jsend, $logger): Response {
    $logger->error('error', ['msg' => $e->getMessage(), 'type' => get_class($e)]);
    $payload = ['error' => get_class($e)];
    if ($display) {
        $payload += ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
    } else {
        $payload['message'] = 'internal_error';
    }
    return $jsend(new SlimResponse(), $payload, 500);
});

// Routes (unchanged core)
$app->get('/', fn(Request $q, Response $r) => $jsend($r, ['status' => 'ok', 'service' => 'php-postgres-api']));
$app->get('/health', fn(Request $q, Response $r) => $jsend($r, ['status' => 'ok']));
$app->map(
    ['GET', 'POST', 'PUT', 'DELETE'],
    '/_echo',
    fn(Request $q, Response $r)
    => $jsend($r, ['method' => $q->getMethod(), 'query' => $q->getQueryParams(), 'body' => (array)$q->getParsedBody()])
);
(require __DIR__ . '/../src/Routes/tasks.php')($app, $jsend);

$app->run();
