<?php

use PHPUnit\Framework\TestCase;
use Services\SsoService;

final class SsoServiceAttributeTest extends TestCase
{
    public function testExtractAttributeSupportsDirectAndDotPath(): void
    {
        $ref = new ReflectionClass(SsoService::class);
        $service = $ref->newInstanceWithoutConstructor();

        $claims = [
            'email' => 'user@example.com',
            'profile' => [
                'name' => 'Test User',
                'department' => [
                    'code' => 'DEV'
                ]
            ]
        ];

        $this->assertSame('user@example.com', $service->extractAttribute($claims, 'email'));
        $this->assertSame('Test User', $service->extractAttribute($claims, 'profile.name'));
        $this->assertSame('DEV', $service->extractAttribute($claims, 'profile.department.code'));
        $this->assertNull($service->extractAttribute($claims, 'profile.unknown'));
    }
}
