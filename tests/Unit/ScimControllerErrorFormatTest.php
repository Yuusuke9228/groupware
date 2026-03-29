<?php

use Controllers\ScimController;
use PHPUnit\Framework\TestCase;

final class ScimControllerErrorFormatTest extends TestCase
{
    private function invokeScimError(string $detail, int $statusCode, ?string $scimType = null): array
    {
        $ref = new ReflectionClass(ScimController::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('scimError');
        $method->setAccessible(true);
        /** @var array $result */
        $result = $method->invoke($instance, $detail, $statusCode, $scimType);
        return $result;
    }

    public function testErrorResponseContainsRequiredFields(): void
    {
        $error = $this->invokeScimError('invalid token', 401, 'invalidValue');

        $this->assertSame(['urn:ietf:params:scim:api:messages:2.0:Error'], $error['schemas']);
        $this->assertSame('401', $error['status']);
        $this->assertSame('invalid token', $error['detail']);
        $this->assertSame(401, $error['code']);
        $this->assertSame('invalidValue', $error['scimType']);
    }

    public function testScimTypeIsOptional(): void
    {
        $error = $this->invokeScimError('forbidden', 403);
        $this->assertArrayNotHasKey('scimType', $error);
    }
}
