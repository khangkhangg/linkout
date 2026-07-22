<?php
use PHPUnit\Framework\TestCase;

final class VotesTest extends TestCase
{
    private PDO $pdo;
    private int $uid;
    private int $sid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
        $cid = company_find_or_create($this->pdo, 'VoteCo', 'voteco' . uniqid() . '.com',
            $this->uid)['company']['id'];
        $this->sid = story_create($this->pdo, $this->uid, $cid, ['title' => 't',
            'body' => 'b', 'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3,
            'r_balance' => 3, 'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
    }

    private function score(): int
    {
        return (int)$this->pdo->query("SELECT vote_score FROM stories WHERE id=$this->sid")->fetchColumn();
    }

    public function test_upvote_flip_and_toggle_off(): void
    {
        $r = cast_vote($this->pdo, $this->uid, $this->sid, 1);
        $this->assertSame([1, 1], [$r['score'], $r['my_vote']]);
        $this->assertSame(1, $this->score());

        $r = cast_vote($this->pdo, $this->uid, $this->sid, -1);   // flip
        $this->assertSame([-1, -1], [$r['score'], $r['my_vote']]);

        $r = cast_vote($this->pdo, $this->uid, $this->sid, -1);   // toggle off
        $this->assertSame([0, 0], [$r['score'], $r['my_vote']]);
        $this->assertSame(0, $this->score());
    }

    public function test_two_users_accumulate(): void
    {
        $u2 = seed_user($this->pdo);
        cast_vote($this->pdo, $this->uid, $this->sid, 1);
        cast_vote($this->pdo, $u2, $this->sid, 1);
        $this->assertSame(2, $this->score());
    }

    public function test_invalid_value_and_missing_story(): void
    {
        $this->assertFalse(cast_vote($this->pdo, $this->uid, $this->sid, 5)['ok']);
        $this->assertFalse(cast_vote($this->pdo, $this->uid, 999999, 1)['ok']);
    }
}
