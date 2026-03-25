<?php
namespace Models;

use Core\Database;

class CalendarIntegrationSetting
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    public function getByUserId($userId)
    {
        return $this->db->fetch(
            "SELECT * FROM calendar_integration_settings WHERE user_id = ? LIMIT 1",
            [(int)$userId]
        );
    }

    public function getByToken($token)
    {
        return $this->db->fetch(
            "SELECT * FROM calendar_integration_settings WHERE ics_token = ? LIMIT 1",
            [trim((string)$token)]
        );
    }

    public function getOrCreateByUserId($userId)
    {
        $settings = $this->getByUserId($userId);
        if ($settings) {
            return $settings;
        }

        $defaults = self::normalizeData([]);
        $this->db->execute(
            "INSERT INTO calendar_integration_settings (
                user_id,
                ics_token,
                feed_enabled,
                include_private,
                include_participant,
                include_organization,
                include_public,
                allow_ics_import
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int)$userId,
                $defaults['ics_token'],
                $defaults['feed_enabled'],
                $defaults['include_private'],
                $defaults['include_participant'],
                $defaults['include_organization'],
                $defaults['include_public'],
                $defaults['allow_ics_import']
            ]
        );

        return $this->getByUserId($userId);
    }

    public function updateForUser($userId, array $data)
    {
        $current = $this->getOrCreateByUserId($userId);
        $normalized = self::normalizeData(array_merge($current, $data));

        return $this->db->execute(
            "UPDATE calendar_integration_settings
             SET feed_enabled = ?,
                 include_private = ?,
                 include_participant = ?,
                 include_organization = ?,
                 include_public = ?,
                 allow_ics_import = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE user_id = ?",
            [
                $normalized['feed_enabled'],
                $normalized['include_private'],
                $normalized['include_participant'],
                $normalized['include_organization'],
                $normalized['include_public'],
                $normalized['allow_ics_import'],
                (int)$userId
            ]
        );
    }

    public function regenerateToken($userId)
    {
        $this->getOrCreateByUserId($userId);
        $token = self::generateToken();

        $this->db->execute(
            "UPDATE calendar_integration_settings
             SET ics_token = ?, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = ?",
            [$token, (int)$userId]
        );

        return $this->getByUserId($userId);
    }

    public static function normalizeData(array $data)
    {
        return [
            'ics_token' => !empty($data['ics_token']) ? trim((string)$data['ics_token']) : self::generateToken(),
            'feed_enabled' => self::normalizeBool($data['feed_enabled'] ?? 1, true),
            'include_private' => self::normalizeBool($data['include_private'] ?? 0, false),
            'include_participant' => self::normalizeBool($data['include_participant'] ?? 1, true),
            'include_organization' => self::normalizeBool($data['include_organization'] ?? 1, true),
            'include_public' => self::normalizeBool($data['include_public'] ?? 1, true),
            'allow_ics_import' => self::normalizeBool($data['allow_ics_import'] ?? 1, true),
        ];
    }

    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    private static function normalizeBool($value, $default)
    {
        if ($value === null || $value === '') {
            return $default ? 1 : 0;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return $default ? 1 : 0;
        }

        return $normalized ? 1 : 0;
    }
}
