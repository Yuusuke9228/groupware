-- ============================================================
-- TeamSpace Groupware - Comprehensive Demo Data (Supplement)
-- Purpose: Add rich sample data to all modules
-- Safe to run multiple times (uses INSERT IGNORE)
-- Generated: 2026-03-26
-- ============================================================

SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. Address Book - 10 business contacts
-- ============================================================
INSERT IGNORE INTO address_book (id, name, name_kana, company, department, position_title, email, phone, mobile, fax, postal_code, address, url, category, memo, created_by, created_at, updated_at) VALUES
(1, '高橋 誠', 'タカハシ マコト', 'ABC商事株式会社', '営業本部', '部長', 'takahashi@abc-shoji.co.jp', '03-5555-1001', '090-5555-1001', '03-5555-1099', '100-0001', '東京都千代田区千代田1-1-1 ABCビル5F', 'https://www.abc-shoji.co.jp', '取引先', '主要取引先。月次定例会議あり。', 2, NOW(), NOW()),
(2, '中村 裕子', 'ナカムラ ユウコ', 'DEF工業株式会社', '購買部', '課長', 'nakamura@def-kogyo.co.jp', '06-6666-2001', '090-6666-2001', '06-6666-2099', '530-0001', '大阪府大阪市北区梅田2-2-2', 'https://www.def-kogyo.co.jp', '取引先', '新規取引開始予定。見積書提出済み。', 2, NOW(), NOW()),
(3, '伊藤 健一', 'イトウ ケンイチ', '株式会社GHIシステムズ', '開発部', 'CTO', 'ito@ghi-systems.co.jp', '03-7777-3001', '090-7777-3001', NULL, '150-0002', '東京都渋谷区渋谷3-3-3 GHIタワー10F', 'https://www.ghi-systems.co.jp', '協力会社', 'システム開発パートナー。プロジェクトAlpha連携先。', 3, NOW(), NOW()),
(4, '小林 美樹', 'コバヤシ ミキ', 'JKL法律事務所', NULL, '弁護士', 'kobayashi@jkl-law.jp', '03-8888-4001', '090-8888-4001', '03-8888-4099', '100-0005', '東京都千代田区丸の内1-4-4', 'https://www.jkl-law.jp', '顧問', '顧問弁護士。契約書レビュー依頼先。', 4, NOW(), NOW()),
(5, '渡辺 大輔', 'ワタナベ ダイスケ', 'MNO印刷株式会社', '営業部', '主任', 'watanabe@mno-print.co.jp', '03-9999-5001', '090-9999-5001', '03-9999-5099', '101-0001', '東京都千代田区神田1-5-5', NULL, '仕入先', '名刺・印刷物の発注先。', 4, NOW(), NOW()),
(6, '加藤 翔太', 'カトウ ショウタ', '株式会社PQRコンサルティング', '戦略部門', 'シニアコンサルタント', 'kato@pqr-consul.co.jp', '03-1111-6001', '090-1111-6001', NULL, '107-0052', '東京都港区赤坂5-6-6 PQRビル3F', 'https://www.pqr-consul.co.jp', '取引先', '業務改善コンサルティング契約中。', 1, NOW(), NOW()),
(7, '松本 さくら', 'マツモト サクラ', 'STU銀行', '法人営業部', '担当者', 'matsumoto@stu-bank.co.jp', '03-2222-7001', '090-2222-7001', NULL, '103-0027', '東京都中央区日本橋2-7-7', 'https://www.stu-bank.co.jp', '金融機関', 'メインバンク担当者。', 5, NOW(), NOW()),
(8, '井上 拓也', 'イノウエ タクヤ', 'VWXクラウド株式会社', '技術サポート', 'エンジニア', 'inoue@vwx-cloud.co.jp', '03-3333-8001', '090-3333-8001', NULL, '141-0032', '東京都品川区大崎3-8-8', 'https://www.vwx-cloud.co.jp', '協力会社', 'クラウドインフラ運用委託先。障害時の連絡先。', 3, NOW(), NOW()),
(9, '木村 恵', 'キムラ メグミ', '株式会社YZリクルート', '人材紹介部', 'マネージャー', 'kimura@yz-recruit.co.jp', '03-4444-9001', '090-4444-9001', NULL, '160-0023', '東京都新宿区西新宿1-9-9 YZビル7F', 'https://www.yz-recruit.co.jp', '人材', '中途採用エージェント。', 4, NOW(), NOW()),
(10, '斎藤 龍一', 'サイトウ リュウイチ', '国際物流サービス株式会社', '国際部', '係長', 'saito@kokusai-logistics.co.jp', '045-5555-0001', '090-5555-0010', '045-5555-0099', '220-0012', '神奈川県横浜市西区みなとみらい4-10-10', 'https://www.kokusai-logistics.co.jp', '仕入先', '海外発送の手配先。', 2, NOW(), NOW());

-- ============================================================
-- 2. File Management - Folders and Files
-- ============================================================
INSERT IGNORE INTO file_folders (id, name, parent_id, description, created_by, created_at, updated_at) VALUES
(1, '社内規定', NULL, '就業規則・社内規定類を管理', 4, NOW(), NOW()),
(2, 'プロジェクト資料', NULL, 'プロジェクト関連の資料を保管', 3, NOW(), NOW()),
(3, 'テンプレート', NULL, '各種書式テンプレート', 4, NOW(), NOW()),
(4, '共有フォルダ', NULL, '全社員共有のフォルダ', 1, NOW(), NOW()),
(5, 'プロジェクトAlpha', 2, 'プロジェクトAlpha関連資料', 3, NOW(), NOW()),
(6, '議事録', 4, '会議議事録', 4, NOW(), NOW());

INSERT IGNORE INTO file_entries (id, folder_id, title, description, filename, original_name, file_size, mime_type, version, approval_status, uploaded_by, download_count, created_at, updated_at) VALUES
(1, 1, '就業規則', '最新の就業規則（2026年4月改定版）', 'file_rules_001.pdf', '就業規則_2026年版.pdf', 2048000, 'application/pdf', 1, 'approved', 4, 15, NOW(), NOW()),
(2, 1, '出張旅費規程', '出張時の旅費精算に関する規程', 'file_travel_002.pdf', '出張旅費規程.pdf', 1024000, 'application/pdf', 1, 'approved', 4, 8, NOW(), NOW()),
(3, 1, '情報セキュリティポリシー', '情報セキュリティに関する基本方針', 'file_security_003.pdf', '情報セキュリティポリシー.pdf', 1536000, 'application/pdf', 1, 'approved', 1, 22, NOW(), NOW()),
(4, 3, '議事録テンプレート', '会議議事録の標準テンプレート', 'file_template_004.docx', '議事録テンプレート.docx', 51200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 'none', 4, 30, NOW(), NOW()),
(5, 3, '経費精算書テンプレート', '経費精算申請用のExcelテンプレート', 'file_template_005.xlsx', '経費精算書テンプレート.xlsx', 40960, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1, 'none', 5, 18, NOW(), NOW()),
(6, 5, 'プロジェクトAlpha 要件定義書', 'プロジェクトAlphaの要件定義書（第2版）', 'file_alpha_006.pdf', 'Alpha_要件定義書_v2.pdf', 3072000, 'application/pdf', 2, 'approved', 3, 12, NOW(), NOW()),
(7, 5, 'プロジェクトAlpha 設計書', 'システム設計書（基本設計）', 'file_alpha_007.pdf', 'Alpha_基本設計書.pdf', 5120000, 'application/pdf', 1, 'approved', 3, 7, NOW(), NOW()),
(8, 5, 'API仕様書', 'プロジェクトAlpha API仕様書', 'file_alpha_008.pdf', 'Alpha_API仕様書.pdf', 2560000, 'application/pdf', 1, 'none', 3, 5, NOW(), NOW()),
(9, 6, '2026/03/23 週次営業ミーティング 議事録', '週次営業ミーティングの議事録', 'file_minutes_009.docx', '議事録_20260323_営業MTG.docx', 81920, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 'none', 2, 4, NOW(), NOW()),
(10, 6, '2026/03/24 プロジェクトAlpha定例会 議事録', 'プロジェクトAlpha定例会の議事録', 'file_minutes_010.docx', '議事録_20260324_Alpha定例.docx', 92160, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 'none', 3, 3, NOW(), NOW()),
(11, 4, 'ABC商事様 提案書', 'ABC商事様向け提案資料', 'file_proposal_011.pptx', 'ABC商事様_提案書_20260326.pptx', 8192000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1, 'none', 2, 6, NOW(), NOW()),
(12, 1, '個人情報取扱規程', '個人情報の取扱いに関する社内規程', 'file_privacy_012.pdf', '個人情報取扱規程.pdf', 1280000, 'application/pdf', 1, 'approved', 4, 10, NOW(), NOW());

INSERT IGNORE INTO file_versions (id, file_id, version_number, filename, original_name, file_size, mime_type, uploaded_by, comment, created_at) VALUES
(1, 6, 1, 'file_alpha_006_v1.pdf', 'Alpha_要件定義書_v1.pdf', 2800000, 'application/pdf', 3, '初版作成', '2026-03-10 10:00:00'),
(2, 6, 2, 'file_alpha_006.pdf', 'Alpha_要件定義書_v2.pdf', 3072000, 'application/pdf', 3, '顧客フィードバック反映、非機能要件追加', '2026-03-20 15:00:00');

-- ============================================================
-- 3. Web Databases
-- ============================================================
-- Database 1: 顧客管理
INSERT IGNORE INTO web_databases (id, name, description, icon, color, is_public, creator_id, created_at, updated_at) VALUES
(1, '顧客管理', '取引先・見込み客の管理データベース', 'users', '#3498db', 1, 2, NOW(), NOW()),
(2, '商品マスタ', '自社商品・サービスの管理', 'package', '#27ae60', 1, 1, NOW(), NOW()),
(3, 'プロジェクト管理', 'プロジェクトの進捗管理', 'briefcase', '#e67e22', 1, 3, NOW(), NOW());

-- Fields for 顧客管理
INSERT IGNORE INTO web_database_fields (id, database_id, name, description, type, options, required, sort_order, is_title_field, is_filterable, is_sortable) VALUES
(1,  1, '会社名', NULL, 'text', NULL, 1, 1, 1, 1, 1),
(2,  1, '担当者名', NULL, 'text', NULL, 1, 2, 0, 1, 1),
(3,  1, '電話番号', NULL, 'phone', NULL, 0, 3, 0, 0, 0),
(4,  1, 'メールアドレス', NULL, 'email', NULL, 0, 4, 0, 0, 0),
(5,  1, 'ステータス', NULL, 'select', '["見込み","提案中","契約済","休眠"]', 1, 5, 0, 1, 1),
(6,  1, '備考', NULL, 'textarea', NULL, 0, 6, 0, 0, 0);

-- Fields for 商品マスタ
INSERT IGNORE INTO web_database_fields (id, database_id, name, description, type, options, required, sort_order, is_title_field, is_filterable, is_sortable) VALUES
(7,  2, '商品名', NULL, 'text', NULL, 1, 1, 1, 1, 1),
(8,  2, '商品コード', NULL, 'text', NULL, 1, 2, 0, 1, 1),
(9,  2, 'カテゴリ', NULL, 'select', '["ソフトウェア","ハードウェア","サービス","サポート"]', 1, 3, 0, 1, 1),
(10, 2, '単価', NULL, 'currency', NULL, 1, 4, 0, 0, 1),
(11, 2, 'ステータス', NULL, 'select', '["販売中","販売終了","準備中"]', 1, 5, 0, 1, 1),
(12, 2, '説明', NULL, 'textarea', NULL, 0, 6, 0, 0, 0);

-- Fields for プロジェクト管理
INSERT IGNORE INTO web_database_fields (id, database_id, name, description, type, options, required, sort_order, is_title_field, is_filterable, is_sortable) VALUES
(13, 3, 'プロジェクト名', NULL, 'text', NULL, 1, 1, 1, 1, 1),
(14, 3, 'PM', '担当プロジェクトマネージャー', 'text', NULL, 1, 2, 0, 1, 1),
(15, 3, '開始日', NULL, 'date', NULL, 1, 3, 0, 0, 1),
(16, 3, '終了予定日', NULL, 'date', NULL, 0, 4, 0, 0, 1),
(17, 3, 'ステータス', NULL, 'select', '["企画","進行中","テスト","完了","保留"]', 1, 5, 0, 1, 1),
(18, 3, '予算（万円）', NULL, 'number', NULL, 0, 6, 0, 0, 1);

-- Records for 顧客管理
INSERT IGNORE INTO web_database_records (id, database_id, creator_id, created_at, updated_at) VALUES
(1, 1, 2, NOW(), NOW()),
(2, 1, 2, NOW(), NOW()),
(3, 1, 2, NOW(), NOW()),
(4, 1, 2, NOW(), NOW()),
(5, 1, 2, NOW(), NOW());

INSERT IGNORE INTO web_database_record_data (id, record_id, field_id, value) VALUES
-- ABC商事
(1,  1, 1, 'ABC商事株式会社'),
(2,  1, 2, '高橋 誠'),
(3,  1, 3, '03-5555-1001'),
(4,  1, 4, 'takahashi@abc-shoji.co.jp'),
(5,  1, 5, '契約済'),
(6,  1, 6, '主要取引先。年間契約更新済み。'),
-- DEF工業
(7,  2, 1, 'DEF工業株式会社'),
(8,  2, 2, '中村 裕子'),
(9,  2, 3, '06-6666-2001'),
(10, 2, 4, 'nakamura@def-kogyo.co.jp'),
(11, 2, 5, '提案中'),
(12, 2, 6, '新規提案中。見積書提出済み、4月回答予定。'),
-- GHIシステムズ
(13, 3, 1, '株式会社GHIシステムズ'),
(14, 3, 2, '伊藤 健一'),
(15, 3, 3, '03-7777-3001'),
(16, 3, 4, 'ito@ghi-systems.co.jp'),
(17, 3, 5, '契約済'),
(18, 3, 6, '開発パートナー。プロジェクトAlpha協業中。'),
-- PQRコンサルティング
(19, 4, 1, '株式会社PQRコンサルティング'),
(20, 4, 2, '加藤 翔太'),
(21, 4, 3, '03-1111-6001'),
(22, 4, 4, 'kato@pqr-consul.co.jp'),
(23, 4, 5, '契約済'),
(24, 4, 6, 'コンサルティング契約中。業務改善プロジェクト。'),
-- 新興テクノロジー
(25, 5, 1, '新興テクノロジー株式会社'),
(26, 5, 2, '森田 隆'),
(27, 5, 3, '03-2222-0001'),
(28, 5, 4, 'morita@shinko-tech.co.jp'),
(29, 5, 5, '見込み'),
(30, 5, 6, '展示会で名刺交換。IoT分野で協業の可能性あり。');

-- Records for 商品マスタ
INSERT IGNORE INTO web_database_records (id, database_id, creator_id, created_at, updated_at) VALUES
(6,  2, 1, NOW(), NOW()),
(7,  2, 1, NOW(), NOW()),
(8,  2, 1, NOW(), NOW()),
(9,  2, 1, NOW(), NOW());

INSERT IGNORE INTO web_database_record_data (id, record_id, field_id, value) VALUES
(31, 6,  7, 'TeamSpace グループウェア'),
(32, 6,  8, 'SW-001'),
(33, 6,  9, 'ソフトウェア'),
(34, 6, 10, '500000'),
(35, 6, 11, '販売中'),
(36, 6, 12, '中小企業向け統合グループウェア。スケジュール・ワークフロー・ファイル管理等を統合。'),
(37, 7,  7, 'TeamSpace クラウドホスティング'),
(38, 7,  8, 'SV-001'),
(39, 7,  9, 'サービス'),
(40, 7, 10, '30000'),
(41, 7, 11, '販売中'),
(42, 7, 12, 'TeamSpace専用クラウドホスティングサービス。月額制。'),
(43, 8,  7, 'TeamSpace 導入支援パック'),
(44, 8,  8, 'SP-001'),
(45, 8,  9, 'サポート'),
(46, 8, 10, '200000'),
(47, 8, 11, '販売中'),
(48, 8, 12, '導入コンサルティング・初期設定・社員研修をパッケージ化。'),
(49, 9,  7, 'TeamSpace モバイルアプリ'),
(50, 9,  8, 'SW-002'),
(51, 9,  9, 'ソフトウェア'),
(52, 9, 10, '100000'),
(53, 9, 11, '準備中'),
(54, 9, 12, 'iOS/Android対応モバイルアプリ（2026年夏リリース予定）。');

-- Records for プロジェクト管理
INSERT IGNORE INTO web_database_records (id, database_id, creator_id, created_at, updated_at) VALUES
(10, 3, 3, NOW(), NOW()),
(11, 3, 3, NOW(), NOW()),
(12, 3, 3, NOW(), NOW());

INSERT IGNORE INTO web_database_record_data (id, record_id, field_id, value) VALUES
(55, 10, 13, 'プロジェクトAlpha'),
(56, 10, 14, '田中花子'),
(57, 10, 15, '2026-02-01'),
(58, 10, 16, '2026-06-30'),
(59, 10, 17, '進行中'),
(60, 10, 18, '1500'),
(61, 11, 13, '社内DX推進'),
(62, 11, 14, '鈴木一郎'),
(63, 11, 15, '2026-01-15'),
(64, 11, 16, '2026-12-31'),
(65, 11, 17, '進行中'),
(66, 11, 18, '800'),
(67, 12, 13, 'モバイルアプリ開発'),
(68, 12, 14, '田中花子'),
(69, 12, 15, '2026-04-01'),
(70, 12, 16, '2026-09-30'),
(71, 12, 17, '企画'),
(72, 12, 18, '2000');

-- ============================================================
-- 4. More Daily Reports
-- ============================================================
INSERT IGNORE INTO daily_reports (id, user_id, report_date, title, content, status, is_template) VALUES
(3, 2, '2026-03-26', '3/26 営業日報',
'【本日の業務】
・ABC商事様訪問（13:00-15:00）→ デモ実施、好感触
・DEF工業様向け見積書作成・送付
・新規リード3件のフォローアップ電話

【商談状況】
・ABC商事様：デモに対する反応は非常に良好。来週中に社内稟議を上げていただける見込み。
・DEF工業様：見積書を本日送付。来週月曜に電話確認予定。

【明日の予定】
・提案資料修正（ABC商事様フィードバック反映）
・新規見込み客リストの電話アプローチ
・月末営業数値まとめ

【所感】
ABC商事様のデモが順調に進み、4月の受注目標達成に向けて大きく前進。', 'published', 0),

(4, 3, '2026-03-26', '3/26 開発日報',
'【本日の業務】
・プロジェクトAlpha：APIエンドポイント実装（5/5件完了！）
・単体テスト作成（カバレッジ85%達成）
・コードレビュー実施（PR #42, #43）

【進捗】
・API実装フェーズ完了。来週からフロントエンド連携テストに入る。
・セキュリティパッチの適用完了、動作確認済み。

【課題・相談事項】
・フロントエンド連携テストの環境構築について、インフラチームとの調整が必要。
・パフォーマンステストのシナリオ作成を来週開始予定。

【明日の予定】
・テスト環境のセットアップ
・結合テスト計画書の作成', 'published', 0),

(5, 5, '2026-03-26', '3/26 経理日報',
'【本日の業務】
・3月度仕訳入力（90%完了）
・売掛金残高確認・消込処理
・月次決算準備（固定資産台帳更新）
・経費精算書の確認・承認処理（5件）

【明日の予定】
・月次決算処理（残業予定あり）
・試算表作成
・銀行残高照合

【連絡事項】
・3月分の経費精算は明日27日が最終締切です。未提出の方は本日中にワークフローから提出をお願いします。', 'published', 0),

(6, 4, '2026-03-26', '3/26 総務日報',
'【本日の業務】
・4月入社式の準備（式次第作成、会場レイアウト確定）
・ABC商事様来社対応（応接室準備、お茶手配）
・備品棚卸し準備（チェックリスト配布）
・社内研修の受講者取りまとめ

【明日の予定】
・入社式リハーサル
・新入社員の備品準備（PC・携帯・名刺発注）
・月末棚卸し実施

【連絡事項】
・4月10日の情報セキュリティ研修は全社員必須です。スケジュール調整をお願いします。', 'published', 0),

(7, 2, '2026-03-24', '3/24 営業日報',
'【本日の業務】
・プロジェクトAlpha定例会参加（14:00-15:30）
・新規営業リスト50件の整理・優先順位付け
・ABC商事様向けデモ環境の最終確認

【明日の予定】
・ABC商事様提案資料の最終調整
・週次営業会議の準備
・DEF工業様フォロー電話

【所感】
Alpha定例会で開発進捗を確認。予定通り進んでおり、デモでの訴求ポイントを再整理できた。', 'published', 0),

(8, 3, '2026-03-24', '3/24 開発日報',
'【本日の業務】
・プロジェクトAlpha定例会（14:00-15:30）
・APIエンドポイント実装（4/5件完了）
・バグ修正（Issue #38: ページネーション不具合）

【課題】
・外部API連携の仕様について、ABC商事様経由で確認中の項目あり。山田さんに依頼済み。

【明日の予定】
・APIエンドポイント実装（残り1件）
・単体テスト作成開始', 'published', 0);

-- ============================================================
-- 5. More Messages
-- ============================================================
INSERT IGNORE INTO messages (id, subject, body, sender_id, parent_id, thread_id, created_at) VALUES
(3, 'プロジェクトAlpha 進捗報告',
'皆さん

お疲れ様です。田中です。
プロジェクトAlphaの進捗をご報告します。

■ 完了項目
・API設計（全エンドポイント確定）
・データベース設計（テーブル定義完了）
・APIエンドポイント実装（5/5件完了）

■ 今後の予定
・3/30週：フロントエンド連携テスト
・4/6週：パフォーマンステスト
・4/13週：ユーザー受入テスト

順調に進んでいます。何かご質問があればお気軽にどうぞ。',
3, NULL, 3, '2026-03-26 17:00:00'),

(4, 'Re: プロジェクトAlpha 進捗報告',
'田中さん

報告ありがとうございます。
順調な進捗で安心しました。

ABC商事様にもデモ時にこの進捗状況をお伝えしました。
先方も非常に期待されています。

外部API連携の仕様確認については、先方の伊藤さん（GHIシステムズ）に
直接確認していただいて問題ありません。
連絡先は共有アドレス帳に登録済みです。

山田',
2, 3, 3, '2026-03-26 17:30:00'),

(5, '4月度 社内研修のご案内',
'社員各位

お疲れ様です。総務部の鈴木です。

4月度の社内研修について以下の通りご案内いたします。

■ ビジネスマナー研修（新入社員向け）
日時：4月2日（木）10:00-17:00
場所：会議室A

■ 情報セキュリティ研修（全社員必須）
日時：4月10日（金）14:00-16:00
場所：会議室A（オンライン同時配信あり）

情報セキュリティ研修は全社員必須受講です。
やむを得ず参加できない方は、4月17日の補講にご参加ください。

出欠の回答は3月31日までにお願いいたします。',
4, NULL, 5, '2026-03-26 10:00:00'),

(6, '月末経費精算のお願い',
'社員各位

お疲れ様です。経理部の佐藤です。

3月度の経費精算について、以下ご確認をお願いいたします。

■ 締切：3月27日（金）17:00
■ 提出方法：ワークフローの「経費精算」テンプレートから申請

領収書の添付をお忘れなく。
不明点がございましたら経理部までお問い合わせください。',
5, NULL, 6, '2026-03-25 09:00:00'),

(7, 'Re: 月末経費精算のお願い',
'佐藤さん

確認しました。
技術書籍購入の経費精算を先日申請済みですが、
領収書の画像が不鮮明だったかもしれません。
必要であれば再添付しますのでお知らせください。

田中',
3, 6, 6, '2026-03-25 12:00:00');

-- Thread self-references
UPDATE messages SET thread_id = 3 WHERE id = 3 AND thread_id IS NULL;
UPDATE messages SET thread_id = 5 WHERE id = 5 AND thread_id IS NULL;
UPDATE messages SET thread_id = 6 WHERE id = 6 AND thread_id IS NULL;

INSERT IGNORE INTO message_recipients (message_id, user_id, is_read, is_starred) VALUES
(3, 1, 1, 0),
(3, 2, 1, 0),
(3, 4, 0, 0),
(4, 3, 1, 0),
(5, 1, 1, 0),
(5, 2, 0, 0),
(5, 3, 0, 0),
(5, 5, 1, 0),
(6, 1, 1, 0),
(6, 2, 1, 0),
(6, 3, 0, 0),
(6, 4, 1, 0),
(7, 5, 1, 0);

-- ============================================================
-- 6. Task Boards
-- ============================================================
INSERT IGNORE INTO task_boards (id, name, description, owner_type, owner_id, is_public, background_color, created_by, created_at, updated_at) VALUES
(1, 'プロジェクトAlpha', 'プロジェクトAlphaのタスク管理ボード', 'organization', 3, 1, '#e8f5e9', 3, NOW(), NOW()),
(2, '総務部タスク', '総務部の業務タスク管理', 'organization', 4, 0, '#e3f2fd', 4, NOW(), NOW());

INSERT IGNORE INTO task_board_members (board_id, user_id, role) VALUES
(1, 3, 'admin'),
(1, 2, 'editor'),
(1, 1, 'viewer'),
(2, 4, 'admin'),
(2, 1, 'editor');

INSERT IGNORE INTO task_lists (id, board_id, name, description, color, sort_order) VALUES
(1, 1, '未着手', 'まだ開始していないタスク', '#f5f5f5', 1),
(2, 1, '進行中', '現在作業中のタスク', '#fff3e0', 2),
(3, 1, 'レビュー', 'レビュー待ちのタスク', '#e8eaf6', 3),
(4, 1, '完了', '完了したタスク', '#e8f5e9', 4),
(5, 2, '未対応', '未対応タスク', '#f5f5f5', 1),
(6, 2, '対応中', '対応中タスク', '#fff3e0', 2),
(7, 2, '完了', '完了タスク', '#e8f5e9', 3);

-- Task cards for プロジェクトAlpha board
INSERT IGNORE INTO task_cards (id, list_id, title, description, due_date, priority, status, progress, sort_order, created_by) VALUES
-- 未着手
(1, 1, 'パフォーマンステスト計画策定', 'パフォーマンステストのシナリオ・環境・ツール選定', '2026-04-10', 'high', 'not_started', 0, 1, 3),
(2, 1, 'ユーザーマニュアル作成', 'エンドユーザー向けの操作マニュアルを作成', '2026-04-20', 'normal', 'not_started', 0, 2, 3),
-- 進行中
(3, 2, 'フロントエンド連携テスト環境構築', 'テスト環境のセットアップとCI/CD設定', '2026-03-31', 'highest', 'in_progress', 40, 1, 3),
(4, 2, '結合テスト計画書作成', 'API結合テストの計画書を作成', '2026-04-03', 'high', 'in_progress', 60, 2, 3),
-- レビュー
(5, 3, 'API仕様書（最終版）', 'API仕様書の最終版をレビュー依頼中', '2026-03-28', 'high', 'in_progress', 90, 1, 3),
(6, 3, 'データベース設計書', 'DB設計書の最終レビュー', '2026-03-27', 'normal', 'in_progress', 95, 2, 3),
-- 完了
(7, 4, 'APIエンドポイント実装（全5件）', 'REST APIの全エンドポイント実装完了', '2026-03-26', 'highest', 'completed', 100, 1, 3),
(8, 4, '要件定義書v2作成', '顧客フィードバックを反映した要件定義書第2版', '2026-03-20', 'high', 'completed', 100, 2, 3);

-- Task cards for 総務部タスク board
INSERT IGNORE INTO task_cards (id, list_id, title, description, due_date, priority, status, progress, sort_order, created_by) VALUES
(9,  5, '新入社員PC発注', '4月入社3名分のPC・周辺機器の発注', '2026-03-28', 'high', 'not_started', 0, 1, 4),
(10, 5, '名刺デザイン確認', '新入社員用名刺のデザイン確認・発注', '2026-03-30', 'normal', 'not_started', 0, 2, 4),
(11, 6, '入社式準備', '4/1入社式の会場設営・式次第準備', '2026-03-31', 'highest', 'in_progress', 70, 1, 4),
(12, 7, '3月度備品棚卸し準備', 'チェックリスト作成・各部署への配布完了', '2026-03-25', 'normal', 'completed', 100, 1, 4);

INSERT IGNORE INTO task_assignees (id, card_id, user_id) VALUES
(1, 1, 3),
(2, 2, 3),
(3, 3, 3),
(4, 4, 3),
(5, 5, 3),
(6, 5, 2),
(7, 6, 3),
(8, 7, 3),
(9, 8, 3),
(10, 9, 4),
(11, 10, 4),
(12, 11, 4),
(13, 11, 1),
(14, 12, 4);

-- ============================================================
-- 7. More Schedules (current week + next 2 weeks)
-- ============================================================
INSERT IGNORE INTO schedules (id, title, description, start_time, end_time, all_day, location, creator_id, visibility, priority, status) VALUES
-- 今週残り
(7, '月次営業レポート作成',
   '3月度の営業実績レポートを作成',
   '2026-03-27 10:00:00', '2026-03-27 12:00:00',
   0, NULL, 2, 'private', 'normal', 'scheduled'),
(8, '月次決算処理',
   '3月度の月次決算処理',
   '2026-03-27 09:00:00', '2026-03-27 21:00:00',
   0, NULL, 5, 'private', 'high', 'scheduled'),

-- 来週 (3/30 - 4/3)
(9, '週次営業ミーティング',
   '来週の営業戦略共有と進捗確認',
   '2026-03-30 10:00:00', '2026-03-30 11:00:00',
   0, '会議室A', 2, 'public', 'normal', 'scheduled'),
(10, 'プロジェクトAlpha 定例会',
    'フロントエンド連携テスト進捗確認',
    '2026-03-31 14:00:00', '2026-03-31 15:30:00',
    0, '会議室B', 3, 'public', 'high', 'scheduled'),
(11, '棚卸し作業',
    '3月度の備品棚卸し実施',
    '2026-03-31 13:00:00', '2026-03-31 17:00:00',
    0, '各部署', 4, 'public', 'normal', 'scheduled'),
(12, '入社式',
    '2026年度新入社員の入社式',
    '2026-04-01 09:30:00', '2026-04-01 11:00:00',
    0, '会議室A', 1, 'public', 'high', 'scheduled'),
(13, '新入社員歓迎ランチ',
    '新入社員との懇親ランチ会',
    '2026-04-01 12:00:00', '2026-04-01 13:30:00',
    0, '社外レストラン', 4, 'public', 'normal', 'scheduled'),
(14, 'ビジネスマナー研修',
    '新入社員向けビジネスマナー研修',
    '2026-04-02 10:00:00', '2026-04-02 17:00:00',
    0, '会議室A', 4, 'public', 'normal', 'scheduled'),
(15, 'DEF工業 フォロー電話',
    '見積書に対する回答確認の電話',
    '2026-03-30 14:00:00', '2026-03-30 14:30:00',
    0, NULL, 2, 'private', 'normal', 'scheduled'),

-- 再来週 (4/6 - 4/10)
(16, '週次営業ミーティング',
    '4月第2週の営業戦略共有',
    '2026-04-06 10:00:00', '2026-04-06 11:00:00',
    0, '会議室A', 2, 'public', 'normal', 'scheduled'),
(17, 'プロジェクトAlpha 定例会',
    'パフォーマンステスト計画レビュー',
    '2026-04-07 14:00:00', '2026-04-07 15:30:00',
    0, '会議室B', 3, 'public', 'high', 'scheduled'),
(18, 'ABC商事 契約交渉',
    '契約条件の詰めの打合せ',
    '2026-04-08 10:00:00', '2026-04-08 12:00:00',
    0, 'ABC商事 本社', 2, 'public', 'high', 'scheduled'),
(19, '情報セキュリティ研修',
    '全社員必須の情報セキュリティ研修',
    '2026-04-10 14:00:00', '2026-04-10 16:00:00',
    0, '会議室A', 1, 'public', 'high', 'scheduled');

-- Schedule participants for new schedules
INSERT IGNORE INTO schedule_participants (schedule_id, user_id, status) VALUES
(7, 2, 'accepted'),
(8, 5, 'accepted'),
(9, 2, 'accepted'), (9, 4, 'accepted'),
(10, 3, 'accepted'), (10, 2, 'accepted'), (10, 1, 'tentative'),
(11, 4, 'accepted'), (11, 1, 'accepted'), (11, 2, 'accepted'), (11, 3, 'accepted'), (11, 5, 'accepted'),
(12, 1, 'accepted'), (12, 2, 'accepted'), (12, 3, 'accepted'), (12, 4, 'accepted'), (12, 5, 'accepted'),
(13, 1, 'accepted'), (13, 2, 'accepted'), (13, 3, 'accepted'), (13, 4, 'accepted'), (13, 5, 'accepted'),
(14, 4, 'accepted'), (14, 1, 'accepted'),
(15, 2, 'accepted'),
(16, 2, 'accepted'), (16, 4, 'accepted'),
(17, 3, 'accepted'), (17, 2, 'accepted'), (17, 1, 'tentative'),
(18, 2, 'accepted'), (18, 4, 'accepted'),
(19, 1, 'accepted'), (19, 2, 'accepted'), (19, 3, 'accepted'), (19, 4, 'accepted'), (19, 5, 'accepted');

-- ============================================================
-- 8. More Facility Reservations
-- ============================================================
INSERT IGNORE INTO facility_reservations (id, facility_id, user_id, title, start_time, end_time, memo, created_at, updated_at) VALUES
(6, 1, 2, '週次営業ミーティング',
   '2026-03-30 10:00:00', '2026-03-30 11:00:00',
   NULL, NOW(), NOW()),
(7, 2, 3, 'プロジェクトAlpha 定例会',
   '2026-03-31 14:00:00', '2026-03-31 15:30:00',
   NULL, NOW(), NOW()),
(8, 1, 1, '入社式',
   '2026-04-01 09:30:00', '2026-04-01 11:00:00',
   '机をスクール形式にレイアウト変更', NOW(), NOW()),
(9, 1, 4, 'ビジネスマナー研修',
   '2026-04-02 10:00:00', '2026-04-02 17:00:00',
   'プロジェクター・マイク使用', NOW(), NOW()),
(10, 1, 2, '週次営業ミーティング',
    '2026-04-06 10:00:00', '2026-04-06 11:00:00',
    NULL, NOW(), NOW()),
(11, 2, 3, 'プロジェクトAlpha 定例会',
    '2026-04-07 14:00:00', '2026-04-07 15:30:00',
    NULL, NOW(), NOW()),
(12, 1, 1, '情報セキュリティ研修',
    '2026-04-10 14:00:00', '2026-04-10 16:00:00',
    'オンライン同時配信のためカメラ・マイク準備', NOW(), NOW());

-- ============================================================
-- Restore foreign key checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- ============================================================
-- Verification
-- ============================================================
SELECT 'address_book' AS `table`, COUNT(*) AS `rows` FROM address_book
UNION ALL SELECT 'file_folders',            COUNT(*) FROM file_folders
UNION ALL SELECT 'file_entries',            COUNT(*) FROM file_entries
UNION ALL SELECT 'file_versions',           COUNT(*) FROM file_versions
UNION ALL SELECT 'web_databases',           COUNT(*) FROM web_databases
UNION ALL SELECT 'web_database_fields',     COUNT(*) FROM web_database_fields
UNION ALL SELECT 'web_database_records',    COUNT(*) FROM web_database_records
UNION ALL SELECT 'web_database_record_data',COUNT(*) FROM web_database_record_data
UNION ALL SELECT 'daily_reports',           COUNT(*) FROM daily_reports
UNION ALL SELECT 'messages',                COUNT(*) FROM messages
UNION ALL SELECT 'message_recipients',      COUNT(*) FROM message_recipients
UNION ALL SELECT 'task_boards',             COUNT(*) FROM task_boards
UNION ALL SELECT 'task_board_members',      COUNT(*) FROM task_board_members
UNION ALL SELECT 'task_lists',              COUNT(*) FROM task_lists
UNION ALL SELECT 'task_cards',              COUNT(*) FROM task_cards
UNION ALL SELECT 'task_assignees',          COUNT(*) FROM task_assignees
UNION ALL SELECT 'schedules',              COUNT(*) FROM schedules
UNION ALL SELECT 'schedule_participants',   COUNT(*) FROM schedule_participants
UNION ALL SELECT 'facility_reservations',  COUNT(*) FROM facility_reservations;
