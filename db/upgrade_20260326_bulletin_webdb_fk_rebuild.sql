ALTER TABLE bulletin_categories
    MODIFY created_by INT NULL COMMENT '作成者ID';

ALTER TABLE bulletin_posts
    MODIFY author_id INT NULL;

ALTER TABLE web_databases
    MODIFY creator_id INT NULL;

ALTER TABLE web_database_records
    MODIFY creator_id INT NULL,
    MODIFY updater_id INT NULL COMMENT '更新者ID';

ALTER TABLE web_database_views
    MODIFY creator_id INT NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_categories'
      AND CONSTRAINT_NAME = 'fk_bulletin_categories_created_by'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_categories DROP FOREIGN KEY fk_bulletin_categories_created_by', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_categories
    ADD CONSTRAINT fk_bulletin_categories_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_posts'
      AND CONSTRAINT_NAME = 'fk_bulletin_posts_author'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_posts DROP FOREIGN KEY fk_bulletin_posts_author', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_posts
    ADD CONSTRAINT fk_bulletin_posts_author
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_attachments'
      AND CONSTRAINT_NAME = 'bulletin_attachments_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_attachments DROP FOREIGN KEY bulletin_attachments_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_attachments'
      AND CONSTRAINT_NAME = 'fk_bulletin_attachments_post'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_attachments DROP FOREIGN KEY fk_bulletin_attachments_post', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_attachments
    ADD CONSTRAINT fk_bulletin_attachments_post
        FOREIGN KEY (post_id) REFERENCES bulletin_posts(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_comments'
      AND CONSTRAINT_NAME = 'bulletin_comments_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_comments DROP FOREIGN KEY bulletin_comments_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_comments'
      AND CONSTRAINT_NAME = 'fk_bulletin_comments_post'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_comments DROP FOREIGN KEY fk_bulletin_comments_post', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_comments
    ADD CONSTRAINT fk_bulletin_comments_post
        FOREIGN KEY (post_id) REFERENCES bulletin_posts(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_post_reads'
      AND CONSTRAINT_NAME = 'bulletin_post_reads_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_post_reads DROP FOREIGN KEY bulletin_post_reads_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_post_reads'
      AND CONSTRAINT_NAME = 'fk_bulletin_post_reads_post'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_post_reads DROP FOREIGN KEY fk_bulletin_post_reads_post', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_post_reads
    ADD CONSTRAINT fk_bulletin_post_reads_post
        FOREIGN KEY (post_id) REFERENCES bulletin_posts(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_post_targets'
      AND CONSTRAINT_NAME = 'bulletin_post_targets_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_post_targets DROP FOREIGN KEY bulletin_post_targets_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bulletin_post_targets'
      AND CONSTRAINT_NAME = 'fk_bulletin_post_targets_post'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE bulletin_post_targets DROP FOREIGN KEY fk_bulletin_post_targets_post', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE bulletin_post_targets
    ADD CONSTRAINT fk_bulletin_post_targets_post
        FOREIGN KEY (post_id) REFERENCES bulletin_posts(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_databases'
      AND CONSTRAINT_NAME = 'fk_web_databases_creator'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_databases DROP FOREIGN KEY fk_web_databases_creator', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_databases
    ADD CONSTRAINT fk_web_databases_creator
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_records'
      AND CONSTRAINT_NAME = 'fk_web_database_records_creator'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_records DROP FOREIGN KEY fk_web_database_records_creator', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_records
    ADD CONSTRAINT fk_web_database_records_creator
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_records'
      AND CONSTRAINT_NAME = 'fk_web_database_records_updater'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_records DROP FOREIGN KEY fk_web_database_records_updater', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_records
    ADD CONSTRAINT fk_web_database_records_updater
        FOREIGN KEY (updater_id) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND CONSTRAINT_NAME = 'fk_web_database_views_creator'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_views DROP FOREIGN KEY fk_web_database_views_creator', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_views
    ADD CONSTRAINT fk_web_database_views_creator
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_fields'
      AND CONSTRAINT_NAME = 'web_database_fields_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_fields DROP FOREIGN KEY web_database_fields_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_fields'
      AND CONSTRAINT_NAME = 'fk_web_database_fields_database'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_fields DROP FOREIGN KEY fk_web_database_fields_database', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_fields
    ADD CONSTRAINT fk_web_database_fields_database
        FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_permissions'
      AND CONSTRAINT_NAME = 'web_database_permissions_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_permissions DROP FOREIGN KEY web_database_permissions_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_permissions'
      AND CONSTRAINT_NAME = 'fk_web_database_permissions_database'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_permissions DROP FOREIGN KEY fk_web_database_permissions_database', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_permissions
    ADD CONSTRAINT fk_web_database_permissions_database
        FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_records'
      AND CONSTRAINT_NAME = 'web_database_records_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_records DROP FOREIGN KEY web_database_records_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_records'
      AND CONSTRAINT_NAME = 'fk_web_database_records_database'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_records DROP FOREIGN KEY fk_web_database_records_database', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_records
    ADD CONSTRAINT fk_web_database_records_database
        FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND CONSTRAINT_NAME = 'web_database_views_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_views DROP FOREIGN KEY web_database_views_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND CONSTRAINT_NAME = 'fk_web_database_views_database'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_views DROP FOREIGN KEY fk_web_database_views_database', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_views
    ADD CONSTRAINT fk_web_database_views_database
        FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND CONSTRAINT_NAME = 'web_database_record_data_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_record_data DROP FOREIGN KEY web_database_record_data_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND CONSTRAINT_NAME = 'fk_web_database_record_data_record'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_record_data DROP FOREIGN KEY fk_web_database_record_data_record', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_record_data
    ADD CONSTRAINT fk_web_database_record_data_record
        FOREIGN KEY (record_id) REFERENCES web_database_records(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND CONSTRAINT_NAME = 'web_database_record_data_ibfk_2'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_record_data DROP FOREIGN KEY web_database_record_data_ibfk_2', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND CONSTRAINT_NAME = 'fk_web_database_record_data_field'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_record_data DROP FOREIGN KEY fk_web_database_record_data_field', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_record_data
    ADD CONSTRAINT fk_web_database_record_data_field
        FOREIGN KEY (field_id) REFERENCES web_database_fields(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'web_database_relations_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY web_database_relations_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'fk_web_database_relations_source_record'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY fk_web_database_relations_source_record', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_relations
    ADD CONSTRAINT fk_web_database_relations_source_record
        FOREIGN KEY (source_record_id) REFERENCES web_database_records(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'web_database_relations_ibfk_2'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY web_database_relations_ibfk_2', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'fk_web_database_relations_source_field'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY fk_web_database_relations_source_field', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_relations
    ADD CONSTRAINT fk_web_database_relations_source_field
        FOREIGN KEY (source_field_id) REFERENCES web_database_fields(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'web_database_relations_ibfk_3'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY web_database_relations_ibfk_3', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'fk_web_database_relations_target_record'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY fk_web_database_relations_target_record', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_relations
    ADD CONSTRAINT fk_web_database_relations_target_record
        FOREIGN KEY (target_record_id) REFERENCES web_database_records(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'web_database_relations_ibfk_4'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY web_database_relations_ibfk_4', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND CONSTRAINT_NAME = 'fk_web_database_relations_target_database'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE web_database_relations DROP FOREIGN KEY fk_web_database_relations_target_database', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_relations
    ADD CONSTRAINT fk_web_database_relations_target_database
        FOREIGN KEY (target_database_id) REFERENCES web_databases(id) ON DELETE CASCADE;
