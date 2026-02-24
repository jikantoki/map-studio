<?php
require_once '../env.php';
require_once './settings.php';
require_once DIR_ROOT . '/php/myAutoLoad.php';
require_once DIR_ROOT . '/php/functions/authAPIforUse.php';
require_once DIR_ROOT . '/php/functions/authAccountforUse.php';

makeFavoriteTable();

$serverId = $_POST['serverId'] ?? '';
if (!$serverId) {
  echo json_encode(['status' => 'invalid', 'reason' => 'serverId is required', 'errCode' => 10]);
  exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

// 既にお気に入り登録済みか確認
$existing = SQLfindSome('map_favorite_list', [
  ['key' => 'randUserId', 'value' => $secretId, 'func' => '='],
  ['key' => 'serverId', 'value' => $serverId, 'func' => '=']
]);

if ($existing) {
  // 既存ならお気に入り解除
  $pdo = SQLConnect();
  $stmt = $pdo->prepare('DELETE FROM map_favorite_list WHERE randUserId=? AND serverId=? LIMIT 1');
  $stmt->execute([$secretId, $serverId]);
  echo json_encode(['status' => 'ok', 'action' => 'removed']);
} else {
  // 未登録なら追加
  $pdo = SQLConnect();
  $stmt = $pdo->prepare('INSERT INTO map_favorite_list (randUserId, serverId, createdAt, ipAddress) VALUES (?,?,?,?)');
  $stmt->execute([$secretId, $serverId, time(), $ipAddress]);
  echo json_encode(['status' => 'ok', 'action' => 'added']);
}
