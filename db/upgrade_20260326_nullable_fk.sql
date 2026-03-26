-- ユーザー削除対応: 外部キーカラムをNULLABLEに変更
-- ユーザー削除時にSET NULLで関連データを保持する

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE bulletin_categories MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE bulletin_posts MODIFY author_id INT UNSIGNED NULL;
ALTER TABLE daily_reports MODIFY user_id INT UNSIGNED NULL;
ALTER TABLE daily_report_templates MODIFY user_id INT UNSIGNED NULL;
ALTER TABLE file_approval_steps MODIFY approver_id INT UNSIGNED NULL;
ALTER TABLE file_entries MODIFY uploaded_by INT UNSIGNED NULL;
ALTER TABLE file_folders MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE file_permissions MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE file_versions MODIFY uploaded_by INT UNSIGNED NULL;
ALTER TABLE messages MODIFY sender_id INT UNSIGNED NULL;
ALTER TABLE schedules MODIFY creator_id INT UNSIGNED NULL;
ALTER TABLE task_attachments MODIFY uploaded_by INT UNSIGNED NULL;
ALTER TABLE task_boards MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE task_cards MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE teams MODIFY created_by INT UNSIGNED NULL;
ALTER TABLE web_databases MODIFY creator_id INT UNSIGNED NULL;
ALTER TABLE web_database_records MODIFY creator_id INT UNSIGNED NULL;
ALTER TABLE web_database_views MODIFY creator_id INT UNSIGNED NULL;
ALTER TABLE workflow_approvals MODIFY approver_id INT UNSIGNED NULL;
ALTER TABLE workflow_requests MODIFY requester_id INT UNSIGNED NULL;
ALTER TABLE workflow_templates MODIFY creator_id INT UNSIGNED NULL;
ALTER TABLE file_approval_requests MODIFY requested_by INT UNSIGNED NULL;

SET FOREIGN_KEY_CHECKS = 1;
