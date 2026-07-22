<?php
date_default_timezone_set('UTC');
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/router.php';
require __DIR__ . '/../src/lib.php';
require __DIR__ . '/../src/i18n.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/ratelimit.php';
require __DIR__ . '/../src/models/companies.php';
require __DIR__ . '/../src/models/stories.php';
require __DIR__ . '/../src/models/votes.php';
require __DIR__ . '/../src/models/comments.php';
require __DIR__ . '/../src/models/reports.php';
require __DIR__ . '/../src/models/admin.php';

function test_db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=linkout_test;charset=utf8mb4',
            'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("SET time_zone = '+00:00'");
        // fresh schema every run
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) $pdo->exec("DROP TABLE `$t`");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        foreach (glob(__DIR__ . '/../migrations/*.sql') as $f) {
            $pdo->exec(file_get_contents($f));
        }
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
