-- ============================================================
-- TeamSpace Groupware - Demo Data
-- Purpose: Production demo site seed data
-- Safe to run multiple times (uses INSERT IGNORE / ON DUPLICATE KEY)
-- Generated: 2026-03-26
-- ============================================================

SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. Organizations
-- ============================================================
INSERT IGNORE INTO organizations (id, name, code, parent_id, level, sort_order, description)
VALUES
    (1, '株式会社デモ',  'DEMO',    NULL, 1, 1, 'デモ用親会社'),
    (2, '営業部',        'SALES',   1,    2, 1, '営業活動全般を担当'),
    (3, '開発部',        'DEV',     1,    2, 2, 'システム開発・保守を担当'),
    (4, '総務部',        'GA',      1,    2, 3, '総務・庶務全般を担当'),
    (5, '経理部',        'ACCT',    1,    2, 4, '経理・財務全般を担当');

-- ============================================================
-- 2. Users (password = 'demo1234')
-- ============================================================
-- Hash generated via: php -r "echo password_hash('demo1234', PASSWORD_DEFAULT);"
SET @pw = '$2y$12$fXFFJBuFh3r9eZZB6zcgzO7IAyMhrx4sFGoplLKDaTucac26lCzqu';

INSERT INTO users (id, username, password, email, first_name, last_name, display_name, organization_id, position, phone, mobile_phone, status, role)
VALUES
    (1, 'admin',  @pw, 'admin@demo.example.com',  '管理者', '',     '管理者',     4, 'システム管理者',  '03-1234-0001', '090-1234-0001', 'active', 'admin'),
    (2, 'yamada', @pw, 'yamada@demo.example.com', '太郎',   '山田', '山田太郎',   2, '営業主任',        '03-1234-0002', '090-1234-0002', 'active', 'user'),
    (3, 'tanaka', @pw, 'tanaka@demo.example.com', '花子',   '田中', '田中花子',   3, 'エンジニア',      '03-1234-0003', '090-1234-0003', 'active', 'user'),
    (4, 'suzuki', @pw, 'suzuki@demo.example.com', '一郎',   '鈴木', '鈴木一郎',  4, '総務課長',        '03-1234-0004', '090-1234-0004', 'active', 'manager'),
    (5, 'sato',   @pw, 'sato@demo.example.com',   '美咲',   '佐藤', '佐藤美咲',  5, '経理担当',        '03-1234-0005', '090-1234-0005', 'active', 'user')
ON DUPLICATE KEY UPDATE
    password     = VALUES(password),
    display_name = VALUES(display_name),
    email        = VALUES(email),
    organization_id = VALUES(organization_id),
    role         = VALUES(role);

-- User-Organization relations (primary)
INSERT IGNORE INTO user_organizations (user_id, organization_id, is_primary)
VALUES
    (1, 4, 1),
    (2, 2, 1),
    (3, 3, 1),
    (4, 4, 1),
    (5, 5, 1);

-- ============================================================
-- 3. Settings
-- ============================================================
INSERT INTO settings (setting_key, setting_value, description)
VALUES
    ('app_name',     'TeamSpace Demo', 'アプリケーション表示名'),
    ('company_name', '株式会社デモ',    '会社名')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);

-- ============================================================
-- 4. Facilities (会議室・応接室)
-- ============================================================
INSERT IGNORE INTO facilities (id, name, description, capacity, sort_order, created_at)
VALUES
    (1, '会議室A',  '本社3階 大会議室（プロジェクター・ホワイトボード完備）', 20, 1, NOW()),
    (2, '会議室B',  '本社3階 小会議室（モニター完備）',                       8,  2, NOW()),
    (3, '応接室',   '本社1階 応接室（来客用）',                               6,  3, NOW());

-- ============================================================
-- 5. Workflow Templates
-- ============================================================
INSERT INTO workflow_templates (id, name, description, status, creator_id, created_at, updated_at)
VALUES
    (1, '有給休暇申請',   '有給休暇の取得申請',       'active', 1, NOW(), NOW()),
    (2, '経費精算',       '業務上の経費精算申請',     'active', 1, NOW(), NOW()),
    (3, '出張申請',       '出張の事前申請',           'active', 1, NOW(), NOW()),
    (4, '備品購入申請',   '業務用備品の購入申請',     'active', 1, NOW(), NOW()),
    (5, '残業申請',       '時間外労働の事前申請',     'active', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name        = VALUES(name),
    description = VALUES(description),
    status      = VALUES(status);

-- ------------------------------------------------------------
-- 5a. Form Definitions
-- ------------------------------------------------------------
-- Delete existing form defs for idempotency
DELETE FROM workflow_form_definitions WHERE template_id IN (1,2,3,4,5);

-- Template 1: 有給休暇申請
INSERT INTO workflow_form_definitions (template_id, field_id, field_type, label, placeholder, help_text, options, validation, is_required, sort_order) VALUES
(1, 'leave_type',   'select',   '休暇種別', NULL,           NULL, '["有給休暇","半日有給（午前）","半日有給（午後）","特別休暇"]', NULL, 1, 1),
(1, 'start_date',   'date',     '開始日',   NULL,           NULL, NULL, NULL, 1, 2),
(1, 'end_date',     'date',     '終了日',   NULL,           '半日有給の場合は開始日と同日を指定', NULL, NULL, 1, 3),
(1, 'days_count',   'number',   '取得日数', '1',            NULL, NULL, '{"min":0.5,"max":40}', 1, 4),
(1, 'reason',       'textarea', '事由',     '休暇の理由を入力してください', NULL, NULL, NULL, 1, 5),
(1, 'contact',      'text',     '緊急連絡先', '090-XXXX-XXXX', '休暇中の連絡先', NULL, NULL, 0, 6);

-- Template 2: 経費精算
INSERT INTO workflow_form_definitions (template_id, field_id, field_type, label, placeholder, help_text, options, validation, is_required, sort_order) VALUES
(2, 'expense_date',   'date',     '発生日',   NULL, NULL, NULL, NULL, 1, 1),
(2, 'expense_type',   'select',   '経費種別', NULL, NULL, '["交通費","交際費","消耗品費","通信費","その他"]', NULL, 1, 2),
(2, 'amount',         'number',   '金額（税込）', '0', NULL, NULL, '{"min":1}', 1, 3),
(2, 'payment_method', 'select',   '支払方法', NULL, NULL, '["立替","法人カード","請求書払い"]', NULL, 1, 4),
(2, 'description',    'textarea', '内容・用途', '経費の内容と業務上の用途を記入', NULL, NULL, NULL, 1, 5),
(2, 'receipt',        'file',     '領収書',    NULL, '領収書の画像またはPDFを添付', NULL, NULL, 0, 6);

-- Template 3: 出張申請
INSERT INTO workflow_form_definitions (template_id, field_id, field_type, label, placeholder, help_text, options, validation, is_required, sort_order) VALUES
(3, 'destination',    'text',     '出張先',       '例: 大阪本社', NULL, NULL, NULL, 1, 1),
(3, 'purpose',        'textarea', '出張目的',     '出張の目的を入力', NULL, NULL, NULL, 1, 2),
(3, 'depart_date',    'date',     '出発日',       NULL, NULL, NULL, NULL, 1, 3),
(3, 'return_date',    'date',     '帰着日',       NULL, NULL, NULL, NULL, 1, 4),
(3, 'transport',      'select',   '交通手段',     NULL, NULL, '["新幹線","飛行機","自家用車","バス","その他"]', NULL, 1, 5),
(3, 'est_cost',       'number',   '概算費用（円）', '0', '交通費・宿泊費の概算合計', NULL, '{"min":0}', 1, 6),
(3, 'accommodation',  'text',     '宿泊先',       '例: ○○ホテル', NULL, NULL, NULL, 0, 7),
(3, 'remarks',        'textarea', '備考',         NULL, NULL, NULL, NULL, 0, 8);

-- Template 4: 備品購入申請
INSERT INTO workflow_form_definitions (template_id, field_id, field_type, label, placeholder, help_text, options, validation, is_required, sort_order) VALUES
(4, 'item_name',      'text',     '品名',         '購入する備品名', NULL, NULL, NULL, 1, 1),
(4, 'quantity',        'number',   '数量',         '1', NULL, NULL, '{"min":1}', 1, 2),
(4, 'unit_price',      'number',   '単価（税込）', '0', NULL, NULL, '{"min":1}', 1, 3),
(4, 'total_price',     'calc',     '合計金額',     NULL, NULL, NULL, '{"formula":"quantity * unit_price"}', 0, 4),
(4, 'purchase_reason', 'textarea', '購入理由',     '購入の必要性を記入', NULL, NULL, NULL, 1, 5),
(4, 'vendor',          'text',     '購入先',       '例: Amazon / ヨドバシカメラ', NULL, NULL, NULL, 0, 6),
(4, 'desired_date',    'date',     '希望納品日',   NULL, NULL, NULL, NULL, 0, 7);

-- Template 5: 残業申請
INSERT INTO workflow_form_definitions (template_id, field_id, field_type, label, placeholder, help_text, options, validation, is_required, sort_order) VALUES
(5, 'overtime_date',   'date',     '残業日',       NULL, NULL, NULL, NULL, 1, 1),
(5, 'start_time',      'text',     '開始予定時刻', '18:00', NULL, NULL, NULL, 1, 2),
(5, 'end_time',        'text',     '終了予定時刻', '20:00', NULL, NULL, NULL, 1, 3),
(5, 'hours',           'number',   '予定時間（h）','2', NULL, NULL, '{"min":0.5,"max":8}', 1, 4),
(5, 'task_detail',     'textarea', '作業内容',     '残業で行う作業内容を記入', NULL, NULL, NULL, 1, 5),
(5, 'reason',          'textarea', '残業理由',     '通常時間内に完了できない理由', NULL, NULL, NULL, 1, 6);

-- ------------------------------------------------------------
-- 5b. Approval Route Definitions
-- ------------------------------------------------------------
DELETE FROM workflow_route_definitions WHERE template_id IN (1,2,3,4,5);

-- All templates: Step 1 = Manager approval, Step 2 = Admin final approval
INSERT INTO workflow_route_definitions (template_id, step_number, step_type, step_name, approver_type, approver_id, allow_delegation, allow_self_approval, parallel_approval, sort_order) VALUES
-- 有給休暇申請
(1, 1, 'approval',     '上長承認', 'user', 4, 1, 0, 0, 1),
(1, 2, 'approval',     '最終承認', 'user', 1, 0, 0, 0, 2),
-- 経費精算
(2, 1, 'approval',     '上長承認', 'user', 4, 1, 0, 0, 1),
(2, 2, 'approval',     '経理確認', 'user', 5, 0, 0, 0, 2),
-- 出張申請
(3, 1, 'approval',     '上長承認', 'user', 4, 1, 0, 0, 1),
(3, 2, 'approval',     '最終承認', 'user', 1, 0, 0, 0, 2),
-- 備品購入申請
(4, 1, 'approval',     '上長承認', 'user', 4, 1, 0, 0, 1),
(4, 2, 'approval',     '経理確認', 'user', 5, 0, 0, 0, 2),
-- 残業申請
(5, 1, 'approval',     '上長承認', 'user', 4, 1, 0, 0, 1);

-- ============================================================
-- 6. Sample Schedules (current week: 2026-03-23 ~ 2026-03-29)
-- ============================================================
-- Use date arithmetic relative to today so data stays relevant
-- when script is run on 2026-03-26

INSERT IGNORE INTO schedules (id, title, description, start_time, end_time, all_day, location, creator_id, visibility, priority, status)
VALUES
(1, '週次営業ミーティング',
    '今週の営業進捗共有と来週の計画策定',
    '2026-03-23 10:00:00', '2026-03-23 11:00:00',
    0, '会議室A', 2, 'public', 'normal', 'scheduled'),

(2, 'プロジェクトAlpha 定例会',
    '開発進捗確認、課題の洗い出し',
    '2026-03-24 14:00:00', '2026-03-24 15:30:00',
    0, '会議室B', 3, 'public', 'high', 'scheduled'),

(3, '新入社員研修準備',
    '4月入社メンバー向け研修の資料・会場準備',
    '2026-03-25 09:00:00', '2026-03-25 12:00:00',
    0, '会議室A', 4, 'public', 'normal', 'scheduled'),

(4, '経費精算締切',
    '3月分の経費精算書を提出してください',
    '2026-03-27 00:00:00', '2026-03-27 23:59:59',
    1, NULL, 5, 'public', 'high', 'scheduled'),

(5, 'お客様訪問（ABC商事）',
    '提案書の説明とデモ実施',
    '2026-03-26 13:00:00', '2026-03-26 15:00:00',
    0, 'ABC商事 本社', 2, 'public', 'high', 'scheduled'),

(6, '全体朝礼',
    '月末の全社朝礼',
    '2026-03-27 09:00:00', '2026-03-27 09:30:00',
    0, '会議室A', 1, 'public', 'normal', 'scheduled');

-- Schedule participants
INSERT IGNORE INTO schedule_participants (schedule_id, user_id, status) VALUES
-- 週次営業ミーティング
(1, 2, 'accepted'), (1, 4, 'accepted'),
-- プロジェクトAlpha 定例会
(2, 3, 'accepted'), (2, 2, 'accepted'), (2, 1, 'tentative'),
-- 新入社員研修準備
(3, 4, 'accepted'), (3, 1, 'accepted'),
-- 経費精算締切
(4, 5, 'accepted'), (4, 1, 'accepted'), (4, 2, 'accepted'), (4, 3, 'accepted'), (4, 4, 'accepted'),
-- お客様訪問
(5, 2, 'accepted'), (5, 4, 'accepted'),
-- 全体朝礼
(6, 1, 'accepted'), (6, 2, 'accepted'), (6, 3, 'accepted'), (6, 4, 'accepted'), (6, 5, 'accepted');

-- ============================================================
-- 7. Facility Reservations (current week)
-- ============================================================
INSERT IGNORE INTO facility_reservations (id, facility_id, user_id, title, start_time, end_time, memo, created_at, updated_at)
VALUES
(1, 1, 2, '週次営業ミーティング',
    '2026-03-23 10:00:00', '2026-03-23 11:00:00',
    'プロジェクター使用', NOW(), NOW()),
(2, 2, 3, 'プロジェクトAlpha 定例会',
    '2026-03-24 14:00:00', '2026-03-24 15:30:00',
    'モニター接続確認済', NOW(), NOW()),
(3, 1, 4, '新入社員研修準備',
    '2026-03-25 09:00:00', '2026-03-25 12:00:00',
    '机のレイアウト変更あり', NOW(), NOW()),
(4, 3, 2, 'お客様訪問（ABC商事様来社）',
    '2026-03-26 13:00:00', '2026-03-26 15:00:00',
    'お茶の手配をお願いします', NOW(), NOW()),
(5, 1, 1, '全体朝礼',
    '2026-03-27 09:00:00', '2026-03-27 09:30:00',
    NULL, NOW(), NOW());

-- ============================================================
-- 8. Bulletin Board
-- ============================================================
-- Categories (matches upgrade_20260325_bulletin.sql initial data)
INSERT IGNORE INTO bulletin_categories (id, name, description, sort_order, created_by) VALUES
(1, 'お知らせ',     '全社向けのお知らせを掲載します',         1,  1),
(2, '総務・人事',   '総務・人事部門からの連絡事項',           2,  1),
(3, 'IT・システム', 'IT関連の情報やメンテナンス情報',         3,  1),
(4, 'その他',       '上記カテゴリに当てはまらない投稿',       99, 1);

-- Posts
INSERT IGNORE INTO bulletin_posts (id, category_id, title, body, is_pinned, status, visibility, author_id, view_count, created_at, updated_at)
VALUES
(1, 1, '【重要】年度末の業務スケジュールについて',
'社員各位

お疲れ様です。総務部の鈴木です。

年度末に伴い、以下のスケジュールにご注意ください。

■ 3月27日（金）経費精算 最終締切
  3月分の経費精算書は必ず27日中にワークフローから提出してください。

■ 3月31日（火）棚卸し作業
  各部署の備品棚卸しを実施します。担当者は事前に備品リストの確認をお願いします。

■ 4月1日（水）入社式・辞令交付
  新入社員の入社式を本社3階会議室Aにて実施します。
  各部署の受け入れ担当者は9:30までに集合してください。

ご不明点は総務部までお問い合わせください。',
1, 'published', 'all', 4, 12, '2026-03-20 09:00:00', '2026-03-20 09:00:00'),

(2, 3, 'システムメンテナンスのお知らせ（3/29）',
'社員各位

下記の日程でシステムメンテナンスを実施いたします。

■ 日時：2026年3月29日（日）02:00 ～ 06:00
■ 影響：グループウェア全機能が一時的に利用できなくなります
■ 対象：TeamSpace 全サービス

メンテナンス終了後、正常に利用できることをご確認ください。
万一、メンテナンス後に不具合がございましたら開発部までご連絡ください。

開発部 田中',
0, 'published', 'all', 3, 8, '2026-03-22 11:30:00', '2026-03-22 11:30:00'),

(3, 2, '4月度 社内研修の受講者募集',
'社員各位

4月に開催予定の社内研修について受講者を募集いたします。

■ ビジネスマナー研修（新入社員向け）
  日時：4月2日（木）10:00-17:00
  場所：会議室A
  定員：20名

■ 情報セキュリティ研修（全社員対象）
  日時：4月10日（金）14:00-16:00
  場所：会議室A（オンライン同時配信あり）
  ※ 全社員必須受講です

参加希望の方は3月28日までに総務部へご連絡ください。

総務部 鈴木',
0, 'published', 'all', 4, 5, '2026-03-24 10:00:00', '2026-03-24 10:00:00');

-- ============================================================
-- 9. Sample Workflow Requests
-- ============================================================
-- 9a. Paid leave request (yamada -> approved)
INSERT IGNORE INTO workflow_requests (id, request_number, template_id, title, status, current_step, requester_id, created_at, updated_at)
VALUES
(1, 'WF-2026-0001', 1, '有給休暇申請（4/3-4/4）', 'approved', 2, 2, '2026-03-18 10:30:00', '2026-03-19 14:00:00');

INSERT IGNORE INTO workflow_request_data (request_id, field_id, value) VALUES
(1, 'leave_type',  '有給休暇'),
(1, 'start_date',  '2026-04-03'),
(1, 'end_date',    '2026-04-04'),
(1, 'days_count',  '2'),
(1, 'reason',      '家族行事のため'),
(1, 'contact',     '090-1234-0002');

INSERT IGNORE INTO workflow_approvals (id, request_id, step_number, approver_id, status, comment, acted_at) VALUES
(1, 1, 1, 4, 'approved', '承認します。業務の引き継ぎをお願いします。', '2026-03-18 16:00:00'),
(2, 1, 2, 1, 'approved', '承認しました。', '2026-03-19 14:00:00');

-- 9b. Expense reimbursement (tanaka -> pending)
INSERT IGNORE INTO workflow_requests (id, request_number, template_id, title, status, current_step, requester_id, created_at, updated_at)
VALUES
(2, 'WF-2026-0002', 2, '経費精算：技術書籍購入', 'pending', 1, 3, '2026-03-25 09:15:00', '2026-03-25 09:15:00');

INSERT IGNORE INTO workflow_request_data (request_id, field_id, value) VALUES
(2, 'expense_date',   '2026-03-22'),
(2, 'expense_type',   'その他'),
(2, 'amount',         '3520'),
(2, 'payment_method', '立替'),
(2, 'description',    'クラウドアーキテクチャ設計パターン（技術書籍）を業務参考のため購入');

INSERT IGNORE INTO workflow_approvals (id, request_id, step_number, approver_id, status, comment, acted_at) VALUES
(3, 2, 1, 4, 'pending', NULL, NULL);

-- 9c. Overtime request (sato -> pending)
INSERT IGNORE INTO workflow_requests (id, request_number, template_id, title, status, current_step, requester_id, created_at, updated_at)
VALUES
(3, 'WF-2026-0003', 5, '残業申請（3/27 月次決算対応）', 'pending', 1, 5, '2026-03-26 08:30:00', '2026-03-26 08:30:00');

INSERT IGNORE INTO workflow_request_data (request_id, field_id, value) VALUES
(3, 'overtime_date', '2026-03-27'),
(3, 'start_time',    '18:00'),
(3, 'end_time',      '21:00'),
(3, 'hours',         '3'),
(3, 'task_detail',   '月次決算の仕訳入力および試算表作成'),
(3, 'reason',        '月末締切に伴う決算処理のため、通常時間内での完了が困難');

INSERT IGNORE INTO workflow_approvals (id, request_id, step_number, approver_id, status, comment, acted_at) VALUES
(4, 3, 1, 4, 'pending', NULL, NULL);

-- ============================================================
-- 10. Sample Daily Reports
-- ============================================================
INSERT IGNORE INTO daily_reports (id, user_id, report_date, title, content, status, is_template)
VALUES
(1, 2, '2026-03-25', '3/25 営業日報',
'【本日の業務】
・ABC商事様への提案資料作成（完了）
・新規見込み客リスト整理（進行中）
・週次営業ミーティング資料準備

【明日の予定】
・ABC商事様訪問（13:00-15:00）
・見積書作成（DEF工業様向け）

【所感】
ABC商事様向けの提案資料が完成。明日の訪問に向けてデモ環境の最終確認を行う予定。
先方の反応は良好で、4月中の受注を目指す。',
'published', 0),

(2, 3, '2026-03-25', '3/25 開発日報',
'【本日の業務】
・プロジェクトAlpha：APIエンドポイント実装（3/5件完了）
・コードレビュー対応（PRコメント修正）
・セキュリティパッチ適用調査

【明日の予定】
・プロジェクトAlpha：APIエンドポイント実装（継続）
・単体テスト作成

【課題・相談事項】
・外部API連携部分の仕様確認が必要（担当：山田さん経由でクライアント確認）',
'published', 0);

-- ============================================================
-- 11. Notification Settings (defaults for all users)
-- ============================================================
INSERT IGNORE INTO notification_settings (user_id, notify_schedule, notify_workflow, notify_message, email_notify)
VALUES
(1, 1, 1, 1, 1),
(2, 1, 1, 1, 1),
(3, 1, 1, 1, 1),
(4, 1, 1, 1, 1),
(5, 1, 1, 1, 1);

-- ============================================================
-- 12. Sample Messages
-- ============================================================
INSERT IGNORE INTO messages (id, subject, body, sender_id, parent_id, thread_id, created_at)
VALUES
(1, 'ABC商事様 訪問の件',
'山田さん

お疲れ様です。鈴木です。
3/26のABC商事様訪問について、応接室を予約しました。
お茶の手配も総務で対応しますので、ご安心ください。

当日はよろしくお願いいたします。',
4, NULL, NULL, '2026-03-25 14:00:00'),

(2, 'Re: ABC商事様 訪問の件',
'鈴木さん

ご対応ありがとうございます。
資料の印刷を5部お願いしてもよろしいでしょうか。
データはファイル管理にアップロード済みです。

よろしくお願いします。',
2, 1, 1, '2026-03-25 14:30:00');

-- Thread self-reference for first message
UPDATE messages SET thread_id = 1 WHERE id = 1 AND thread_id IS NULL;

INSERT IGNORE INTO message_recipients (message_id, user_id, is_read, is_starred) VALUES
(1, 2, 1, 0),
(2, 4, 1, 0);

-- ============================================================
-- Restore foreign key checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- ============================================================
-- Verification summary
-- ============================================================
SELECT 'organizations' AS `table`, COUNT(*) AS `rows` FROM organizations
UNION ALL SELECT 'users',                    COUNT(*) FROM users
UNION ALL SELECT 'user_organizations',       COUNT(*) FROM user_organizations
UNION ALL SELECT 'settings',                 COUNT(*) FROM settings
UNION ALL SELECT 'facilities',               COUNT(*) FROM facilities
UNION ALL SELECT 'workflow_templates',       COUNT(*) FROM workflow_templates
UNION ALL SELECT 'workflow_form_definitions',COUNT(*) FROM workflow_form_definitions
UNION ALL SELECT 'workflow_route_definitions',COUNT(*) FROM workflow_route_definitions
UNION ALL SELECT 'workflow_requests',        COUNT(*) FROM workflow_requests
UNION ALL SELECT 'workflow_request_data',    COUNT(*) FROM workflow_request_data
UNION ALL SELECT 'workflow_approvals',       COUNT(*) FROM workflow_approvals
UNION ALL SELECT 'schedules',               COUNT(*) FROM schedules
UNION ALL SELECT 'schedule_participants',    COUNT(*) FROM schedule_participants
UNION ALL SELECT 'facility_reservations',   COUNT(*) FROM facility_reservations
UNION ALL SELECT 'bulletin_categories',     COUNT(*) FROM bulletin_categories
UNION ALL SELECT 'bulletin_posts',          COUNT(*) FROM bulletin_posts
UNION ALL SELECT 'daily_reports',           COUNT(*) FROM daily_reports
UNION ALL SELECT 'messages',                COUNT(*) FROM messages
UNION ALL SELECT 'notification_settings',   COUNT(*) FROM notification_settings;
