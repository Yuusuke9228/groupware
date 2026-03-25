<?php
use Models\CalendarImportSubscription;
use PHPUnit\Framework\TestCase;

final class CalendarImportSubscriptionTest extends TestCase
{
    public function testNormalizeDataAppliesBoundsAndDefaults(): void
    {
        $normalized = CalendarImportSubscription::normalizeData([
            'name' => '',
            'source_url' => ' https://example.test/a.ics ',
            'is_enabled' => 'false',
            'sync_interval_minutes' => 1,
            'visibility' => 'unexpected'
        ]);

        $this->assertSame('外部カレンダー', $normalized['name']);
        $this->assertSame('https://example.test/a.ics', $normalized['source_url']);
        $this->assertSame(0, $normalized['is_enabled']);
        $this->assertSame(5, $normalized['sync_interval_minutes']);
        $this->assertSame('public', $normalized['visibility']);
    }
}
