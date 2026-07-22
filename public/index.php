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

$out = dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($out === null) { http_response_code(404); echo '404'; exit; }
echo $out;
