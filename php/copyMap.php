<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定（ゲスト不可）

// コピー元のserverId
if (!isset($_POST['sourceServerId'])) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'sourceServerId is required',
    'errCode' => 10
  ]);
  exit;
}

// コピー先の情報
if (!isset($_POST['newServerId']) || !isset($_POST['newName'])) {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'newServerId and newName are required',
    'errCode' => 11
  ]);
  exit;
}

$sourceServerId = trim($_POST['sourceServerId']);
$newServerId = trim($_POST['newServerId']);
$newName = trim($_POST['newName']);
$newDescription = isset($_POST['newDescription']) ? trim($_POST['newDescription']) : '';

if ($newServerId === '' || $newName === '') {
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'newServerId and newName must not be empty',
    'errCode' => 12
  ]);
  exit;
}

// 新しいIDが既に使われていないか確認
$existingMap = SQLfind('mapList', 'serverId', $newServerId);
if ($existingMap) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'serverId already in use',
    'errCode' => 409
  ]);
  exit;
}

// コピー元の地図を取得
$sourceMap = SQLfind('mapList', 'serverId', $sourceServerId);
if (!$sourceMap) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'source map not found',
    'errCode' => 404
  ]);
  exit;
}

// コピー元の地図へのアクセス権を確認
$sourceOwnerSecretId = $sourceMap['randOwnerUserId'];
$sourceIsPublic = (bool)$sourceMap['isPublic'];
$sourceSharedList = array_values(array_filter(explode(',', $sourceMap['sharedUserIds'] ?? '')));
$sourceEditorList = array_values(array_filter(explode(',', $sourceMap['editorUserIds'] ?? '')));

$canAccess = false;
if ($secretId === $sourceOwnerSecretId) {
  $canAccess = true;
} elseif (in_array($id, $sourceSharedList) || in_array($id, $sourceEditorList)) {
  $canAccess = true;
} elseif ($sourceIsPublic) {
  $canAccess = true;
}

if (!$canAccess) {
  echo json_encode([
    'status' => 'ng',
    'reason' => 'permission denied: cannot access source map',
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

$randServerId = SQLmakeRandomId('mapList', 'randServerId');
$createdAt = time() * 1000;

// 新しい所有者は現在のユーザー、sharedUserIds/editorUserIdsはリセット
$stmt = $pdo->prepare(
  'INSERT INTO mapList (randServerId, randOwnerUserId, serverId, description, serverName, iconUrl, createdAt, isPublic, sharedUserIds, editorUserIds, pointsList, linesList) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
);
$stmt->execute([
  $randServerId,
  $secretId,
  $newServerId,
  $newDescription,
  $newName,
  $sourceMap['iconUrl'] ?? '',
  $createdAt,
  0,
  '',
  '',
  $sourceMap['pointsList'] ?? '[]',
  $sourceMap['linesList'] ?? '[]',
]);

echo json_encode([
  'status' => 'ok',
  'reason' => 'map copied successfully',
  'newServerId' => $newServerId,
]);
