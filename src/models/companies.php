<?php

function company_find_or_create(PDO $pdo, string $name, string $domainInput, int $userId): array
{
    $domain = normalize_domain($domainInput);
    $name = trim($name);
    if (!$domain) return ['ok' => false, 'error' => 'bad_domain'];
    if ($name === '' || mb_strlen($name) > 190) return ['ok' => false, 'error' => 'bad_name'];

    $existing = company_by_domain($pdo, $domain);
    if ($existing) return ['ok' => true, 'company' => $existing];

    try {
        $pdo->prepare('INSERT INTO companies (domain, name, created_by) VALUES (?,?,?)')
            ->execute([$domain, $name, $userId]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] ?? null) {
            if ((int)($e->errorInfo[1]) === 1062) {
                $existing = company_by_domain($pdo, $domain);
                if ($existing) return ['ok' => true, 'company' => $existing];
            }
        }
        throw $e;
    }
    return ['ok' => true, 'company' => company_by_domain($pdo, $domain)];
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
