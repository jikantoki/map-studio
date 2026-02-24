<?php
require_once '../env.php';
require_once './settings.php';
require_once DIR_ROOT . '/php/myAutoLoad.php';
require_once DIR_ROOT . '/php/functions/authAPIforUse.php';

makeCommentTable();

$serverId = $_SERVER['HTTP_SERVERID'] ?? '';
if (!$serverId) {
  echo json_encode(['status' => 'invalid', 'reason' => 'serverId is required', 'errCode' => 10]);
  exit;
}

$pdo = SQLConnect();
if (!$pdo) {
  echo json_encode(['status' => 'ng', 'reason' => 'database connection failed', 'errCode' => 500]);
  exit;
}

$stmt = $pdo->prepare('SELECT id, randUserId, comment, createdAt FROM map_comment_list WHERE serverId=? ORDER BY createdAt DESC');
$stmt->execute([$serverId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comments = [];
foreach ($rows as $row) {
  $userId = secretIdToId($row['randUserId']) ?? '不明なユーザー';
  $comments[] = [
    'id'        => (int)$row['id'],
    'userId'    => $userId,
    'comment'   => $row['comment'],
    'createdAt' => (int)$row['createdAt'],
  ];
}

echo json_encode(['status' => 'ok', 'comments' => $comments]);
