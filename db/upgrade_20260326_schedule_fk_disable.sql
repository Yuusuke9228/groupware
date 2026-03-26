-- Broken schedule parent FKs can reject valid child rows even when schedules.id exists.
-- Drop only the schedule_id-side constraints and keep user/org integrity constraints.

ALTER TABLE calendar_import_event_map
    DROP FOREIGN KEY fk_calendar_import_event_map_schedule;

ALTER TABLE daily_report_schedules
    DROP FOREIGN KEY daily_report_schedules_ibfk_2;

ALTER TABLE schedule_organizations
    DROP FOREIGN KEY schedule_organizations_ibfk_1;

ALTER TABLE schedule_participants
    DROP FOREIGN KEY schedule_participants_ibfk_1;
