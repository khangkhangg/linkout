<?php
use PHPUnit\Framework\TestCase;

final class ReportsTest extends TestCase
{
    private PDO $pdo;
    private int $author;
    private int $sid;
    private string $companyDomain;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->author = seed_user($this->pdo);
        $this->companyDomain = 'repco' . uniqid() . '.com';
        $cid = company_find_or_create($this->pdo, 'RepCo', $this->companyDomain,
            $this->author)['company']['id'];
        $this->sid = story_create($this->pdo, $this->author, $cid, ['title' => 't',
            'body' => 'b', 'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3,
            'r_balance' => 3, 'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
    }

    private function fullReport(int $uid, string $email): array
    {
        $r = start_report($this->pdo, $uid, $this->sid, 'false_info', null, $email);
        return $r['ok'] ? verify_report($this->pdo, $r['report_id'], $uid, $r['code']) : $r;
    }

    public function test_freemail_and_bad_email_rejected(): void
    {
        $uid = seed_user($this->pdo);
        $this->assertSame('freemail', start_report($this->pdo, $uid, $this->sid,
            'spam', null, 'x@gmail.com')['error']);
        $this->assertSame('invalid_email', start_report($this->pdo, $uid, $this->sid,
            'spam', null, 'not-an-email')['error']);
        $this->assertSame('bad_reason', start_report($this->pdo, $uid, $this->sid,
            'nonsense', null, 'x@corp.com')['error']);
    }

    public function test_wrong_code_five_attempts_then_locked(): void
    {
        $uid = seed_user($this->pdo);
        $r = start_report($this->pdo, $uid, $this->sid, 'spam', null, 'x@corpa.com');
        $this->assertTrue($r['ok']);
        for ($i = 0; $i < 5; $i++) {
            $bad = $r['code'] === '000000' ? '000001' : '000000';
            $this->assertSame('bad_code',
                verify_report($this->pdo, $r['report_id'], $uid, $bad)['error']);
        }
        $this->assertSame('too_many_attempts',
            verify_report($this->pdo, $r['report_id'], $uid, $r['code'])['error']);
    }

    public function test_expired_code_rejected(): void
    {
        $uid = seed_user($this->pdo);
        $r = start_report($this->pdo, $uid, $this->sid, 'spam', null, 'x@corpb.com');
        $this->pdo->exec("UPDATE reports SET code_expires_at = NOW() - INTERVAL 1 MINUTE
            WHERE id = {$r['report_id']}");
        $this->assertSame('code_expired',
            verify_report($this->pdo, $r['report_id'], $uid, $r['code'])['error']);
    }

    public function test_company_match_flag(): void
    {
        $uid = seed_user($this->pdo);
        $this->fullReport($uid, 'hr@' . $this->companyDomain);
        $m = $this->pdo->query("SELECT is_company_match FROM reports
            WHERE story_id = $this->sid AND reporter_user_id = $uid")->fetchColumn();
        $this->assertSame(1, (int)$m);
    }

    public function test_three_distinct_domains_hide_story_same_domain_does_not(): void
    {
        // three reporters, SAME domain -> stays active
        foreach (range(1, 3) as $i) {
            $this->fullReport(seed_user($this->pdo), "p$i@samecorp.com");
        }
        $status = $this->pdo->query("SELECT status FROM stories WHERE id=$this->sid")->fetchColumn();
        $this->assertSame('active', $status, 'same-domain reports must not hide');

        // two more reporters with two NEW domains -> 3 distinct -> hidden
        $r1 = $this->fullReport(seed_user($this->pdo), 'a@corp-one.com');
        $this->assertFalse($r1['hidden']);
        $r2 = $this->fullReport(seed_user($this->pdo), 'b@corp-two.com');
        $this->assertTrue($r2['hidden']);
        $status = $this->pdo->query("SELECT status FROM stories WHERE id=$this->sid")->fetchColumn();
        $this->assertSame('auto_hidden', $status);
    }

    public function test_duplicate_report_restart_reissues_code(): void
    {
        $uid = seed_user($this->pdo);
        $r1 = start_report($this->pdo, $uid, $this->sid, 'spam', null, 'x@corpc.com');
        $r2 = start_report($this->pdo, $uid, $this->sid, 'doxxing', null, 'x@corpd.com');
        $this->assertTrue($r2['ok']);
        $this->assertSame($r1['report_id'], $r2['report_id'], 'row reused');
        $this->assertTrue(verify_report($this->pdo, $r2['report_id'], $uid, $r2['code'])['ok']);
        // once verified, a further start is refused
        $this->assertSame('already_reported', start_report($this->pdo, $uid, $this->sid,
            'spam', null, 'x@corpe.com')['error']);
    }
}
