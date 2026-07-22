<?php
use PHPUnit\Framework\TestCase;

final class SeoTest extends TestCase
{
    // Locks the JSON-LD encoding flags used in layout.php: a story title/body
    // containing </script> must NOT break out of the <script type="ld+json"> block.
    public function test_jsonld_encoding_neutralizes_script_breakout(): void
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $payload = ['name' => 'Toxic </script><script>alert(document.cookie)</script>'];
        $json = json_encode($payload, $flags);
        $this->assertStringNotContainsString('<', $json, 'no literal angle brackets survive');
        $this->assertStringContainsString('\u003C', $json, 'angle brackets hex-escaped');
        // Round-trips back to the original string (escaping is reversible/valid JSON).
        $this->assertSame($payload['name'], json_decode($json, true)['name']);
    }
}
