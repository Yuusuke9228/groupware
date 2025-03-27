-- データベース作成
CREATE DATABASE IF NOT EXISTS g_session;
USE g_session;

-- 組織テーブル
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    parent_id INT NULL,
    level INT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    organization_id INT,
    position VARCHAR(100),
    phone VARCHAR(20),
    mobile_phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ユーザー組織関連テーブル（一人のユーザーが複数の組織に所属可能）
CREATE TABLE IF NOT EXISTS user_organizations (
    user_id INT NOT NULL,
    organization_id INT NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, organization_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_tokens テーブルを作成
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- スケジュールテーブル
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    all_day BOOLEAN NOT NULL DEFAULT 0,
    location VARCHAR(255),
    creator_id INT NOT NULL,
    visibility ENUM('public', 'private', 'specific') NOT NULL DEFAULT 'public',
    priority ENUM('high', 'normal', 'low') NOT NULL DEFAULT 'normal',
    status ENUM('scheduled', 'tentative', 'cancelled') NOT NULL DEFAULT 'scheduled',
    repeat_type ENUM('none', 'daily', 'weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'none',
    repeat_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- スケジュール参加者テーブル
CREATE TABLE IF NOT EXISTS schedule_participants (
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'tentative') NOT NULL DEFAULT 'pending',
    notification_sent BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (schedule_id, user_id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- スケジュール組織共有テーブル
CREATE TABLE IF NOT EXISTS schedule_organizations (
    schedule_id INT NOT NULL,
    organization_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (schedule_id, organization_id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- システム設定テーブル
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ワークフローテンプレートテーブル
CREATE TABLE IF NOT EXISTS workflow_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'テンプレート名',
    description TEXT COMMENT '説明',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'ステータス',
    creator_id INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフローテンプレート';

-- フォーム定義テーブル
CREATE TABLE IF NOT EXISTS workflow_form_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL COMMENT 'テンプレートID',
    field_id VARCHAR(50) NOT NULL COMMENT 'フィールドID',
    field_type ENUM('text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'file', 'heading', 'hidden') NOT NULL COMMENT 'フィールドタイプ',
    label VARCHAR(100) NOT NULL COMMENT 'ラベル',
    placeholder VARCHAR(100) COMMENT 'プレースホルダー',
    help_text TEXT COMMENT 'ヘルプテキスト',
    options TEXT COMMENT '選択肢（JSON形式）',
    validation TEXT COMMENT 'バリデーションルール（JSON形式）',
    is_required BOOLEAN NOT NULL DEFAULT FALSE COMMENT '必須項目か',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE,
    UNIQUE KEY (template_id, field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフローフォーム定義';

-- 承認経路定義テーブル
CREATE TABLE IF NOT EXISTS workflow_route_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL COMMENT 'テンプレートID',
    step_number INT NOT NULL COMMENT 'ステップ番号',
    step_type ENUM('approval', 'notification') NOT NULL DEFAULT 'approval' COMMENT 'ステップタイプ（承認/通知）',
    step_name VARCHAR(100) NOT NULL COMMENT 'ステップ名',
    approver_type ENUM('user', 'role', 'organization', 'dynamic') NOT NULL COMMENT '承認者タイプ',
    approver_id INT COMMENT '承認者ID（user, role, organizationの場合）',
    dynamic_approver_field_id VARCHAR(50) COMMENT '動的承認者フィールドID（dynamicの場合）',
    allow_delegation BOOLEAN NOT NULL DEFAULT FALSE COMMENT '代理承認を許可するか',
    allow_self_approval BOOLEAN NOT NULL DEFAULT FALSE COMMENT '自己承認を許可するか',
    parallel_approval BOOLEAN NOT NULL DEFAULT FALSE COMMENT '平行承認か',
    approval_condition TEXT COMMENT '承認条件（JSON形式）',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー承認経路定義';

-- 申請テーブル
CREATE TABLE IF NOT EXISTS workflow_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL COMMENT '申請番号',
    template_id INT NOT NULL COMMENT 'テンプレートID',
    title VARCHAR(255) NOT NULL COMMENT '申請タイトル',
    status ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft' COMMENT 'ステータス',
    current_step INT DEFAULT NULL COMMENT '現在のステップ',
    requester_id INT NOT NULL COMMENT '申請者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (template_id) REFERENCES workflow_templates(id) ON DELETE RESTRICT,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY (request_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー申請';

-- 申請データテーブル
CREATE TABLE IF NOT EXISTS workflow_request_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL COMMENT '申請ID',
    field_id VARCHAR(50) NOT NULL COMMENT 'フィールドID',
    value TEXT COMMENT '値',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (request_id) REFERENCES workflow_requests(id) ON DELETE CASCADE,
    UNIQUE KEY (request_id, field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー申請データ';

-- 添付ファイルテーブル
CREATE TABLE IF NOT EXISTS workflow_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL COMMENT '申請ID',
    field_id VARCHAR(50) NOT NULL COMMENT 'フィールドID',
    file_name VARCHAR(255) NOT NULL COMMENT 'ファイル名',
    file_path VARCHAR(255) NOT NULL COMMENT 'ファイルパス',
    file_size INT NOT NULL COMMENT 'ファイルサイズ',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (request_id) REFERENCES workflow_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー添付ファイル';

-- 承認履歴テーブル
CREATE TABLE IF NOT EXISTS workflow_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL COMMENT '申請ID',
    step_number INT NOT NULL COMMENT 'ステップ番号',
    approver_id INT NOT NULL COMMENT '承認者ID',
    delegate_id INT COMMENT '代理承認者ID',
    status ENUM('pending', 'approved', 'rejected', 'skipped') NOT NULL DEFAULT 'pending' COMMENT 'ステータス',
    comment TEXT COMMENT 'コメント',
    acted_at TIMESTAMP NULL DEFAULT NULL COMMENT '承認/却下日時',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (request_id) REFERENCES workflow_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (delegate_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー承認履歴';

-- コメントテーブル
CREATE TABLE IF NOT EXISTS workflow_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL COMMENT '申請ID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    comment TEXT NOT NULL COMMENT 'コメント',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (request_id) REFERENCES workflow_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフローコメント';

-- 代理承認設定テーブル
CREATE TABLE IF NOT EXISTS workflow_delegates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ユーザーID',
    delegate_id INT NOT NULL COMMENT '代理人ID',
    template_id INT COMMENT 'テンプレートID（NULLの場合はすべてのテンプレート）',
    start_date DATE NOT NULL COMMENT '開始日',
    end_date DATE NOT NULL COMMENT '終了日',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'ステータス',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (delegate_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフロー代理承認設定';

-- メッセージテーブル
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL COMMENT 'メッセージの件名',
    body TEXT NOT NULL COMMENT 'メッセージの本文',
    sender_id INT NOT NULL COMMENT '送信者ID',
    parent_id INT NULL COMMENT '親メッセージID（返信の場合）',
    thread_id INT NULL COMMENT 'スレッドID（最初のメッセージのID）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE SET NULL,
    FOREIGN KEY (thread_id) REFERENCES messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='メッセージ';

-- メッセージ受信者テーブル
CREATE TABLE IF NOT EXISTS message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL COMMENT 'メッセージID',
    user_id INT NOT NULL COMMENT '受信者ID',
    is_read BOOLEAN NOT NULL DEFAULT 0 COMMENT '既読フラグ',
    read_at TIMESTAMP NULL COMMENT '既読日時',
    is_starred BOOLEAN NOT NULL DEFAULT 0 COMMENT 'スター付きフラグ',
    is_deleted BOOLEAN NOT NULL DEFAULT 0 COMMENT '削除フラグ（受信者側）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (message_id, user_id) COMMENT 'メッセージと受信者の組み合わせは一意'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='メッセージ受信者';

-- 組織宛メッセージテーブル
CREATE TABLE IF NOT EXISTS message_organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL COMMENT 'メッセージID',
    organization_id INT NOT NULL COMMENT '組織ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY (message_id, organization_id) COMMENT 'メッセージと組織の組み合わせは一意'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='組織宛メッセージ';

-- メッセージ添付ファイルテーブル
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL COMMENT 'メッセージID',
    file_name VARCHAR(255) NOT NULL COMMENT 'ファイル名',
    file_path VARCHAR(255) NOT NULL COMMENT 'ファイルパス',
    file_size INT NOT NULL COMMENT 'ファイルサイズ',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='メッセージ添付ファイル';

-- 通知機能に必要なテーブル追加
-- 通知設定テーブル
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notify_schedule BOOLEAN NOT NULL DEFAULT 1 COMMENT 'スケジュール通知',
    notify_workflow BOOLEAN NOT NULL DEFAULT 1 COMMENT 'ワークフロー通知',
    notify_message BOOLEAN NOT NULL DEFAULT 1 COMMENT 'メッセージ通知',
    email_notify BOOLEAN NOT NULL DEFAULT 1 COMMENT 'メール通知',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'ユーザー毎の通知設定';

-- 通知テーブル
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '通知対象ユーザーID',
    type ENUM('schedule', 'workflow', 'message', 'system') NOT NULL COMMENT '通知タイプ',
    title VARCHAR(255) NOT NULL COMMENT '通知タイトル',
    content TEXT NOT NULL COMMENT '通知内容',
    link VARCHAR(255) COMMENT '関連リンク',
    reference_id INT COMMENT '参照ID',
    reference_type VARCHAR(50) COMMENT '参照タイプ',
    is_read BOOLEAN NOT NULL DEFAULT 0 COMMENT '既読フラグ',
    is_email_sent BOOLEAN NOT NULL DEFAULT 0 COMMENT 'メール送信済みフラグ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '通知';

-- メール送信キューテーブル
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL COMMENT '送信先メールアドレス',
    subject VARCHAR(255) NOT NULL COMMENT '件名',
    body TEXT NOT NULL COMMENT '本文',
    is_html BOOLEAN NOT NULL DEFAULT 1 COMMENT 'HTML形式',
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending' COMMENT '送信ステータス',
    attempts INT NOT NULL DEFAULT 0 COMMENT '送信試行回数',
    notification_id INT COMMENT '関連通知ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL COMMENT '送信日時',
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'メール送信キュー';

-- WEBデータベーステーブル
CREATE TABLE IF NOT EXISTS web_databases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'データベース名',
    description TEXT COMMENT '説明',
    icon VARCHAR(50) DEFAULT 'database' COMMENT 'アイコン',
    color VARCHAR(20) DEFAULT '#3498db' COMMENT 'カラー',
    is_public BOOLEAN NOT NULL DEFAULT 0 COMMENT '公開フラグ',
    creator_id INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベース';

-- WEBデータベースフィールド定義テーブル
CREATE TABLE IF NOT EXISTS web_database_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    database_id INT NOT NULL COMMENT 'データベースID',
    name VARCHAR(100) NOT NULL COMMENT 'フィールド名',
    description TEXT COMMENT '説明',
    type ENUM(
        'text',
        'textarea',
        'number',
        'date',
        'datetime',
        'select',
        'radio',
        'checkbox',
        'file',
        'user',
        'organization'
    ) NOT NULL COMMENT 'フィールドタイプ',
    options TEXT COMMENT 'オプション（JSON形式）',
    required BOOLEAN NOT NULL DEFAULT 0 COMMENT '必須フラグ',
    unique_value BOOLEAN NOT NULL DEFAULT 0 COMMENT 'ユニーク値フラグ',
    default_value TEXT COMMENT 'デフォルト値',
    validation TEXT COMMENT 'バリデーションルール（JSON形式）',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    is_title_field BOOLEAN NOT NULL DEFAULT 0 COMMENT 'タイトルフィールドフラグ',
    is_filterable BOOLEAN NOT NULL DEFAULT 0 COMMENT 'フィルタ可能フラグ',
    is_sortable BOOLEAN NOT NULL DEFAULT 0 COMMENT 'ソート可能フラグ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベースフィールド定義';

-- WEBデータベースレコードテーブル
CREATE TABLE IF NOT EXISTS web_database_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    database_id INT NOT NULL COMMENT 'データベースID',
    creator_id INT NOT NULL COMMENT '作成者ID',
    updater_id INT COMMENT '更新者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updater_id) REFERENCES users(id) ON DELETE
    SET
        NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベースレコード';

-- WEBデータベースレコードデータテーブル
CREATE TABLE IF NOT EXISTS web_database_record_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL COMMENT 'レコードID',
    field_id INT NOT NULL COMMENT 'フィールドID',
    value TEXT COMMENT '値',
    file_info TEXT COMMENT 'ファイル情報（JSON形式）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (record_id) REFERENCES web_database_records(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES web_database_fields(id) ON DELETE CASCADE,
    INDEX (record_id, field_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベースレコードデータ';

-- WEBデータベース権限テーブル
CREATE TABLE IF NOT EXISTS web_database_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    database_id INT NOT NULL COMMENT 'データベースID',
    target_type ENUM('user', 'organization') NOT NULL COMMENT '対象タイプ',
    target_id INT NOT NULL COMMENT '対象ID',
    permission_level ENUM('view', 'edit', 'admin') NOT NULL COMMENT '権限レベル',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
    UNIQUE KEY (database_id, target_type, target_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベース権限';

-- WEBデータベースビューテーブル
CREATE TABLE IF NOT EXISTS web_database_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    database_id INT NOT NULL COMMENT 'データベースID',
    name VARCHAR(100) NOT NULL COMMENT 'ビュー名',
    description TEXT COMMENT '説明',
    type ENUM('list', 'kanban', 'calendar', 'gantt', 'custom') NOT NULL DEFAULT 'list' COMMENT 'ビュータイプ',
    settings TEXT COMMENT 'ビュー設定（JSON形式）',
    is_default BOOLEAN NOT NULL DEFAULT 0 COMMENT 'デフォルトビューフラグ',
    creator_id INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'WEBデータベースビュー';

-- タスクボード（カンバンボード）テーブル
CREATE TABLE IF NOT EXISTS task_boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'ボード名',
    description TEXT COMMENT '説明',
    owner_type ENUM('user', 'team', 'organization') NOT NULL COMMENT '所有者タイプ',
    owner_id INT NOT NULL COMMENT '所有者ID',
    is_public BOOLEAN NOT NULL DEFAULT 0 COMMENT '公開フラグ',
    background_color VARCHAR(20) DEFAULT '#f0f2f5' COMMENT '背景色',
    created_by INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクボード';

-- タスクリスト（カンバンのカラム）テーブル
CREATE TABLE IF NOT EXISTS task_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL COMMENT 'ボードID',
    name VARCHAR(100) NOT NULL COMMENT 'リスト名',
    description TEXT COMMENT '説明',
    color VARCHAR(20) DEFAULT '#ffffff' COMMENT '色',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (board_id) REFERENCES task_boards(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクリスト';

-- タスクカード（カンバンのカード）テーブル
CREATE TABLE IF NOT EXISTS task_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL COMMENT 'リストID',
    title VARCHAR(255) NOT NULL COMMENT 'タスク名',
    description TEXT COMMENT '説明',
    due_date DATE COMMENT '期限日',
    priority ENUM('highest', 'high', 'normal', 'low', 'lowest') NOT NULL DEFAULT 'normal' COMMENT '優先度',
    status ENUM(
        'not_started',
        'in_progress',
        'completed',
        'deferred'
    ) NOT NULL DEFAULT 'not_started' COMMENT 'ステータス',
    progress INT NOT NULL DEFAULT 0 COMMENT '進捗率（0-100）',
    color VARCHAR(20) DEFAULT NULL COMMENT 'カード色',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_by INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (list_id) REFERENCES task_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクカード';

-- タスク担当者テーブル
CREATE TABLE IF NOT EXISTS task_assignees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL COMMENT 'カードID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (card_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスク担当者';

-- タスクラベルテーブル
CREATE TABLE IF NOT EXISTS task_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL COMMENT 'ボードID',
    name VARCHAR(50) NOT NULL COMMENT 'ラベル名',
    color VARCHAR(20) NOT NULL DEFAULT '#cccccc' COMMENT 'ラベル色',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (board_id) REFERENCES task_boards(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクラベル';

-- タスクカードとラベルの関連テーブル
CREATE TABLE IF NOT EXISTS task_card_labels (
    card_id INT NOT NULL COMMENT 'カードID',
    label_id INT NOT NULL COMMENT 'ラベルID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    PRIMARY KEY (card_id, label_id),
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES task_labels(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクカードラベル関連';

-- タスクボードメンバーテーブル
CREATE TABLE IF NOT EXISTS task_board_members (
    board_id INT NOT NULL COMMENT 'ボードID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer' COMMENT '権限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    PRIMARY KEY (board_id, user_id),
    FOREIGN KEY (board_id) REFERENCES task_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクボードメンバー';

-- チームテーブル
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'チーム名',
    description TEXT COMMENT 'チーム説明',
    created_by INT NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'チーム';

-- チームメンバーテーブル
CREATE TABLE IF NOT EXISTS team_members (
    team_id INT NOT NULL COMMENT 'チームID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member' COMMENT '権限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    PRIMARY KEY (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'チームメンバー';

-- タスクコメントテーブル
CREATE TABLE IF NOT EXISTS task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL COMMENT 'カードID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    comment TEXT NOT NULL COMMENT 'コメント',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクコメント';

-- タスク添付ファイルテーブル
CREATE TABLE IF NOT EXISTS task_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL COMMENT 'カードID',
    file_name VARCHAR(255) NOT NULL COMMENT 'ファイル名',
    file_path VARCHAR(255) NOT NULL COMMENT 'ファイルパス',
    file_size INT NOT NULL COMMENT 'ファイルサイズ',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    uploaded_by INT NOT NULL COMMENT 'アップロード者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスク添付ファイル';

-- タスクチェックリストテーブル
CREATE TABLE IF NOT EXISTS task_checklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL COMMENT 'カードID',
    title VARCHAR(100) NOT NULL COMMENT 'チェックリスト名',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクチェックリスト';

-- タスクチェックリスト項目テーブル
CREATE TABLE IF NOT EXISTS task_checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL COMMENT 'チェックリストID',
    content VARCHAR(255) NOT NULL COMMENT '項目内容',
    is_checked BOOLEAN NOT NULL DEFAULT 0 COMMENT 'チェック状態',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (checklist_id) REFERENCES task_checklists(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスクチェックリスト項目';

-- タスク活動履歴テーブル
CREATE TABLE IF NOT EXISTS task_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL COMMENT 'ボードID',
    card_id INT COMMENT 'カードID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    action_type VARCHAR(50) NOT NULL COMMENT 'アクション種類',
    action_data TEXT COMMENT 'アクションデータ（JSON形式）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (board_id) REFERENCES task_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES task_cards(id) ON DELETE
    SET
        NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'タスク活動履歴';

-- 初期データ挿入
INSERT INTO organizations (name, code, level, description)
VALUES ('本社', 'HQ', 1, 'トップレベル組織');

INSERT INTO users (username, password, email, first_name, last_name, display_name, organization_id, role)
VALUES ('admin', '$2y$10$fIfMRDXytV.YStSWln4raOAWV9xEfOUui9JAj0.2z3ejVahDvjpwq', 'admin@example.com', '管理者', 'ユーザー', '管理者', 1, 'admin');

INSERT INTO user_organizations (user_id, organization_id, is_primary)
VALUES (1, 1, 1);

INSERT INTO
    settings (setting_key, setting_value, description)
VALUES
    ('app_name', 'GroupWare', 'アプリケーション名'),
    ('company_name', '株式会社サンプル', '会社名'),
    (
        'notification_email',
        'notification@example.com',
        '通知送信用メールアドレス'
    ),
    ('smtp_host', 'smtp.example.com', 'SMTPサーバーホスト'),
    ('smtp_port', '587', 'SMTPサーバーポート'),
    ('smtp_secure', 'tls', 'SMTPセキュリティ（tls/ssl）'),
    (
        'smtp_username',
        'notification@example.com',
        'SMTPユーザー名'
    ),
    ('smtp_password', 'password', 'SMTPパスワード'),
    ('notification_enabled', '1', '通知機能の有効化') ON DUPLICATE KEY
UPDATE
    setting_value =
VALUES
    (setting_value);