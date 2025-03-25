<?php
// models/Workflow.php
namespace Models;

use Core\Database;

class Workflow
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* テンプレート関連メソッド */

    /**
     * 全てのワークフローテンプレートを取得
     */
    public function getAllTemplates($page = 1, $limit = 20, $search = null)
    {
        $offset = ($page - 1) * $limit;
        $params = [];

        $sql = "SELECT t.*, u.display_name as creator_name 
                FROM workflow_templates t 
                JOIN users u ON t.creator_id = u.id ";

        // 検索条件
        if ($search) {
            $sql .= "WHERE t.name LIKE ? OR t.description LIKE ? ";
            $searchTerm = "%" . $search . "%";
            $params = [$searchTerm, $searchTerm];
        }

        $sql .= "ORDER BY t.name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * テンプレート数を取得
     */
    public function getTemplateCount($search = null)
    {
        $sql = "SELECT COUNT(*) as count FROM workflow_templates";
        $params = [];

        // 検索条件
        if ($search) {
            $sql .= " WHERE name LIKE ? OR description LIKE ?";
            $searchTerm = "%" . $search . "%";
            $params = [$searchTerm, $searchTerm];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 指定IDのテンプレートを取得
     */
    public function getTemplateById($id)
    {
        $sql = "SELECT t.*, u.display_name as creator_name 
                FROM workflow_templates t 
                JOIN users u ON t.creator_id = u.id 
                WHERE t.id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * テンプレートを作成
     */
    public function createTemplate($data)
    {
        $sql = "INSERT INTO workflow_templates (
                    name, 
                    description, 
                    status, 
                    creator_id
                ) VALUES (?, ?, ?, ?)";

        $this->db->execute($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $data['creator_id']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * テンプレートを更新
     */
    public function updateTemplate($id, $data)
    {
        $sql = "UPDATE workflow_templates SET 
                name = ?, 
                description = ?, 
                status = ? 
                WHERE id = ?";

        return $this->db->execute($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    /**
     * テンプレートを削除
     */
    public function deleteTemplate($id)
    {
        // テンプレートを使った申請がないか確認
        $sql = "SELECT COUNT(*) as count FROM workflow_requests WHERE template_id = ?";
        $result = $this->db->fetch($sql, [$id]);

        if ($result['count'] > 0) {
            return false; // 既に申請があるテンプレートは削除不可
        }

        return $this->db->execute("DELETE FROM workflow_templates WHERE id = ?", [$id]);
    }

    /* フォーム定義関連メソッド */

    /**
     * テンプレートのフォーム定義を取得
     */
    public function getFormDefinitions($templateId)
    {
        $sql = "SELECT * FROM workflow_form_definitions 
                WHERE template_id = ? 
                ORDER BY sort_order";
        return $this->db->fetchAll($sql, [$templateId]);
    }

    /**
     * フォームフィールドを追加
     */
    public function addFormField($data)
    {
        $sql = "INSERT INTO workflow_form_definitions (
                    template_id, 
                    field_id, 
                    field_type, 
                    label, 
                    placeholder, 
                    help_text, 
                    options, 
                    validation, 
                    is_required, 
                    sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $data['template_id'],
            $data['field_id'],
            $data['field_type'],
            $data['label'],
            $data['placeholder'] ?? null,
            $data['help_text'] ?? null,
            $data['options'] ?? null,
            $data['validation'] ?? null,
            $data['is_required'] ? 1 : 0,
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * フォームフィールドを更新
     */
    public function updateFormField($id, $data)
    {
        $sql = "UPDATE workflow_form_definitions SET 
                field_id = ?, 
                field_type = ?, 
                label = ?, 
                placeholder = ?, 
                help_text = ?, 
                options = ?, 
                validation = ?, 
                is_required = ?, 
                sort_order = ? 
                WHERE id = ?";

        return $this->db->execute($sql, [
            $data['field_id'],
            $data['field_type'],
            $data['label'],
            $data['placeholder'] ?? null,
            $data['help_text'] ?? null,
            $data['options'] ?? null,
            $data['validation'] ?? null,
            $data['is_required'] ? 1 : 0,
            $data['sort_order'] ?? 0,
            $id
        ]);
    }

    /**
     * フォームフィールドを削除
     */
    public function deleteFormField($id)
    {
        return $this->db->execute("DELETE FROM workflow_form_definitions WHERE id = ?", [$id]);
    }

    /**
     * テンプレートの全フォームフィールドを削除
     */
    public function deleteAllFormFields($templateId)
    {
        return $this->db->execute("DELETE FROM workflow_form_definitions WHERE template_id = ?", [$templateId]);
    }

    /* 承認経路関連メソッド */

    /**
     * テンプレートの承認経路を取得
     */
    public function getRouteDefinitions($templateId)
    {
        $sql = "SELECT * FROM workflow_route_definitions 
                WHERE template_id = ? 
                ORDER BY step_number, sort_order";
        return $this->db->fetchAll($sql, [$templateId]);
    }

    /**
     * 承認ステップを追加
     */
    public function addRouteStep($data)
    {
        $sql = "INSERT INTO workflow_route_definitions (
                    template_id, 
                    step_number, 
                    step_type, 
                    step_name, 
                    approver_type, 
                    approver_id, 
                    dynamic_approver_field_id, 
                    allow_delegation, 
                    allow_self_approval, 
                    parallel_approval, 
                    approval_condition, 
                    sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [
            $data['template_id'],
            $data['step_number'],
            $data['step_type'] ?? 'approval',
            $data['step_name'],
            $data['approver_type'],
            $data['approver_id'] ?? null,
            $data['dynamic_approver_field_id'] ?? null,
            $data['allow_delegation'] ? 1 : 0,
            $data['allow_self_approval'] ? 1 : 0,
            $data['parallel_approval'] ? 1 : 0,
            $data['approval_condition'] ?? null,
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * 承認ステップを更新
     */
    public function updateRouteStep($id, $data)
    {
        $sql = "UPDATE workflow_route_definitions SET 
                step_number = ?, 
                step_type = ?, 
                step_name = ?, 
                approver_type = ?, 
                approver_id = ?, 
                dynamic_approver_field_id = ?, 
                allow_delegation = ?, 
                allow_self_approval = ?, 
                parallel_approval = ?, 
                approval_condition = ?, 
                sort_order = ? 
                WHERE id = ?";

        return $this->db->execute($sql, [
            $data['step_number'],
            $data['step_type'] ?? 'approval',
            $data['step_name'],
            $data['approver_type'],
            $data['approver_id'] ?? null,
            $data['dynamic_approver_field_id'] ?? null,
            $data['allow_delegation'] ? 1 : 0,
            $data['allow_self_approval'] ? 1 : 0,
            $data['parallel_approval'] ? 1 : 0,
            $data['approval_condition'] ?? null,
            $data['sort_order'] ?? 0,
            $id
        ]);
    }

    /**
     * 承認ステップを削除
     */
    public function deleteRouteStep($id)
    {
        return $this->db->execute("DELETE FROM workflow_route_definitions WHERE id = ?", [$id]);
    }

    /**
     * テンプレートの全承認ステップを削除
     */
    public function deleteAllRouteSteps($templateId)
    {
        return $this->db->execute("DELETE FROM workflow_route_definitions WHERE template_id = ?", [$templateId]);
    }

    /* 申請関連メソッド */

    /**
     * 次の申請番号を生成
     */
    public function generateRequestNumber($templateId)
    {
        // 年月日を含む申請番号を生成 (例: WF-20230501-001)
        $prefix = "WF";
        $date = date('Ymd');

        // 同じ日付のカウンターを取得
        $sql = "SELECT MAX(request_number) as max_number 
                FROM workflow_requests 
                WHERE request_number LIKE ?";
        $result = $this->db->fetch($sql, [$prefix . '-' . $date . '-%']);

        $counter = 1;
        if ($result && $result['max_number']) {
            $parts = explode('-', $result['max_number']);
            $lastCounter = (int)end($parts);
            $counter = $lastCounter + 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 申請を作成
     */
    public function createRequest($data)
    {
        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 申請番号を生成
            $requestNumber = $this->generateRequestNumber($data['template_id']);

            // 申請データを挿入
            $sql = "INSERT INTO workflow_requests (
                        request_number,
                        template_id, 
                        title, 
                        status, 
                        requester_id
                    ) VALUES (?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $requestNumber,
                $data['template_id'],
                $data['title'],
                $data['status'] ?? 'draft',
                $data['requester_id']
            ]);

            $requestId = $this->db->lastInsertId();

            // フォームデータを挿入
            if (isset($data['form_data']) && is_array($data['form_data'])) {
                foreach ($data['form_data'] as $fieldId => $value) {
                    $this->saveRequestData($requestId, $fieldId, $value);
                }
            }

            // 添付ファイルを保存
            if (isset($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $fieldId => $fileInfo) {
                    $this->saveAttachment($requestId, $fieldId, $fileInfo);
                }
            }

            // 申請を提出する場合、最初のステップの承認者を設定
            if ($data['status'] === 'pending') {
                $this->setupInitialApprovalStep($requestId, $data['template_id'], $data['requester_id']);
            }

            $this->db->commit();
            return $requestId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 申請を更新
     */
    public function updateRequest($id, $data)
    {
        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 申請データを更新
            $sql = "UPDATE workflow_requests SET 
                    title = ?, 
                    status = ? 
                    WHERE id = ?";

            $this->db->execute($sql, [
                $data['title'],
                $data['status'] ?? 'draft',
                $id
            ]);

            // フォームデータを更新
            if (isset($data['form_data']) && is_array($data['form_data'])) {
                foreach ($data['form_data'] as $fieldId => $value) {
                    $this->saveRequestData($id, $fieldId, $value);
                }
            }

            // 添付ファイルを保存
            if (isset($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $fieldId => $fileInfo) {
                    $this->saveAttachment($id, $fieldId, $fileInfo);
                }
            }

            // 申請を提出する場合、最初のステップの承認者を設定
            $request = $this->getRequestById($id);
            if ($data['status'] === 'pending' && $request['status'] === 'draft') {
                $this->setupInitialApprovalStep($id, $request['template_id'], $request['requester_id']);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 申請フォームデータを保存
     */
    private function saveRequestData($requestId, $fieldId, $value)
    {
        // 既存データがあるか確認
        $sql = "SELECT id FROM workflow_request_data 
                WHERE request_id = ? AND field_id = ? LIMIT 1";
        $existing = $this->db->fetch($sql, [$requestId, $fieldId]);

        if ($existing) {
            // 更新
            $sql = "UPDATE workflow_request_data 
                    SET value = ? 
                    WHERE request_id = ? AND field_id = ?";
            return $this->db->execute($sql, [$value, $requestId, $fieldId]);
        } else {
            // 新規作成
            $sql = "INSERT INTO workflow_request_data (
                        request_id, 
                        field_id, 
                        value
                    ) VALUES (?, ?, ?)";
            return $this->db->execute($sql, [$requestId, $fieldId, $value]);
        }
    }

    /**
     * 添付ファイルを保存
     */
    private function saveAttachment($requestId, $fieldId, $fileInfo)
    {
        $sql = "INSERT INTO workflow_attachments (
                    request_id, 
                    field_id, 
                    file_name, 
                    file_path, 
                    file_size, 
                    mime_type
                ) VALUES (?, ?, ?, ?, ?, ?)";

        return $this->db->execute($sql, [
            $requestId,
            $fieldId,
            $fileInfo['name'],
            $fileInfo['path'],
            $fileInfo['size'],
            $fileInfo['type']
        ]);
    }

    /**
     * 初期承認ステップをセットアップ
     */
    private function setupInitialApprovalStep($requestId, $templateId, $requesterId)
    {
        // テンプレートから最初のステップを取得
        $sql = "SELECT * FROM workflow_route_definitions 
                WHERE template_id = ? 
                ORDER BY step_number, sort_order 
                LIMIT 1";
        $firstStep = $this->db->fetch($sql, [$templateId]);

        if (!$firstStep) {
            return false; // 承認ステップが定義されていない
        }

        // 申請の現在のステップを更新
        $this->db->execute(
            "UPDATE workflow_requests SET current_step = ? WHERE id = ?",
            [$firstStep['step_number'], $requestId]
        );

        // 承認者を決定
        $approvers = $this->determineApprovers($firstStep, $requesterId, $requestId);

        // 各承認者に対して承認タスクを作成
        foreach ($approvers as $approverId) {
            $sql = "INSERT INTO workflow_approvals (
                        request_id, 
                        step_number, 
                        approver_id, 
                        status
                    ) VALUES (?, ?, ?, 'pending')";

            $this->db->execute($sql, [
                $requestId,
                $firstStep['step_number'],
                $approverId
            ]);
        }

        return true;
    }

    /**
     * ステップの承認者を決定
     */
    private function determineApprovers($step, $requesterId, $requestId = null)
    {
        $approvers = [];

        switch ($step['approver_type']) {
            case 'user':
                // 特定のユーザー
                if ($step['approver_id']) {
                    $approvers[] = $step['approver_id'];
                }
                break;

            case 'role':
                // 特定のロール（役割）を持つすべてのユーザー
                $sql = "SELECT id FROM users WHERE role = ? AND status = 'active'";
                $users = $this->db->fetchAll($sql, [$step['approver_id']]);
                foreach ($users as $user) {
                    $approvers[] = $user['id'];
                }
                break;

            case 'organization':
                // 特定の組織のすべてのユーザー
                $sql = "SELECT user_id FROM user_organizations WHERE organization_id = ?";
                $users = $this->db->fetchAll($sql, [$step['approver_id']]);
                foreach ($users as $user) {
                    $approvers[] = $user['user_id'];
                }
                break;

            case 'dynamic':
                // 動的承認者（申請フォームのフィールドから取得）
                if ($requestId && $step['dynamic_approver_field_id']) {
                    $sql = "SELECT value FROM workflow_request_data 
                        WHERE request_id = ? AND field_id = ? LIMIT 1";
                    $result = $this->db->fetch($sql, [$requestId, $step['dynamic_approver_field_id']]);

                    if ($result && !empty($result['value'])) {
                        $approvers[] = $result['value'];
                    }
                }
                break;
        }

        // 自己承認が許可されていない場合、申請者を除外
        if (!$step['allow_self_approval']) {
            $approvers = array_filter($approvers, function ($approverId) use ($requesterId) {
                return $approverId != $requesterId;
            });
        }

        // デバッグ用
        error_log("determineApprovers for step {$step['step_number']}: " . json_encode($approvers));

        return array_values(array_unique($approvers));
    }

    /**
     * 申請一覧を取得
     */
    public function getRequests($filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $params = [];

        $sql = "SELECT r.*, t.name as template_name, u.display_name as requester_name 
            FROM workflow_requests r 
            JOIN workflow_templates t ON r.template_id = t.id 
            JOIN users u ON r.requester_id = u.id 
            WHERE 1=1 ";

        // フィルタ条件を適用
        if (isset($filters['status']) && $filters['status']) {
            $sql .= "AND r.status = ? ";
            $params[] = $filters['status'];
        }

        if (isset($filters['template_id']) && $filters['template_id']) {
            $sql .= "AND r.template_id = ? ";
            $params[] = $filters['template_id'];
        }

        if (isset($filters['requester_id']) && $filters['requester_id']) {
            $sql .= "AND r.requester_id = ? ";
            $params[] = $filters['requester_id'];
        }

        if (isset($filters['search']) && $filters['search']) {
            $sql .= "AND (r.title LIKE ? OR r.request_number LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 承認待ちの申請を取得する場合
        if (isset($filters['pending_approval']) && $filters['pending_approval'] && isset($filters['user_id'])) {
            $sql .= "AND r.status = 'pending' 
                AND r.id IN (
                    SELECT wa.request_id FROM workflow_approvals wa
                    WHERE wa.approver_id = ? AND wa.status = 'pending'
                    AND wa.step_number = r.current_step
                ) ";
            $params[] = $filters['user_id'];
        }

        $sql .= "ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }


    /**
     * 申請数を取得
     */
    public function getRequestCount($filters = [])
    {
        $params = [];

        $sql = "SELECT COUNT(*) as count 
            FROM workflow_requests r 
            WHERE 1=1 ";

        // フィルタ条件を適用
        if (isset($filters['status']) && $filters['status']) {
            $sql .= "AND r.status = ? ";
            $params[] = $filters['status'];
        }

        if (isset($filters['template_id']) && $filters['template_id']) {
            $sql .= "AND r.template_id = ? ";
            $params[] = $filters['template_id'];
        }

        if (isset($filters['requester_id']) && $filters['requester_id']) {
            $sql .= "AND r.requester_id = ? ";
            $params[] = $filters['requester_id'];
        }

        if (isset($filters['search']) && $filters['search']) {
            $sql .= "AND (r.title LIKE ? OR r.request_number LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 承認待ちの申請を取得する場合 - 修正：ここが重要
        if (isset($filters['pending_approval']) && $filters['pending_approval'] && isset($filters['user_id'])) {
            $sql .= "AND r.status = 'pending' 
                AND r.id IN (
                    SELECT wa.request_id FROM workflow_approvals wa
                    WHERE wa.approver_id = ? AND wa.status = 'pending'
                    AND wa.step_number = r.current_step
                ) ";
            $params[] = $filters['user_id'];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 指定IDの申請を取得
     */
    public function getRequestById($id)
    {
        $sql = "SELECT r.*, t.name as template_name, u.display_name as requester_name 
                FROM workflow_requests r 
                JOIN workflow_templates t ON r.template_id = t.id 
                JOIN users u ON r.requester_id = u.id 
                WHERE r.id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * 申請のフォームデータを取得
     */
    public function getRequestData($requestId)
    {
        $sql = "SELECT field_id, value FROM workflow_request_data WHERE request_id = ?";
        $results = $this->db->fetchAll($sql, [$requestId]);

        $data = [];
        foreach ($results as $row) {
            $data[$row['field_id']] = $row['value'];
        }

        return $data;
    }

    /**
     * 申請の添付ファイルを取得
     */
    public function getRequestAttachments($requestId)
    {
        $sql = "SELECT * FROM workflow_attachments WHERE request_id = ?";
        return $this->db->fetchAll($sql, [$requestId]);
    }

    /**
     * 申請の承認履歴を取得
     */
    public function getRequestApprovals($requestId)
    {
        $sql = "SELECT a.*, 
                    u.display_name as approver_name, 
                    d.display_name as delegate_name 
                FROM workflow_approvals a 
                JOIN users u ON a.approver_id = u.id 
                LEFT JOIN users d ON a.delegate_id = d.id 
                WHERE a.request_id = ? 
                ORDER BY a.step_number, a.created_at";
        return $this->db->fetchAll($sql, [$requestId]);
    }

    public function processApproval($requestId, $approverId, $data)
    {
        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 申請情報を取得
            $request = $this->getRequestById($requestId);
            if (!$request || $request['status'] !== 'pending') {
                throw new \Exception('有効な申請が見つかりません');
            }

            // 現在のステップの承認情報を取得
            $sql = "SELECT * FROM workflow_approvals 
            WHERE request_id = ? AND step_number = ? AND approver_id = ? AND status = 'pending' LIMIT 1";
            $approval = $this->db->fetch($sql, [$requestId, $request['current_step'], $approverId]);

            if (!$approval) {
                throw new \Exception('承認タスクが見つかりません');
            }

            // 代理承認の場合
            $delegateId = null;
            if (isset($data['is_delegate']) && $data['is_delegate'] && isset($data['delegate_id'])) {
                // 代理権限を確認
                $hasDelegate = $this->checkDelegationPermission($approverId, $data['delegate_id'], $request['template_id']);

                if (!$hasDelegate) {
                    throw new \Exception('代理承認の権限がありません');
                }

                $delegateId = $data['delegate_id'];
            }

            // 承認/却下を記録
            $sql = "UPDATE workflow_approvals SET 
            status = ?, 
            delegate_id = ?, 
            comment = ?, 
            acted_at = NOW() 
            WHERE id = ?";

            $this->db->execute($sql, [
                $data['action'],
                $delegateId,
                $data['comment'] ?? null,
                $approval['id']
            ]);

            // 却下の場合は申請全体を却下
            if ($data['action'] === 'rejected') {
                $this->db->execute(
                    "UPDATE workflow_requests SET status = 'rejected' WHERE id = ?",
                    [$requestId]
                );

                $this->db->commit();
                return true;
            }

            // 現在のステップの他の未承認タスクをチェック
            $sql = "SELECT COUNT(*) as count FROM workflow_approvals 
            WHERE request_id = ? AND step_number = ? AND status = 'pending'";
            $pendingResult = $this->db->fetch($sql, [$requestId, $request['current_step']]);
            $pendingCount = $pendingResult ? (int)$pendingResult['count'] : 0;

            // デバッグ用
            error_log("pendingCount: " . $pendingCount);

            // 保留中の承認がなければ次のステップへ
            if ($pendingCount === 0) {
                // 次のステップを取得
                $sql = "SELECT * FROM workflow_route_definitions 
                WHERE template_id = ? AND step_number > ? 
                ORDER BY step_number, sort_order 
                LIMIT 1";
                $nextStep = $this->db->fetch($sql, [
                    $request['template_id'],
                    $request['current_step']
                ]);

                // デバッグ用
                if ($nextStep) {
                    error_log("Next step found: " . json_encode($nextStep));
                } else {
                    error_log("No next step found");
                }

                if ($nextStep) {
                    // 次のステップがある場合
                    $this->db->execute(
                        "UPDATE workflow_requests SET current_step = ? WHERE id = ?",
                        [$nextStep['step_number'], $requestId]
                    );

                    // 次のステップの承認者を設定
                    $approvers = $this->determineApprovers($nextStep, $request['requester_id'], $requestId);

                    // デバッグ用
                    error_log("Approvers for next step: " . json_encode($approvers));

                    if (!empty($approvers)) {
                        foreach ($approvers as $nextApproverId) {
                            $sql = "INSERT INTO workflow_approvals (
                                request_id, 
                                step_number, 
                                approver_id, 
                                status
                            ) VALUES (?, ?, ?, 'pending')";

                            $this->db->execute($sql, [
                                $requestId,
                                $nextStep['step_number'],
                                $nextApproverId
                            ]);
                        }
                    } else {
                        // 承認者が見つからない場合は次のステップをスキップ
                        error_log("No approvers found for step " . $nextStep['step_number'] . ", skipping to next step");

                        // 再帰的に次のステップを処理
                        $this->moveToNextStep($requestId, $request['template_id'], $nextStep['step_number'], $request['requester_id']);
                    }
                } else {
                    // 次のステップがない場合は承認完了
                    error_log("No more steps, marking request as approved");
                    $this->db->execute(
                        "UPDATE workflow_requests SET status = 'approved' WHERE id = ?",
                        [$requestId]
                    );
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error in processApproval: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 次のステップに移動（再帰的に処理）
     */
    public function moveToNextStep($requestId, $templateId, $currentStep, $requesterId)
    {
        // 次のステップを取得
        $sql = "SELECT * FROM workflow_route_definitions 
            WHERE template_id = ? AND step_number > ? 
            ORDER BY step_number, sort_order 
            LIMIT 1";
        $nextStep = $this->db->fetch($sql, [
            $templateId,
            $currentStep
        ]);

        if ($nextStep) {
            // 次のステップの情報をセット
            $this->db->execute(
                "UPDATE workflow_requests SET current_step = ? WHERE id = ?",
                [$nextStep['step_number'], $requestId]
            );

            // 次のステップの承認者を設定
            $approvers = $this->determineApprovers($nextStep, $requesterId, $requestId);

            if (!empty($approvers)) {
                foreach ($approvers as $nextApproverId) {
                    $sql = "INSERT INTO workflow_approvals (
                            request_id, 
                            step_number, 
                            approver_id, 
                            status
                        ) VALUES (?, ?, ?, 'pending')";

                    $this->db->execute($sql, [
                        $requestId,
                        $nextStep['step_number'],
                        $nextApproverId
                    ]);
                }
                return true;
            } else {
                // 承認者が見つからない場合は次のステップを再帰的に処理
                return $this->moveToNextStep($requestId, $templateId, $nextStep['step_number'], $requesterId);
            }
        } else {
            // 次のステップがない場合は承認完了
            $this->db->execute(
                "UPDATE workflow_requests SET status = 'approved' WHERE id = ?",
                [$requestId]
            );
            return true;
        }
    }

    /**
     * 代理承認の権限をチェック
     */
    private function checkDelegationPermission($userId, $delegateId, $templateId)
    {
        $sql = "SELECT * FROM workflow_delegates 
                WHERE user_id = ? AND delegate_id = ? 
                AND status = 'active' 
                AND CURRENT_DATE BETWEEN start_date AND end_date 
                AND (template_id IS NULL OR template_id = ?)";

        $result = $this->db->fetch($sql, [$userId, $delegateId, $templateId]);
        return $result ? true : false;
    }

    /**
     * 申請をキャンセル
     */
    public function cancelRequest($id, $userId)
    {
        // 申請情報を取得
        $request = $this->getRequestById($id);

        // 権限チェック（申請者または管理者のみキャンセル可能）
        if (!$request || ($request['requester_id'] != $userId && !\Core\Auth::getInstance()->isAdmin())) {
            return false;
        }

        // 承認済みまたは却下済みの申請はキャンセル不可
        if ($request['status'] === 'approved' || $request['status'] === 'rejected') {
            return false;
        }

        // キャンセル処理
        return $this->db->execute(
            "UPDATE workflow_requests SET status = 'cancelled' WHERE id = ?",
            [$id]
        );
    }

    /**
     * 申請にコメントを追加
     */
    public function addComment($requestId, $userId, $comment)
    {
        $sql = "INSERT INTO workflow_comments (
                    request_id, 
                    user_id, 
                    comment
                ) VALUES (?, ?, ?)";

        return $this->db->execute($sql, [
            $requestId,
            $userId,
            $comment
        ]);
    }

    /**
     * 申請のコメントを取得
     */
    public function getComments($requestId)
    {
        $sql = "SELECT c.*, u.display_name as user_name 
                FROM workflow_comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.request_id = ? 
                ORDER BY c.created_at DESC";

        return $this->db->fetchAll($sql, [$requestId]);
    }

    /**
     * 代理承認設定を追加
     */
    public function addDelegation($data)
    {
        $sql = "INSERT INTO workflow_delegates (
                    user_id, 
                    delegate_id, 
                    template_id, 
                    start_date, 
                    end_date, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?)";

        return $this->db->execute($sql, [
            $data['user_id'],
            $data['delegate_id'],
            $data['template_id'] ?? null,
            $data['start_date'],
            $data['end_date'],
            $data['status'] ?? 'active'
        ]);
    }

    /**
     * 代理承認設定を取得
     */
    public function getUserDelegations($userId)
    {
        $sql = "SELECT d.*, 
                    u.display_name as user_name,
                    d2.display_name as delegate_name,
                    t.name as template_name
                FROM workflow_delegates d 
                JOIN users u ON d.user_id = u.id 
                JOIN users d2 ON d.delegate_id = d2.id 
                LEFT JOIN workflow_templates t ON d.template_id = t.id 
                WHERE d.user_id = ? 
                ORDER BY d.start_date DESC";

        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * PDFエクスポート用のデータを取得
     */
    public function getRequestExportData($requestId)
    {
        // 申請基本情報
        $request = $this->getRequestById($requestId);
        if (!$request) {
            return null;
        }

        // フォーム定義
        $formDefs = $this->getFormDefinitions($request['template_id']);

        // フォームデータ
        $formData = $this->getRequestData($requestId);

        // 承認履歴
        $approvals = $this->getRequestApprovals($requestId);

        return [
            'request' => $request,
            'form_definitions' => $formDefs,
            'form_data' => $formData,
            'approvals' => $approvals
        ];
    }
}
