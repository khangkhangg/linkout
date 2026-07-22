<?php
use PHPUnit\Framework\TestCase;

final class CommentsTest extends TestCase
{
    private PDO $pdo;
    private int $uid;
    private int $sid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
        $cid = company_find_or_create($this->pdo, 'ComCo', 'comco' . uniqid() . '.com',
            $this->uid)['company']['id'];
        $this->sid = story_create($this->pdo, $this->uid, $cid, ['title' => 't',
            'body' => 'b', 'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3,
            'r_balance' => 3, 'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
    }

    public function test_add_and_list(): void
    {
        $r = comment_add($this->pdo, $this->uid, $this->sid, 'Same happened to me.');
        $this->assertTrue($r['ok']);
        $list = comments_for_story($this->pdo, $this->sid);
        $this->assertCount(1, $list);
        $this->assertArrayHasKey('handle', $list[0]);
    }

    public function test_validation_and_hidden_story(): void
    {
        $this->assertFalse(comment_add($this->pdo, $this->uid, $this->sid, '')['ok']);
        $this->assertFalse(comment_add($this->pdo, $this->uid, $this->sid,
            str_repeat('x', 2001))['ok']);
        $this->pdo->exec("UPDATE stories SET status='auto_hidden' WHERE id=$this->sid");
        $this->assertFalse(comment_add($this->pdo, $this->uid, $this->sid, 'hi')['ok']);
    }

    public function test_rate_limit_20_per_hour(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue(comment_add($this->pdo, $this->uid, $this->sid, "c$i")['ok']);
        }
        $r = comment_add($this->pdo, $this->uid, $this->sid, 'over');
        $this->assertSame('rate_limited', $r['error']);
    }

    public function test_delete_own_within_24h_only(): void
    {
        $cidm = comment_add($this->pdo, $this->uid, $this->sid, 'mine')['comment_id'];
        $other = seed_user($this->pdo);
        $this->assertFalse(comment_delete($this->pdo, $cidm, $other));
        $this->assertTrue(comment_delete($this->pdo, $cidm, $this->uid));
        $this->assertCount(0, comments_for_story($this->pdo, $this->sid));

        $cid2 = comment_add($this->pdo, $this->uid, $this->sid, 'old')['comment_id'];
        $this->pdo->exec("UPDATE comments SET created_at = created_at - INTERVAL 25 HOUR WHERE id=$cid2");
        $this->assertFalse(comment_delete($this->pdo, $cid2, $this->uid));
    }
}
