<?php
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    protected function setUp(): void { router_reset(); }

    public function test_static_route_dispatches(): void
    {
        route('GET', '/', fn() => 'home');
        $this->assertSame('home', dispatch('GET', '/'));
    }

    public function test_params_are_passed(): void
    {
        route('GET', '/story/{id}', fn($p) => 'story-' . $p['id']);
        $this->assertSame('story-42', dispatch('GET', '/story/42'));
    }

    public function test_unknown_route_returns_null(): void
    {
        $this->assertNull(dispatch('GET', '/nope'));
    }

    public function test_method_mismatch_returns_null(): void
    {
        route('POST', '/login', fn() => 'x');
        $this->assertNull(dispatch('GET', '/login'));
    }
}
