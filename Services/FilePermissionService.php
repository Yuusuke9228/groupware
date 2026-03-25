<?php
namespace Services;

use Core\Database;

class FilePermissionService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    public function canViewFolder(array $folder, array $user)
    {
        return $this->canAccess('folder', $folder, $user, 'view');
    }

    public function canEditFolder(array $folder, array $user)
    {
        return $this->canAccess('folder', $folder, $user, 'edit');
    }

    public function canAdminFolder(array $folder, array $user)
    {
        return $this->canAccess('folder', $folder, $user, 'admin');
    }

    public function canViewFile(array $file, array $user)
    {
        return $this->canAccess('file', $file, $user, 'view');
    }

    public function canEditFile(array $file, array $user)
    {
        return $this->canAccess('file', $file, $user, 'edit');
    }

    public function canApproveFile(array $file, array $user)
    {
        return $this->canAccess('file', $file, $user, 'approve');
    }

    public function canAdminFile(array $file, array $user)
    {
        return $this->canAccess('file', $file, $user, 'admin');
    }

    public function getPermissionSummary($resourceType, $resourceId)
    {
        $rows = $this->db->fetchAll(
            "SELECT fp.*, o.name AS organization_name, u.display_name AS user_name
             FROM file_permissions fp
             LEFT JOIN organizations o ON o.id = fp.subject_id AND fp.subject_type = 'organization'
             LEFT JOIN users u ON u.id = fp.subject_id AND fp.subject_type = 'user'
             WHERE fp.resource_type = ? AND fp.resource_id = ?
             ORDER BY FIELD(fp.permission_type, 'view', 'edit', 'approve', 'admin'), fp.subject_type, fp.subject_id",
            [(string)$resourceType, (int)$resourceId]
        );

        $summary = [
            'view' => ['organizations' => [], 'users' => []],
            'edit' => ['organizations' => [], 'users' => []],
            'approve' => ['organizations' => [], 'users' => []],
            'admin' => ['organizations' => [], 'users' => []],
        ];

        foreach ($rows as $row) {
            $label = $row['subject_type'] === 'organization'
                ? ($row['organization_name'] ?? ('組織#' . $row['subject_id']))
                : ($row['user_name'] ?? ('ユーザー#' . $row['subject_id']));
            $summary[$row['permission_type']][$row['subject_type'] . 's'][] = [
                'id' => (int)$row['subject_id'],
                'label' => $label,
            ];
        }

        return $summary;
    }

    public function replacePermissions($resourceType, $resourceId, array $permissionMap, $actorUserId)
    {
        $resourceType = $resourceType === 'folder' ? 'folder' : 'file';
        $resourceId = (int)$resourceId;
        $actorUserId = (int)$actorUserId;

        $this->db->execute(
            "DELETE FROM file_permissions WHERE resource_type = ? AND resource_id = ?",
            [$resourceType, $resourceId]
        );

        foreach ($permissionMap as $permissionType => $subjects) {
            if (!in_array($permissionType, ['view', 'edit', 'approve', 'admin'], true)) {
                continue;
            }

            foreach (['organization', 'user'] as $subjectType) {
                $key = $subjectType . '_ids';
                foreach ($this->normalizeIds($subjects[$key] ?? []) as $subjectId) {
                    $this->db->execute(
                        "INSERT INTO file_permissions (resource_type, resource_id, subject_type, subject_id, permission_type, created_by)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$resourceType, $resourceId, $subjectType, $subjectId, $permissionType, $actorUserId]
                    );
                }
            }
        }
    }

    public function extractPermissionMapFromRequest(array $request)
    {
        $map = [];
        foreach (['view', 'edit', 'approve', 'admin'] as $permissionType) {
            $map[$permissionType] = [
                'organization_ids' => $this->normalizeIds($request[$permissionType . '_organization_ids'] ?? []),
                'user_ids' => $this->normalizeIds($request[$permissionType . '_user_ids'] ?? []),
            ];
        }
        return $map;
    }

    public function normalizeIds($values)
    {
        if (!is_array($values)) {
            $values = $values === null || $values === '' ? [] : [$values];
        }

        $unique = [];
        foreach ($values as $value) {
            $value = (int)$value;
            if ($value > 0) {
                $unique[$value] = $value;
            }
        }
        return array_values($unique);
    }

    public function canAccess($resourceType, array $resource, array $user, $requiredPermission)
    {
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        if ($resourceType === 'folder' && (int)($resource['created_by'] ?? 0) === $userId) {
            return true;
        }

        if ($resourceType === 'file' && (int)($resource['uploaded_by'] ?? 0) === $userId) {
            return true;
        }

        $requiredLevel = $this->permissionLevel($requiredPermission);
        $maxLevel = $this->maxGrantedLevel($resourceType, (int)$resource['id'], $userId);

        if ($maxLevel === 0 && $resourceType === 'file' && !empty($resource['folder_id'])) {
            $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [(int)$resource['folder_id']]);
            if ($folder) {
                return $this->canAccess('folder', $folder, $user, $requiredPermission);
            }
        }

        if ($maxLevel < 0) {
            return false;
        }

        if ($maxLevel === 0) {
            return true;
        }

        return $maxLevel >= $requiredLevel;
    }

    private function maxGrantedLevel($resourceType, $resourceId, $userId)
    {
        $organizationIds = $this->db->fetchAll(
            "SELECT organization_id FROM user_organizations WHERE user_id = ?",
            [$userId]
        );
        $organizationIds = array_map('intval', array_column($organizationIds, 'organization_id'));

        $rows = $this->db->fetchAll(
            "SELECT permission_type, subject_type, subject_id
             FROM file_permissions
             WHERE resource_type = ? AND resource_id = ?",
            [$resourceType, $resourceId]
        );

        if (empty($rows)) {
            return 0;
        }

        $maxLevel = 0;
        foreach ($rows as $row) {
            if ($row['subject_type'] === 'user' && (int)$row['subject_id'] === $userId) {
                $maxLevel = max($maxLevel, $this->permissionLevel($row['permission_type']));
            }
            if ($row['subject_type'] === 'organization' && in_array((int)$row['subject_id'], $organizationIds, true)) {
                $maxLevel = max($maxLevel, $this->permissionLevel($row['permission_type']));
            }
        }

        return $maxLevel > 0 ? $maxLevel : -1;
    }

    private function permissionLevel($permissionType)
    {
        static $levels = [
            'view' => 1,
            'edit' => 2,
            'approve' => 3,
            'admin' => 4,
        ];

        return $levels[$permissionType] ?? 0;
    }
}
