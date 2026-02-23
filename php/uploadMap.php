<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定（ゲスト不可）

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
$sharedUserIds = '';
if (is_array($mapData['sharedUserIds'] ?? null)) {
  $sharedUserIds = implode(',', array_filter(array_map('trim', array_map('strval', $mapData['sharedUserIds']))));
}
$editorUserIds = '';
if (is_array($mapData['editorUserIds'] ?? null)) {
  $editorUserIds = implode(',', array_filter(array_map('trim', array_map('strval', $mapData['editorUserIds']))));
}
$pointsList = json_encode($mapData['points'] ?? []);
$linesList = json_encode($mapData['lines'] ?? []);
// ペイロードサイズ制限（各1MB）
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
    'UPDATE mapList SET serverName=?, description=?, iconUrl=?, isPublic=?, sharedUserIds=?, editorUserIds=?, pointsList=?, linesList=? WHERE randServerId=?'
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
    $randServerId,
  ]);
} else {
  // 新規マップを挿入
  $randServerId = SQLmakeRandomId('mapList', 'randServerId');
  $stmt = $pdo->prepare(
    'INSERT INTO mapList (randServerId, randOwnerUserId, serverId, description, serverName, iconUrl, createdAt, isPublic, sharedUserIds, editorUserIds, pointsList, linesList) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
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
  ]);
}

echo json_encode([
  'status' => 'ok',
  'reason' => 'map uploaded successfully',
  'serverId' => $serverId
]);
