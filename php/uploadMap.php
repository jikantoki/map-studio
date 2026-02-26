<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定（ゲスト不可）

/**
 * ポイント/ラインのリストを3方向マージする
 *
 * base:   クライアントが最後にサーバーから取得した状態
 * local:  クライアントの現在の状態（編集後）
 * server: サーバーの現在の状態
 *
 * ルール:
 * - ローカルが追加したもの（baseにない）→ 採用
 * - ローカルが削除したもの（baseにあってlocalにない）→ 除外
 * - ローカルが変更・維持したもの → ローカル版を採用（last-write-wins per item）
 * - 別ユーザーがサーバーに追加したもの（baseにもlocalにもない）→ 保持
 * - IDを持たないアイテム（後方互換）→ ローカルのものをそのまま採用
 *
 * @param array $base
 * @param array $local
 * @param array $server
 * @return array マージ後のリスト
 */
function mergeMapItems(array $base, array $local, array $server): array {
  $baseById = [];
  foreach ($base as $item) {
    if (!empty($item['id'])) $baseById[$item['id']] = $item;
  }
  $localById = [];
  $localNoId = [];
  foreach ($local as $item) {
    if (!empty($item['id'])) $localById[$item['id']] = $item;
    else $localNoId[] = $item;
  }
  $serverById = [];
  $serverNoId = [];
  foreach ($server as $item) {
    if (!empty($item['id'])) $serverById[$item['id']] = $item;
    else $serverNoId[] = $item;
  }

  $allIds = array_unique(array_merge(
    array_keys($baseById),
    array_keys($localById),
    array_keys($serverById)
  ));

  $result = [];
  foreach ($allIds as $id) {
    $inBase   = isset($baseById[$id]);
    $inLocal  = isset($localById[$id]);
    $inServer = isset($serverById[$id]);

    if (!$inBase && $inLocal) {
      // ローカルが追加 → 採用
      $result[] = $localById[$id];
    } elseif ($inBase && !$inLocal) {
      // ローカルが削除 → 除外（サーバー側に残っていても削除扱い）
    } elseif ($inBase && $inLocal) {
      // ローカルで変更または維持 → ローカル版を採用
      $result[] = $localById[$id];
    } elseif (!$inBase && !$inLocal && $inServer) {
      // 別ユーザーがサーバーに追加したもの → 保持
      $result[] = $serverById[$id];
    }
  }

  // IDなしのローカルアイテム（後方互換）はそのまま追加
  foreach ($localNoId as $item) {
    $result[] = $item;
  }
  // IDなしのサーバーアイテムはローカルにIDなしアイテムがない場合のみ保持
  if (empty($localNoId)) {
    foreach ($serverNoId as $item) {
      $result[] = $item;
    }
  }

  return $result;
}

// マップデータの受け取り
if (!isset($_POST['mapData'])) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'map data is required',
    'errCode' => 10
  ]);
  exit;
}

$mapData = json_decode($_POST['mapData'], true);
if (!is_array($mapData)) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'invalid map data format',
    'errCode' => 11
  ]);
  exit;
}

// 必須フィールドの確認
$serverId = $mapData['serverId'] ?? '';
$serverName = $mapData['name'] ?? '';
if ($serverId === '' || $serverName === '') {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'serverId and name are required',
    'errCode' => 12
  ]);
  exit;
}

// 既存マップの確認
$existingMap = SQLfind('mapList', 'serverId', $serverId);
if ($existingMap) {
  $existingOwnerSecretId = $existingMap['randOwnerUserId'];
  $existingEditorList = array_values(array_filter(explode(',', $existingMap['editorUserIds'] ?? '')));

  if ($existingOwnerSecretId === $secretId) {
    // 自分が所有者なら上書き可
  } elseif (in_array($id, $existingEditorList)) {
    // 自分が編集権限を持っているなら保存可
  } else {
    // 権限なし
    echo json_encode([
      'status' => 'ng',
      'reason' => 'permission denied: another user owns this serverId',
      'errCode' => 403
    ]);
    exit;
  }
}

// データの準備（PDOを使用して安全にエスケープ）
$pdo = SQLConnect();
if (!$pdo) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'database connection failed',
    'errCode' => 500
  ]);
  exit;
}

$description = $mapData['description'] ?? '';
$iconUrl = $mapData['icon'] ?? '';
$isPublic = isset($mapData['isPublic']) && $mapData['isPublic'] ? 1 : 0;
$defaultCenterLat = isset($mapData['defaultCenterLatLng'][0]) ? (float)$mapData['defaultCenterLatLng'][0] : null;
$defaultCenterLng = isset($mapData['defaultCenterLatLng'][1]) ? (float)$mapData['defaultCenterLatLng'][1] : null;
$sharedUserIds = '';
if (is_array($mapData['sharedUserIds'] ?? null)) {
  $sharedUserIds = implode(',', array_filter(array_map('trim', array_map('strval', $mapData['sharedUserIds']))));
}
$editorUserIds = '';
if (is_array($mapData['editorUserIds'] ?? null)) {
  $editorUserIds = implode(',', array_filter(array_map('trim', array_map('strval', $mapData['editorUserIds']))));
}

// baseDataが提供されていれば差分マージを実行
$baseMapData = null;
if (isset($_POST['baseData'])) {
  $decoded = json_decode($_POST['baseData'], true);
  if (is_array($decoded)) $baseMapData = $decoded;
}

$wasMerged = false;
$mergedPoints = $mapData['points'] ?? [];
$mergedLines  = $mapData['lines'] ?? [];

if ($existingMap && $baseMapData !== null) {
  $serverPoints = json_decode($existingMap['pointsList'] ?? '[]', true) ?: [];
  $serverLines  = json_decode($existingMap['linesList']  ?? '[]', true) ?: [];
  $mergedPoints = mergeMapItems($baseMapData['points'] ?? [], $mapData['points'] ?? [], $serverPoints);
  $mergedLines  = mergeMapItems($baseMapData['lines']  ?? [], $mapData['lines']  ?? [], $serverLines);
  $wasMerged    = true;
}

$pointsList = json_encode($mergedPoints);
$linesList  = json_encode($mergedLines);
// ペイロードサイズ制限（マージ後の最終データで確認・各1MB）
if (strlen($pointsList) > 1_000_000 || strlen($linesList) > 1_000_000) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'map data is too large',
    'errCode' => 13
  ]);
  exit;
}
$createdAt = isset($mapData['createdAt']) ? (int)$mapData['createdAt'] : (time() * 1000);

// ownerUserIdはクライアントから信頼せず、認証済みユーザーのsecretIdを使用
$ownerSecretId = $secretId;

if ($existingMap) {
  // 既存マップを更新
  $randServerId = $existingMap['randServerId'];
  $stmt = $pdo->prepare(
    'UPDATE mapList SET serverName=?, description=?, iconUrl=?, isPublic=?, sharedUserIds=?, editorUserIds=?, pointsList=?, linesList=?, defaultCenterLat=?, defaultCenterLng=? WHERE randServerId=?'
  );
  $stmt->execute([
    $serverName,
    $description,
    $iconUrl,
    $isPublic,
    $sharedUserIds,
    $editorUserIds,
    $pointsList,
    $linesList,
    $defaultCenterLat,
    $defaultCenterLng,
    $randServerId,
  ]);
} else {
  // 新規マップを挿入
  $randServerId = SQLmakeRandomId('mapList', 'randServerId');
  $stmt = $pdo->prepare(
    'INSERT INTO mapList (randServerId, randOwnerUserId, serverId, description, serverName, iconUrl, createdAt, isPublic, sharedUserIds, editorUserIds, pointsList, linesList, defaultCenterLat, defaultCenterLng) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
  );
  $stmt->execute([
    $randServerId,
    $ownerSecretId,
    $serverId,
    $description,
    $serverName,
    $iconUrl,
    $createdAt,
    $isPublic,
    $sharedUserIds,
    $editorUserIds,
    $pointsList,
    $linesList,
    $defaultCenterLat,
    $defaultCenterLng,
  ]);
}

echo json_encode([
  'status' => 'ok',
  'reason' => 'map uploaded successfully',
  'serverId' => $serverId,
  'wasMerged' => $wasMerged,
  'points' => $wasMerged ? $mergedPoints : null,
  'lines'  => $wasMerged ? $mergedLines  : null,
]);
