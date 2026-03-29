<?php
namespace Services;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Models\PushSubscription;
use Models\Setting;

class WebPushService
{
    private $settingModel;
    private $pushSubscriptionModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->pushSubscriptionModel = new PushSubscription();
    }

    public function ensureVapidKeys()
    {
        $this->ensureVendorAutoload();
        $public = trim((string)$this->settingModel->get('pwa_vapid_public_key', ''));
        $private = trim((string)$this->settingModel->get('pwa_vapid_private_key', ''));
        $subject = trim((string)$this->settingModel->get('pwa_vapid_subject', 'mailto:admin@example.com'));

        if ($public !== '' && $private !== '') {
            return [
                'publicKey' => $public,
                'privateKey' => $private,
                'subject' => $subject !== '' ? $subject : 'mailto:admin@example.com'
            ];
        }

        $keys = VAPID::createVapidKeys();
        $this->settingModel->update('pwa_vapid_public_key', (string)$keys['publicKey']);
        $this->settingModel->update('pwa_vapid_private_key', (string)$keys['privateKey']);
        if ($subject === '') {
            $subject = 'mailto:admin@example.com';
            $this->settingModel->update('pwa_vapid_subject', $subject);
        }

        return [
            'publicKey' => (string)$keys['publicKey'],
            'privateKey' => (string)$keys['privateKey'],
            'subject' => $subject
        ];
    }

    public function getPublicVapidKey()
    {
        $keys = $this->ensureVapidKeys();
        return $keys['publicKey'];
    }

    public function sendTestNotificationToUser($userId, array $payload)
    {
        $this->ensureVendorAutoload();
        $subs = $this->pushSubscriptionModel->getActiveByUserId((int)$userId);
        if (empty($subs)) {
            return [
                'success' => false,
                'message' => '有効なPush購読がありません'
            ];
        }

        $keys = $this->ensureVapidKeys();
        $auth = [
            'VAPID' => [
                'subject' => $keys['subject'],
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey']
            ]
        ];

        $webPush = new WebPush($auth);
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $queued = 0;

        foreach ($subs as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['public_key'],
                    'authToken' => $sub['auth_token'],
                    'contentEncoding' => $sub['content_encoding'] ?: 'aesgcm',
                ]);
                $webPush->queueNotification($subscription, $jsonPayload);
                $queued++;
            } catch (\Throwable $e) {
                $this->pushSubscriptionModel->markFailure((int)$sub['id'], $e->getMessage());
            }
        }

        $success = 0;
        $failed = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = (string)$report->getRequest()->getUri();
            $matchedId = null;
            foreach ($subs as $sub) {
                if ((string)$sub['endpoint'] === $endpoint) {
                    $matchedId = (int)$sub['id'];
                    break;
                }
            }

            if ($report->isSuccess()) {
                $success++;
                if ($matchedId) {
                    $this->pushSubscriptionModel->markSuccess($matchedId);
                }
            } else {
                $failed++;
                if ($matchedId) {
                    $reason = $report->getReason() ?: 'push failed';
                    $this->pushSubscriptionModel->markFailure($matchedId, $reason);
                }
            }
        }

        return [
            'success' => $success > 0,
            'message' => "Push送信結果: 成功 {$success} / 失敗 {$failed}",
            'data' => [
                'queued' => $queued,
                'success' => $success,
                'failed' => $failed
            ]
        ];
    }

    private function ensureVendorAutoload()
    {
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
            if (!file_exists($autoload)) {
                throw new \RuntimeException('composer dependencies are not installed');
            }
            require_once $autoload;
        }
    }
}
