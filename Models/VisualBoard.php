<?php
namespace Models;

use Core\Database;

class VisualBoard
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createBoard($data)
    {
        try {
            $sql = "INSERT INTO visual_boards (
                name,
                description,
                template_key,
                owner_type,
                owner_id,
                is_public,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                $data['template_key'] ?? 'mind_map',
                $data['owner_type'],
                (int)$data['owner_id'],
                !empty($data['is_public']) ? 1 : 0,
                (int)$data['created_by']
            ]);

            $boardId = (int)$this->db->lastInsertId();
            $this->addBoardMember($boardId, (int)$data['created_by'], 'admin');

            if ($data['owner_type'] === 'team') {
                $teamModel = new Team();
                $members = $teamModel->getMembers((int)$data['owner_id']);
                foreach ($members as $member) {
                    $role = (($member['role'] ?? '') === 'admin') ? 'admin' : 'editor';
                    $this->addBoardMember($boardId, (int)$member['user_id'], $role);
                }
            } elseif ($data['owner_type'] === 'organization') {
                $userModel = new User();
                $members = $userModel->getUsersByOrganization((int)$data['owner_id']);
                foreach ($members as $member) {
                    $role = ((int)$member['id'] === (int)$data['created_by']) ? 'admin' : 'editor';
                    $this->addBoardMember($boardId, (int)$member['id'], $role);
                }
            } elseif ($data['owner_type'] === 'user') {
                $this->addBoardMember($boardId, (int)$data['owner_id'], 'admin');
            }

            $this->seedTemplate($boardId, $data['template_key'] ?? 'mind_map', (int)$data['created_by']);

            return $boardId;
        } catch (\Exception $e) {
            error_log('Error creating visual board: ' . $e->getMessage());
            return false;
        }
    }

    public function updateBoard($boardId, $data)
    {
        try {
            $sql = "UPDATE visual_boards
                    SET name = ?,
                        description = ?,
                        is_public = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            return $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                !empty($data['is_public']) ? 1 : 0,
                (int)$boardId
            ]);
        } catch (\Exception $e) {
            error_log('Error updating visual board: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteBoard($boardId)
    {
        try {
            return $this->db->execute('DELETE FROM visual_boards WHERE id = ?', [(int)$boardId]);
        } catch (\Exception $e) {
            error_log('Error deleting visual board: ' . $e->getMessage());
            return false;
        }
    }

    public function getBoard($boardId)
    {
        try {
            $sql = "SELECT b.*, u.display_name AS creator_name
                    FROM visual_boards b
                    JOIN users u ON u.id = b.created_by
                    WHERE b.id = ?
                    LIMIT 1";
            return $this->db->fetch($sql, [(int)$boardId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserBoards($userId)
    {
        try {
            $sql = "SELECT DISTINCT b.*, u.display_name AS creator_name
                    FROM visual_boards b
                    JOIN users u ON u.id = b.created_by
                    LEFT JOIN visual_board_members bm
                        ON bm.board_id = b.id
                        AND bm.user_id = ?
                    WHERE b.is_public = 1
                       OR (b.owner_type = 'user' AND b.owner_id = ?)
                       OR bm.user_id IS NOT NULL
                       OR (
                            b.owner_type = 'team'
                            AND EXISTS (
                                SELECT 1
                                FROM team_members tm
                                WHERE tm.team_id = b.owner_id
                                  AND tm.user_id = ?
                            )
                       )
                       OR (
                            b.owner_type = 'organization'
                            AND EXISTS (
                                SELECT 1
                                FROM user_organizations uo
                                WHERE uo.organization_id = b.owner_id
                                  AND uo.user_id = ?
                            )
                       )
                    ORDER BY b.updated_at DESC, b.created_at DESC";

            return $this->db->fetchAll($sql, [(int)$userId, (int)$userId, (int)$userId, (int)$userId]);
        } catch (\Exception $e) {
            error_log('Error getting visual boards: ' . $e->getMessage());
            return [];
        }
    }

    public function getBoardMembers($boardId)
    {
        try {
            $sql = "SELECT bm.board_id, bm.user_id, bm.role, bm.created_at, u.display_name, u.email
                    FROM visual_board_members bm
                    JOIN users u ON u.id = bm.user_id
                    WHERE bm.board_id = ?
                    ORDER BY FIELD(bm.role, 'admin', 'editor', 'viewer'), u.display_name";
            return $this->db->fetchAll($sql, [(int)$boardId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board members: ' . $e->getMessage());
            return [];
        }
    }

    public function addBoardMember($boardId, $userId, $role = 'viewer')
    {
        try {
            $exists = $this->db->fetch(
                'SELECT board_id, user_id FROM visual_board_members WHERE board_id = ? AND user_id = ?',
                [(int)$boardId, (int)$userId]
            );

            if ($exists) {
                return $this->db->execute(
                    'UPDATE visual_board_members SET role = ? WHERE board_id = ? AND user_id = ?',
                    [$role, (int)$boardId, (int)$userId]
                );
            }

            return $this->db->execute(
                'INSERT INTO visual_board_members (board_id, user_id, role) VALUES (?, ?, ?)',
                [(int)$boardId, (int)$userId, $role]
            );
        } catch (\Exception $e) {
            error_log('Error adding visual board member: ' . $e->getMessage());
            return false;
        }
    }

    public function isUserBoardAdmin($boardId, $userId)
    {
        try {
            $board = $this->getBoard($boardId);
            if (!$board) {
                return false;
            }

            if ($board['owner_type'] === 'user' && (int)$board['owner_id'] === (int)$userId) {
                return true;
            }

            if ((int)$board['created_by'] === (int)$userId) {
                return true;
            }

            $sql = "SELECT board_id
                    FROM visual_board_members
                    WHERE board_id = ?
                      AND user_id = ?
                      AND role = 'admin'
                    LIMIT 1";
            return (bool)$this->db->fetch($sql, [(int)$boardId, (int)$userId]);
        } catch (\Exception $e) {
            error_log('Error checking visual board admin: ' . $e->getMessage());
            return false;
        }
    }

    public function canUserAccessBoard($boardId, $userId)
    {
        try {
            $board = $this->getBoard($boardId);
            if (!$board) {
                return false;
            }

            if (!empty($board['is_public'])) {
                return true;
            }

            if ($board['owner_type'] === 'user' && (int)$board['owner_id'] === (int)$userId) {
                return true;
            }

            $member = $this->db->fetch(
                'SELECT board_id FROM visual_board_members WHERE board_id = ? AND user_id = ? LIMIT 1',
                [(int)$boardId, (int)$userId]
            );
            if ($member) {
                return true;
            }

            if ($board['owner_type'] === 'team') {
                $teamModel = new Team();
                return $teamModel->isUserTeamMember((int)$board['owner_id'], (int)$userId);
            }

            if ($board['owner_type'] === 'organization') {
                $userModel = new User();
                $orgIds = $userModel->getUserOrganizationIds((int)$userId);
                return in_array((int)$board['owner_id'], array_map('intval', $orgIds), true);
            }

            return false;
        } catch (\Exception $e) {
            error_log('Error checking visual board access: ' . $e->getMessage());
            return false;
        }
    }

    public function canUserEditBoard($boardId, $userId)
    {
        try {
            if ($this->isUserBoardAdmin($boardId, $userId)) {
                return true;
            }

            $board = $this->getBoard($boardId);
            if (!$board) {
                return false;
            }

            $member = $this->db->fetch(
                "SELECT role
                 FROM visual_board_members
                 WHERE board_id = ?
                   AND user_id = ?
                   AND role IN ('admin', 'editor')
                 LIMIT 1",
                [(int)$boardId, (int)$userId]
            );
            if ($member) {
                return true;
            }

            if ($board['owner_type'] === 'team') {
                $teamModel = new Team();
                return $teamModel->isUserTeamMember((int)$board['owner_id'], (int)$userId);
            }

            if ($board['owner_type'] === 'organization') {
                $userModel = new User();
                $orgIds = $userModel->getUserOrganizationIds((int)$userId);
                return in_array((int)$board['owner_id'], array_map('intval', $orgIds), true);
            }

            return false;
        } catch (\Exception $e) {
            error_log('Error checking visual board edit permission: ' . $e->getMessage());
            return false;
        }
    }

    public function getBoardNodes($boardId)
    {
        try {
            $sql = "SELECT n.*,
                           tc.title AS linked_task_title
                    FROM visual_board_nodes n
                    LEFT JOIN task_cards tc ON tc.id = n.linked_task_id
                    WHERE n.board_id = ?
                    ORDER BY n.sort_order, n.id";
            return $this->db->fetchAll($sql, [(int)$boardId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board nodes: ' . $e->getMessage());
            return [];
        }
    }

    public function getNode($nodeId)
    {
        try {
            $sql = "SELECT n.*,
                           tc.title AS linked_task_title
                    FROM visual_board_nodes n
                    LEFT JOIN task_cards tc ON tc.id = n.linked_task_id
                    WHERE n.id = ?
                    LIMIT 1";
            return $this->db->fetch($sql, [(int)$nodeId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board node: ' . $e->getMessage());
            return null;
        }
    }

    public function createNode($boardId, $data, $userId)
    {
        try {
            $result = $this->db->fetch(
                'SELECT MAX(sort_order) AS max_order FROM visual_board_nodes WHERE board_id = ?',
                [(int)$boardId]
            );
            $sortOrder = (int)($result['max_order'] ?? 0) + 1;

            $sql = "INSERT INTO visual_board_nodes (
                        board_id,
                        parent_id,
                        linked_task_id,
                        node_type,
                        title,
                        content,
                        x,
                        y,
                        width,
                        height,
                        color,
                        is_collapsed,
                        sort_order,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                (int)$boardId,
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                !empty($data['linked_task_id']) ? (int)$data['linked_task_id'] : null,
                $data['node_type'] ?? 'note',
                $data['title'] ?? 'Node',
                $data['content'] ?? null,
                isset($data['x']) ? (float)$data['x'] : 0.0,
                isset($data['y']) ? (float)$data['y'] : 0.0,
                isset($data['width']) ? (int)$data['width'] : 220,
                isset($data['height']) ? (int)$data['height'] : 96,
                $data['color'] ?? '#fff4c2',
                !empty($data['is_collapsed']) ? 1 : 0,
                isset($data['sort_order']) ? (int)$data['sort_order'] : $sortOrder,
                (int)$userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log('Error creating visual board node: ' . $e->getMessage());
            return false;
        }
    }

    public function updateNode($nodeId, $data)
    {
        try {
            $fields = [];
            $params = [];

            $map = [
                'parent_id' => function ($value) {
                    return $value === null || $value === '' ? null : (int)$value;
                },
                'linked_task_id' => function ($value) {
                    return $value === null || $value === '' ? null : (int)$value;
                },
                'node_type' => function ($value) {
                    return (string)$value;
                },
                'title' => function ($value) {
                    return (string)$value;
                },
                'content' => function ($value) {
                    return $value === null ? null : (string)$value;
                },
                'x' => function ($value) {
                    return (float)$value;
                },
                'y' => function ($value) {
                    return (float)$value;
                },
                'width' => function ($value) {
                    return (int)$value;
                },
                'height' => function ($value) {
                    return (int)$value;
                },
                'color' => function ($value) {
                    return (string)$value;
                },
                'is_collapsed' => function ($value) {
                    return !empty($value) ? 1 : 0;
                },
                'sort_order' => function ($value) {
                    return (int)$value;
                }
            ];

            foreach ($map as $column => $caster) {
                if (array_key_exists($column, $data)) {
                    $fields[] = $column . ' = ?';
                    $params[] = $caster($data[$column]);
                }
            }

            if (empty($fields)) {
                return true;
            }

            $fields[] = 'updated_at = CURRENT_TIMESTAMP';
            $params[] = (int)$nodeId;

            $sql = 'UPDATE visual_board_nodes SET ' . implode(', ', $fields) . ' WHERE id = ?';
            return $this->db->execute($sql, $params);
        } catch (\Exception $e) {
            error_log('Error updating visual board node: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteNode($nodeId)
    {
        try {
            return $this->db->execute('DELETE FROM visual_board_nodes WHERE id = ?', [(int)$nodeId]);
        } catch (\Exception $e) {
            error_log('Error deleting visual board node: ' . $e->getMessage());
            return false;
        }
    }

    public function getBoardEdges($boardId)
    {
        try {
            $sql = "SELECT e.*
                    FROM visual_board_edges e
                    WHERE e.board_id = ?
                    ORDER BY e.id";
            return $this->db->fetchAll($sql, [(int)$boardId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board edges: ' . $e->getMessage());
            return [];
        }
    }

    public function getEdge($edgeId)
    {
        try {
            return $this->db->fetch('SELECT * FROM visual_board_edges WHERE id = ? LIMIT 1', [(int)$edgeId]);
        } catch (\Exception $e) {
            error_log('Error getting visual board edge: ' . $e->getMessage());
            return null;
        }
    }

    public function createEdge($boardId, $sourceNodeId, $targetNodeId, $label, $lineStyle, $userId)
    {
        try {
            $sql = "INSERT INTO visual_board_edges (
                        board_id,
                        source_node_id,
                        target_node_id,
                        label,
                        line_style,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                (int)$boardId,
                (int)$sourceNodeId,
                (int)$targetNodeId,
                $label,
                ($lineStyle === 'dashed') ? 'dashed' : 'solid',
                (int)$userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log('Error creating visual board edge: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEdge($edgeId)
    {
        try {
            return $this->db->execute('DELETE FROM visual_board_edges WHERE id = ?', [(int)$edgeId]);
        } catch (\Exception $e) {
            error_log('Error deleting visual board edge: ' . $e->getMessage());
            return false;
        }
    }

    public function replaceBoardGraph($boardId, $nodes, $edges, $userId)
    {
        try {
            $this->db->beginTransaction();

            $this->db->execute('DELETE FROM visual_board_edges WHERE board_id = ?', [(int)$boardId]);
            $this->db->execute('DELETE FROM visual_board_nodes WHERE board_id = ?', [(int)$boardId]);

            $insertedMap = [];
            $parentLinks = [];
            $taskIds = [];

            foreach ($nodes as $index => $node) {
                $linkedTaskId = (!empty($node['linked_task_id'])) ? (int)$node['linked_task_id'] : null;
                if ($linkedTaskId) {
                    $taskIds[] = $linkedTaskId;
                }

                $nodeId = $this->createNode($boardId, [
                    'parent_id' => null,
                    'linked_task_id' => $linkedTaskId,
                    'node_type' => $node['node_type'] ?? 'note',
                    'title' => trim((string)($node['title'] ?? 'Node')) !== '' ? (string)$node['title'] : 'Node',
                    'content' => isset($node['content']) ? (string)$node['content'] : null,
                    'x' => isset($node['x']) ? (float)$node['x'] : 0.0,
                    'y' => isset($node['y']) ? (float)$node['y'] : 0.0,
                    'width' => isset($node['width']) ? (int)$node['width'] : 220,
                    'height' => isset($node['height']) ? (int)$node['height'] : 96,
                    'color' => $node['color'] ?? '#fff4c2',
                    'is_collapsed' => !empty($node['is_collapsed']) ? 1 : 0,
                    'sort_order' => isset($node['sort_order']) ? (int)$node['sort_order'] : $index + 1
                ], $userId);

                if (!$nodeId) {
                    throw new \RuntimeException('Failed to insert visual board node.');
                }

                $clientId = (string)($node['client_id'] ?? $node['id'] ?? ('n' . $index));
                $insertedMap[$clientId] = $nodeId;

                $parentClientId = $node['parent_client_id'] ?? ($node['parent_id'] ?? null);
                if ($parentClientId !== null && $parentClientId !== '') {
                    $parentLinks[] = [
                        'node_id' => $nodeId,
                        'parent_client_id' => (string)$parentClientId
                    ];
                }
            }

            foreach ($parentLinks as $link) {
                $parentId = $insertedMap[$link['parent_client_id']] ?? null;
                $this->updateNode((int)$link['node_id'], ['parent_id' => $parentId]);
            }

            foreach ($edges as $edge) {
                $sourceClientId = (string)($edge['source_client_id'] ?? $edge['source_node_id'] ?? '');
                $targetClientId = (string)($edge['target_client_id'] ?? $edge['target_node_id'] ?? '');
                if ($sourceClientId === '' || $targetClientId === '') {
                    continue;
                }

                $sourceNodeId = $insertedMap[$sourceClientId] ?? null;
                $targetNodeId = $insertedMap[$targetClientId] ?? null;
                if (!$sourceNodeId || !$targetNodeId || $sourceNodeId === $targetNodeId) {
                    continue;
                }

                $this->createEdge(
                    $boardId,
                    (int)$sourceNodeId,
                    (int)$targetNodeId,
                    $edge['label'] ?? null,
                    $edge['line_style'] ?? 'solid',
                    $userId
                );
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error replacing visual board graph: ' . $e->getMessage());
            return false;
        }
    }

    public function applyAutoLayout($boardId)
    {
        $nodes = $this->getBoardNodes($boardId);
        if (empty($nodes)) {
            return [];
        }

        $childrenByParent = [];
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeId = (int)$node['id'];
            $parentId = isset($node['parent_id']) ? (int)$node['parent_id'] : 0;
            $childrenByParent[$parentId][] = $nodeId;
            $nodeMap[$nodeId] = $node;
        }

        $roots = $childrenByParent[0] ?? [];
        if (empty($roots)) {
            $roots = array_keys($nodeMap);
        }

        $xStep = 320;
        $yStep = 140;
        $baseX = 120;
        $cursorY = 120;
        $positions = [];
        $visited = [];

        $layout = function ($nodeId, $depth) use (&$layout, &$childrenByParent, &$positions, &$cursorY, &$visited, $xStep, $yStep, $baseX) {
            if (isset($visited[$nodeId])) {
                return (float)$cursorY;
            }
            $visited[$nodeId] = true;

            $children = $childrenByParent[(int)$nodeId] ?? [];
            if (empty($children)) {
                $y = (float)$cursorY;
                $cursorY += $yStep;
            } else {
                $firstY = null;
                $lastY = null;
                foreach ($children as $childId) {
                    $childY = $layout((int)$childId, $depth + 1);
                    if ($firstY === null) {
                        $firstY = $childY;
                    }
                    $lastY = $childY;
                }
                if ($firstY === null || $lastY === null) {
                    $y = (float)$cursorY;
                    $cursorY += $yStep;
                } else {
                    $y = ($firstY + $lastY) / 2;
                }
            }

            $positions[(int)$nodeId] = [
                'x' => $baseX + ($depth * $xStep),
                'y' => $y
            ];

            return $y;
        };

        foreach ($roots as $rootId) {
            $layout((int)$rootId, 0);
            $cursorY += $yStep;
        }

        try {
            $this->db->beginTransaction();
            foreach ($positions as $nodeId => $pos) {
                $this->db->execute(
                    'UPDATE visual_board_nodes SET x = ?, y = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
                    [(float)$pos['x'], (float)$pos['y'], (int)$nodeId]
                );
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error applying visual board auto layout: ' . $e->getMessage());
        }

        return $this->getBoardNodes($boardId);
    }

    public function getBoardAvailableTasks($boardId, $userId, $limit = 200)
    {
        $safeLimit = max(20, min(500, (int)$limit));
        try {
            $sql = "SELECT DISTINCT c.id, c.title, c.status, c.due_date, b.name AS board_name
                    FROM task_cards c
                    JOIN task_lists l ON l.id = c.list_id
                    JOIN task_boards b ON b.id = l.board_id
                    LEFT JOIN task_assignees a
                        ON a.card_id = c.id
                        AND a.user_id = ?
                    LEFT JOIN task_board_members bm
                        ON bm.board_id = b.id
                        AND bm.user_id = ?
                    WHERE a.user_id IS NOT NULL
                       OR c.created_by = ?
                       OR (b.owner_type = 'user' AND b.owner_id = ?)
                       OR bm.user_id IS NOT NULL
                       OR (
                            b.owner_type = 'team'
                            AND EXISTS (
                                SELECT 1
                                FROM team_members tm
                                WHERE tm.team_id = b.owner_id
                                  AND tm.user_id = ?
                            )
                       )
                       OR (
                            b.owner_type = 'organization'
                            AND EXISTS (
                                SELECT 1
                                FROM user_organizations uo
                                WHERE uo.organization_id = b.owner_id
                                  AND uo.user_id = ?
                            )
                       )
                    ORDER BY c.updated_at DESC, c.id DESC
                    LIMIT " . $safeLimit;

            return $this->db->fetchAll($sql, [
                (int)$userId,
                (int)$userId,
                (int)$userId,
                (int)$userId,
                (int)$userId,
                (int)$userId
            ]);
        } catch (\Exception $e) {
            error_log('Error getting visual board task options: ' . $e->getMessage());
            return [];
        }
    }

    public function getExportPayload($boardId)
    {
        return [
            'board' => $this->getBoard($boardId),
            'members' => $this->getBoardMembers($boardId),
            'nodes' => $this->getBoardNodes($boardId),
            'edges' => $this->getBoardEdges($boardId),
            'exported_at' => date('c')
        ];
    }

    private function seedTemplate($boardId, $templateKey, $userId)
    {
        $template = $this->getTemplateSeed($templateKey);
        if (empty($template)) {
            return;
        }

        $inserted = [];
        foreach ($template as $i => $node) {
            $newId = $this->createNode($boardId, [
                'parent_id' => null,
                'node_type' => $node['node_type'],
                'title' => $node['title'],
                'content' => $node['content'] ?? null,
                'x' => $node['x'],
                'y' => $node['y'],
                'width' => $node['width'] ?? 220,
                'height' => $node['height'] ?? 96,
                'color' => $node['color'] ?? '#fff4c2',
                'sort_order' => $i + 1
            ], $userId);

            if ($newId) {
                $inserted[$node['key']] = $newId;
            }
        }

        foreach ($template as $node) {
            if (empty($node['parent_key'])) {
                continue;
            }
            $nodeId = $inserted[$node['key']] ?? null;
            $parentId = $inserted[$node['parent_key']] ?? null;
            if ($nodeId && $parentId) {
                $this->updateNode($nodeId, ['parent_id' => $parentId]);
                $this->createEdge($boardId, $parentId, $nodeId, null, 'solid', $userId);
            }
        }
    }

    private function getTemplateSeed($templateKey)
    {
        switch ($templateKey) {
            case 'flowchart':
                return [
                    ['key' => 'root', 'parent_key' => null, 'node_type' => 'topic', 'title' => 'Process', 'x' => 120, 'y' => 180, 'color' => '#dcecff'],
                    ['key' => 'step1', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Start', 'x' => 420, 'y' => 60, 'color' => '#e8f5e9'],
                    ['key' => 'step2', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Review', 'x' => 420, 'y' => 180, 'color' => '#fff8e1'],
                    ['key' => 'step3', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Finish', 'x' => 420, 'y' => 300, 'color' => '#fbe9e7'],
                ];
            case 'brainstorm':
                return [
                    ['key' => 'root', 'parent_key' => null, 'node_type' => 'topic', 'title' => 'Brainstorm', 'x' => 120, 'y' => 180, 'color' => '#fff3cd'],
                    ['key' => 'idea1', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Idea A', 'x' => 420, 'y' => 80, 'color' => '#e3f2fd'],
                    ['key' => 'idea2', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Idea B', 'x' => 420, 'y' => 180, 'color' => '#f3e5f5'],
                    ['key' => 'idea3', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Idea C', 'x' => 420, 'y' => 280, 'color' => '#e8f5e9'],
                ];
            case 'planning':
                return [
                    ['key' => 'root', 'parent_key' => null, 'node_type' => 'topic', 'title' => 'Planning', 'x' => 120, 'y' => 180, 'color' => '#d6ecff'],
                    ['key' => 'goal', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Goal', 'x' => 420, 'y' => 70, 'color' => '#fff8e1'],
                    ['key' => 'timeline', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Timeline', 'x' => 420, 'y' => 180, 'color' => '#f1f8e9'],
                    ['key' => 'owner', 'parent_key' => 'root', 'node_type' => 'action', 'title' => 'Owner', 'x' => 420, 'y' => 290, 'color' => '#fce4ec'],
                ];
            case 'mind_map':
            default:
                return [
                    ['key' => 'root', 'parent_key' => null, 'node_type' => 'topic', 'title' => 'Mind Map', 'x' => 120, 'y' => 180, 'color' => '#fff4c2'],
                    ['key' => 'branch1', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Branch 1', 'x' => 420, 'y' => 80, 'color' => '#e3f2fd'],
                    ['key' => 'branch2', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Branch 2', 'x' => 420, 'y' => 180, 'color' => '#f3e5f5'],
                    ['key' => 'branch3', 'parent_key' => 'root', 'node_type' => 'idea', 'title' => 'Branch 3', 'x' => 420, 'y' => 280, 'color' => '#e8f5e9'],
                ];
        }
    }
}
