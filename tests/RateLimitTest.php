<?php
use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase
{
    public function test_limit_blocks_at_max_within_window(): void
    {
        $pdo = test_db();
        $uid = seed_user($pdo);
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue(rate_limit_ok($pdo, $uid, 'story', 3, 1440));
            rate_note($pdo, $uid, 'story');
        }
        $this->assertFalse(rate_limit_ok($pdo, $uid, 'story', 3, 1440));
        // outside the window it frees up
        $pdo->exec("UPDATE rate_events SET created_at = created_at - INTERVAL 2 DAY
                    WHERE user_id = $uid");
        $this->assertTrue(rate_limit_ok($pdo, $uid, 'story', 3, 1440));
    }
}
