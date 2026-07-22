<?php
require __DIR__ . '/router.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/ratelimit.php';
require __DIR__ . '/models/companies.php';
require __DIR__ . '/models/stories.php';
require __DIR__ . '/models/votes.php';
require __DIR__ . '/models/comments.php';

function config(string $key)
{
    static $cfg;
    $cfg ??= require __DIR__ . '/config.php';
    return $cfg[$key] ?? null;
}

function db(): PDO
{
    static $pdo;
    $pdo ??= new PDO(config('db_dsn'), config('db_user'), config('db_pass'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): never { header('Location: ' . $path); exit; }

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function view(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $template . '.php';
    $content = ob_get_clean();
    ob_start();
    require __DIR__ . '/views/layout.php';
    return ob_get_clean();
}

date_default_timezone_set('UTC');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax',
    'secure' => config('env') === 'prod']);
session_start();

if (!function_exists('t')) { function t(string $k): string { return $k; } }
