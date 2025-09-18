<?php

declare(strict_types=1);

final class Db
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo) return self::$pdo;

        $dsn  = getenv('DB_DSN')  ?: 'pgsql:host=localhost;port=5432;dbname=app';
        $user = getenv('DB_USER') ?: 'app';
        $pass = getenv('DB_PASS') ?: 'app';

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        if (str_starts_with($dsn, 'pgsql:')) {
            $pdo->exec("SET client_encoding TO 'UTF8'");
        }
        return self::$pdo = $pdo;
    }
}
