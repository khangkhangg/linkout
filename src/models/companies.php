<?php

function company_insert_or_get(PDO $pdo, string $domain, string $name, int $userId): ?array
{
    try {
        $pdo->prepare('INSERT INTO companies (domain, name, created_by) VALUES (?,?,?)')
            ->execute([$domain, $name, $userId]);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) !== 1062) throw $e;   // 1062 = ER_DUP_ENTRY: lost the race, row exists
    }
    return company_by_domain($pdo, $domain);
}

function company_find_or_create(PDO $pdo, string $name, string $domainInput, int $userId): array
{
    $domain = normalize_domain($domainInput);
    $name = trim($name);
    if (!$domain) return ['ok' => false, 'error' => 'bad_domain'];
    if ($name === '' || mb_strlen($name) > 190) return ['ok' => false, 'error' => 'bad_name'];

    $existing = company_by_domain($pdo, $domain);
    if ($existing) return ['ok' => true, 'company' => $existing];

    return ['ok' => true, 'company' => company_insert_or_get($pdo, $domain, $name, $userId)];
}

function company_by_domain(PDO $pdo, string $domain): ?array
{
    $st = $pdo->prepare('SELECT * FROM companies WHERE domain = ?');
    $st->execute([$domain]);
    return $st->fetch() ?: null;
}

function company_search(PDO $pdo, string $q, int $limit = 8): array
{
    $q = trim($q);
    if ($q === '') return [];
    $st = $pdo->prepare('SELECT id, domain, name FROM companies
        WHERE name LIKE ? OR domain LIKE ? ORDER BY name LIMIT ' . (int)$limit);
    $st->execute(["%$q%", "$q%"]);
    return $st->fetchAll();
}

function company_aggregates(PDO $pdo, int $companyId): ?array
{
    $st = $pdo->prepare("SELECT COUNT(*) AS story_count,
        AVG(r_leadership) AS avg_leadership, AVG(r_culture) AS avg_culture,
        AVG(r_benefits) AS avg_benefits, AVG(r_balance) AS avg_balance,
        AVG(r_growth) AS avg_growth, AVG(r_exit) AS avg_exit,
        AVG(recommend) * 100 AS recommend_pct
        FROM stories WHERE company_id = ? AND status = 'active'");
    $st->execute([$companyId]);
    $a = $st->fetch();
    if (!$a || (int)$a['story_count'] === 0) {
        return ['story_count' => 0, 'avg_leadership' => null, 'avg_culture' => null,
                'avg_benefits' => null, 'avg_balance' => null, 'avg_growth' => null,
                'avg_exit' => null, 'recommend_pct' => null];
    }
    $a['story_count'] = (int)$a['story_count'];
    foreach ($a as $k => $v) if ($k !== 'story_count' && $v !== null) $a[$k] = (float)$v;
    return $a;
}

function rail_top_companies(PDO $pdo, int $n = 5): array
{
    // Bayesian smoothing: (sum + global_avg * 10) / (count + 10), where each
    // story contributes the mean of its six dimension ratings. Min 3 stories.
    return $pdo->query("
        WITH story_means AS (
            SELECT company_id,
                   (r_leadership + r_culture + r_benefits + r_balance + r_growth + r_exit) / 6.0
                       AS mean_rating
            FROM stories WHERE status = 'active'
        ), g AS (SELECT AVG(mean_rating) AS global_avg FROM story_means)
        SELECT c.domain, c.name, COUNT(*) AS story_count,
               (SUM(sm.mean_rating) + g.global_avg * 10) / (COUNT(*) + 10) AS bayes_score
        FROM story_means sm
        JOIN companies c ON c.id = sm.company_id
        CROSS JOIN g
        GROUP BY c.id, c.domain, c.name, g.global_avg
        HAVING COUNT(*) >= 3
        ORDER BY bayes_score DESC
        LIMIT $n")->fetchAll();
}

function company_rename(PDO $pdo, int $companyId, string $newName): void
{
    $newName = trim($newName);
    if ($newName === '' || mb_strlen($newName) > 190) return;
    $pdo->prepare('UPDATE companies SET name = ? WHERE id = ?')->execute([$newName, $companyId]);
}

function company_merge(PDO $pdo, int $fromId, int $intoId): void
{
    if ($fromId === $intoId) return;
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE stories SET company_id = ? WHERE company_id = ?')
            ->execute([$intoId, $fromId]);
        $pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([$fromId]);
        $pdo->commit();
    } catch (Throwable $t) { $pdo->rollBack(); throw $t; }
}
