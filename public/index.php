<?php
require __DIR__ . '/../src/bootstrap.php';

route('GET', '/health', fn() => json_out(['ok' => true]));
route('GET', '/api/companies', fn() => json_out(['ok' => true,
    'companies' => company_search(db(), $_GET['q'] ?? '')]));
route('GET', '/', fn() => view('feed', ['title' => 'LinkOut', 'stories' => [],
    'tab' => 'new', 'rails' => ['trending' => [], 'liked' => [], 'rated' => []], 'page' => 1]));

route('GET', '/signup', fn() => view('auth/signup', ['title' => 'Sign up']));
route('POST', '/signup', function () {
    $r = signup(db(), $_POST['email'] ?? '', $_POST['password'] ?? '');
    if (!$r['ok']) return view('auth/signup', ['title' => 'Sign up', 'error' => t('err_' . $r['error'])]);
    send_mail(strtolower(trim($_POST['email'])), t('mail_confirm_subject'),
        t('mail_confirm_body') . "\n\n" . config('base_url') . '/confirm/' . $r['confirm_token']);
    return view('auth/login', ['title' => 'Log in', 'notice' => t('notice_check_email')]);
});
route('GET', '/confirm/{token}', fn($p) =>
    view('auth/confirm', ['title' => 'Confirm', 'confirmed' => confirm_email(db(), $p['token'])]));
route('GET', '/login', fn() => view('auth/login', ['title' => 'Log in']));
route('POST', '/login', function () {
    $u = attempt_login(db(), $_POST['email'] ?? '', $_POST['password'] ?? '');
    if (!$u) return view('auth/login', ['title' => 'Log in', 'error' => t('err_bad_credentials')]);
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    redirect('/');
});
route('GET', '/logout', function () { session_destroy(); redirect('/'); });

route('GET', '/post', function () {
    require_verified_user();
    return view('post', ['title' => 'Share your story', 'editing' => false, 'story' => null]);
});
route('POST', '/post', function () {
    $u = require_verified_user();
    $c = company_find_or_create(db(), $_POST['company_name'] ?? '',
        $_POST['company_domain'] ?? '', $u['id']);
    if (!$c['ok']) return view('post', ['title' => 'Share your story', 'editing' => false,
        'story' => null, 'error' => t('err_' . $c['error'])]);
    $r = story_create(db(), $u['id'], $c['company']['id'], $_POST);
    if (!$r['ok']) return view('post', ['title' => 'Share your story', 'editing' => false,
        'story' => null, 'error' => t('err_' . $r['error'])]);
    redirect('/story/' . $r['story_id']);
});
route('GET', '/story/{id}/edit', function ($p) {
    $u = require_verified_user();
    $s = story_get(db(), (int)$p['id']);
    if (!$s || !story_editable_by($s, $u)) redirect('/story/' . (int)$p['id']);
    return view('post', ['title' => 'Edit story', 'editing' => true, 'story' => $s]);
});
route('POST', '/story/{id}/edit', function ($p) {
    $u = require_verified_user();
    $s = story_get(db(), (int)$p['id']);
    if (!$s || !story_editable_by($s, $u)) redirect('/story/' . (int)$p['id']);
    if ($err = story_validate($_POST)) return view('post', ['title' => 'Edit story',
        'editing' => true, 'story' => $s, 'error' => t('err_' . $err)]);
    story_update(db(), $s['id'], $_POST);
    redirect('/story/' . $s['id']);
});
route('POST', '/story/{id}/delete', function ($p) {
    $u = require_verified_user();
    $s = story_get(db(), (int)$p['id']);
    if ($s && story_editable_by($s, $u)) story_delete(db(), $s['id']);
    redirect('/');
});

$out = dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($out === null) { http_response_code(404); echo '404'; exit; }
echo $out;
