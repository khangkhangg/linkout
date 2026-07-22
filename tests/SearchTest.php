<?php
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    private PDO $pdo;
    private int $uid;

    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events');
        $this->uid = seed_user($this->pdo);
    }

    public function test_fulltext_matches_title_and_body_active_only(): void
    {
        $cid = company_find_or_create($this->pdo, 'SearchCo', 'searchco' . uniqid() . '.com',
            $this->uid)['company']['id'];
        $mk = fn(string $title, string $body, string $status) => $this->pdo
            ->prepare("INSERT INTO stories (user_id, company_id, title, body,
                r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit,
                recommend, status) VALUES (?,?,?,?,3,3,3,3,3,3,1,?)")
            ->execute([$this->uid, $cid, $title, $body, $status]);
        $mk('Toxic overtime culture', 'they made us work weekends', 'active');
        $mk('Fine place', 'nothing about that word here', 'active');
        $mk('Toxic managers everywhere', 'hidden story', 'removed');

        $rows = story_search($this->pdo, 'toxic');
        $titles = array_column($rows, 'title');
        $this->assertContains('Toxic overtime culture', $titles);
        $this->assertNotContains('Toxic managers everywhere', $titles, 'removed excluded');

        $rows = story_search($this->pdo, 'weekends');
        $this->assertNotEmpty($rows, 'body matches too');

        $this->assertSame([], story_search($this->pdo, ''));
    }

    public function test_stories_for_company_active_newest_first(): void
    {
        $cid = company_find_or_create($this->pdo, 'ListCo', 'listco' . uniqid() . '.com',
            $this->uid)['company']['id'];
        $a = story_create($this->pdo, $this->uid, $cid, ['title' => 'older', 'body' => 'b',
            'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3, 'r_balance' => 3,
            'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
        $this->pdo->exec("UPDATE stories SET created_at = created_at - INTERVAL 1 DAY WHERE id=$a");
        $b = story_create($this->pdo, $this->uid, $cid, ['title' => 'newer', 'body' => 'b',
            'r_leadership' => 3, 'r_culture' => 3, 'r_benefits' => 3, 'r_balance' => 3,
            'r_growth' => 3, 'r_exit' => 3, 'recommend' => 1])['story_id'];
        $rows = stories_for_company($this->pdo, $cid);
        $this->assertSame([$b, $a], array_map('intval', array_column($rows, 'id')));
    }
}
