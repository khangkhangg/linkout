<?php

const REPORT_REASONS = ['false_info','doxxing','harassment','spam','other'];

function report_threshold(PDO $pdo): int
{
    $st = $pdo->prepare("SELECT v FROM settings WHERE k = 'report_threshold'");
    $st->execute();
    return (int)($st->fetchColumn() ?: 3);
}

function start_report(PDO $pdo, int $userId, int $storyId, string $reason,
                      ?string $reasonText, string $corpEmail): array
{
    if (!in_array($reason, REPORT_REASONS, true)) return ['ok' => false, 'error' => 'bad_reason'];
    $corpEmail = strtolower(trim($corpEmail));
    if (!filter_var($corpEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email'];
    }
    $domain = substr(strrchr($corpEmail, '@'), 1);
    if (is_freemail($domain)) return ['ok' => false, 'error' => 'freemail'];

    $st = $pdo->prepare("SELECT id FROM stories WHERE id = ? AND status <> 'removed'");
    $st->execute([$storyId]);
    if (!$st->fetch()) return ['ok' => false, 'error' => 'not_found'];

    if (!rate_limit_ok($pdo, $userId, 'verify_email', 5, 60)) {
        return ['ok' => false, 'error' => 'rate_limited'];
    }

    $st = $pdo->prepare('SELECT id, verified_at FROM reports
        WHERE story_id = ? AND reporter_user_id = ?');
    $st->execute([$storyId, $userId]);
    $existing = $st->fetch();
    if ($existing && $existing['verified_at']) return ['ok' => false, 'error' => 'already_reported'];

    $code = random_code();
    if ($existing) {
        $pdo->prepare('UPDATE reports SET corp_email = ?, corp_domain = ?, reason = ?,
            reason_text = ?, verify_code = ?, code_expires_at = NOW() + INTERVAL 15 MINUTE,
            code_attempts = 0 WHERE id = ?')
            ->execute([$corpEmail, $domain, $reason, $reasonText, $code, $existing['id']]);
        $id = (int)$existing['id'];
    } else {
        $pdo->prepare('INSERT INTO reports (story_id, reporter_user_id, corp_email,
            corp_domain, reason, reason_text, verify_code, code_expires_at)
            VALUES (?,?,?,?,?,?,?, NOW() + INTERVAL 15 MINUTE)')
            ->execute([$storyId, $userId, $corpEmail, $domain, $reason, $reasonText, $code]);
        $id = (int)$pdo->lastInsertId();
    }
    rate_note($pdo, $userId, 'verify_email');
    return ['ok' => true, 'report_id' => $id, 'code' => $code];
}

function verify_report(PDO $pdo, int $reportId, int $userId, string $code): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT r.*, s.company_id FROM reports r
            JOIN stories s ON s.id = r.story_id
            WHERE r.id = ? AND r.reporter_user_id = ? FOR UPDATE');
        $st->execute([$reportId, $userId]);
        $r = $st->fetch();
        if (!$r || $r['verified_at']) { $pdo->rollBack(); return ['ok' => false, 'error' => 'not_found']; }
        if ((int)$r['code_attempts'] >= 5) { $pdo->rollBack(); return ['ok' => false, 'error' => 'too_many_attempts']; }
        if (strtotime($r['code_expires_at']) < time()) { $pdo->rollBack(); return ['ok' => false, 'error' => 'code_expired']; }
        if (!hash_equals($r['verify_code'], $code)) {
            $pdo->prepare('UPDATE reports SET code_attempts = code_attempts + 1 WHERE id = ?')
                ->execute([$reportId]);
            $pdo->commit();
            return ['ok' => false, 'error' => 'bad_code'];
        }

        $st = $pdo->prepare('SELECT domain FROM companies WHERE id = ?');
        $st->execute([$r['company_id']]);
        $match = ($st->fetchColumn() === $r['corp_domain']) ? 1 : 0;
        $pdo->prepare('UPDATE reports SET verified_at = NOW(), is_company_match = ?,
            verify_code = NULL WHERE id = ?')->execute([$match, $reportId]);

        $st = $pdo->prepare("SELECT COUNT(DISTINCT corp_domain) FROM reports
            WHERE story_id = ? AND status = 'pending' AND verified_at IS NOT NULL");
        $st->execute([$r['story_id']]);
        $hidden = false;
        if ((int)$st->fetchColumn() >= report_threshold($pdo)) {
            $pdo->prepare("UPDATE stories SET status = 'auto_hidden'
                WHERE id = ? AND status = 'active'")->execute([$r['story_id']]);
            $hidden = true;
        }
        $pdo->commit();
        return ['ok' => true, 'hidden' => $hidden];
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}

function admin_queue(PDO $pdo): array
{
    return $pdo->query("SELECT r.*, s.title AS story_title, s.status AS story_status,
            u.handle AS reporter_handle
        FROM reports r
        JOIN stories s ON s.id = r.story_id
        JOIN users u ON u.id = r.reporter_user_id
        WHERE r.status = 'pending' AND r.verified_at IS NOT NULL
        ORDER BY r.is_company_match DESC, r.created_at ASC")->fetchAll();
}

function admin_restore_story(PDO $pdo, int $storyId): void
{
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE stories SET status = 'active'
            WHERE id = ? AND status = 'auto_hidden'")->execute([$storyId]);
        $pdo->prepare("UPDATE reports SET status = 'dismissed'
            WHERE story_id = ? AND status = 'pending'")->execute([$storyId]);
        $pdo->commit();
    } catch (Throwable $t) { $pdo->rollBack(); throw $t; }
}

function admin_remove_story(PDO $pdo, int $storyId): void
{
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE stories SET status = 'removed' WHERE id = ?")->execute([$storyId]);
        $pdo->prepare("UPDATE reports SET status = 'actioned'
            WHERE story_id = ? AND status = 'pending'")->execute([$storyId]);
        $pdo->commit();
    } catch (Throwable $t) { $pdo->rollBack(); throw $t; }
}

function admin_dismiss_report(PDO $pdo, int $reportId): void
{
    $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE id = ?")->execute([$reportId]);
}

function admin_set_ban(PDO $pdo, int $userId, bool $banned): void
{
    $pdo->prepare('UPDATE users SET banned_at = ' . ($banned ? 'NOW()' : 'NULL') .
        " WHERE id = ? AND role <> 'admin'")->execute([$userId]);
}
