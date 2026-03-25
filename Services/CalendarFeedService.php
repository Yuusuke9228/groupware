<?php
namespace Services;

class CalendarFeedService
{
    public static function buildCalendar(array $rows, $appName = 'TeamSpace')
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . self::escapeText($appName) . '//JP',
            'CALSCALE:GREGORIAN'
        ];

        foreach ($rows as $row) {
            $lines = array_merge($lines, self::buildEventLines($row));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    public static function buildEventLines(array $row)
    {
        $eventId = (int)($row['id'] ?? 0);
        $uid = 'schedule-' . $eventId . '@groupware.local';
        $allDay = !empty($row['all_day']);
        $summary = self::escapeText((string)($row['title'] ?? ''));
        $description = self::escapeText((string)($row['description'] ?? ''));
        $location = self::escapeText((string)($row['location'] ?? ''));
        $link = trim((string)($row['link'] ?? ''));

        $lines = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . gmdate('Ymd\THis\Z')
        ];

        if ($allDay) {
            $start = date('Ymd', strtotime((string)$row['start_time']));
            $endSource = !empty($row['end_time']) ? (string)$row['end_time'] : (string)$row['start_time'];
            $endDate = new \DateTimeImmutable(date('Y-m-d', strtotime($endSource)));
            $endExclusive = $endDate->modify('+1 day')->format('Ymd');
            $lines[] = 'DTSTART;VALUE=DATE:' . $start;
            $lines[] = 'DTEND;VALUE=DATE:' . $endExclusive;
        } else {
            $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', strtotime((string)$row['start_time']));
            $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', strtotime((string)$row['end_time']));
        }

        $lines[] = 'SUMMARY:' . $summary;

        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $description;
        }

        if ($location !== '') {
            $lines[] = 'LOCATION:' . $location;
        }

        if ($link !== '') {
            $lines[] = 'URL:' . self::escapeText($link);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    public static function escapeText($text)
    {
        $text = str_replace("\\", "\\\\", (string)$text);
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace(",", "\\,", $text);
        $text = str_replace(";", "\\;", $text);

        return $text;
    }

    public static function buildAbsoluteUrl($path, $appUrl = '', $scheme = 'http', $host = 'localhost', $basePath = '')
    {
        $path = '/' . ltrim((string)$path, '/');

        if ($appUrl !== '') {
            return rtrim($appUrl, '/') . $path;
        }

        return rtrim($scheme . '://' . $host . $basePath, '/') . $path;
    }

    public static function parseIcsEvents($ics)
    {
        $ics = preg_replace("/\r\n[ \t]/", '', (string)$ics);
        $events = [];
        $current = null;

        foreach (preg_split('/\r\n|\n|\r/', $ics) as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                if (!empty($current)) {
                    $events[] = $current;
                }
                $current = null;
                continue;
            }

            if ($current === null || strpos($line, ':') === false) {
                continue;
            }

            list($rawName, $value) = explode(':', $line, 2);
            $property = strtoupper((string)strtok($rawName, ';'));
            $value = trim($value);

            $rawUpper = strtoupper($rawName);

            if ($property === 'SUMMARY') {
                $current['summary'] = self::unescapeText($value);
            } elseif ($property === 'DESCRIPTION') {
                $current['description'] = self::unescapeText($value);
            } elseif ($property === 'LOCATION') {
                $current['location'] = self::unescapeText($value);
            } elseif ($property === 'UID') {
                $current['uid'] = $value;
            } elseif ($property === 'DTSTART') {
                $current['dtstart'] = $value;
                if (strpos($rawUpper, 'VALUE=DATE') !== false) {
                    $current['all_day'] = 1;
                }
            } elseif ($property === 'DTEND') {
                $current['dtend'] = $value;
                if (strpos($rawUpper, 'VALUE=DATE') !== false) {
                    $current['all_day'] = 1;
                }
            }
        }

        return $events;
    }

    public static function unescapeText($text)
    {
        $text = str_replace('\\n', "\n", (string)$text);
        $text = str_replace(['\\,', '\\;', '\\\\'], [',', ';', '\\'], $text);
        return $text;
    }
}
