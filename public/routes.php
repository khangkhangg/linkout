<?php
route('GET', '/health', fn() => json_out(['ok' => true]));
route('GET', '/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return "User-agent: *\n"
        . "Disallow: /admin\nDisallow: /api\nDisallow: /post\nDisallow: /login\n"
        . "Disallow: /signup\nDisallow: /reset\nDisallow: /confirm\nDisallow: /search\n"
        . "Sitemap: https://linkout.didudi.com/sitemap.xml\n";
});
route('GET', '/sitemap.xml', function () {
    header('Content-Type: application/xml; charset=utf-8');
    $base = 'https://linkout.didudi.com';
    $urls = [['loc' => $base . '/', 'freq' => 'hourly', 'pri' => '1.0']];
    foreach (db()->query("SELECT domain FROM companies ORDER BY name LIMIT 5000") as $c) {
        $urls[] = ['loc' => $base . '/company/' . rawurlencode($c['domain']), 'freq' => 'daily', 'pri' => '0.7'];
    }
    foreach (db()->query("SELECT id, created_at FROM stories WHERE status='active'
             ORDER BY created_at DESC LIMIT 20000") as $s) {
        $urls[] = ['loc' => $base . '/story/' . (int)$s['id'],
            'lastmod' => date('Y-m-d', strtotime($s['created_at'])), 'freq' => 'weekly', 'pri' => '0.8'];
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
         . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
             . (isset($u['lastmod']) ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
             . '<changefreq>' . $u['freq'] . '</changefreq>'
             . '<priority>' . $u['pri'] . '</priority></url>' . "\n";
    }
    return $xml . '</urlset>' . "\n";
});
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
    if (!send_mail($in['corp_email'], t('mail_report_code_subject'),
            t('mail_report_code_body') . ' ' . $r['code'])) {
        return json_out(['ok' => false, 'error' => t('err_mail_failed')], 502);
    }
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
        'meta_description' => 'LinkOut is where people who left their jobs tell the real story — anonymous exit reviews rating companies on leadership, culture, pay, work-life balance, growth and how departures are handled. Read honest reviews or share yours.',
        'noindex' => $page > 1,
        'stories' => feed_stories(db(), $tab, $page, 20, $viewer['id'] ?? null),
        'rails' => ['trending' => rail_trending(db()), 'liked' => rail_most_liked(db()),
                    'rated' => rail_top_companies(db())],
    ]);
});

route('GET', '/signup', fn() => view('auth/signup', ['title' => 'Sign up']));
route('POST', '/signup', function () {
    $r = signup(db(), $_POST['email'] ?? '', $_POST['password'] ?? '');
    if (!$r['ok']) return view('auth/signup', ['title' => 'Sign up', 'error' => t('err_' . $r['error'])]);
    $sent = send_mail(strtolower(trim($_POST['email'])), t('mail_confirm_subject'),
        t('mail_confirm_body') . "\n\n" . config('base_url') . '/confirm/' . $r['confirm_token']);
    if (!$sent) return view('auth/signup', ['title' => 'Sign up', 'error' => t('err_mail_failed')]);
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
route('POST', '/logout', function () { session_destroy(); redirect('/'); });

route('GET', '/reset', fn() => view('auth/reset_request', ['title' => 'Reset password']));
route('POST', '/reset', function () {
    $token = reset_start(db(), $_POST['email'] ?? '');
    if ($token) {
        $sent = send_mail(strtolower(trim($_POST['email'])), t('mail_reset_subject'),
            t('mail_reset_body') . "\n\n" . config('base_url') . '/reset/' . $token);
        if (!$sent) {
            return view('auth/reset_request', ['title' => 'Reset password',
                'error' => t('err_mail_failed')]);
        }
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
    if (empty($_POST['attest'])) return view('post', ['title' => 'Share your story',
        'editing' => false, 'story' => null, 'error' => t('err_attest_required')]);
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
    $similar = $s['status'] === 'active'
        ? similar_stories(db(), (int)$s['company_id'], (int)$s['id']) : [];
    $excerpt = trim(preg_replace('/\s+/', ' ', mb_substr($s['body'], 0, 300)));
    $overall = round(array_sum(array_map(fn($k) => (int)$s[$k], RATING_KEYS)) / 6, 1);
    $jsonLd = null;
    if ($s['status'] === 'active') {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $s['title'],
            'reviewBody' => $excerpt,
            'datePublished' => date('c', strtotime($s['created_at'])),
            'author' => ['@type' => 'Person', 'name' => $s['handle']],
            'itemReviewed' => ['@type' => 'Organization', 'name' => $s['company_name'],
                'url' => 'https://' . $s['domain']],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $overall,
                'bestRating' => 5, 'worstRating' => 1],
        ];
    }
    return view('story', ['title' => $s['title'], 'story' => $s, 'my_vote' => $mv,
        'comments' => $s['status'] === 'active' ? comments_for_story(db(), $s['id']) : [],
        'similar' => $similar,
        'trending' => $s['status'] === 'active' ? rail_trending(db()) : [],
        'meta_description' => $s['company_name'] . ' — ' . $excerpt,
        'og_type' => 'article',
        'noindex' => $s['status'] !== 'active',
        'json_ld' => $jsonLd]);
});
route('POST', '/story/{id}/comment', function ($p) {
    $u = require_verified_user();
    $id = (int)$p['id'];
    $r = comment_add(db(), $u['id'], $id, $_POST['body'] ?? '');
    if (!$r['ok']) redirect('/story/' . $id . '?cerr=' . urlencode($r['error']) . '#comments');
    redirect('/story/' . $id . '#comments');
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

// Every admin GET view gets $nav_pending for the Reports badge.
function admin_view(string $tpl, array $data): string
{
    $data['nav_pending'] = (int) db()->query("SELECT COUNT(*) FROM reports
        WHERE status='pending' AND verified_at IS NOT NULL")->fetchColumn();
    $data['title'] = $data['title'] ?? 'Admin';
    return view($tpl, $data);
}

route('GET', '/admin', function () {
    require_admin();
    return admin_view('admin/dashboard', ['title' => 'Admin', 'stats' => admin_stats(db()),
        'actions' => admin_recent_actions(db(), 12)]);
});
route('GET', '/admin/queue', function () {
    require_admin();
    return admin_view('admin/queue', ['title' => 'Reports', 'queue' => admin_queue(db())]);
});
route('POST', '/admin/action', function () {
    $a = require_admin();
    $sid = (int)($_POST['story_id'] ?? 0);
    $rid = (int)($_POST['report_id'] ?? 0);
    switch ($_POST['do'] ?? '') {
        case 'restore': admin_restore_story(db(), $sid);
            log_admin_action(db(), $a['id'], 'restore_story', 'story', $sid); break;
        case 'remove': admin_remove_story(db(), $sid);
            log_admin_action(db(), $a['id'], 'remove_story', 'story', $sid); break;
        case 'dismiss': admin_dismiss_report(db(), $rid);
            log_admin_action(db(), $a['id'], 'dismiss_report', 'report', $rid); break;
    }
    redirect('/admin/queue');
});
route('GET', '/admin/clusters', function () {
    require_admin();
    return admin_view('admin/clusters', ['title' => 'Coordinated', 'clusters' => admin_report_clusters(db())]);
});
route('GET', '/admin/stories', function () {
    require_admin();
    $status = $_GET['status'] ?? '';
    $q = $_GET['q'] ?? '';
    return admin_view('admin/stories', ['title' => 'Stories', 'status' => $status, 'q' => $q,
        'stories' => admin_stories(db(), $status, $q)]);
});
route('POST', '/admin/story', function () {
    $a = require_admin();
    $sid = (int)($_POST['story_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    admin_set_story_status(db(), $sid, $status);
    log_admin_action(db(), $a['id'], 'set_story_' . $status, 'story', $sid);
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/stories');
});
route('GET', '/admin/comments', function () {
    require_admin();
    return admin_view('admin/comments', ['title' => 'Comments', 'comments' => admin_recent_comments(db())]);
});
route('POST', '/admin/comment', function () {
    $a = require_admin();
    $cid = (int)($_POST['comment_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    admin_set_comment_status(db(), $cid, $status);
    log_admin_action(db(), $a['id'], 'set_comment_' . $status, 'comment', $cid);
    redirect('/admin/comments');
});
route('GET', '/admin/users', function () {
    require_admin();
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $st = db()->prepare('SELECT * FROM users WHERE handle LIKE ? OR email LIKE ?
            ORDER BY id DESC LIMIT 200');
        $st->execute(["%$q%", "%$q%"]);
        $users = $st->fetchAll();
    } else {
        $users = db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
    }
    return admin_view('admin/users', ['title' => 'Users', 'q' => $q, 'users' => $users]);
});
route('GET', '/admin/user/{id}', function ($p) {
    require_admin();
    $detail = admin_user_detail(db(), (int)$p['id']);
    if (!$detail) { http_response_code(404); return '404'; }
    return admin_view('admin/user_detail', ['title' => $detail['user']['handle'], 'detail' => $detail]);
});
route('POST', '/admin/ban', function () {
    $a = require_admin();
    $uid = (int)($_POST['user_id'] ?? 0);
    $banned = !empty($_POST['banned']);
    admin_set_ban(db(), $uid, $banned);
    log_admin_action(db(), $a['id'], $banned ? 'ban_user' : 'unban_user', 'user', $uid);
    $ret = $_POST['return'] ?? '/admin/users';
    redirect(str_starts_with($ret, '/admin') ? $ret : '/admin/users');
});
route('POST', '/admin/role', function () {
    $a = require_admin();
    $uid = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if (admin_set_role(db(), $uid, $role, (int)$a['id'])) {
        log_admin_action(db(), $a['id'], 'set_role_' . $role, 'user', $uid);
    }
    redirect('/admin/user/' . $uid);
});
route('GET', '/admin/companies', function () {
    require_admin();
    return admin_view('admin/companies', ['title' => 'Companies', 'error' => $_GET['err'] ?? '',
        'companies' => db()->query("SELECT c.*,
            (SELECT COUNT(*) FROM stories s WHERE s.company_id = c.id) AS story_count
            FROM companies c ORDER BY c.name LIMIT 500")->fetchAll()]);
});
route('POST', '/admin/company', function () {
    $a = require_admin();
    $cid = (int)($_POST['company_id'] ?? 0);
    switch ($_POST['do'] ?? '') {
        case 'rename':
            company_rename(db(), $cid, $_POST['name'] ?? '');
            log_admin_action(db(), $a['id'], 'rename_company', 'company', $cid, $_POST['name'] ?? '');
            break;
        case 'merge':
            $into = company_by_domain(db(), normalize_domain($_POST['merge_into_domain'] ?? '') ?? '');
            if ($into) { company_merge(db(), $cid, (int)$into['id']);
                log_admin_action(db(), $a['id'], 'merge_company', 'company', $cid, 'into ' . $into['domain']); }
            break;
        case 'delete':
            if (admin_delete_company(db(), $cid)) {
                log_admin_action(db(), $a['id'], 'delete_company', 'company', $cid);
            } else { redirect('/admin/companies?err=' . urlencode('Company still has stories.')); }
            break;
    }
    redirect('/admin/companies');
});
route('GET', '/admin/blocklist', function () {
    require_admin();
    return admin_view('admin/blocklist', ['title' => 'Blocklist', 'domains' => blocked_domains_list(db())]);
});
route('POST', '/admin/blocklist', function () {
    $a = require_admin();
    $domain = $_POST['domain'] ?? '';
    if (($_POST['do'] ?? '') === 'add') {
        if ($norm = blocked_domain_add(db(), $domain, (int)$a['id'])) {
            log_admin_action(db(), $a['id'], 'block_domain', 'domain', null, $norm);
        }
    } elseif (($_POST['do'] ?? '') === 'remove') {
        blocked_domain_remove(db(), $domain);
        log_admin_action(db(), $a['id'], 'unblock_domain', 'domain', null, $domain);
    }
    redirect('/admin/blocklist');
});
route('GET', '/admin/settings', function () {
    require_admin();
    return admin_view('admin/settings', ['title' => 'Settings',
        'report_threshold' => setting_get(db(), 'report_threshold', '3'),
        'announcement' => setting_get(db(), 'announcement', ''),
        'saved' => !empty($_GET['saved']), 'purged' => isset($_GET['purged']) ? (int)$_GET['purged'] : null]);
});
route('POST', '/admin/settings', function () {
    $a = require_admin();
    switch ($_POST['do'] ?? '') {
        case 'threshold':
            $n = max(1, min(20, (int)($_POST['report_threshold'] ?? 3)));
            setting_set(db(), 'report_threshold', (string)$n);
            log_admin_action(db(), $a['id'], 'set_threshold', 'setting', null, (string)$n);
            redirect('/admin/settings?saved=1');
        case 'announcement':
            setting_set(db(), 'announcement', trim($_POST['announcement'] ?? ''));
            log_admin_action(db(), $a['id'], 'set_announcement', 'setting', null,
                trim($_POST['announcement'] ?? '') === '' ? 'cleared' : 'set');
            redirect('/admin/settings?saved=1');
        case 'purge_emails':
            $n = admin_purge_resolved_emails(db());
            log_admin_action(db(), $a['id'], 'purge_emails', null, null, "$n reports");
            redirect('/admin/settings?purged=' . $n);
    }
    redirect('/admin/settings');
});
route('GET', '/admin/log', function () {
    require_admin();
    return admin_view('admin/log', ['title' => 'Audit log', 'actions' => admin_recent_actions(db(), 200)]);
});
