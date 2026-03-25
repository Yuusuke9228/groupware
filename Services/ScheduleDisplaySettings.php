<?php
namespace Services;

class ScheduleDisplaySettings
{
    public static function normalize($startTime, $endTime)
    {
        $startHour = self::normalizeHour($startTime, 0);
        $endHour = self::normalizeHour($endTime, 23);

        if ($endHour < $startHour) {
            $endHour = $startHour;
        }

        return [
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'start_hour' => $startHour,
            'end_hour' => $endHour,
        ];
    }

    public static function normalizeHour($time, $default)
    {
        if (is_int($time) || ctype_digit((string)$time)) {
            $hour = (int)$time;
            return max(0, min(23, $hour));
        }

        $time = trim((string)$time);
        if (!preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
            return (int)$default;
        }

        return (int)substr($time, 0, 2);
    }
}
