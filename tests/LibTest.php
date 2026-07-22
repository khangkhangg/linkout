<?php
use PHPUnit\Framework\TestCase;

final class LibTest extends TestCase
{
    public function test_normalize_domain_strips_scheme_www_path_case(): void
    {
        $this->assertSame('companyx.com', normalize_domain('  HTTPS://WWW.CompanyX.com/careers?a=1 '));
        $this->assertSame('sub.companyx.com.vn', normalize_domain('sub.companyx.com.vn'));
    }

    public function test_normalize_domain_rejects_invalid(): void
    {
        $this->assertNull(normalize_domain('not a domain'));
        $this->assertNull(normalize_domain('nodot'));
        $this->assertNull(normalize_domain(''));
        $this->assertNull(normalize_domain('-bad.com'));
    }

    public function test_freemail_blocklist(): void
    {
        foreach (['gmail.com','yahoo.com','outlook.com','hotmail.com','icloud.com',
                  'proton.me','protonmail.com','zoho.com','aol.com','mail.com',
                  'gmx.com','yandex.com','qq.com','163.com','ymail.com','rocketmail.com',
                  'hotmail.co.uk','mail.ru'] as $d) {
            $this->assertTrue(is_freemail($d), $d);
        }
        $this->assertFalse(is_freemail('fpt.com.vn'));
        $this->assertFalse(is_freemail('anthropic.com'));
        $this->assertTrue(is_freemail('GMAIL.COM'), 'case-insensitive');
        $this->assertTrue(is_freemail('mail.gmail.com'), 'subdomain of blocked provider');
        $this->assertTrue(is_freemail('anything.yahoo.com'), 'subdomain of blocked provider');
        $this->assertFalse(is_freemail('notgmail.com'), 'suffix must match on label boundary');
    }

    public function test_generate_handle_shape_and_variety(): void
    {
        $h = generate_handle();
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+[A-Z][a-z]+\d{1,3}$/', $h);
        $this->assertGreaterThan(1, count(array_unique(
            array_map(fn() => generate_handle(), range(1, 20)))));
    }

    public function test_random_code_is_six_digits(): void
    {
        $this->assertMatchesRegularExpression('/^\d{6}$/', random_code());
    }

    public function test_time_ago(): void
    {
        $this->assertSame('just now', time_ago(date('Y-m-d H:i:s')));
    }
}
