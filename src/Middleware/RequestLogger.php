<?php

declare(strict_types=1);

use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestLogger implements MiddlewareInterface
{
    public function __construct(private Logger $log) {}

    public function process(Request $req, RequestHandlerInterface $h): Response
    {
        $rid = bin2hex(random_bytes(8));
        $start = microtime(true);

        $this->log->info('req', [
            'id' => $rid,
            'method' => $req->getMethod(),
            'path' => (string)$req->getUri()->getPath(),
            'query' => $req->getQueryParams(),
            'body' => $this->readJsonBody($req),
        ]);

        $res = $h->handle($req);
        $dur = (int)round((microtime(true) - $start) * 1000);

        $this->log->info('res', [
            'id' => $rid,
            'status' => $res->getStatusCode(),
            'duration_ms' => $dur,
        ]);

        return $res->withHeader('X-Request-Id', $rid)
            ->withHeader('X-Response-Time', (string)$dur);
    }

    private function readJsonBody(Request $req): array
    {
        $ct = $req->getHeaderLine('Content-Type');
        if (str_contains($ct, 'application/json')) {
            $b = $req->getParsedBody();
            return is_array($b) ? $b : [];
        }
        return [];
    }
}
