<?php
use PHPUnit\Framework\TestCase;
use Services\ScheduleDisplaySettings;

final class ScheduleDisplaySettingsTest extends TestCase
{
    public function testNormalizeReturnsHoursAndFormattedStrings(): void
    {
        $settings = ScheduleDisplaySettings::normalize('08:00', '18:00');

        $this->assertSame('08:00', $settings['start_time']);
        $this->assertSame('18:00', $settings['end_time']);
        $this->assertSame(8, $settings['start_hour']);
        $this->assertSame(18, $settings['end_hour']);
    }

    public function testNormalizePreventsEndBeforeStart(): void
    {
        $settings = ScheduleDisplaySettings::normalize('19:00', '08:00');

        $this->assertSame('19:00', $settings['start_time']);
        $this->assertSame('19:00', $settings['end_time']);
        $this->assertSame(19, $settings['end_hour']);
    }
}
