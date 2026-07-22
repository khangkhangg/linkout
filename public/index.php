<?php
require __DIR__ . '/../src/bootstrap.php';

route('GET', '/health', fn() => json_out(['ok' => true]));
route('GET', '/', fn() => view('feed', ['title' => 'LinkOut', 'stories' => [],
    'tab' => 'new', 'rails' => ['trending' => [], 'liked' => [], 'rated' => []], 'page' => 1]));

$out = dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($out === null) { http_response_code(404); echo '404'; exit; }
echo $out;
