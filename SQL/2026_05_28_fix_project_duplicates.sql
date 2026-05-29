USE taskcolab;

START TRANSACTION;

CREATE TEMPORARY TABLE tmp_duplicate_projects AS
SELECT
  p.id AS duplicate_id,
  keepers.keep_id
FROM projects p
INNER JOIN (
  SELECT
    LOWER(TRIM(name)) AS normalized_name,
    owner_id,
    MIN(id) AS keep_id,
    COUNT(*) AS total
  FROM projects
  GROUP BY LOWER(TRIM(name)), owner_id
  HAVING COUNT(*) > 1
) keepers
  ON keepers.normalized_name = LOWER(TRIM(p.name))
 AND keepers.owner_id <=> p.owner_id
WHERE p.id <> keepers.keep_id;

UPDATE boards b
INNER JOIN tmp_duplicate_projects d ON d.duplicate_id = b.project_id
SET b.project_id = d.keep_id;

INSERT IGNORE INTO project_members (project_id, user_id, role_in_project, joined_at)
SELECT d.keep_id, pm.user_id, pm.role_in_project, pm.joined_at
FROM project_members pm
INNER JOIN tmp_duplicate_projects d ON d.duplicate_id = pm.project_id;

CREATE TEMPORARY TABLE tmp_duplicate_project_conversations AS
SELECT
  c.id AS duplicate_conversation_id,
  existing.id AS keep_conversation_id,
  d.keep_id
FROM conversations c
INNER JOIN tmp_duplicate_projects d ON d.duplicate_id = c.project_id
LEFT JOIN conversations existing
  ON existing.project_id = d.keep_id
 AND existing.type = c.type
WHERE c.type = 'project';

UPDATE conversations c
INNER JOIN tmp_duplicate_project_conversations d
  ON d.duplicate_conversation_id = c.id
SET c.project_id = d.keep_id
WHERE d.keep_conversation_id IS NULL;

INSERT IGNORE INTO conversation_members
  (conversation_id, user_id, role_in_conversation, joined_at, last_read_message_id, muted, deleted_at)
SELECT
  d.keep_conversation_id,
  cm.user_id,
  cm.role_in_conversation,
  cm.joined_at,
  cm.last_read_message_id,
  cm.muted,
  cm.deleted_at
FROM conversation_members cm
INNER JOIN tmp_duplicate_project_conversations d
  ON d.duplicate_conversation_id = cm.conversation_id
WHERE d.keep_conversation_id IS NOT NULL;

UPDATE messages m
INNER JOIN tmp_duplicate_project_conversations d
  ON d.duplicate_conversation_id = m.conversation_id
SET m.conversation_id = d.keep_conversation_id
WHERE d.keep_conversation_id IS NOT NULL;

DELETE cm
FROM conversation_members cm
INNER JOIN tmp_duplicate_project_conversations d
  ON d.duplicate_conversation_id = cm.conversation_id
WHERE d.keep_conversation_id IS NOT NULL;

DELETE c
FROM conversations c
INNER JOIN tmp_duplicate_project_conversations d
  ON d.duplicate_conversation_id = c.id
WHERE d.keep_conversation_id IS NOT NULL;

UPDATE activity_logs al
INNER JOIN tmp_duplicate_projects d
  ON al.entity_type = 'project'
 AND al.entity_id = d.duplicate_id
SET al.entity_id = d.keep_id;

DELETE pm
FROM project_members pm
INNER JOIN tmp_duplicate_projects d ON d.duplicate_id = pm.project_id;

DELETE p
FROM projects p
INNER JOIN tmp_duplicate_projects d ON d.duplicate_id = p.id;

DROP TEMPORARY TABLE IF EXISTS tmp_duplicate_project_conversations;
DROP TEMPORARY TABLE IF EXISTS tmp_duplicate_projects;

COMMIT;
