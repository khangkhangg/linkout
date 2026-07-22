<?php
use PHPUnit\Framework\TestCase;

final class AdminTest extends TestCase
{
    private PDO $pdo;
    private int $uid;
    private int $sid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
        $cid = company_find_or_create($this->pdo, 'AdmCo', 'admco' . uniqid() . '.com',
            $this->uid)['company']['id'];
        $this->sid = story_create($this->pdo, $this->uid, $cid, ['title' => 't',
            'body' => 'b', 'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3,
            'r_balance' => 3, 'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
        // three verified reports from distinct domains -> auto_hidden
        foreach (['a@c1.com', 'b@c2.com', 'c@c3.com'] as $email) {
            $ruid = seed_user($this->pdo);
            $r = start_report($this->pdo, $ruid, $this->sid, 'spam', null, $email);
            verify_report($this->pdo, $r['report_id'], $ruid, $r['code']);
        }
    }

    private function storyStatus(): string
    {
        return $this->pdo->query("SELECT status FROM stories WHERE id=$this->sid")->fetchColumn();
    }

    public function test_queue_lists_verified_pending_reports(): void
    {
        $this->assertSame('auto_hidden', $this->storyStatus());
        $q = admin_queue($this->pdo);
        $this->assertCount(3, array_filter($q, fn($r) => (int)$r['story_id'] === $this->sid));
    }

    public function test_restore_reactivates_and_dismisses(): void
    {
        admin_restore_story($this->pdo, $this->sid);
        $this->assertSame('active', $this->storyStatus());
        $left = $this->pdo->query("SELECT COUNT(*) FROM reports
            WHERE story_id=$this->sid AND status='pending'")->fetchColumn();
        $this->assertSame(0, (int)$left, 'pending reports dismissed — no instant re-hide');
    }

    public function test_remove_marks_removed_and_actions_reports(): void
    {
        admin_remove_story($this->pdo, $this->sid);
        $this->assertSame('removed', $this->storyStatus());
        $st = $this->pdo->query("SELECT DISTINCT status FROM reports
            WHERE story_id=$this->sid")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['actioned'], $st);
    }

    public function test_company_merge_moves_stories(): void
    {
        $a = company_find_or_create($this->pdo, 'KeepCo', 'keepco' . uniqid() . '.com', $this->uid)['company'];
        $b = company_find_or_create($this->pdo, 'DupCo', 'dupco' . uniqid() . '.com', $this->uid)['company'];
        story_create($this->pdo, $this->uid, $b['id'], ['title' => 'x', 'body' => 'b',
            'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3, 'r_balance' => 3,
            'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1]);
        company_merge($this->pdo, $b['id'], $a['id']);
        $n = $this->pdo->query("SELECT COUNT(*) FROM stories WHERE company_id={$a['id']}")->fetchColumn();
        $this->assertSame(1, (int)$n);
        $this->assertNull(company_by_domain($this->pdo, $b['domain']));
    }
}
