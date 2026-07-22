<?php
use PHPUnit\Framework\TestCase;

final class ResetTest extends TestCase
{
    public function test_reset_flow(): void
    {
        $pdo = test_db();
        $email = 'r' . uniqid() . '@x.com';
        $uid = seed_user($pdo, ['email' => $email]);

        $this->assertNull(reset_start($pdo, 'missing@x.com'));
        $token = reset_start($pdo, $email);
        $this->assertNotNull($token);

        $this->assertFalse(reset_finish($pdo, 'wrongtoken', 'newpass123'));
        $this->assertFalse(reset_finish($pdo, $token, 'short'), 'min 8 chars');
        $this->assertTrue(reset_finish($pdo, $token, 'newpass123'));
        $this->assertFalse(reset_finish($pdo, $token, 'newpass123'), 'single use');
        $this->assertNotNull(attempt_login($pdo, $email, 'newpass123'));
    }

    public function test_expired_token_rejected(): void
    {
        $pdo = test_db();
        $email = 'e' . uniqid() . '@x.com';
        seed_user($pdo, ['email' => $email]);
        $token = reset_start($pdo, $email);
        $pdo->exec("UPDATE users SET reset_expires_at = NOW() - INTERVAL 1 MINUTE
            WHERE email = '$email'");
        $this->assertFalse(reset_finish($pdo, $token, 'newpass123'));
    }
}
