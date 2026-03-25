-- ワークフローテンプレートの承認ルート補完
-- 既存の TEST テンプレートのステップ番号不整合を補正
UPDATE workflow_route_definitions
SET step_number = 2,
    sort_order = 2
WHERE template_id = 1
  AND step_name = '最終承認'
  AND step_number = 1;

-- 申請テンプレート 2-8 に最低限の承認ルートを付与
INSERT INTO workflow_route_definitions (
    template_id,
    step_number,
    step_type,
    step_name,
    approver_type,
    approver_id,
    allow_delegation,
    allow_self_approval,
    parallel_approval,
    sort_order,
    created_at,
    updated_at
)
SELECT
    t.id,
    1,
    'approval',
    '承認',
    'user',
    4,
    0,
    1,
    0,
    1,
    NOW(),
    NOW()
FROM workflow_templates t
WHERE t.id BETWEEN 2 AND 8
  AND NOT EXISTS (
      SELECT 1
      FROM workflow_route_definitions r
      WHERE r.template_id = t.id
  );
