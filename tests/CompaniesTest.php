<?php
use PHPUnit\Framework\TestCase;

final class CompaniesTest extends TestCase
{
    private PDO $pdo;
    private int $uid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->uid = seed_user($this->pdo);
    }

    public function test_create_normalizes_domain_and_dedupes(): void
    {
        $r1 = company_find_or_create($this->pdo, 'Acme Corp', 'https://WWW.Acme.com/jobs', $this->uid);
        $this->assertTrue($r1['ok']);
        $this->assertSame('acme.com', $r1['company']['domain']);
        $r2 = company_find_or_create($this->pdo, 'ACME!!', 'acme.com', $this->uid);
        $this->assertSame($r1['company']['id'], $r2['company']['id'], 'same domain = same company');
    }

    public function test_create_rejects_bad_domain_or_empty_name(): void
    {
        $this->assertFalse(company_find_or_create($this->pdo, 'X', 'not a domain', $this->uid)['ok']);
        $this->assertFalse(company_find_or_create($this->pdo, '  ', 'ok.com', $this->uid)['ok']);
    }

    public function test_search_matches_name_and_domain_prefix(): void
    {
        company_find_or_create($this->pdo, 'FPT Software', 'fpt-software.com', $this->uid);
        $this->assertNotEmpty(company_search($this->pdo, 'fpt'));
        $this->assertNotEmpty(company_search($this->pdo, 'FPT Soft'));
        $this->assertEmpty(company_search($this->pdo, 'zzzzz'));
    }

    public function test_aggregates_only_count_active_stories(): void
    {
        $c = company_find_or_create($this->pdo, 'AggCo', 'aggco.com', $this->uid)['company'];
        $ins = $this->pdo->prepare('INSERT INTO stories (user_id, company_id, title, body,
            r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit, recommend, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$this->uid, $c['id'], 't1', 'b', 5,5,5,5,5,5, 1, 'active']);
        $ins->execute([$this->uid, $c['id'], 't2', 'b', 1,1,1,1,1,1, 0, 'active']);
        $ins->execute([$this->uid, $c['id'], 't3', 'b', 3,3,3,3,3,3, 1, 'removed']);
        $a = company_aggregates($this->pdo, $c['id']);
        $this->assertSame(2, $a['story_count']);
        $this->assertEqualsWithDelta(3.0, $a['avg_leadership'], 0.01);
        $this->assertEqualsWithDelta(50.0, $a['recommend_pct'], 0.01);
    }

    public function test_insert_or_get_recovers_when_domain_already_exists(): void
    {
        $domain = 'racetest' . uniqid() . '.com';
        $first = company_find_or_create($this->pdo, 'Original Name', $domain, $this->uid)['company'];
        // Direct call bypasses the pre-check, so the INSERT hits the UNIQUE key
        // and the 1062 catch path must recover by returning the existing row.
        $got = company_insert_or_get($this->pdo, $domain, 'Racing Name', $this->uid);
        $this->assertSame((int)$first['id'], (int)$got['id']);
        $this->assertSame('Original Name', $got['name']);
    }
}
