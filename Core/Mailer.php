<?php
// core/Mailer.php
namespace Core;

use Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    private $setting;

    public function __construct($setting = null)
    {
        $this->setting = $setting ?: new Setting();
    }

    /**
     * メール送信
     *
     * @param string $toEmail
     * @param string $subject
     * @param string $body
     * @param bool $isHtml
     * @return void
     * @throws \Exception
     */
    public function send($toEmail, $subject, $body, $isHtml = true)
    {
        if (!$this->setting->isEmailConfigured()) {
            throw new \Exception('メール送信設定が不完全です。管理画面で設定を確認してください。');
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('送信先メールアドレスが不正です。');
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        $config = $this->setting->getMailConfig();
        $mail = $this->createMailer($config);

        $mail->addAddress($toEmail);
        $mail->isHTML((bool)$isHtml);
        $mail->Subject = (string)$subject;
        $mail->Body = (string)$body;

        if (!(bool)$isHtml) {
            $mail->AltBody = (string)$body;
        } else {
            $mail->AltBody = trim(strip_tags((string)$body));
        }

        $mail->send();
    }

    /**
     * PHPMailerのインスタンス作成
     *
     * @param array $config
     * @return PHPMailer
     */
    private function createMailer($config)
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Timeout = (int)($config['smtp_timeout'] ?? 30);

        $transport = $config['transport'] ?? 'smtp';
        if ($transport === 'sendmail') {
            if (!empty($config['sendmail_path'])) {
                $mail->Sendmail = $config['sendmail_path'];
            }
            $mail->isSendmail();
        } elseif ($transport === 'mail') {
            $mail->isMail();
        } else {
            $mail->isSMTP();
            $mail->Host = (string)($config['smtp_host'] ?? '');
            $mail->Port = (int)($config['smtp_port'] ?? 587);
            $mail->SMTPAuth = (bool)($config['smtp_auth'] ?? true);

            if ($mail->SMTPAuth) {
                $mail->Username = (string)($config['smtp_username'] ?? '');
                $mail->Password = (string)($config['smtp_password'] ?? '');
            }

            $secure = strtolower((string)($config['smtp_secure'] ?? 'tls'));
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            if (!empty($config['smtp_allow_self_signed'])) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }
        }

        $fromEmail = (string)($config['from_email'] ?? '');
        $fromName = (string)($config['from_name'] ?? $this->setting->getAppName());
        $mail->setFrom($fromEmail, $fromName);

        $replyTo = (string)($config['reply_to_email'] ?? '');
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, $fromName);
        }

        return $mail;
    }
}
