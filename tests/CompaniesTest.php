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

    public function test_duplicate_domain_race_returns_existing_company(): void
    {
        // Insert a company row directly via SQL (simulates another concurrent request winning the race)
        $ins = $this->pdo->prepare('INSERT INTO companies (domain, name, created_by) VALUES (?,?,?)');
        $ins->execute(['race-test.com', 'First Name', $this->uid]);
        $existingId = $this->pdo->lastInsertId();

        // Call company_find_or_create with a DIFFERENT name but same domain
        // This will trigger the INSERT attempt, which will fail with duplicate key,
        // then the catch block should re-fetch and return the existing company
        $r = company_find_or_create($this->pdo, 'Different Name', 'race-test.com', $this->uid);

        $this->assertTrue($r['ok']);
        $this->assertSame((int)$existingId, $r['company']['id'], 'should return existing company id on duplicate domain');
        $this->assertSame('First Name', $r['company']['name'], 'should keep the original name');
        $this->assertSame('race-test.com', $r['company']['domain']);
    }
}
