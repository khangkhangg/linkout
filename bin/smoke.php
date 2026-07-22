#!/usr/bin/env php
<?php
// Usage: php bin/smoke.php  (run against the dev DB; exits non-zero on failure)
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
require __DIR__ . '/../src/bootstrap.php';

$checks = [
    ['GET', '/health', 200],
    ['GET', '/', 200],
    ['GET', '/?tab=trending', 200],
    ['GET', '/?tab=top', 200],
    ['GET', '/search?q=test', 200],
    ['GET', '/signup', 200],
    ['GET', '/login', 200],
    ['GET', '/reset', 200],
    ['GET', '/post', 302],          // not logged in -> redirect
    ['GET', '/admin', 404],         // not admin -> hidden
    ['GET', '/story/999999', 404],
    ['GET', '/company/no-such-domain.com', 404],
    ['GET', '/nope', 404],
];

// register routes exactly as the front controller does
ob_start();
$GLOBALS['__smoke'] = true;
require __DIR__ . '/../public/routes.php';
ob_end_clean();

$fail = 0;
$lines = '';
foreach ($checks as [$method, $url, $want]) {
    $_GET = [];
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $_GET);
    http_response_code(200);
    $got = 200;
    try {
        ob_start();
        $out = dispatch($method, parse_url($url, PHP_URL_PATH));
        ob_end_clean();
        $got = $out === null ? 404 : http_response_code();
    } catch (RedirectException $e) {
        ob_end_clean();
        $got = http_response_code() === 200 ? 302 : http_response_code();
    } catch (JsonOutException $e) {
        // json_out() also exits like redirect() in real request handling;
        // guarded the same way in smoke mode (see src/bootstrap.php).
        ob_end_clean();
        $got = http_response_code();
    }
    $mark = $got === $want ? 'ok  ' : 'FAIL';
    if ($got !== $want) $fail++;
    // Buffer output rather than echo per-iteration: an un-buffered echo here
    // would count as body output, and PHP then silently refuses every later
    // http_response_code() call for the rest of the run ("headers already
    // sent"), freezing $got for all subsequent checks.
    $lines .= "$mark $method $url -> $got (want $want)\n";
}
echo $lines;
exit($fail ? 1 : 0);
