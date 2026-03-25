<?php
use Models\CalendarIntegrationSetting;
use PHPUnit\Framework\TestCase;

final class CalendarIntegrationSettingTest extends TestCase
{
    public function testNormalizeDataProvidesDefaults(): void
    {
        $normalized = CalendarIntegrationSetting::normalizeData([]);

        $this->assertSame(1, $normalized['feed_enabled']);
        $this->assertSame(0, $normalized['include_private']);
        $this->assertSame(1, $normalized['include_participant']);
        $this->assertSame(1, $normalized['include_organization']);
        $this->assertSame(1, $normalized['include_public']);
        $this->assertSame(1, $normalized['allow_ics_import']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $normalized['ics_token']);
    }

    public function testNormalizeDataConvertsCheckboxValues(): void
    {
        $normalized = CalendarIntegrationSetting::normalizeData([
            'feed_enabled' => '0',
            'include_private' => '1',
            'include_participant' => 'false',
            'include_organization' => 'true',
            'include_public' => '',
            'allow_ics_import' => '0'
        ]);

        $this->assertSame(0, $normalized['feed_enabled']);
        $this->assertSame(1, $normalized['include_private']);
        $this->assertSame(0, $normalized['include_participant']);
        $this->assertSame(1, $normalized['include_organization']);
        $this->assertSame(1, $normalized['include_public']);
        $this->assertSame(0, $normalized['allow_ics_import']);
    }

    public function testGenerateTokenProducesExpectedLength(): void
    {
        $token = CalendarIntegrationSetting::generateToken();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }
}
