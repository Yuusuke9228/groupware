ALTER TABLE workflow_request_data
  DROP FOREIGN KEY workflow_request_data_ibfk_1;

ALTER TABLE workflow_attachments
  DROP FOREIGN KEY workflow_attachments_ibfk_1;

ALTER TABLE workflow_approvals
  DROP FOREIGN KEY workflow_approvals_ibfk_1;
