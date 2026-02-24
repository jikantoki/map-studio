<?php
require_once '../env.php';
require_once './settings.php';
require_once DIR_ROOT . '/php/myAutoLoad.php';
require_once DIR_ROOT . '/php/functions/authAPIforUse.php';
require_once DIR_ROOT . '/php/functions/authAccountforUse.php';

makeFavoriteTable();

$pdo = SQLConnect();
if (!$pdo) {
  echo json_encode(['status' => 'ng', 'reason' => 'database connection failed', 'errCode' => 500]);
  exit;
}

$stmt = $pdo->prepare('SELECT serverId, createdAt FROM map_favorite_list WHERE randUserId=? ORDER BY createdAt DESC');
$stmt->execute([$secretId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// それぞれのserverId に対応するmapの情報を取得
$result = [];
foreach ($favorites as $fav) {
  $mapRecord = SQLfind('mapList', 'serverId', $fav['serverId']);
  if ($mapRecord) {
    $ownerUserId = secretIdToId($mapRecord['randOwnerUserId']) ?? '';
    $result[] = [
      'serverId'    => $mapRecord['serverId'],
      'name'        => $mapRecord['serverName'],
      'description' => $mapRecord['description'] ?? '',
      'icon'        => $mapRecord['iconUrl'] ?? null,
      'createdAt'   => (int)$mapRecord['createdAt'],
      'ownerUserId' => $ownerUserId,
      'isPublic'    => (bool)$mapRecord['isPublic'],
      'favoritedAt' => (int)$fav['createdAt'],
    ];
  }
}

echo json_encode(['status' => 'ok', 'favorites' => $result]);
