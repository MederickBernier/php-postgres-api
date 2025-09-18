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
        $st->bindValue(1, (int)$args['id'], \PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        if (!$row) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, $row);
    });

    $app->post('/tasks', function (Request $req, Response $res) use ($jsend) {
        $body  = (array)$req->getParsedBody();
        $title = trim((string)($body['title'] ?? ''));
        if ($title === '') return $jsend($res, ['error' => 'title_required'], 400);

        // Accept true/false/1/0/"true"/"false"
        $doneV = filter_var($body['done'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $done  = (bool)($doneV ?? false);

        $pdo = Db::pdo();
        $st = $pdo->prepare('INSERT INTO tasks(title, done) VALUES (?, ?) RETURNING id');
        $st->bindValue(1, $title, \PDO::PARAM_STR);
        $st->bindValue(2, $done,  \PDO::PARAM_BOOL);
        $st->execute();
        $id = (int)$st->fetchColumn();
        return $jsend($res, ['id' => $id, 'title' => $title, 'done' => $done], 201);
    });

    $app->put('/tasks/{id:\d+}', function (Request $req, Response $res, array $args) use ($jsend) {
        $id = (int)$args['id'];
        $body  = (array)$req->getParsedBody();
        $title = array_key_exists('title', $body) ? trim((string)$body['title']) : null;
        $doneV = array_key_exists('done',  $body) ? filter_var($body['done'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        if ($title === null && $doneV === null) {
            return $jsend($res, ['error' => 'nothing_to_update'], 400);
        }

        $pdo = Db::pdo();
        $st = $pdo->prepare('UPDATE tasks SET title = COALESCE(?, title), done = COALESCE(?, done) WHERE id = ?');

        // bind title
        if ($title === null) $st->bindValue(1, null, \PDO::PARAM_NULL);
        else                 $st->bindValue(1, $title, \PDO::PARAM_STR);

        // bind done
        if ($doneV === null) $st->bindValue(2, null, \PDO::PARAM_NULL);
        else                 $st->bindValue(2, (bool)$doneV, \PDO::PARAM_BOOL);

        $st->bindValue(3, $id, \PDO::PARAM_INT);
        $st->execute();

        if ($st->rowCount() === 0) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, ['ok' => true]);
    });

    $app->delete('/tasks/{id:\d+}', function (Request $req, Response $res, array $args) use ($jsend) {
        $pdo = Db::pdo();
        $st = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
        $st->bindValue(1, (int)$args['id'], \PDO::PARAM_INT);
        $st->execute();
        if ($st->rowCount() === 0) return $jsend($res, ['error' => 'not_found'], 404);
        return $jsend($res, ['ok' => true]);
    });
};
