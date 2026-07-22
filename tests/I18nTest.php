<?php
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    public function test_lookup_fallback_chain(): void
    {
        i18n_set_lang('vi');
        $this->assertSame('Câu chuyện', t('nav_stories'));
        i18n_set_lang('en');
        $this->assertSame('Stories', t('nav_stories'));
        $this->assertSame('no_such_key', t('no_such_key'), 'missing key returns key');
    }

    public function test_both_files_have_identical_key_sets(): void
    {
        $en = require __DIR__ . '/../lang/en.php';
        $vi = require __DIR__ . '/../lang/vi.php';
        $this->assertSame([], array_diff_key($en, $vi), 'keys missing from vi');
        $this->assertSame([], array_diff_key($vi, $en), 'keys missing from en');
    }
}
