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

function feed_stories(PDO $pdo, string $tab, int $page, int $perPage = 20, ?int $viewerId = null): array
{
    $offset = max(0, $page - 1) * $perPage;
    $order = match ($tab) {
        'trending' => 'hot DESC',
        'top'      => 's.vote_score DESC, s.created_at DESC',
        default    => 's.created_at DESC',
    };
    $where = match ($tab) {
        'trending' => "s.status = 'active' AND s.created_at > NOW() - INTERVAL 14 DAY",
        'top'      => "s.status = 'active' AND s.created_at > NOW() - INTERVAL 30 DAY",
        default    => "s.status = 'active'",
    };
    $st = $pdo->prepare("SELECT s.*, c.domain, c.name AS company_name, u.handle,
            (SELECT COUNT(*) FROM comments cm
             WHERE cm.story_id = s.id AND cm.status = 'active') AS comment_count,
            COALESCE(v.value, 0) AS my_vote,
            s.vote_score / POW(TIMESTAMPDIFF(HOUR, s.created_at, NOW()) + 2, 1.5) AS hot
        FROM stories s
        JOIN companies c ON c.id = s.company_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN votes v ON v.story_id = s.id AND v.user_id = :viewer
        WHERE $where
        ORDER BY $order
        LIMIT $perPage OFFSET $offset");
    $st->execute(['viewer' => $viewerId ?? 0]);
    return $st->fetchAll();
}

function rail_trending(PDO $pdo, int $n = 5): array
{
    return $pdo->query("SELECT s.id, s.title, s.vote_score
        FROM stories s
        WHERE s.status = 'active' AND s.created_at > NOW() - INTERVAL 14 DAY
        ORDER BY s.vote_score / POW(TIMESTAMPDIFF(HOUR, s.created_at, NOW()) + 2, 1.5) DESC
        LIMIT $n")->fetchAll();
}

function similar_stories(PDO $pdo, int $companyId, int $excludeStoryId, int $n = 4): array
{
    // Other active stories about the same company, newest first.
    $st = $pdo->prepare("SELECT s.id, s.title, s.vote_score, u.handle, s.created_at
        FROM stories s
        JOIN users u ON u.id = s.user_id
        WHERE s.company_id = :cid AND s.id <> :sid AND s.status = 'active'
        ORDER BY s.created_at DESC
        LIMIT $n");
    $st->execute(['cid' => $companyId, 'sid' => $excludeStoryId]);
    return $st->fetchAll();
}

function rail_most_liked(PDO $pdo, int $n = 5): array
{
    return $pdo->query("SELECT s.id, s.title, s.vote_score
        FROM stories s
        WHERE s.status = 'active' AND s.created_at > NOW() - INTERVAL 30 DAY
        ORDER BY s.vote_score DESC, s.created_at DESC
        LIMIT $n")->fetchAll();
}

function story_search(PDO $pdo, string $q, int $limit = 20): array
{
    $q = trim($q);
    if ($q === '') return [];
    $st = $pdo->prepare("SELECT s.*, c.domain, c.name AS company_name, u.handle,
            (SELECT COUNT(*) FROM comments cm
             WHERE cm.story_id = s.id AND cm.status = 'active') AS comment_count
        FROM stories s
        JOIN companies c ON c.id = s.company_id
        JOIN users u ON u.id = s.user_id
        WHERE s.status = 'active'
          AND MATCH(s.title, s.body) AGAINST (? IN NATURAL LANGUAGE MODE)
        LIMIT " . (int)$limit);
    $st->execute([$q]);
    return $st->fetchAll();
}

function stories_for_company(PDO $pdo, int $companyId, ?int $viewerId = null): array
{
    $st = $pdo->prepare("SELECT s.*, c.domain, c.name AS company_name, u.handle,
            (SELECT COUNT(*) FROM comments cm
             WHERE cm.story_id = s.id AND cm.status = 'active') AS comment_count,
            COALESCE(v.value, 0) AS my_vote
        FROM stories s
        JOIN companies c ON c.id = s.company_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN votes v ON v.story_id = s.id AND v.user_id = :viewer
        WHERE s.company_id = :cid AND s.status = 'active'
        ORDER BY s.created_at DESC");
    $st->execute(['cid' => $companyId, 'viewer' => $viewerId ?? 0]);
    return $st->fetchAll();
}
