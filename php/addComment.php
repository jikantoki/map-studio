<?php
require_once '../env.php';
require_once './settings.php';
require_once DIR_ROOT . '/php/myAutoLoad.php';
require_once DIR_ROOT . '/php/functions/authAPIforUse.php';
require_once DIR_ROOT . '/php/functions/authAccountforUse.php';

$serverId = $_POST['serverId'] ?? '';
$comment  = $_POST['comment']  ?? '';
if (!$serverId || !$comment) {
  echo json_encode(['status' => 'invalid', 'reason' => 'serverId and comment are required', 'errCode' => 10]);
  exit;
}

// コメントの長さ制限
if (mb_strlen($comment) > 1000) {
  echo json_encode(['status' => 'invalid', 'reason' => 'comment is too long', 'errCode' => 11]);
  exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

$pdo = SQLConnect();
if (!$pdo) {
  echo json_encode(['status' => 'ng', 'reason' => 'database connection failed', 'errCode' => 500]);
  exit;
}

$stmt = $pdo->prepare('INSERT INTO map_comment_list (randUserId, serverId, comment, createdAt, ipAddress) VALUES (?,?,?,?,?)');
$stmt->execute([$secretId, $serverId, $comment, time(), $ipAddress]);

echo json_encode(['status' => 'ok', 'reason' => 'comment added']);
