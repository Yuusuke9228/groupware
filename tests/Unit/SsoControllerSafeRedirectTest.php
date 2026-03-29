<?php

use Controllers\SsoController;
use PHPUnit\Framework\TestCase;

final class SsoControllerSafeRedirectTest extends TestCase
{
    private function invokeSafeRedirect(string $input): string
    {
        $ref = new ReflectionClass(SsoController::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('safeRedirect');
        $method->setAccessible(true);
        return (string)$method->invoke($instance, $input);
    }

    public function testRelativePathIsAllowed(): void
    {
        $this->assertSame('/daily-report', $this->invokeSafeRedirect('/daily-report'));
        $this->assertSame('/settings/security', $this->invokeSafeRedirect('settings/security'));
    }

    public function testExternalUrlFallsBackToRoot(): void
    {
        $this->assertSame('/', $this->invokeSafeRedirect('https://example.com/evil'));
        $this->assertSame('/', $this->invokeSafeRedirect('http://example.com/evil'));
    }

    public function testEmptyPathFallsBackToRoot(): void
    {
        $this->assertSame('/', $this->invokeSafeRedirect(''));
    }
}
