<?php

function rate_limit_ok(PDO $pdo, int $userId, string $kind, int $max, int $windowMinutes): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM rate_events
        WHERE user_id = ? AND kind = ? AND created_at > NOW() - INTERVAL ? MINUTE');
    $st->execute([$userId, $kind, $windowMinutes]);
    return (int)$st->fetchColumn() < $max;
}

function rate_note(PDO $pdo, int $userId, string $kind): void
{
    $pdo->prepare('INSERT INTO rate_events (user_id, kind) VALUES (?,?)')
        ->execute([$userId, $kind]);
}
