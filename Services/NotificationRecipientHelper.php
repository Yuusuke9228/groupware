<?php
namespace Services;

class NotificationRecipientHelper
{
    public static function uniqueRecipients(array $userIds, array $excludedIds = [])
    {
        $excludedLookup = [];
        foreach ($excludedIds as $excludedId) {
            $excludedLookup[(int)$excludedId] = true;
        }

        $unique = [];
        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId <= 0 || isset($excludedLookup[$userId])) {
                continue;
            }

            $unique[$userId] = $userId;
        }

        return array_values($unique);
    }
}
