<?php
namespace Services;

use Core\Database;
use Models\CalendarImportSubscription;

class CalendarImportService
{
    private $db;
    private $subscriptions;

    public function __construct($db = null, $subscriptions = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->subscriptions = $subscriptions ?: new CalendarImportSubscription($this->db);
    }

    public function syncDueSubscriptions($limit = 20)
    {
        $results = [];
        $items = $this->subscriptions->getEnabledDueSubscriptions($limit);
        foreach ($items as $subscription) {
            $results[] = $this->syncSubscription($subscription);
        }
        return $results;
    }

    public function syncSubscriptionById($subscriptionId)
    {
        $subscription = $this->subscriptions->getById($subscriptionId);
        if (!$subscription) {
            return ['success' => false, 'message' => '購読設定が見つかりません'];
        }
        return $this->syncSubscription($subscription);
    }

    public function syncSubscription(array $subscription)
    {
        $sourceUrl = trim((string)$subscription['source_url']);
        if ($sourceUrl === '') {
            $this->subscriptions->markSyncResult($subscription['id'], false, 'URLが設定されていません');
            return ['success' => false, 'subscription_id' => (int)$subscription['id'], 'message' => 'URLが設定されていません'];
        }

        $ics = $this->fetchIcs($sourceUrl);
        if ($ics === false) {
            $this->subscriptions->markSyncResult($subscription['id'], false, 'ICS取得に失敗しました');
            return ['success' => false, 'subscription_id' => (int)$subscription['id'], 'message' => 'ICS取得に失敗しました'];
        }

        $events = CalendarFeedService::parseIcsEvents($ics);
        $seenUids = [];
        $created = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($events as $event) {
            $uid = trim((string)($event['uid'] ?? ''));
            if ($uid === '' || empty($event['summary']) || empty($event['dtstart'])) {
                continue;
            }
            $seenUids[$uid] = true;

            $payload = $this->normalizeEventPayload($subscription, $event);
            $syncHash = sha1(json_encode($payload, JSON_UNESCAPED_UNICODE));
            $map = $this->db->fetch(
                "SELECT * FROM calendar_import_event_map WHERE subscription_id = ? AND external_uid = ? LIMIT 1",
                [(int)$subscription['id'], $uid]
            );

            if ($map) {
                if ($map['sync_hash'] !== $syncHash) {
                    $this->db->execute(
                        "UPDATE schedules
                         SET title = ?, description = ?, start_time = ?, end_time = ?, all_day = ?, location = ?, visibility = ?, updated_at = NOW()
                         WHERE id = ?",
                        [
                            $payload['title'],
                            $payload['description'],
                            $payload['start_time'],
                            $payload['end_time'],
                            $payload['all_day'],
                            $payload['location'],
                            $payload['visibility'],
                            (int)$map['schedule_id']
                        ]
                    );
                    $updated++;
                }

                $this->db->execute(
                    "UPDATE calendar_import_event_map
                     SET sync_hash = ?, last_seen_at = NOW(), is_deleted = 0
                     WHERE id = ?",
                    [$syncHash, (int)$map['id']]
                );
                continue;
            }

            $this->db->execute(
                "INSERT INTO schedules (
                    title, description, start_time, end_time, all_day, location,
                    creator_id, visibility, priority, status, repeat_type, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'normal', 'scheduled', 'none', NOW(), NOW())",
                [
                    $payload['title'],
                    $payload['description'],
                    $payload['start_time'],
                    $payload['end_time'],
                    $payload['all_day'],
                    $payload['location'],
                    (int)$subscription['user_id'],
                    $payload['visibility']
                ]
            );
            $scheduleId = (int)$this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO calendar_import_event_map (subscription_id, external_uid, schedule_id, sync_hash, last_seen_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [(int)$subscription['id'], $uid, $scheduleId, $syncHash]
            );
            $created++;
        }

        $staleMaps = $this->db->fetchAll(
            "SELECT * FROM calendar_import_event_map WHERE subscription_id = ? AND is_deleted = 0",
            [(int)$subscription['id']]
        );

        foreach ($staleMaps as $map) {
            if (isset($seenUids[$map['external_uid']])) {
                continue;
            }

            $this->db->execute("DELETE FROM schedules WHERE id = ?", [(int)$map['schedule_id']]);
            $this->db->execute(
                "UPDATE calendar_import_event_map SET is_deleted = 1, last_seen_at = NOW() WHERE id = ?",
                [(int)$map['id']]
            );
            $deleted++;
        }

        $message = sprintf('同期完了: 追加 %d / 更新 %d / 削除 %d', $created, $updated, $deleted);
        $this->subscriptions->markSyncResult($subscription['id'], true, $message);

        return [
            'success' => true,
            'subscription_id' => (int)$subscription['id'],
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'message' => $message,
        ];
    }

    public function fetchIcs($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'user_agent' => 'TeamSpace Calendar Importer/1.0'
            ],
            'https' => [
                'timeout' => 20,
                'user_agent' => 'TeamSpace Calendar Importer/1.0'
            ]
        ]);

        return @file_get_contents($url, false, $context);
    }

    private function normalizeEventPayload(array $subscription, array $event)
    {
        $isAllDay = !empty($event['all_day']);
        $startTime = $this->normalizeDateTime($event['dtstart'], $isAllDay);
        $endTime = !empty($event['dtend'])
            ? $this->normalizeDateTime($event['dtend'], $isAllDay)
            : ($isAllDay ? date('Y-m-d 23:59:59', strtotime($startTime)) : date('Y-m-d H:i:s', strtotime($startTime . ' +1 hour')));

        return [
            'title' => trim((string)$event['summary']),
            'description' => trim((string)($event['description'] ?? '')),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => $isAllDay ? 1 : 0,
            'location' => trim((string)($event['location'] ?? '')),
            'visibility' => in_array($subscription['visibility'], ['public', 'private', 'specific'], true) ? $subscription['visibility'] : 'public',
        ];
    }

    private function normalizeDateTime($value, $allDay = false)
    {
        $value = trim((string)$value);
        if ($allDay || preg_match('/^\d{8}$/', $value)) {
            return date('Y-m-d 00:00:00', strtotime($value));
        }

        if (preg_match('/^\d{8}T\d{6}Z$/', $value)) {
            $dt = \DateTimeImmutable::createFromFormat('Ymd\\THis\\Z', $value, new \DateTimeZone('UTC'));
            return $dt ? $dt->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($value));
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }
}
