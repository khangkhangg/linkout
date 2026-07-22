<?php
require __DIR__ . '/../src/bootstrap.php';

route('GET', '/health', fn() => json_out(['ok' => true]));
route('GET', '/api/companies', fn() => json_out(['ok' => true,
    'companies' => company_search(db(), $_GET['q'] ?? '')]));
route('POST', '/api/vote', function () {
    $u = require_verified_user_json();
    $in = json_decode(file_get_contents('php://input'), true) ?? [];
    $r = cast_vote(db(), $u['id'], (int)($in['story_id'] ?? 0), (int)($in['value'] ?? 0));
    if (!$r['ok']) return json_out(['ok' => false, 'error' => t('err_' . $r['error'])], 400);
    return json_out($r);
});
route('GET', '/', function () {
    $tab = in_array($_GET['tab'] ?? 'new', ['new', 'trending', 'top'], true)
        ? ($_GET['tab'] ?? 'new') : 'new';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $viewer = current_user();
    return view('feed', [
        'title' => 'LinkOut', 'tab' => $tab, 'page' => $page,
        'stories' => feed_stories(db(), $tab, $page, 20, $viewer['id'] ?? null),
        'rails' => ['trending' => rail_trending(db()), 'liked' => rail_most_liked(db()),
                    'rated' => rail_top_companies(db())],
    ]);
});

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

route('GET', '/story/{id}', function ($p) {
    $s = story_get(db(), (int)$p['id']);
    if (!$s || $s['status'] === 'removed') { http_response_code(404); return '404'; }
    $u = current_user();
    $mv = 0;
    if ($u) {
        $st = db()->prepare('SELECT value FROM votes WHERE user_id = ? AND story_id = ?');
        $st->execute([$u['id'], $s['id']]);
        $mv = (int)($st->fetchColumn() ?: 0);
    }
    return view('story', ['title' => $s['title'], 'story' => $s, 'my_vote' => $mv,
        'comments' => $s['status'] === 'active' ? comments_for_story(db(), $s['id']) : []]);
});
route('POST', '/story/{id}/comment', function ($p) {
    $u = require_verified_user();
    comment_add(db(), $u['id'], (int)$p['id'], $_POST['body'] ?? '');
    redirect('/story/' . (int)$p['id'] . '#comments');
});
route('POST', '/comment/{id}/delete', function ($p) {
    $u = require_verified_user();
    $st = db()->prepare('SELECT story_id FROM comments WHERE id = ?');
    $st->execute([(int)$p['id']]);
    $sid = (int)($st->fetchColumn() ?: 0);
    comment_delete(db(), (int)$p['id'], $u['id']);
    redirect($sid ? "/story/$sid#comments" : '/');
});

route('GET', '/company/{domain}', function ($p) {
    $c = company_by_domain(db(), strtolower($p['domain']));
    if (!$c) { http_response_code(404); return '404'; }
    $viewer = current_user();
    return view('company', ['title' => $c['name'], 'company' => $c,
        'agg' => company_aggregates(db(), $c['id']),
        'stories' => stories_for_company(db(), $c['id'], $viewer['id'] ?? null)]);
});
route('GET', '/search', function () {
    $q = trim($_GET['q'] ?? '');
    return view('search', ['title' => 'Search', 'q' => $q,
        'companies' => company_search(db(), $q),
        'stories' => story_search(db(), $q)]);
});

$out = dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($out === null) { http_response_code(404); echo '404'; exit; }
echo $out;
