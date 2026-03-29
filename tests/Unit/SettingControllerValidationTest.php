<?php

use Controllers\SettingController;
use PHPUnit\Framework\TestCase;

final class SettingControllerValidationTest extends TestCase
{
    private function invokeNormalize(string $key, $value): string
    {
        $ref = new ReflectionClass(SettingController::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('normalizeSettingValue');
        $method->setAccessible(true);
        return (string)$method->invoke($instance, $key, $value);
    }

    public function testBooleanSettingsAreNormalized(): void
    {
        $this->assertSame('1', $this->invokeNormalize('sso_enabled', true));
        $this->assertSame('0', $this->invokeNormalize('pwa_enabled', 'false'));
        $this->assertSame('1', $this->invokeNormalize('scim_enabled', '1'));
    }

    public function testSsoProviderValidation(): void
    {
        $this->assertSame('oidc', $this->invokeNormalize('sso_provider', 'oidc'));
        $this->assertSame('saml', $this->invokeNormalize('sso_provider', 'saml'));

        $this->expectException(InvalidArgumentException::class);
        $this->invokeNormalize('sso_provider', 'local');
    }

    public function testScimBasePathValidation(): void
    {
        $this->assertSame('/api/scim/v2', $this->invokeNormalize('scim_base_path', '/api/scim/v2/'));

        $this->expectException(InvalidArgumentException::class);
        $this->invokeNormalize('scim_base_path', 'api/scim/v2');
    }
}
