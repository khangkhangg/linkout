<?php
// Admin-panel data layer: dashboard stats, audit log, proactive story/comment
// moderation, coordinated-report detection, email blocklist, settings, and
// user/company management. All mutating helpers are logged via log_admin_action
// at the route layer (which holds the acting admin's id).

/* ---------- audit log ---------- */

function log_admin_action(PDO $pdo, int $adminId, string $action,
                          ?string $targetType = null, ?int $targetId = null,
                          ?string $detail = null): void
{
    $pdo->prepare('INSERT INTO admin_actions (admin_id, action, target_type, target_id, detail)
        VALUES (?,?,?,?,?)')->execute([$adminId, $action, $targetType, $targetId,
        $detail !== null ? mb_substr($detail, 0, 255) : null]);
}

function admin_recent_actions(PDO $pdo, int $n = 50): array
{
    return $pdo->query("SELECT a.*, u.handle AS admin_handle
        FROM admin_actions a JOIN users u ON u.id = a.admin_id
        ORDER BY a.created_at DESC LIMIT $n")->fetchAll();
}

/* ---------- dashboard stats ---------- */

function admin_stats(PDO $pdo): array
{
    $one = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
    return [
        'pending_reports'  => $one("SELECT COUNT(*) FROM reports WHERE status='pending' AND verified_at IS NOT NULL"),
        'auto_hidden'      => $one("SELECT COUNT(*) FROM stories WHERE status='auto_hidden'"),
        'stories_total'    => $one("SELECT COUNT(*) FROM stories WHERE status='active'"),
        'stories_today'    => $one("SELECT COUNT(*) FROM stories WHERE created_at > NOW() - INTERVAL 1 DAY"),
        'stories_week'     => $one("SELECT COUNT(*) FROM stories WHERE created_at > NOW() - INTERVAL 7 DAY"),
        'comments_today'   => $one("SELECT COUNT(*) FROM comments WHERE status='active' AND created_at > NOW() - INTERVAL 1 DAY"),
        'companies_total'  => $one("SELECT COUNT(*) FROM companies"),
        'users_total'      => $one("SELECT COUNT(*) FROM users"),
        'users_today'      => $one("SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL 1 DAY"),
        'banned_users'     => $one("SELECT COUNT(*) FROM users WHERE banned_at IS NOT NULL"),
    ];
}

/* ---------- browse & moderate all stories ---------- */

function admin_stories(PDO $pdo, string $status = '', string $q = '', int $limit = 50): array
{
    $where = [];
    $args = [];
    if (in_array($status, ['active', 'auto_hidden', 'removed'], true)) {
        $where[] = 's.status = ?';
        $args[] = $status;
    }
    if (trim($q) !== '') {
        $where[] = '(s.title LIKE ? OR c.name LIKE ?)';
        $args[] = '%' . trim($q) . '%';
        $args[] = '%' . trim($q) . '%';
    }
    $clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $st = $pdo->prepare("SELECT s.id, s.title, s.status, s.vote_score, s.created_at,
            c.name AS company_name, c.domain, u.handle,
            (SELECT COUNT(*) FROM reports r WHERE r.story_id=s.id AND r.status='pending' AND r.verified_at IS NOT NULL) AS report_count
        FROM stories s
        JOIN companies c ON c.id = s.company_id
        JOIN users u ON u.id = s.user_id
        $clause
        ORDER BY s.created_at DESC
        LIMIT " . (int)$limit);
    $st->execute($args);
    return $st->fetchAll();
}

function admin_set_story_status(PDO $pdo, int $storyId, string $status): void
{
    if (!in_array($status, ['active', 'auto_hidden', 'removed'], true)) return;
    $pdo->prepare('UPDATE stories SET status = ? WHERE id = ?')->execute([$status, $storyId]);
}

/* ---------- comment moderation ---------- */

function admin_recent_comments(PDO $pdo, int $limit = 60): array
{
    return $pdo->query("SELECT cm.id, cm.body, cm.status, cm.created_at,
            u.handle, s.id AS story_id, s.title AS story_title
        FROM comments cm
        JOIN users u ON u.id = cm.user_id
        JOIN stories s ON s.id = cm.story_id
        ORDER BY cm.created_at DESC LIMIT $limit")->fetchAll();
}

function admin_set_comment_status(PDO $pdo, int $commentId, string $status): void
{
    if (!in_array($status, ['active', 'removed'], true)) return;
    $pdo->prepare('UPDATE comments SET status = ? WHERE id = ?')->execute([$status, $commentId]);
}

/* ---------- coordinated-report detection ---------- */

function admin_report_clusters(PDO $pdo, int $limit = 30): array
{
    // Stories carrying multiple verified reports, plus how many distinct corp
    // domains and how tight the report window is — a proxy for coordination.
    return $pdo->query("SELECT s.id AS story_id, s.title, s.status,
            COUNT(*) AS report_count,
            COUNT(DISTINCT r.corp_domain) AS distinct_domains,
            TIMESTAMPDIFF(MINUTE, MIN(r.verified_at), MAX(r.verified_at)) AS span_minutes,
            MAX(r.verified_at) AS last_report
        FROM reports r
        JOIN stories s ON s.id = r.story_id
        WHERE r.verified_at IS NOT NULL AND r.status IN ('pending','actioned')
        GROUP BY s.id, s.title, s.status
        HAVING report_count >= 2
        ORDER BY report_count DESC, last_report DESC
        LIMIT $limit")->fetchAll();
}

/* ---------- email blocklist ---------- */

function domain_blocked(PDO $pdo, string $domain): bool
{
    $domain = strtolower(trim($domain));
    if ($domain === '') return false;
    $st = $pdo->prepare("SELECT 1 FROM blocked_domains
        WHERE domain = ? OR ? LIKE CONCAT('%.', domain) LIMIT 1");
    $st->execute([$domain, $domain]);
    return (bool)$st->fetchColumn();
}

function blocked_domains_list(PDO $pdo): array
{
    return $pdo->query("SELECT domain, created_at FROM blocked_domains ORDER BY domain")->fetchAll();
}

function blocked_domain_add(PDO $pdo, string $domain, int $adminId): ?string
{
    // Reject anything that isn't a clean domain — a raw fallback could store LIKE
    // wildcards (e.g. "%.com") that domain_blocked() would then over-match.
    $norm = normalize_domain($domain);
    if ($norm === null) return null;
    $pdo->prepare('INSERT IGNORE INTO blocked_domains (domain, added_by) VALUES (?,?)')
        ->execute([$norm, $adminId]);
    return $norm;
}

function blocked_domain_remove(PDO $pdo, string $domain): void
{
    $pdo->prepare('DELETE FROM blocked_domains WHERE domain = ?')->execute([strtolower(trim($domain))]);
}

/* ---------- settings ---------- */

function setting_get(PDO $pdo, string $key, string $default = ''): string
{
    $st = $pdo->prepare('SELECT v FROM settings WHERE k = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : (string)$v;
}

function setting_set(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare('INSERT INTO settings (k, v) VALUES (?,?)
        ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$key, $value]);
}

// Validate analytics/ads IDs to strict charsets so only known-safe tokens ever
// reach the page markup (we build the official snippets ourselves). '' = clear.
function valid_ga_id(string $id): bool
{
    return $id === '' || (bool) preg_match('/^(G|GT|AW|UA)-[A-Z0-9-]{4,20}$/', $id);
}

function valid_adsense_client(string $id): bool
{
    return $id === '' || (bool) preg_match('/^ca-pub-[0-9]{10,20}$/', $id);
}

/* ---------- user detail & roles ---------- */

function admin_user_detail(PDO $pdo, int $userId): ?array
{
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$userId]);
    $u = $st->fetch();
    if (!$u) return null;
    $stories = $pdo->prepare("SELECT s.id, s.title, s.status, s.created_at, c.name AS company_name
        FROM stories s JOIN companies c ON c.id = s.company_id
        WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 100");
    $stories->execute([$userId]);
    $comments = $pdo->prepare("SELECT cm.id, cm.body, cm.status, cm.created_at, cm.story_id
        FROM comments cm WHERE cm.user_id = ? ORDER BY cm.created_at DESC LIMIT 100");
    $comments->execute([$userId]);
    $counts = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM votes WHERE user_id = :u) AS votes,
            (SELECT COUNT(*) FROM reports WHERE reporter_user_id = :u) AS reports_filed");
    $counts->execute(['u' => $userId]);
    return [
        'user' => $u,
        'stories' => $stories->fetchAll(),
        'comments' => $comments->fetchAll(),
        'counts' => $counts->fetch(),
    ];
}

function admin_set_role(PDO $pdo, int $userId, string $role, int $actingAdminId): bool
{
    if (!in_array($role, ['user', 'admin'], true)) return false;
    if ($userId === $actingAdminId) return false;   // never demote yourself
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
    return true;
}

/* ---------- company delete (only when empty) ---------- */

function admin_delete_company(PDO $pdo, int $companyId): bool
{
    $n = (int)$pdo->query("SELECT COUNT(*) FROM stories WHERE company_id = $companyId")->fetchColumn();
    if ($n > 0) return false;                         // refuse: would orphan stories
    $pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([$companyId]);
    return true;
}

/* ---------- privacy: purge corp emails from resolved reports ---------- */

function admin_purge_resolved_emails(PDO $pdo): int
{
    $st = $pdo->prepare("UPDATE reports SET corp_email = '', corp_domain = ''
        WHERE status IN ('dismissed','actioned') AND corp_email <> ''");
    $st->execute();
    return $st->rowCount();
}
