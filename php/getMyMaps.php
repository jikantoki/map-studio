<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定（ゲスト不可）

$pdo = SQLConnect();
if (!$pdo) {
  http_response_code(500);
  echo json_encode([
    'status' => 'ng',
    'reason' => 'database connection failed',
    'errCode' => 500
  ]);
  exit;
}

// 自分が所有者またはエディターの地図を取得
$stmt = $pdo->prepare(
  'SELECT serverId, serverName, description, iconUrl, createdAt, isPublic, sharedUserIds, editorUserIds, randOwnerUserId
   FROM mapList
   WHERE randOwnerUserId = ? OR FIND_IN_SET(?, editorUserIds) > 0
   ORDER BY createdAt DESC'
);
$stmt->execute([$secretId, $id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$maps = [];
foreach ($rows as $row) {
  $ownerUserId = secretIdToId($row['randOwnerUserId']) ?? '';
  $sharedList = array_values(array_filter(explode(',', $row['sharedUserIds'] ?? '')));
  $editorList = array_values(array_filter(explode(',', $row['editorUserIds'] ?? '')));
  $maps[] = [
    'serverId'      => $row['serverId'],
    'name'          => $row['serverName'],
    'description'   => $row['description'] ?? '',
    'icon'          => $row['iconUrl'] ?? null,
    'createdAt'     => (int)$row['createdAt'],
    'isPublic'      => (bool)$row['isPublic'],
    'ownerUserId'   => $ownerUserId,
    'sharedUserIds' => $sharedList,
    'editorUserIds' => $editorList,
  ];
}

echo json_encode(['status' => 'ok', 'maps' => $maps]);
