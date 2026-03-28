-- team_members -> teams の外部キー不整合を解消するための再構築
-- 実行対象DB: g_session / xs857756_groupware など

SET @schema_name := DATABASE();

-- 既存FK(team_members_ibfk_1)があれば削除
SET @drop_fk_sql := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `team_members` DROP FOREIGN KEY `team_members_ibfk_1`',
        'SELECT 1')
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = @schema_name
      AND TABLE_NAME = 'team_members'
      AND CONSTRAINT_NAME = 'team_members_ibfk_1'
);
PREPARE drop_fk_stmt FROM @drop_fk_sql;
EXECUTE drop_fk_stmt;
DEALLOCATE PREPARE drop_fk_stmt;

-- FKを再作成（未存在時のみ）
SET @add_fk_sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `team_members` ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE',
        'SELECT 1')
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = @schema_name
      AND TABLE_NAME = 'team_members'
      AND CONSTRAINT_NAME = 'team_members_ibfk_1'
);
PREPARE add_fk_stmt FROM @add_fk_sql;
EXECUTE add_fk_stmt;
DEALLOCATE PREPARE add_fk_stmt;
