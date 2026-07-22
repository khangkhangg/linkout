<?php

function i18n_set_lang(string $lang): void
{
    $GLOBALS['__lang'] = in_array($lang, ['en', 'vi'], true) ? $lang : 'en';
    $GLOBALS['__strings'] = null;
}

function current_lang(): string
{
    if (!empty($GLOBALS['__lang'])) return $GLOBALS['__lang'];
    $lang = $_COOKIE['lang']
        ?? (str_starts_with($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 'vi') ? 'vi' : 'en');
    i18n_set_lang($lang);
    return $GLOBALS['__lang'];
}

function t(string $key): string
{
    if (!isset($GLOBALS['__strings'])) {
        $lang = current_lang();
        $en = require __DIR__ . '/../lang/en.php';
        $GLOBALS['__strings'] = $lang === 'en'
            ? $en
            : array_merge($en, require __DIR__ . '/../lang/' . $lang . '.php');
    }
    return $GLOBALS['__strings'][$key] ?? $key;
}
