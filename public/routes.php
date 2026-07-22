<?php
route('GET', '/health', fn() => json_out(['ok' => true]));
route('GET', '/lang/{code}', function ($p) {
    setcookie('lang', in_array($p['code'], ['en', 'vi'], true) ? $p['code'] : 'en',
        time() + 86400 * 365, '/');
    $ref = parse_url($_SERVER['HTTP_REFERER'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!str_starts_with($ref, '/') || str_starts_with($ref, '//')) $ref = '/';
    redirect($ref);
});
route('GET', '/api/companies', fn() => json_out(['ok' => true,
    'companies' => company_search(db(), $_GET['q'] ?? '')]));
route('POST', '/api/vote', function () {
    $u = require_verified_user_json();
    $in = json_decode(file_get_contents('php://input'), true) ?? [];
    $r = cast_vote(db(), $u['id'], (int)($in['story_id'] ?? 0), (int)($in['value'] ?? 0));
    if (!$r['ok']) return json_out(['ok' => false, 'error' => t('err_' . $r['error'])], 400);
    return json_out($r);
});
route('POST', '/api/report/start', function () {
    $u = require_verified_user_json();
    $in = json_decode(file_get_contents('php://input'), true) ?? [];
    $r = start_report(db(), $u['id'], (int)($in['story_id'] ?? 0),
        $in['reason'] ?? '', trim($in['reason_text'] ?? '') ?: null, $in['corp_email'] ?? '');
    if (!$r['ok']) return json_out(['ok' => false, 'error' => t('err_' . $r['error'])], 400);
    send_mail($in['corp_email'], t('mail_report_code_subject'),
        t('mail_report_code_body') . ' ' . $r['code']);
    return json_out(['ok' => true, 'report_id' => $r['report_id']]);
});
route('POST', '/api/report/verify', function () {
    $u = require_verified_user_json();
    $in = json_decode(file_get_contents('php://input'), true) ?? [];
    $r = verify_report(db(), (int)($in['report_id'] ?? 0), $u['id'],
        (string)($in['code'] ?? ''));
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

route('GET', '/reset', fn() => view('auth/reset_request', ['title' => 'Reset password']));
route('POST', '/reset', function () {
    $token = reset_start(db(), $_POST['email'] ?? '');
    if ($token) {
        send_mail(strtolower(trim($_POST['email'])), t('mail_reset_subject'),
            t('mail_reset_body') . "\n\n" . config('base_url') . '/reset/' . $token);
    }
    return view('auth/reset_request', ['title' => 'Reset password',
        'notice' => t('notice_reset_sent')]);   // same notice either way — no account probing
});
route('GET', '/reset/{token}', fn($p) =>
    view('auth/reset_form', ['title' => 'Reset password', 'token' => $p['token']]));
route('POST', '/reset/{token}', function ($p) {
    if (!reset_finish(db(), $p['token'], $_POST['password'] ?? '')) {
        return view('auth/reset_form', ['title' => 'Reset password', 'token' => $p['token'],
            'error' => t('err_reset_invalid')]);
    }
    return view('auth/login', ['title' => 'Log in', 'notice' => t('notice_password_updated')]);
});

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

route('GET', '/admin', function () {
    require_admin();
    return view('admin/queue', ['title' => 'Admin', 'queue' => admin_queue(db())]);
});
route('POST', '/admin/action', function () {
    require_admin();
    $sid = (int)($_POST['story_id'] ?? 0);
    match ($_POST['do'] ?? '') {
        'restore' => admin_restore_story(db(), $sid),
        'remove'  => admin_remove_story(db(), $sid),
        'dismiss' => admin_dismiss_report(db(), (int)($_POST['report_id'] ?? 0)),
        default   => null,
    };
    redirect('/admin');
});
route('GET', '/admin/users', function () {
    require_admin();
    return view('admin/users', ['title' => 'Users',
        'users' => db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 200')->fetchAll()]);
});
route('POST', '/admin/ban', function () {
    require_admin();
    admin_set_ban(db(), (int)($_POST['user_id'] ?? 0), !empty($_POST['banned']));
    redirect('/admin/users');
});
route('GET', '/admin/companies', function () {
    require_admin();
    return view('admin/companies', ['title' => 'Companies',
        'companies' => db()->query('SELECT * FROM companies ORDER BY name LIMIT 500')->fetchAll()]);
});
route('POST', '/admin/company', function () {
    require_admin();
    $cid = (int)($_POST['company_id'] ?? 0);
    if (($_POST['do'] ?? '') === 'rename') {
        company_rename(db(), $cid, $_POST['name'] ?? '');
    } elseif (($_POST['do'] ?? '') === 'merge') {
        $into = company_by_domain(db(), normalize_domain($_POST['merge_into_domain'] ?? '') ?? '');
        if ($into) company_merge(db(), $cid, (int)$into['id']);
    }
    redirect('/admin/companies');
});
