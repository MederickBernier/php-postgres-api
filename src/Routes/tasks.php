<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../Db.php';

return function (\Slim\App $app, callable $jsend): void {

    $app->get('/tasks', function (Request $req, Response $res) use ($jsend) {
        $pdo = Db::pdo();
        $q = $pdo->query('SELECT id, title, done, created_at, updated_at FROM tasks ORDER BY id');
        return $jsend($res, ['items' => $q->fetchAll()]);
    });

    $app->get('/tasks/{id:\d+}', function (Request $req, Response $res, array $args) use ($jsend) {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT id, title, done, created_at, updated_at FROM tasks WHERE id = ?');
        $st->execute([(int)$args['id']]);
        $row = $st->fetch();
        if (!$row) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, $row);
    });

    $app->post('/tasks', function (Request $req, Response $res) use ($jsend) {
        $body = (array)$req->getParsedBody();
        $title = trim((string)($body['title'] ?? ''));
        $done  = (bool)($body['done'] ?? false);
        if ($title === '') return $jsend($res, ['error' => 'title_required'], 400);

        $pdo = Db::pdo();
        $st = $pdo->prepare('INSERT INTO tasks(title, done) VALUES (?, ?) RETURNING id');
        $st->execute([$title, $done]);
        $id = (int)$st->fetchColumn();
        return $jsend($res, ['id' => $id, 'title' => $title, 'done' => $done], 201);
    });

    $app->put('/tasks/{id:\d+}', function (Request $req, Response $res, array $args) use ($jsend) {
        $id = (int)$args['id'];
        $body  = (array)$req->getParsedBody();
        $title = array_key_exists('title', $body) ? trim((string)$body['title']) : null;
        $done  = array_key_exists('done',  $body) ? (bool)$body['done'] : null;
        if ($title === null && $done === null) return $jsend($res, ['error' => 'nothing_to_update'], 400);

        $pdo = Db::pdo();
        $st = $pdo->prepare('UPDATE tasks SET title = COALESCE(?, title), done = COALESCE(?, done) WHERE id = ?');
        $st->execute([$title, $done, $id]);
        if ($st->rowCount() === 0) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, ['ok' => true]);
    });

    $app->delete('/tasks/{id:\d+}', function (Request $req, Response $res, array $args) use ($jsend) {
        $pdo = Db::pdo();
        $st = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
        $st->execute([(int)$args['id']]);
        if ($st->rowCount() === 0) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, ['ok' => true]);
    });
};
