<?php
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    private PDO $pdo;
    protected function setUp(): void
    {
        $this->pdo = test_db();
        $this->pdo->exec('DELETE FROM rate_events'); // isolation between tests
    }

    public function test_signup_creates_unverified_user_with_handle(): void
    {
        $r = signup($this->pdo, 'a' . uniqid() . '@x.com', 'secret123');
        $this->assertTrue($r['ok']);
        $u = $this->pdo->query('SELECT * FROM users WHERE id=' . $r['user_id'])->fetch();
        $this->assertNull($u['email_verified_at']);
        $this->assertNotEmpty($u['handle']);
        $this->assertNotEmpty($r['confirm_token']);
    }

    public function test_signup_rejects_duplicate_email_and_short_password(): void
    {
        $email = 'dup' . uniqid() . '@x.com';
        signup($this->pdo, $email, 'secret123');
        $this->assertFalse(signup($this->pdo, $email, 'secret123')['ok']);
        $this->assertFalse(signup($this->pdo, 'ok@x.com', 'short')['ok']);
    }

    public function test_confirm_then_login(): void
    {
        $email = 'c' . uniqid() . '@x.com';
        $r = signup($this->pdo, $email, 'secret123');
        $this->assertNull(attempt_login($this->pdo, $email, 'wrongpass'));
        $this->assertTrue(confirm_email($this->pdo, $r['confirm_token']));
        $this->assertFalse(confirm_email($this->pdo, $r['confirm_token']), 'token single-use');
        $u = attempt_login($this->pdo, $email, 'secret123');
        $this->assertNotNull($u);
        $this->assertNotNull($u['email_verified_at']);
    }

    public function test_banned_user_cannot_login(): void
    {
        $email = 'b' . uniqid() . '@x.com';
        $r = signup($this->pdo, $email, 'secret123');
        confirm_email($this->pdo, $r['confirm_token']);
        $this->pdo->exec('UPDATE users SET banned_at=NOW() WHERE id=' . $r['user_id']);
        $this->assertNull(attempt_login($this->pdo, $email, 'secret123'));
    }
}
