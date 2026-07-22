<?php

function comment_add(PDO $pdo, int $userId, int $storyId, string $body): array
{
    $body = trim($body);
    if ($body === '' || mb_strlen($body) > 2000) return ['ok' => false, 'error' => 'bad_comment'];
    $st = $pdo->prepare("SELECT id FROM stories WHERE id = ? AND status = 'active'");
    $st->execute([$storyId]);
    if (!$st->fetch()) return ['ok' => false, 'error' => 'not_found'];
    if (!rate_limit_ok($pdo, $userId, 'comment', 20, 60)) {
        return ['ok' => false, 'error' => 'rate_limited'];
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO comments (story_id, user_id, body) VALUES (?,?,?)')
            ->execute([$storyId, $userId, $body]);
        $id = (int)$pdo->lastInsertId();
        rate_note($pdo, $userId, 'comment');
        $pdo->commit();
        return ['ok' => true, 'comment_id' => $id];
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}

function comments_for_story(PDO $pdo, int $storyId): array
{
    $st = $pdo->prepare("SELECT cm.*, u.handle FROM comments cm
        JOIN users u ON u.id = cm.user_id
        WHERE cm.story_id = ? AND cm.status = 'active'
        ORDER BY cm.created_at ASC");
    $st->execute([$storyId]);
    return $st->fetchAll();
}

function comment_delete(PDO $pdo, int $commentId, int $userId): bool
{
    $st = $pdo->prepare("UPDATE comments SET status = 'removed'
        WHERE id = ? AND user_id = ? AND status = 'active'
          AND created_at > NOW() - INTERVAL 24 HOUR");
    $st->execute([$commentId, $userId]);
    return $st->rowCount() === 1;
}
