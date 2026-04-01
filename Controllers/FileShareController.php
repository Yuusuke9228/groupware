<?php
namespace Controllers;

use Core\Controller;
use Core\Database;

class FileShareController extends Controller
{
    private $db;
    private $uploadDir;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/files/';
    }

    public function access($params)
    {
        $token = trim((string)($params['token'] ?? ''));
        if ($token === '') {
            http_response_code(404);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => 'not_found',
                'message' => tr_text('共有リンクが見つかりません。', 'Share link was not found.'),
            ]);
            return;
        }

        $share = $this->db->fetch(
            "SELECT fsl.*, fe.title, fe.original_name, fe.filename, fe.mime_type, fe.file_size
             FROM file_share_links fsl
             INNER JOIN file_entries fe ON fe.id = fsl.file_id
             WHERE fsl.token = ?
             LIMIT 1",
            [$token]
        );

        if (!$share) {
            http_response_code(404);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => 'not_found',
                'message' => tr_text('共有リンクが見つかりません。', 'Share link was not found.'),
            ]);
            return;
        }

        $status = $this->validateShareStatus($share);
        if ($status !== 'ok') {
            http_response_code(410);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => $status,
                'share' => $share,
                'message' => $this->statusMessage($status),
            ]);
            return;
        }

        $targets = $this->db->fetchAll(
            "SELECT target_type, target_id FROM file_share_targets WHERE share_link_id = ?",
            [(int)$share['id']]
        );
        $targetCheck = $this->validateTargetAccess($targets);
        if ($targetCheck !== 'ok') {
            $httpCode = $targetCheck === 'login_required' ? 401 : 403;
            http_response_code($httpCode);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => $targetCheck,
                'share' => $share,
                'message' => $this->statusMessage($targetCheck),
            ]);
            return;
        }

        $passwordError = '';
        if (!empty($share['password_hash']) && !$this->isSharePasswordVerified($token)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = (string)($_POST['share_password'] ?? '');
                if ($password !== '' && password_verify($password, (string)$share['password_hash'])) {
                    $this->markSharePasswordVerified($token);
                    $this->redirect(BASE_PATH . '/files/share/' . urlencode($token));
                    return;
                }
                $passwordError = tr_text('パスワードが一致しません。', 'Password is incorrect.');
            }

            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => 'password_required',
                'share' => $share,
                'message' => tr_text('このリンクはパスワード保護されています。', 'This share link is password protected.'),
                'passwordError' => $passwordError,
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['download'] ?? '') === '1') {
            $this->streamSharedFile($share);
            return;
        }

        $this->view('file_manager/shared_download', [
            'title' => tr_text('共有ファイル', 'Shared file'),
            'status' => 'ready',
            'share' => $share,
            'message' => tr_text('共有ファイルをダウンロードできます。', 'This shared file is ready to download.'),
        ]);
    }

    private function validateShareStatus(array $share)
    {
        if (!empty($share['revoked_at'])) {
            return 'revoked';
        }
        if (!empty($share['expires_at']) && strtotime((string)$share['expires_at']) < time()) {
            return 'expired';
        }
        if (!is_null($share['max_downloads']) && (int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) {
            return 'download_limit';
        }
        return 'ok';
    }

    private function validateTargetAccess(array $targets)
    {
        if (empty($targets)) {
            return 'ok';
        }

        $user = $this->auth->user();
        if (!$user) {
            return 'login_required';
        }

        $userId = (int)$user['id'];
        $userOrgIds = $this->getUserOrganizationIds($userId, (int)($user['organization_id'] ?? 0));

        foreach ($targets as $target) {
            $targetType = (string)($target['target_type'] ?? '');
            $targetId = (int)($target['target_id'] ?? 0);
            if ($targetType === 'user' && $targetId === $userId) {
                return 'ok';
            }
            if ($targetType === 'organization' && in_array($targetId, $userOrgIds, true)) {
                return 'ok';
            }
        }

        return 'forbidden';
    }

    private function getUserOrganizationIds($userId, $primaryOrgId)
    {
        $orgIds = [];
        if ($primaryOrgId > 0) {
            $orgIds[$primaryOrgId] = $primaryOrgId;
        }

        $rows = $this->db->fetchAll(
            "SELECT organization_id FROM user_organizations WHERE user_id = ?",
            [(int)$userId]
        );
        foreach ($rows as $row) {
            $orgId = (int)($row['organization_id'] ?? 0);
            if ($orgId > 0) {
                $orgIds[$orgId] = $orgId;
            }
        }

        return array_values($orgIds);
    }

    private function isSharePasswordVerified($token)
    {
        return !empty($_SESSION['file_share_verified_' . $token]);
    }

    private function markSharePasswordVerified($token)
    {
        $_SESSION['file_share_verified_' . $token] = 1;
    }

    private function statusMessage($status)
    {
        switch ($status) {
            case 'revoked':
                return tr_text('この共有リンクは無効化されています。', 'This share link has been revoked.');
            case 'expired':
                return tr_text('この共有リンクの有効期限は終了しました。', 'This share link has expired.');
            case 'download_limit':
                return tr_text('この共有リンクのダウンロード上限に達しました。', 'This share link reached the download limit.');
            case 'login_required':
                return tr_text('この共有リンクはログインが必要です。', 'This share link requires sign-in.');
            case 'forbidden':
                return tr_text('この共有リンクにアクセスする権限がありません。', 'You do not have permission to access this share link.');
            default:
                return tr_text('共有リンクが見つかりません。', 'Share link was not found.');
        }
    }

    private function streamSharedFile(array $share)
    {
        $status = $this->validateShareStatus($share);
        if ($status !== 'ok') {
            http_response_code(410);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => $status,
                'share' => $share,
                'message' => $this->statusMessage($status),
            ]);
            return;
        }

        $filePath = $this->uploadDir . (string)$share['filename'];
        if (!is_file($filePath)) {
            http_response_code(404);
            $this->view('file_manager/shared_download', [
                'title' => tr_text('共有リンク', 'Shared link'),
                'status' => 'not_found',
                'share' => $share,
                'message' => tr_text('ファイルが見つかりません。', 'The file could not be found.'),
            ]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE file_share_links SET download_count = download_count + 1 WHERE id = ?",
                [(int)$share['id']]
            );
            $this->db->execute(
                "UPDATE file_entries SET download_count = download_count + 1 WHERE id = ?",
                [(int)$share['file_id']]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('streamSharedFile counter update error: ' . $e->getMessage());
        }

        $mimeType = (string)($share['mime_type'] ?? '');
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }
        $downloadName = (string)($share['original_name'] ?? 'download.bin');

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }
}
