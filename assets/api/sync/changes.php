<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();
$userId = (int)$user['id'];
$isAdmin = !empty($user['is_admin']) ? 1 : 0;
$sinceId = max(0, (int)($_GET['since_id'] ?? 0));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

$sql = "
    SELECT se.id, se.event_type, se.entity_type, se.entity_id, se.board_id, se.user_id, se.payload, se.created_at
    FROM sync_events se
    WHERE se.id > ?
      AND (
          ? = 1
          OR se.user_id = ?
          OR se.board_id IS NULL
          OR EXISTS (
              SELECT 1
              FROM boards b
              WHERE b.id = se.board_id
                AND (
                    b.owner_id = ?
                    OR EXISTS (SELECT 1 FROM board_members bm WHERE bm.board_id = b.id AND bm.user_id = ?)
                )
          )
      )
    ORDER BY se.id ASC
    LIMIT {$limit}
";

$stmt = api_db()->prepare($sql);
$stmt->execute([$sinceId, $isAdmin, $userId, $userId, $userId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(static function (array $event): array {
    return [
        'id' => (int)$event['id'],
        'event_type' => $event['event_type'],
        'entity_type' => $event['entity_type'],
        'entity_id' => $event['entity_id'] !== null ? (int)$event['entity_id'] : null,
        'board_id' => $event['board_id'] !== null ? (int)$event['board_id'] : null,
        'user_id' => $event['user_id'] !== null ? (int)$event['user_id'] : null,
        'payload' => $event['payload'] ? json_decode($event['payload'], true) : null,
        'created_at' => $event['created_at'],
    ];
}, $events);

api_json([
    'ok' => true,
    'events' => $result,
    'count' => count($result),
    'latest_id' => count($result) > 0 ? $result[count($result) - 1]['id'] : $sinceId,
]);
?>
