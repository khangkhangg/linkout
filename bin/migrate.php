#!/usr/bin/env php
<?php
$dbName = 'linkout';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--db=')) $dbName = substr($arg, 5);
}
require __DIR__ . '/../src/bootstrap.php';
$pdo = new PDO(
    preg_replace('/dbname=\w+/', "dbname=$dbName", config('db_dsn')),
    config('db_user'), config('db_pass'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET time_zone = '+00:00'");
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    name VARCHAR(190) PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
$done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
foreach (glob(__DIR__ . '/../migrations/*.sql') as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) continue;
    echo "applying $name\n";
    $pdo->exec(file_get_contents($file));
    $pdo->prepare('INSERT INTO migrations (name) VALUES (?)')->execute([$name]);
}
echo "up to date\n";
