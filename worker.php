<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Db.php';

while (true) {
    try {
        Db::pdo()->exec("INSERT INTO tasks(title) VALUES('tick " . date('c') . "')");
        echo "[worker] tick\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "[worker] db error: " . $e->getMessage() . "\n");
    }
    sleep(5);
}
