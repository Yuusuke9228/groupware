ALTER TABLE workflow_requests
  DROP FOREIGN KEY workflow_requests_ibfk_1;

ALTER TABLE workflow_requests
  ADD CONSTRAINT workflow_requests_ibfk_1
  FOREIGN KEY (template_id) REFERENCES workflow_templates(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;
