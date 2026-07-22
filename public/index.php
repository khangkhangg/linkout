<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/routes.php';

$out = dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($out === null) { http_response_code(404); echo '404'; exit; }
echo $out;
