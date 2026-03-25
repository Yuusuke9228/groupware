<?php
use PHPUnit\Framework\TestCase;
use Services\CalendarFeedService;

final class CalendarFeedServiceTest extends TestCase
{
    public function testEscapeTextEscapesReservedCharacters(): void
    {
        $escaped = CalendarFeedService::escapeText("会議,部屋A;確認\n詳細");

        $this->assertSame('会議\\,部屋A\\;確認\\n詳細', $escaped);
    }

    public function testBuildCalendarIncludesTimedEventUrl(): void
    {
        $ics = CalendarFeedService::buildCalendar([
            [
                'id' => 10,
                'title' => '営業会議',
                'description' => '定例',
                'location' => '第1会議室',
                'start_time' => '2026-03-25 09:00:00',
                'end_time' => '2026-03-25 10:00:00',
                'all_day' => 0,
                'link' => 'http://example.test/schedule/view/10'
            ]
        ], 'Groupware');

        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('SUMMARY:営業会議', $ics);
        $this->assertStringContainsString('LOCATION:第1会議室', $ics);
        $this->assertStringContainsString('URL:http://example.test/schedule/view/10', $ics);
        $this->assertStringContainsString('DTSTART:20260325T000000Z', $ics);
        $this->assertStringContainsString('DTEND:20260325T010000Z', $ics);
    }

    public function testBuildCalendarIncludesAllDayEvent(): void
    {
        $ics = CalendarFeedService::buildCalendar([
            [
                'id' => 11,
                'title' => '休暇',
                'start_time' => '2026-03-25 00:00:00',
                'end_time' => '2026-03-25 23:59:59',
                'all_day' => 1
            ]
        ]);

        $this->assertStringContainsString('DTSTART;VALUE=DATE:20260325', $ics);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20260326', $ics);
    }

    public function testParseIcsEventsSupportsFoldedLines(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:出張申請\r\nDESCRIPTION:旅費明細\r\n 続き\r\nDTSTART:20260325T010000Z\r\nDTEND:20260325T020000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $events = CalendarFeedService::parseIcsEvents($ics);

        $this->assertCount(1, $events);
        $this->assertSame('出張申請', $events[0]['summary']);
        $this->assertSame('旅費明細続き', $events[0]['description']);
    }

    public function testParseIcsEventsIncludesUidAndAllDayMarker(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:event-1\r\nDTSTART;VALUE=DATE:20260325\r\nDTEND;VALUE=DATE:20260326\r\nSUMMARY:休暇\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $events = CalendarFeedService::parseIcsEvents($ics);

        $this->assertCount(1, $events);
        $this->assertSame('event-1', $events[0]['uid']);
        $this->assertSame(1, $events[0]['all_day']);
        $this->assertSame('20260325', $events[0]['dtstart']);
    }

    public function testBuildAbsoluteUrlUsesFallbackHost(): void
    {
        $url = CalendarFeedService::buildAbsoluteUrl('/integrations/calendar.ics', '', 'https', 'example.test', '/groupware');
        $this->assertSame('https://example.test/groupware/integrations/calendar.ics', $url);
    }
}
