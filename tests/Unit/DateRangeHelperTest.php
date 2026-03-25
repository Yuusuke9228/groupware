<?php
use PHPUnit\Framework\TestCase;
use Services\DateRangeHelper;

final class DateRangeHelperTest extends TestCase
{
    public function testNormalizesDateOnlyStartBoundary(): void
    {
        $this->assertSame('2026-03-25 00:00:00', DateRangeHelper::normalizeBoundary('2026-03-25', true));
    }

    public function testNormalizesDateOnlyEndBoundary(): void
    {
        $this->assertSame('2026-03-25 23:59:59', DateRangeHelper::normalizeBoundary('2026-03-25', false));
    }

    public function testKeepsDateTimeBoundaryUnchanged(): void
    {
        $this->assertSame('2026-03-25 12:34:56', DateRangeHelper::normalizeBoundary('2026-03-25 12:34:56', true));
    }
}
