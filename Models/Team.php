<?php
// models/Team.php
namespace Models;

use Core\Database;

class Team
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @param mixed $members
     * @return int[]
     */
    private function normalizeMemberIds($members)
    {
        if (!is_array($members)) {
            return [];
        }

        $normalized = [];
        foreach ($members as $memberId) {
            if (!is_scalar($memberId)) {
                continue;
            }

            $id = (int)$memberId;
            if ($id <= 0) {
                continue;
            }

            $normalized[$id] = $id;
        }

        return array_values($normalized);
    }

    /**
     * @param mixed $role
     * @return string
     */
    private function normalizeRole($role)
    {
        return $role === 'admin' ? 'admin' : 'member';
    }

    /**
     * @param int[] $userIds
     * @return int[]
     */
    private function filterExistingUserIds(array $userIds)
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $userIds), static function ($id) {
            return $id > 0;
        })));
        if (empty($normalized)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($normalized), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id FROM users WHERE id IN ({$placeholders})",
            $normalized
        );

        $valid = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) {
                $valid[$id] = $id;
            }
        }
        return array_values($valid);
    }

    /**
     * チームを作成
     * 
     * @param array $data チームデータ
     * @param int $userId 作成者ID
     * @return int|bool 成功時は新規チームID、失敗時はfalse
     */
    public function create($data, $userId)
    {
        try {
            $creatorId = (int)$userId;
            if ($creatorId <= 0) {
                throw new \InvalidArgumentException('Invalid creator user id');
            }

            $memberIds = $this->normalizeMemberIds($data['members'] ?? []);
            $memberRoles = is_array($data['member_roles'] ?? null) ? $data['member_roles'] : [];
            $validUserIds = $this->filterExistingUserIds(array_merge([$creatorId], $memberIds));
            if (!in_array($creatorId, $validUserIds, true)) {
                throw new \RuntimeException('Creator user does not exist');
            }
            $memberIds = array_values(array_filter($memberIds, static function ($id) use ($validUserIds) {
                return in_array((int)$id, $validUserIds, true);
            }));

            $this->db->beginTransaction();

            $sql = "INSERT INTO teams (
                name, 
                description, 
                created_by
            ) VALUES (?, ?, ?)";

            $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                $creatorId
            ]);

            $teamId = (int)$this->db->lastInsertId();
            if ($teamId <= 0) {
                throw new \RuntimeException('Failed to get team id after insert');
            }

            // 作成者を管理者として追加
            $sql = "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'admin')
                    ON DUPLICATE KEY UPDATE role = VALUES(role)";
            $this->db->execute($sql, [$teamId, $creatorId]);

            // メンバーが指定されていれば追加
            if (!empty($memberIds)) {
                foreach ($memberIds as $memberId) {
                    if ($memberId === $creatorId) { // 作成者は既に追加済み
                        continue;
                    }

                    $role = $this->normalizeRole($memberRoles[(string)$memberId] ?? $memberRoles[$memberId] ?? 'member');
                    $sql = "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE role = VALUES(role)";
                    $this->db->execute($sql, [$teamId, $memberId, $role]);
                }
            }

            $this->db->commit();
            return $teamId;
        } catch (\Exception $e) {
            try {
                if ($this->db->getConnection()->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Throwable $ignore) {
            }
            error_log("Error creating team: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チームを更新
     * 
     * @param int $id チームID
     * @param array $data チームデータ
     * @return bool 成功時true、失敗時false
     */
    public function update($id, $data)
    {
        try {
            $this->db->beginTransaction();

            $memberIds = null;
            $memberRoles = [];
            if (isset($data['members'])) {
                $memberIds = $this->normalizeMemberIds($data['members']);
                $memberRoles = is_array($data['member_roles'] ?? null) ? $data['member_roles'] : [];
                $memberIds = $this->filterExistingUserIds($memberIds);
            }

            $sql = "UPDATE teams SET 
                    name = ?, 
                    description = ?, 
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $result = $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                $id
            ]);

            // メンバーの更新
            if ($memberIds !== null) {
                // 現在のメンバーを取得
                $currentMembers = $this->getMembers($id);
                $currentMemberIds = array_map('intval', array_column($currentMembers, 'user_id'));

                // 新しいメンバーを追加
                foreach ($memberIds as $memberId) {
                    if (!in_array($memberId, $currentMemberIds)) {
                        $role = $this->normalizeRole($memberRoles[(string)$memberId] ?? $memberRoles[$memberId] ?? 'member');
                        $sql = "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)";
                        $this->db->execute($sql, [$id, $memberId, $role]);
                    } else {
                        // 既存メンバーの役割を更新
                        if (isset($memberRoles[(string)$memberId]) || isset($memberRoles[$memberId])) {
                            $sql = "UPDATE team_members SET role = ? WHERE team_id = ? AND user_id = ?";
                            $this->db->execute($sql, [$this->normalizeRole($memberRoles[(string)$memberId] ?? $memberRoles[$memberId]), $id, $memberId]);
                        }
                    }
                }

                // 削除されたメンバーを削除
                foreach ($currentMemberIds as $memberId) {
                    if (!in_array($memberId, $memberIds)) {
                        $sql = "DELETE FROM team_members WHERE team_id = ? AND user_id = ?";
                        $this->db->execute($sql, [$id, $memberId]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            try {
                if ($this->db->getConnection()->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Throwable $ignore) {
            }
            error_log("Error updating team: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チームを削除
     * 
     * @param int $id チームID
     * @return bool 成功時true、失敗時false
     */
    public function delete($id)
    {
        try {
            return $this->db->execute("DELETE FROM teams WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting team: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チームの詳細を取得
     * 
     * @param int $id チームID
     * @return array|null チーム情報
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT t.*, u.display_name as creator_name 
                    FROM teams t
                    LEFT JOIN users u ON t.created_by = u.id
                    WHERE t.id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting team: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 全チームリストを取得
     * 
     * @return array チームリスト
     */
    public function getAll()
    {
        try {
            $sql = "SELECT t.*, u.display_name as creator_name,
                    (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count
                    FROM teams t
                    LEFT JOIN users u ON t.created_by = u.id
                    ORDER BY t.name";

            return $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            error_log("Error getting all teams: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ユーザーが所属するチームリストを取得
     * 
     * @param int $userId ユーザーID
     * @return array チームリスト
     */
    public function getUserTeams($userId)
    {
        try {
            $sql = "SELECT t.*, tm.role as user_role, u.display_name as creator_name,
                    (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count
                    FROM teams t
                    JOIN team_members tm ON t.id = tm.team_id
                    LEFT JOIN users u ON t.created_by = u.id
                    WHERE tm.user_id = ?
                    ORDER BY t.name";

            return $this->db->fetchAll($sql, [$userId]);
        } catch (\Exception $e) {
            error_log("Error getting user teams: " . $e->getMessage());
            return [];
        }
    }

    /**
     * チームメンバーリストを取得
     * 
     * @param int $teamId チームID
     * @return array メンバーリスト
     */
    public function getMembers($teamId)
    {
        try {
            $sql = "SELECT tm.*, u.display_name, u.email, u.username 
                    FROM team_members tm
                    JOIN users u ON tm.user_id = u.id
                    WHERE tm.team_id = ?
                    ORDER BY tm.role DESC, u.display_name";

            return $this->db->fetchAll($sql, [$teamId]);
        } catch (\Exception $e) {
            error_log("Error getting team members: " . $e->getMessage());
            return [];
        }
    }

    /**
     * チームにメンバーを追加
     * 
     * @param int $teamId チームID
     * @param int $userId ユーザーID
     * @param string $role 役割（admin or member）
     * @return bool 成功時true、失敗時false
     */
    public function addMember($teamId, $userId, $role = 'member')
    {
        try {
            // 既に存在するか確認
            $sql = "SELECT * FROM team_members WHERE team_id = ? AND user_id = ?";
            $existing = $this->db->fetch($sql, [$teamId, $userId]);

            if ($existing) {
                // 既に存在する場合は役割を更新
                $sql = "UPDATE team_members SET role = ? WHERE team_id = ? AND user_id = ?";
                return $this->db->execute($sql, [$role, $teamId, $userId]);
            } else {
                // 新規追加
                $sql = "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)";
                return $this->db->execute($sql, [$teamId, $userId, $role]);
            }
        } catch (\Exception $e) {
            error_log("Error adding team member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チームからメンバーを削除
     * 
     * @param int $teamId チームID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function removeMember($teamId, $userId)
    {
        try {
            $sql = "DELETE FROM team_members WHERE team_id = ? AND user_id = ?";
            return $this->db->execute($sql, [$teamId, $userId]);
        } catch (\Exception $e) {
            error_log("Error removing team member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * メンバーの役割を更新
     * 
     * @param int $teamId チームID
     * @param int $userId ユーザーID
     * @param string $role 新しい役割
     * @return bool 成功時true、失敗時false
     */
    public function updateMemberRole($teamId, $userId, $role)
    {
        try {
            $sql = "UPDATE team_members SET role = ? WHERE team_id = ? AND user_id = ?";
            return $this->db->execute($sql, [$role, $teamId, $userId]);
        } catch (\Exception $e) {
            error_log("Error updating team member role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーがチームのメンバーかどうかをチェック
     * 
     * @param int $teamId チームID
     * @param int $userId ユーザーID
     * @return bool メンバーの場合true、そうでなければfalse
     */
    public function isUserTeamMember($teamId, $userId)
    {
        try {
            $sql = "SELECT * FROM team_members WHERE team_id = ? AND user_id = ?";
            $result = $this->db->fetch($sql, [$teamId, $userId]);

            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error checking team membership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーがチームの管理者かどうかをチェック
     * 
     * @param int $teamId チームID
     * @param int $userId ユーザーID
     * @return bool 管理者の場合true、そうでなければfalse
     */
    public function isUserTeamAdmin($teamId, $userId)
    {
        try {
            $sql = "SELECT * FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'admin'";
            $result = $this->db->fetch($sql, [$teamId, $userId]);

            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error checking team admin: " . $e->getMessage());
            return false;
        }
    }
}
