<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定（ゲスト不可）

// 削除対象のserverIdを取得
if (!isset($_POST['serverId'])) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'serverId is required',
    'errCode' => 10
  ]);
  exit;
}

$serverId = trim($_POST['serverId']);
if ($serverId === '') {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'serverId must not be empty',
    'errCode' => 11
  ]);
  exit;
}

// 地図を検索
$mapRecord = SQLfind('mapList', 'serverId', $serverId);
if (!$mapRecord) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'map not found',
    'errCode' => 404
  ]);
  exit;
}

// 所有者のみ削除可能（編集者は不可）
if ($mapRecord['randOwnerUserId'] !== $secretId) {
  http_response_code(403);
  echo json_encode([
    'status' => 'ng',
    'reason' => 'permission denied: only the owner can delete this map',
    'errCode' => 403
  ]);
  exit;
}

// データベース接続
$pdo = SQLConnect();
if (!$pdo) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'database connection failed',
    'errCode' => 500
  ]);
  exit;
}

$stmt = $pdo->prepare('DELETE FROM mapList WHERE randServerId = ?');
$stmt->execute([$mapRecord['randServerId']]);

echo json_encode([
  'status' => 'ok',
  'reason' => 'map deleted successfully',
]);
