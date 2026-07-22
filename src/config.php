<?php
$config = [
    'db_dsn'    => 'mysql:host=127.0.0.1;dbname=linkout;charset=utf8mb4',
    'db_user'   => 'root',
    'db_pass'   => '',
    'base_url'  => 'http://localhost:8087',
    'mail_from' => 'LinkOut <no-reply@didudi.com>',
    'env'       => 'dev',
];
if (is_file(__DIR__ . '/../config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/../config.local.php');
}
return $config;
