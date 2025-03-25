<?php
// models/Notification.php
namespace Models;

use Core\Database;

class Notification
{
    private $db;
    private $setting;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->setting = new Setting();
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
            $sendEmail = $this->shouldSendEmail($data['user_id'], $data['type']);

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
            $fullLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
                "://{$_SERVER['HTTP_HOST']}" . BASE_PATH . $data['link'];
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
            email_notify
        ) VALUES (?, 1, 1, 1, 1)";

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

        // パラメータの準備
        $params = [
            isset($data['notify_schedule']) ? 1 : 0,
            isset($data['notify_workflow']) ? 1 : 0,
            isset($data['notify_message']) ? 1 : 0,
            isset($data['email_notify']) ? 1 : 0,
            $userId
        ];

        if ($existing) {
            // 既存の設定を更新
            $sql = "UPDATE notification_settings 
                    SET notify_schedule = ?, 
                        notify_workflow = ?, 
                        notify_message = ?,
                        email_notify = ?
                    WHERE user_id = ?";

            return $this->db->execute($sql, $params);
        } else {
            // 新しい設定を作成
            $sql = "INSERT INTO notification_settings (
                notify_schedule, 
                notify_workflow, 
                notify_message, 
                email_notify, 
                user_id
            ) VALUES (?, ?, ?, ?, ?)";

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

        require_once __DIR__ . '/../vendor/autoload.php';

        // SMTPの設定を取得
        $smtpHost = $this->setting->get('smtp_host');
        $smtpPort = $this->setting->get('smtp_port');
        $smtpSecure = $this->setting->get('smtp_secure');
        $smtpUsername = $this->setting->get('smtp_username');
        $smtpPassword = $this->setting->get('smtp_password');
        $fromEmail = $this->setting->get('notification_email');
        $appName = $this->setting->getAppName();

        $successCount = 0;
        $failedCount = 0;

        foreach ($emails as $email) {
            try {
                // PHPMailerの設定
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;
                $mail->Password = $smtpPassword;
                $mail->SMTPSecure = $smtpSecure;
                $mail->Port = $smtpPort;
                $mail->CharSet = 'UTF-8';

                // 送信元・送信先の設定
                $mail->setFrom($fromEmail, $appName);
                $mail->addAddress($email['to_email']);

                // メール内容の設定
                $mail->isHTML($email['is_html']);
                $mail->Subject = $email['subject'];
                $mail->Body = $email['body'];

                // メール送信
                $mail->send();

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
}
