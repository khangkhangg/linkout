<?php

function normalize_domain(string $input): ?string
{
    $d = strtolower(trim($input));
    $d = preg_replace('#^[a-z]+://#', '', $d);      // scheme
    $d = preg_replace('#[/?\#].*$#', '', $d);        // path/query/fragment
    $d = preg_replace('/^www\./', '', $d);
    if ($d === '' || !str_contains($d, '.')) return null;
    if (!preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/', $d)) return null;
    return $d;
}

const FREEMAIL_DOMAINS = [
    'gmail.com','googlemail.com','yahoo.com','yahoo.com.vn','outlook.com',
    'hotmail.com','live.com','msn.com','icloud.com','me.com','proton.me',
    'protonmail.com','zoho.com','aol.com','mail.com','gmx.com','gmx.net',
    'yandex.com','yandex.ru','qq.com','163.com','126.com','tutanota.com',
    'fastmail.com','hey.com','pm.me','ymail.com','rocketmail.com','hotmail.co.uk',
    'hotmail.fr','hotmail.de','hotmail.it','hotmail.es','live.co.uk','live.fr',
    'live.de','outlook.com.vn','yahoo.co.uk','yahoo.fr','yahoo.de','yahoo.co.jp',
    'googlemail.co.uk','icloud.com.cn','mail.ru','zohomail.com','duck.com',
];

function is_freemail(string $domain): bool
{
    $domain = strtolower($domain);
    foreach (FREEMAIL_DOMAINS as $blocked) {
        if ($domain === $blocked || str_ends_with($domain, '.' . $blocked)) return true;
    }
    return false;
}

const HANDLE_ADJ = ['Quiet','Brave','Sly','Calm','Swift','Bold','Wry','Keen',
    'Lone','Free','Wild','Deft','True','Warm','Cool','Sharp','Plain','Late'];
const HANDLE_NOUN = ['Falcon','Otter','Lynx','Heron','Badger','Fox','Crane',
    'Wolf','Raven','Tiger','Sparrow','Moose','Gecko','Panda','Orca','Bison'];

function generate_handle(): string
{
    return HANDLE_ADJ[random_int(0, count(HANDLE_ADJ) - 1)]
         . HANDLE_NOUN[random_int(0, count(HANDLE_NOUN) - 1)]
         . random_int(1, 999);
}

function random_code(): string
{
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function time_ago(string $datetime): string
{
    $d = time() - strtotime($datetime);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 2592000) return floor($d / 86400) . 'd ago';
    return date('M Y', strtotime($datetime));
}
