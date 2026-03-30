<?php
namespace Controllers;

use Core\Controller;
use Models\PushSubscription;
use Models\Setting;
use Services\WebPushService;

class PwaController extends Controller
{
    private $settingModel;
    private $pushSubscriptionModel;
    private $webPushService;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new Setting();
        $this->pushSubscriptionModel = new PushSubscription();
        $this->webPushService = new WebPushService();
    }

    public function manifest()
    {
        $appName = (string)$this->settingModel->get('pwa_app_name', $this->settingModel->getAppName());
        $shortName = (string)$this->settingModel->get('pwa_short_name', $appName);
        $themeColor = (string)$this->settingModel->get('pwa_theme_color', '#2b7de9');
        $bgColor = (string)$this->settingModel->get('pwa_background_color', '#ffffff');

        $data = [
            'name' => $appName,
            'short_name' => $shortName,
            'start_url' => BASE_PATH . '/',
            'scope' => BASE_PATH . '/',
            'display' => 'standalone',
            'background_color' => $bgColor,
            'theme_color' => $themeColor,
            'lang' => get_locale(),
            'icons' => [
                [
                    'src' => BASE_PATH . '/public/icons/pwa-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => BASE_PATH . '/public/icons/pwa-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function serviceWorker()
    {
        $templatePath = __DIR__ . '/../public/js/service-worker-template.js';
        if (!file_exists($templatePath)) {
            http_response_code(404);
            echo '/* service worker template not found */';
            exit;
        }

        $raw = (string)file_get_contents($templatePath);
        $version = (string)(@filemtime($templatePath) ?: time());
        $replaced = str_replace(
            ['__BASE_PATH__', '__CACHE_VERSION__'],
            [BASE_PATH, $version],
            $raw
        );

        header('Content-Type: application/javascript; charset=utf-8');
        echo $replaced;
        exit;
    }

    public function offline()
    {
        $this->view('pwa/offline', [
            'title' => get_locale() === 'ja' ? 'オフライン' : 'Offline',
        ]);
    }

    public function apiConfig()
    {
        $pwaEnabled = filter_var((string)$this->settingModel->get('pwa_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        $pushEnabled = filter_var((string)$this->settingModel->get('pwa_notifications_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        $publicKey = '';
        if ($pwaEnabled && $pushEnabled) {
            try {
                $publicKey = (string)$this->webPushService->getPublicVapidKey();
            } catch (\Throwable $e) {
                $publicKey = '';
            }
        }

        return [
            'success' => true,
            'data' => [
                'pwa_enabled' => $pwaEnabled,
                'push_enabled' => $pushEnabled,
                'vapid_public_key' => $publicKey
            ]
        ];
    }

    public function apiSubscribe($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $ok = $this->pushSubscriptionModel->upsert((int)$this->auth->id(), is_array($data) ? $data : []);
        if (!$ok) {
            return ['error' => '購読情報の保存に失敗しました', 'code' => 422];
        }

        return [
            'success' => true,
            'message' => 'Push購読を保存しました'
        ];
    }

    public function apiUnsubscribe($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $endpoint = trim((string)($data['endpoint'] ?? ''));
        if ($endpoint === '') {
            return ['error' => 'endpoint が必要です', 'code' => 422];
        }

        $ok = $this->pushSubscriptionModel->deactivateByEndpoint((int)$this->auth->id(), $endpoint);
        return [
            'success' => (bool)$ok,
            'message' => $ok ? 'Push購読を解除しました' : 'Push購読の解除に失敗しました'
        ];
    }

    public function apiTestPush()
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $result = $this->webPushService->sendTestNotificationToUser((int)$this->auth->id(), [
            'title' => 'TeamSpace テスト通知',
            'body' => 'PWA Push通知のテスト送信です。',
            'url' => BASE_PATH . '/notifications',
            'tag' => 'teamspace-test'
        ]);

        if (empty($result['success'])) {
            return ['error' => $result['message'] ?? 'Push通知の送信に失敗しました', 'code' => 422];
        }

        return [
            'success' => true,
            'message' => $result['message'] ?? 'Push通知を送信しました',
            'data' => $result['data'] ?? null
        ];
    }
}
