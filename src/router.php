<?php
$GLOBALS['__routes'] = [];

function router_reset(): void { $GLOBALS['__routes'] = []; }

function route(string $method, string $pattern, callable $handler): void
{
    $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . '$#';
    $GLOBALS['__routes'][] = [$method, $regex, $handler];
}

function dispatch(string $method, string $path)
{
    foreach ($GLOBALS['__routes'] as [$m, $regex, $handler]) {
        if ($m === $method && preg_match($regex, $path, $match)) {
            $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            return $handler($params);
        }
    }
    return null;
}
