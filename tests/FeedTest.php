<?php
use PHPUnit\Framework\TestCase;

final class FeedTest extends TestCase
{
    private PDO $pdo;
    private int $uid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
    }

    private function mkCompany(string $tag): int
    {
        return company_find_or_create($this->pdo, $tag, "$tag.com", $this->uid)['company']['id'];
    }

    private function mkStory(int $cid, int $score = 0, int $hoursAgo = 0, string $status = 'active',
                             int $rating = 3): int
    {
        $this->pdo->prepare('INSERT INTO stories (user_id, company_id, title, body,
            r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit, recommend,
            status, vote_score, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?, NOW() - INTERVAL ? HOUR)')
            ->execute([$this->uid, $cid, 't', 'b', $rating, $rating, $rating, $rating,
                $rating, $rating, $status, $score, $hoursAgo]);
        return (int)$this->pdo->lastInsertId();
    }
    public function test_feed_excludes_hidden_and_removed(): void
    {
        $cid = $this->mkCompany('feedco' . uniqid());
        $keep = $this->mkStory($cid);
        $this->mkStory($cid, 0, 0, 'auto_hidden');
        $this->mkStory($cid, 0, 0, 'removed');
        $rows = array_filter(feed_stories($this->pdo, 'new', 1),
            fn($r) => (int)$r['company_id'] === $cid);
        $this->assertCount(1, $rows);
        $this->assertSame($keep, (int)current($rows)['id']);
    }

    public function test_trending_prefers_fresh_momentum_over_stale_score(): void
    {
        $cid = $this->mkCompany('trendco' . uniqid());
        $stale = $this->mkStory($cid, 50, 300);   // big score, 12.5 days old
        $fresh = $this->mkStory($cid, 10, 2);     // modest score, 2h old
        $ids = array_map('intval', array_column(feed_stories($this->pdo, 'trending', 1), 'id'));
        $this->assertLessThan(array_search($stale, $ids), array_search($fresh, $ids));
    }

    public function test_top_sorts_by_score_and_excludes_older_than_30d(): void
    {
        $cid = $this->mkCompany('topco' . uniqid());
        $old = $this->mkStory($cid, 99, 24 * 40);
        $hi  = $this->mkStory($cid, 5);
        $lo  = $this->mkStory($cid, 1);
        $ids = array_map('intval', array_column(feed_stories($this->pdo, 'top', 1), 'id'));
        $this->assertNotContains($old, $ids);
        $this->assertLessThan(array_search($lo, $ids), array_search($hi, $ids));
    }

    public function test_my_vote_is_joined_for_viewer(): void
    {
        $cid = $this->mkCompany('voteview' . uniqid());
        $sid = $this->mkStory($cid);
        cast_vote($this->pdo, $this->uid, $sid, 1);
        $rows = feed_stories($this->pdo, 'new', 1, 20, $this->uid);
        $row = current(array_filter($rows, fn($r) => (int)$r['id'] === $sid));
        $this->assertSame(1, (int)$row['my_vote']);
    }

    public function test_top_companies_rail_needs_min_3_stories(): void
    {
        $one = $this->mkCompany('oneco' . uniqid());     // 1 perfect story — must NOT appear
        $this->mkStory($one, 0, 0, 'active', 5);
        $many = $this->mkCompany('manyco' . uniqid());   // 3 good stories — appears
        for ($i = 0; $i < 3; $i++) $this->mkStory($many, 0, 0, 'active', 4);
        $domains = array_column(rail_top_companies($this->pdo, 50), 'domain');
        $this->assertCount(1, array_filter($domains, fn($d) => str_starts_with($d, 'manyco')));
        $this->assertCount(0, array_filter($domains, fn($d) => str_starts_with($d, 'oneco')));
    }
}
