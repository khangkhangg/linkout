<?php

function cast_vote(PDO $pdo, int $userId, int $storyId, int $value): array
{
    if (!in_array($value, [1, -1], true)) return ['ok' => false, 'error' => 'bad_vote'];

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT id FROM stories WHERE id = ? AND status = 'active' FOR UPDATE");
        $st->execute([$storyId]);
        if (!$st->fetch()) { $pdo->rollBack(); return ['ok' => false, 'error' => 'not_found']; }

        $st = $pdo->prepare('SELECT value FROM votes WHERE user_id = ? AND story_id = ?');
        $st->execute([$userId, $storyId]);
        $existing = $st->fetchColumn();

        if ($existing === false) {                       // new vote
            $pdo->prepare('INSERT INTO votes (user_id, story_id, value) VALUES (?,?,?)')
                ->execute([$userId, $storyId, $value]);
            $delta = $value; $my = $value;
        } elseif ((int)$existing === $value) {           // toggle off
            $pdo->prepare('DELETE FROM votes WHERE user_id = ? AND story_id = ?')
                ->execute([$userId, $storyId]);
            $delta = -$value; $my = 0;
        } else {                                         // flip
            $pdo->prepare('UPDATE votes SET value = ? WHERE user_id = ? AND story_id = ?')
                ->execute([$value, $userId, $storyId]);
            $delta = 2 * $value; $my = $value;
        }

        $pdo->prepare('UPDATE stories SET vote_score = vote_score + ? WHERE id = ?')
            ->execute([$delta, $storyId]);
        $score = (int)$pdo->query("SELECT vote_score FROM stories WHERE id = $storyId")->fetchColumn();
        $pdo->commit();
        return ['ok' => true, 'score' => $score, 'my_vote' => $my];
    } catch (Throwable $t) {
        $pdo->rollBack();
        throw $t;
    }
}
