<?php
namespace Services;

class DateRangeHelper
{
    /**
     * 日付のみ指定された範囲境界を日時へ正規化する
     *
     * @param string $value
     * @param bool $isStart
     * @return string
     */
    public static function normalizeBoundary($value, $isStart)
    {
        $value = trim((string)$value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ($isStart ? ' 00:00:00' : ' 23:59:59');
        }

        return $value;
    }
}
