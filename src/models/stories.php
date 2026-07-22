<?php

const RATING_KEYS = ['r_leadership','r_culture','r_benefits','r_balance','r_growth','r_exit'];

function story_validate(array $in): ?string
{
    $title = is_string($in['title'] ?? null) ? trim($in['title']) : '';
    $body  = is_string($in['body'] ?? null) ? trim($in['body']) : '';
    if ($title === '' || mb_strlen($title) > 200) return 'bad_title';
    if ($body === '' || mb_strlen($body) > 10000) return 'bad_body';
    foreach (RATING_KEYS as $k) {
        $v = (int)($in[$k] ?? 0);
        if ($v < 1 || $v > 5) return 'bad_rating';
    }
    return null;
}

function story_create(PDO $pdo, int $userId, int $companyId, array $in): array
{
    if ($err = story_validate($in)) return ['ok' => false, 'error' => $err];
    if (!rate_limit_ok($pdo, $userId, 'story', 3, 1440)) {
        return ['ok' => false, 'error' => 'rate_limited'];
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO stories (user_id, company_id, title, body,
            r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit, recommend)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $userId, $companyId, trim($in['title']), trim($in['body']),
            (int)$in['r_leadership'], (int)$in['r_culture'], (int)$in['r_benefits'],
            (int)$in['r_balance'], (int)$in['r_growth'], (int)$in['r_exit'],
            !empty($in['recommend']) ? 1 : 0,
        ]);
        $id = (int)$pdo->lastInsertId();
        rate_note($pdo, $userId, 'story');
        $pdo->commit();
        return ['ok' => true, 'story_id' => $id];
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}

function story_get(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT s.*, c.domain, c.name AS company_name, u.handle
        FROM stories s
        JOIN companies c ON c.id = s.company_id
        JOIN users u ON u.id = s.user_id
        WHERE s.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function story_editable_by(array $story, ?array $user): bool
{
    return $user !== null
        && (int)$story['user_id'] === (int)$user['id']
        && strtotime($story['created_at']) > time() - 86400;
}

function story_update(PDO $pdo, int $storyId, array $in): void
{
    $sets = 'title = ?, body = ?, recommend = ?';
    $vals = [trim($in['title']), trim($in['body']), !empty($in['recommend']) ? 1 : 0];
    foreach (RATING_KEYS as $k) { $sets .= ", $k = ?"; $vals[] = (int)$in[$k]; }
    $vals[] = $storyId;
    $pdo->prepare("UPDATE stories SET $sets WHERE id = ?")->execute($vals);
}

function story_delete(PDO $pdo, int $storyId): void
{
    $pdo->beginTransaction();
    try {
        foreach (['votes', 'comments', 'reports'] as $tbl) {
            $pdo->prepare("DELETE FROM $tbl WHERE story_id = ?")->execute([$storyId]);
        }
        $pdo->prepare('DELETE FROM stories WHERE id = ?')->execute([$storyId]);
        $pdo->commit();
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}
