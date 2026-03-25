-- 2026-03-02 mail delivery enhancement
USE g_session;

INSERT INTO settings (setting_key, setting_value, description)
VALUES
    ('app_url', '', 'アプリケーション公開URL'),
    ('mail_transport', 'smtp', 'メール送信方式(smtp/sendmail/mail)'),
    ('mail_from_name', 'GroupWare', '通知メール送信者名'),
    ('mail_reply_to_email', '', '通知メール返信先アドレス'),
    ('smtp_auth', '1', 'SMTP認証を使用するか'),
    ('smtp_timeout', '30', 'SMTPタイムアウト秒'),
    ('smtp_allow_self_signed', '0', '自己署名証明書許可'),
    ('sendmail_path', '', 'sendmailコマンドパス')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description = VALUES(description);
