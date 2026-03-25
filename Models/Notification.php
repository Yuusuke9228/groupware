<?php
// models/Notification.php
namespace Models;

use Core\Database;
use Core\Mailer;
use Services\ScheduleDisplaySettings;

class Notification
{
    private $db;
    private $setting;
    private $mailer;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->setting = new Setting();
        $this->mailer = new Mailer($this->setting);
    }

    /**
     * 通知を作成
     * 
     * @param array $data 通知データ
     * @return int|bool 成功時は新規通知ID、失敗時はfalse
     */
    public function create($data)
    {
        // 通知が有効かどうかを確認
        if (!$this->setting->isNotificationEnabled()) {
            return false;
        }

        // 必須項目チェック
        if (
            empty($data['user_id']) || empty($data['type']) ||
            empty($data['title']) || empty($data['content'])
        ) {
            return false;
        }

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 通知を作成
            $sql = "INSERT INTO notifications (
                user_id, 
                type, 
                title, 
                content, 
                link, 
                reference_id, 
                reference_type,
                is_read,
                is_email_sent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['user_id'],
                $data['type'],
                $data['title'],
                $data['content'],
                $data['link'] ?? null,
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null,
                $data['is_read'] ?? 0,
                $data['is_email_sent'] ?? 0
            ]);

            $notificationId = $this->db->lastInsertId();

            // ユーザーのメール通知設定を確認
            $sendEmail = empty($data['suppress_email']) && $this->shouldSendEmail($data['user_id'], $data['type']);

            if ($sendEmail) {
                // メール送信キューに追加
                $this->queueEmail($notificationId, $data);
            }

            $this->db->commit();
            return $notificationId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * メール送信キューに追加
     * 
     * @param int $notificationId 通知ID
     * @param array $data 通知データ
     * @return bool 成功時true、失敗時false
     */
    private function queueEmail($notificationId, $data)
    {
        // メール送信に必要な設定が完了しているか確認
        if (!$this->setting->isEmailConfigured()) {
            return false;
        }

        // ユーザーのメールアドレスを取得
        $userModel = new User();
        $user = $userModel->getById($data['user_id']);

        if (!$user || empty($user['email'])) {
            return false;
        }

        // アプリケーション名を取得
        $appName = $this->setting->getAppName();

        // メールの件名と本文を作成
        $subject = "[{$appName}] " . $data['title'];

        // HTML形式のメール本文
        $htmlBody = "<html><body>";
        $htmlBody .= "<h2>{$data['title']}</h2>";
        $htmlBody .= "<p>{$data['content']}</p>";

        if (!empty($data['link'])) {
            $fullLink = $this->buildAbsoluteLink($data['link']);
            $htmlBody .= "<p><a href='{$fullLink}'>詳細を確認</a></p>";
        }

        $htmlBody .= "<hr>";
        $htmlBody .= "<p>このメールは {$appName} からの自動通知です。</p>";
        $htmlBody .= "</body></html>";

        // キューに追加
        $sql = "INSERT INTO email_queue (
            to_email, 
            subject, 
            body, 
            is_html, 
            status,
            notification_id
        ) VALUES (?, ?, ?, ?, ?, ?)";

        return $this->db->execute($sql, [
            $user['email'],
            $subject,
            $htmlBody,
            1, // HTMLメール
            'pending',
            $notificationId
        ]);
    }

    /**
     * メール通知を送信すべきかどうかを判断
     * 
     * @param int $userId ユーザーID
     * @param string $type 通知タイプ
     * @return bool 送信すべきならtrue、そうでなければfalse
     */
    private function shouldSendEmail($userId, $type)
    {
        // ユーザーの通知設定を取得
        $sql = "SELECT * FROM notification_settings WHERE user_id = ? LIMIT 1";
        $settings = $this->db->fetch($sql, [$userId]);

        // 設定がない場合はデフォルト設定を作成
        if (!$settings) {
            $this->createDefaultSettings($userId);
            return true;
        }

        // メール通知が無効化されている場合
        if (!$settings['email_notify']) {
            return false;
        }

        // 通知タイプに応じた設定をチェック
        switch ($type) {
            case 'schedule':
                return (bool)$settings['notify_schedule'];
            case 'workflow':
                return (bool)$settings['notify_workflow'];
            case 'message':
                return (bool)$settings['notify_message'];
            case 'system':
                return true; // システム通知は常に送信
            default:
                return true;
        }
    }

    /**
     * ユーザーのデフォルト通知設定を作成
     * 
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function createDefaultSettings($userId)
    {
        $sql = "INSERT INTO notification_settings (
            user_id, 
            notify_schedule, 
            notify_workflow, 
            notify_message, 
            email_notify,
            schedule_view_start_time,
            schedule_view_end_time
        ) VALUES (?, 1, 1, 1, 1, '00:00:00', '23:00:00')";

        return $this->db->execute($sql, [$userId]);
    }

    /**
     * 未読通知一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param int $limit 件数制限
     * @return array 通知の配列
     */
    public function getUnread($userId, $limit = 10)
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC 
                LIMIT ?";

        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * 未読通知の数を取得
     * 
     * @param int $userId ユーザーID
     * @return int 未読通知数
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0";

        $result = $this->db->fetch($sql, [$userId]);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * 通知一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $limit 1ページあたりの件数
     * @return array 通知の配列
     */
    public function getNotifications($userId, $filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $params = [$userId];

        $sql = "SELECT * FROM notifications WHERE user_id = ? ";

        // フィルター条件を適用
        if (isset($filters['type']) && !empty($filters['type'])) {
            $sql .= "AND type = ? ";
            $params[] = $filters['type'];
        }

        if (isset($filters['is_read'])) {
            $sql .= "AND is_read = ? ";
            $params[] = $filters['is_read'] ? 1 : 0;
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= "AND (title LIKE ? OR content LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 並び順
        $sql .= "ORDER BY created_at DESC ";

        // ページネーション
        $sql .= "LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 通知の総数を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return int 通知数
     */
    public function getCount($userId, $filters = [])
    {
        $params = [$userId];

        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? ";

        // フィルター条件を適用
        if (isset($filters['type']) && !empty($filters['type'])) {
            $sql .= "AND type = ? ";
            $params[] = $filters['type'];
        }

        if (isset($filters['is_read'])) {
            $sql .= "AND is_read = ? ";
            $params[] = $filters['is_read'] ? 1 : 0;
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= "AND (title LIKE ? OR content LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $result = $this->db->fetch($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * 通知を既読にする
     * 
     * @param int $id 通知ID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function markAsRead($id, $userId)
    {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?";

        return $this->db->execute($sql, [$id, $userId]);
    }

    /**
     * 全ての通知を既読にする
     * 
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function markAllAsRead($userId)
    {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0";

        return $this->db->execute($sql, [$userId]);
    }

    /**
     * 通知設定を更新
     * 
     * @param int $userId ユーザーID
     * @param array $data 設定データ
     * @return bool 成功時true、失敗時false
     */
    public function updateSettings($userId, $data)
    {
        // 既存の設定を確認
        $sql = "SELECT id FROM notification_settings WHERE user_id = ? LIMIT 1";
        $existing = $this->db->fetch($sql, [$userId]);
        $toBool = function ($value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        };
        $displaySettings = ScheduleDisplaySettings::normalize(
            $data['schedule_view_start_time'] ?? null,
            $data['schedule_view_end_time'] ?? null
        );

        // パラメータの準備
        $params = [
            $toBool($data['notify_schedule'] ?? false),
            $toBool($data['notify_workflow'] ?? false),
            $toBool($data['notify_message'] ?? false),
            $toBool($data['email_notify'] ?? false),
            $displaySettings['start_time'] . ':00',
            $displaySettings['end_time'] . ':00',
            $userId
        ];

        if ($existing) {
            // 既存の設定を更新
            $sql = "UPDATE notification_settings 
                    SET notify_schedule = ?, 
                        notify_workflow = ?, 
                        notify_message = ?,
                        email_notify = ?,
                        schedule_view_start_time = ?,
                        schedule_view_end_time = ?
                    WHERE user_id = ?";

            return $this->db->execute($sql, $params);
        } else {
            // 新しい設定を作成
            $sql = "INSERT INTO notification_settings (
                notify_schedule, 
                notify_workflow, 
                notify_message, 
                email_notify, 
                schedule_view_start_time,
                schedule_view_end_time,
                user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            return $this->db->execute($sql, $params);
        }
    }

    /**
     * メール送信処理（バッチ処理用）
     * 
     * @param int $limit 一度に処理する件数
     * @return array 処理結果
     */
    public function processEmailQueue($limit = 10)
    {
        // メール送信に必要な設定が完了しているか確認
        if (!$this->setting->isEmailConfigured()) {
            return [
                'success' => false,
                'message' => 'メール設定が完了していません',
                'processed' => 0,
                'success_count' => 0,
                'failed_count' => 0
            ];
        }

        // キューからメールを取得
        $sql = "SELECT * FROM email_queue 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT ?";

        $emails = $this->db->fetchAll($sql, [$limit]);

        if (empty($emails)) {
            return [
                'success' => true,
                'message' => '処理対象のメールがありません',
                'processed' => 0,
                'success_count' => 0,
                'failed_count' => 0
            ];
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($emails as $email) {
            try {
                $this->mailer->send(
                    $email['to_email'],
                    $email['subject'],
                    $email['body'],
                    (bool)$email['is_html']
                );

                // 成功時の処理
                $this->updateEmailStatus($email['id'], 'sent');

                // 関連通知も送信済みに更新
                if (!empty($email['notification_id'])) {
                    $this->markEmailSent($email['notification_id']);
                }

                $successCount++;
            } catch (\Exception $e) {
                // 失敗時の処理
                $attempts = $email['attempts'] + 1;

                // 5回以上失敗した場合は失敗扱いに
                $status = $attempts >= 5 ? 'failed' : 'pending';

                $this->updateEmailStatus($email['id'], $status, $attempts);
                $failedCount++;

                error_log("Failed to send email ID: {$email['id']}, Error: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => 'メール送信処理が完了しました',
            'processed' => count($emails),
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ];
    }

    /**
     * メール送信ステータスを更新
     * 
     * @param int $id メールID
     * @param string $status 新しいステータス
     * @param int $attempts 試行回数
     * @return bool 成功時true、失敗時false
     */
    private function updateEmailStatus($id, $status, $attempts = null)
    {
        $sql = "UPDATE email_queue SET status = ?";
        $params = [$status];

        if ($status === 'sent') {
            $sql .= ", sent_at = NOW()";
        }

        if ($attempts !== null) {
            $sql .= ", attempts = ?";
            $params[] = $attempts;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        return $this->db->execute($sql, $params);
    }

    /**
     * 関連通知のメール送信済みフラグを更新
     * 
     * @param int $notificationId 通知ID
     * @return bool 成功時true、失敗時false
     */
    private function markEmailSent($notificationId)
    {
        $sql = "UPDATE notifications 
                SET is_email_sent = 1 
                WHERE id = ?";

        return $this->db->execute($sql, [$notificationId]);
    }

    /**
     * スケジュール開始前リマインダー通知をキューに追加
     *
     * @param int $minutesAhead 何分先までを通知対象にするか
     * @param int $limit 一度に処理する上限件数
     * @return array
     */
    public function queueUpcomingScheduleReminders($minutesAhead = 30, $limit = 100)
    {
        if (!$this->setting->isNotificationEnabled()) {
            return [
                'success' => false,
                'queued' => 0,
                'message' => '通知機能が無効です'
            ];
        }

        $sql = "SELECT 
                    sp.schedule_id,
                    sp.user_id,
                    s.title,
                    s.start_time
                FROM schedule_participants sp
                INNER JOIN schedules s ON s.id = sp.schedule_id
                WHERE sp.notification_sent = 0
                  AND sp.status IN ('pending', 'accepted', 'tentative')
                  AND s.status = 'scheduled'
                  AND s.start_time >= NOW()
                  AND s.start_time <= DATE_ADD(NOW(), INTERVAL ? MINUTE)
                ORDER BY s.start_time ASC
                LIMIT ?";

        $targets = $this->db->fetchAll($sql, [(int)$minutesAhead, (int)$limit]);
        if (empty($targets)) {
            return [
                'success' => true,
                'queued' => 0,
                'message' => '対象のスケジュールはありません'
            ];
        }

        $queued = 0;
        foreach ($targets as $target) {
            $notificationId = $this->create([
                'user_id' => (int)$target['user_id'],
                'type' => 'schedule',
                'title' => 'スケジュール開始リマインダー',
                'content' => "「{$target['title']}」がまもなく開始されます。\n開始時刻: " .
                    date('Y年m月d日 H:i', strtotime($target['start_time'])),
                'link' => '/schedule/view/' . (int)$target['schedule_id'],
                'reference_id' => (int)$target['schedule_id'],
                'reference_type' => 'schedule_reminder'
            ]);

            if ($notificationId) {
                $this->db->execute(
                    "UPDATE schedule_participants SET notification_sent = 1, updated_at = CURRENT_TIMESTAMP 
                     WHERE schedule_id = ? AND user_id = ?",
                    [(int)$target['schedule_id'], (int)$target['user_id']]
                );
                $queued++;
            }
        }

        return [
            'success' => true,
            'queued' => $queued,
            'message' => 'スケジュールリマインダーをキューに追加しました'
        ];
    }

    /**
     * ワークフロー未承認の催促通知をキューに追加
     *
     * @param int $pendingHours 未承認のまま経過した場合に催促開始する時間
     * @param int $repeatHours 同一承認タスクへの再催促間隔
     * @param int $limit 一度に処理する上限件数
     * @return array
     */
    public function queueWorkflowApprovalReminders($pendingHours = 24, $repeatHours = 24, $limit = 100)
    {
        if (!$this->setting->isNotificationEnabled()) {
            return [
                'success' => false,
                'queued' => 0,
                'message' => '通知機能が無効です'
            ];
        }

        $pendingHours = max(1, (int)$pendingHours);
        $repeatHours = max(1, (int)$repeatHours);
        $limit = max(1, (int)$limit);

        $sql = "SELECT
                    wa.id AS approval_id,
                    wa.request_id,
                    wa.step_number,
                    wa.approver_id,
                    wa.created_at AS assigned_at,
                    wr.title AS request_title,
                    wr.request_number,
                    wt.name AS template_name,
                    req.display_name AS requester_name
                FROM workflow_approvals wa
                INNER JOIN workflow_requests wr ON wr.id = wa.request_id
                INNER JOIN workflow_templates wt ON wt.id = wr.template_id
                INNER JOIN users req ON req.id = wr.requester_id
                WHERE wa.status = 'pending'
                  AND wr.status = 'pending'
                  AND wa.created_at <= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY wa.created_at ASC
                LIMIT ?";

        $targets = $this->db->fetchAll($sql, [$pendingHours, $limit]);
        if (empty($targets)) {
            return [
                'success' => true,
                'queued' => 0,
                'message' => '対象の承認催促はありません'
            ];
        }

        $queued = 0;
        foreach ($targets as $target) {
            $canNotify = $this->canSendWorkflowApprovalReminder(
                (int)$target['approver_id'],
                (int)$target['approval_id'],
                $repeatHours
            );

            if (!$canNotify) {
                continue;
            }

            $content = "未承認の申請があります。確認をお願いします。\n" .
                "申請番号: {$target['request_number']}\n" .
                "申請タイトル: {$target['request_title']}\n" .
                "申請種別: {$target['template_name']}\n" .
                "申請者: {$target['requester_name']}\n" .
                "承認ステップ: {$target['step_number']}\n" .
                "承認待ち開始: " . date('Y年m月d日 H:i', strtotime($target['assigned_at']));

            $notificationId = $this->create([
                'user_id' => (int)$target['approver_id'],
                'type' => 'workflow',
                'title' => '【催促】承認待ちのワークフロー申請があります',
                'content' => $content,
                'link' => '/workflow/view/' . (int)$target['request_id'],
                'reference_id' => (int)$target['approval_id'],
                'reference_type' => 'workflow_approval_reminder'
            ]);

            if ($notificationId) {
                $queued++;
            }
        }

        return [
            'success' => true,
            'queued' => $queued,
            'message' => 'ワークフロー催促通知をキューに追加しました'
        ];
    }

    /**
     * 相対リンクを絶対URLへ変換
     *
     * @param string $link
     * @return string
     */
    private function buildAbsoluteLink($link)
    {
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        $appUrl = $this->setting->getAppUrl();
        if (!empty($appUrl)) {
            return rtrim($appUrl, '/') . '/' . ltrim($link, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';

        return rtrim($scheme . '://' . $host . $basePath, '/') . '/' . ltrim($link, '/');
    }

    /**
     * 同一承認タスクへの催促送信可否を判定
     *
     * @param int $userId
     * @param int $approvalId
     * @param int $repeatHours
     * @return bool
     */
    private function canSendWorkflowApprovalReminder($userId, $approvalId, $repeatHours)
    {
        $sql = "SELECT created_at
                FROM notifications
                WHERE user_id = ?
                  AND reference_id = ?
                  AND reference_type = 'workflow_approval_reminder'
                  AND type = 'workflow'
                ORDER BY created_at DESC
                LIMIT 1";

        $latest = $this->db->fetch($sql, [(int)$userId, (int)$approvalId]);
        if (!$latest) {
            return true;
        }

        $lastSent = strtotime($latest['created_at']);
        return $lastSent <= strtotime('-' . (int)$repeatHours . ' hours');
    }
}
