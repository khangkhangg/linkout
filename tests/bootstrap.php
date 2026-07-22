<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/router.php';
require __DIR__ . '/../src/lib.php';

function test_db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=linkout_test;charset=utf8mb4',
            'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        // fresh schema every run
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) $pdo->exec("DROP TABLE `$t`");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo->exec(file_get_contents(__DIR__ . '/../migrations/001_init.sql'));
    }
    return $pdo;
}

function seed_user(PDO $pdo, array $o = []): int
{
    static $n = 0; $n++;
    $pdo->prepare('INSERT INTO users (email, password_hash, handle, email_verified_at, role)
        VALUES (?,?,?,?,?)')->execute([
        $o['email'] ?? "user$n@example.com",
        password_hash('secret123', PASSWORD_BCRYPT),
        $o['handle'] ?? "TestUser$n",
        $o['email_verified_at'] ?? date('Y-m-d H:i:s'),
        $o['role'] ?? 'user',
    ]);
    return (int)$pdo->lastInsertId();
}
