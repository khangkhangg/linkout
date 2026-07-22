<?php
use PHPUnit\Framework\TestCase;

final class StoriesTest extends TestCase
{
    private PDO $pdo;
    private int $uid;
    private int $cid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
        $this->cid = company_find_or_create($this->pdo, 'StoryCo', 'storyco' . uniqid() . '.com',
            $this->uid)['company']['id'];
    }

    private function input(array $o = []): array
    {
        return array_merge(['title' => 'My exit story', 'body' => str_repeat('x', 50),
            'r_leadership' => 3, 'r_culture' => 4, 'r_benefits' => 2, 'r_balance' => 5,
            'r_growth' => 3, 'r_exit' => 1, 'recommend' => 1], $o);
    }

    public function test_create_and_get(): void
    {
        $r = story_create($this->pdo, $this->uid, $this->cid, $this->input());
        $this->assertTrue($r['ok']);
        $s = story_get($this->pdo, $r['story_id']);
        $this->assertSame('My exit story', $s['title']);
        $this->assertArrayHasKey('handle', $s);
        $this->assertArrayHasKey('domain', $s);
    }

    public function test_validation_rejects_bad_ratings_and_lengths(): void
    {
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['r_exit' => 6]))['ok']);
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['r_exit' => 0]))['ok']);
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['title' => '']))['ok']);
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['body' => str_repeat('x', 10001)]))['ok']);
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['title' => ['x']]))['ok'], 'array title rejected, not fatal');
        $this->assertFalse(story_create($this->pdo, $this->uid, $this->cid,
            $this->input(['body' => ['x']]))['ok'], 'array body rejected, not fatal');
    }

    public function test_rate_limit_three_per_day(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue(story_create($this->pdo, $this->uid, $this->cid, $this->input())['ok']);
        }
        $r = story_create($this->pdo, $this->uid, $this->cid, $this->input());
        $this->assertFalse($r['ok']);
        $this->assertSame('rate_limited', $r['error']);
    }

    public function test_edit_window_24h(): void
    {
        $id = story_create($this->pdo, $this->uid, $this->cid, $this->input())['story_id'];
        $s = story_get($this->pdo, $id);
        $me = ['id' => $this->uid];
        $other = ['id' => $this->uid + 999];
        $this->assertTrue(story_editable_by($s, $me));
        $this->assertFalse(story_editable_by($s, $other));
        $this->assertFalse(story_editable_by($s, null));
        $this->pdo->exec("UPDATE stories SET created_at = created_at - INTERVAL 25 HOUR WHERE id = $id");
        $this->assertFalse(story_editable_by(story_get($this->pdo, $id), $me));
    }

    public function test_delete_removes_dependents(): void
    {
        $id = story_create($this->pdo, $this->uid, $this->cid, $this->input())['story_id'];
        $this->pdo->exec("INSERT INTO votes (user_id, story_id, value) VALUES ($this->uid, $id, 1)");
        $this->pdo->exec("INSERT INTO comments (story_id, user_id, body) VALUES ($id, $this->uid, 'hi')");
        story_delete($this->pdo, $id);
        $this->assertNull(story_get($this->pdo, $id));
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM votes WHERE story_id=$id")->fetchColumn());
    }
}
