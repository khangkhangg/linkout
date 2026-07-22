<?php
use PHPUnit\Framework\TestCase;

final class AdminV2Test extends TestCase
{
    private PDO $pdo;
    private int $uid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
    }

    // Restore shared-DB state this class mutates, so it can't leak into other
    // test classes (settings + admin-added blocklist rows live in a shared DB).
    protected function tearDown(): void
    {
        setting_set($this->pdo, 'report_threshold', '3');
        $this->pdo->exec('DELETE FROM blocked_domains WHERE added_by IS NOT NULL');
    }

    private function mkCompany(string $tag): int
    {
        return company_find_or_create($this->pdo, $tag, "$tag.com", $this->uid)['company']['id'];
    }

    private function mkStory(int $cid): int
    {
        return story_create($this->pdo, $this->uid, $cid, ['title' => 't', 'body' => 'b',
            'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3, 'r_balance' => 3,
            'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
    }

    public function test_blocklist_is_suffix_aware(): void
    {
        blocked_domain_add($this->pdo, 'tempmail.io', $this->uid);
        $this->assertTrue(domain_blocked($this->pdo, 'tempmail.io'));
        $this->assertTrue(domain_blocked($this->pdo, 'mx.tempmail.io'), 'subdomain blocked');
        $this->assertFalse(domain_blocked($this->pdo, 'nottempmail.io'), 'label boundary respected');
        blocked_domain_remove($this->pdo, 'tempmail.io');
        $this->assertFalse(domain_blocked($this->pdo, 'tempmail.io'));
    }

    public function test_blocked_domain_rejects_report(): void
    {
        $cid = $this->mkCompany('blkco' . uniqid());
        $sid = $this->mkStory($cid);
        $reporter = seed_user($this->pdo);
        blocked_domain_add($this->pdo, 'throwaway-x.com', $this->uid);
        $r = start_report($this->pdo, $reporter, $sid, 'spam', null, 'a@throwaway-x.com');
        $this->assertSame('freemail', $r['error']);
    }

    public function test_settings_get_set(): void
    {
        $this->assertSame('3', setting_get($this->pdo, 'report_threshold', '3'));
        setting_set($this->pdo, 'report_threshold', '5');
        $this->assertSame('5', setting_get($this->pdo, 'report_threshold'));
        $this->assertSame('fallback', setting_get($this->pdo, 'no_such_key', 'fallback'));
    }

    public function test_delete_company_refuses_when_nonempty(): void
    {
        $withStory = $this->mkCompany('keep' . uniqid());
        $this->mkStory($withStory);
        $this->assertFalse(admin_delete_company($this->pdo, $withStory), 'refuse: has stories');

        $emptyDomain = 'empty' . uniqid() . '.com';
        $empty = company_find_or_create($this->pdo, 'Empty Co', $emptyDomain, $this->uid)['company']['id'];
        $this->assertTrue(admin_delete_company($this->pdo, $empty));
        $this->assertNull(company_by_domain($this->pdo, $emptyDomain), 'company row gone');
    }

    public function test_set_role_guards_self_demote(): void
    {
        $admin = seed_user($this->pdo, ['role' => 'admin']);
        $other = seed_user($this->pdo);
        $this->assertFalse(admin_set_role($this->pdo, $admin, 'user', $admin), 'cannot demote self');
        $this->assertTrue(admin_set_role($this->pdo, $other, 'admin', $admin));
        $this->assertFalse(admin_set_role($this->pdo, $other, 'superhero', $admin), 'invalid role');
    }

    public function test_purge_resolved_emails_only(): void
    {
        // Neutralize any pre-existing rows from other classes in the shared DB by
        // marking their emails already-empty, so our count reflects only our set.
        $this->pdo->exec("UPDATE reports SET corp_email = '' WHERE corp_email <> ''");
        $cid = $this->mkCompany('purgeco' . uniqid());
        $sid = $this->mkStory($cid);
        $mk = function (string $status, string $email) use ($sid) {
            $ruid = seed_user($this->pdo);
            $this->pdo->prepare("INSERT INTO reports (story_id, reporter_user_id, corp_email,
                corp_domain, reason, verified_at, status) VALUES (?,?,?,?, 'spam', NOW(), ?)")
                ->execute([$sid, $ruid, $email, substr(strrchr($email, '@'), 1), $status]);
        };
        $mk('pending', 'p@corp.com');
        $mk('dismissed', 'd@corp.com');
        $mk('actioned', 'a@corp.com');
        $n = admin_purge_resolved_emails($this->pdo);
        $this->assertSame(2, $n);
        $remaining = $this->pdo->query("SELECT corp_email FROM reports WHERE corp_email <> ''")
            ->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['p@corp.com'], $remaining, 'only pending keeps its email');
    }

    public function test_admin_log_records_and_lists(): void
    {
        $admin = seed_user($this->pdo, ['role' => 'admin']);
        log_admin_action($this->pdo, $admin, 'remove_story', 'story', 42, 'test');
        $actions = admin_recent_actions($this->pdo, 5);
        $this->assertNotEmpty($actions);
        $this->assertSame('remove_story', $actions[0]['action']);
        $this->assertArrayHasKey('admin_handle', $actions[0]);
    }

    public function test_stats_shape(): void
    {
        $cid = $this->mkCompany('statco' . uniqid());
        $this->mkStory($cid);
        $stats = admin_stats($this->pdo);
        foreach (['pending_reports', 'auto_hidden', 'stories_total', 'companies_total',
                  'users_total', 'banned_users'] as $k) {
            $this->assertArrayHasKey($k, $stats);
            $this->assertIsInt($stats[$k]);
        }
        $this->assertGreaterThanOrEqual(1, $stats['stories_total']);
    }
}
