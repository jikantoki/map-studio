<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定

// ユーザー認証（オプション）
$mySecretId = null;
$myUserId = null;
if (isset($_SERVER['HTTP_ID']) && isset($_SERVER['HTTP_TOKEN'])) {
  $tempId = $_SERVER['HTTP_ID'];
  $tempToken = $_SERVER['HTTP_TOKEN'];
  $tempSecretId = idToSecretId($tempId);
  if ($tempSecretId && authAccount($tempSecretId, $tempToken)) {
    $mySecretId = $tempSecretId;
    $myUserId = $tempId;
  }
}

// serverIdの取得
if (!isset($_SERVER['HTTP_SERVERID'])) {
  http_response_code(400);
  echo json_encode([
    'status' => 'invalid',
    'reason' => 'serverId is required',
    'errCode' => 10
  ]);
  exit;
}

$serverId = $_SERVER['HTTP_SERVERID'];

$mapRecord = SQLfind('mapList', 'serverId', $serverId);
if (!$mapRecord) {
  http_response_code(404);
  echo json_encode([
    'status' => 'ng',
    'reason' => 'map not found',
    'errCode' => 404
  ]);
  exit;
}

// 権限チェック
$isPublic = (bool)$mapRecord['isPublic'];
$ownerSecretId = $mapRecord['randOwnerUserId'];
$sharedList = array_values(array_filter(explode(',', $mapRecord['sharedUserIds'] ?? '')));
$editorList = array_values(array_filter(explode(',', $mapRecord['editorUserIds'] ?? '')));

$canAccess = false;
if ($mySecretId !== null && $mySecretId === $ownerSecretId) {
  $canAccess = true;
} elseif ($myUserId !== null && (in_array($myUserId, $sharedList) || in_array($myUserId, $editorList))) {
  $canAccess = true;
} elseif ($isPublic) {
  $canAccess = true;
}

if (!$canAccess) {
  http_response_code(403);
  echo json_encode([
    'status' => 'ng',
    'reason' => 'permission denied',
    'errCode' => 403
  ]);
  exit;
}

// ownerUserIdをsecretIdから取得（解決できない場合のフォールバックあり）
$ownerUserId = secretIdToId($ownerSecretId) ?? '';

// レスポンスの構築
$points = json_decode($mapRecord['pointsList'] ?? '[]', true);
if (!is_array($points)) {
  $points = [];
}
$lines = json_decode($mapRecord['linesList'] ?? '[]', true);
if (!is_array($lines)) {
  $lines = [];
}

// defaultCenterLatLngの組み立て
$hasCenterLat = isset($mapRecord['defaultCenterLat']) && $mapRecord['defaultCenterLat'] !== null;
$hasCenterLng = isset($mapRecord['defaultCenterLng']) && $mapRecord['defaultCenterLng'] !== null;
$defaultCenterLatLng = ($hasCenterLat && $hasCenterLng)
  ? [(float)$mapRecord['defaultCenterLat'], (float)$mapRecord['defaultCenterLng']]
  : null;

// defaultZoomの組み立て
$defaultZoom = isset($mapRecord['defaultZoom']) && $mapRecord['defaultZoom'] !== null;
$defaultZoom = $defaultZoom ? (int)$mapRecord['defaultZoom'] : null;

echo json_encode([
  'status' => 'ok',
  'reason' => 'ok',
  'map' => [
    'serverId' => $mapRecord['serverId'],
    'name' => $mapRecord['serverName'],
    'description' => $mapRecord['description'],
    'icon' => $mapRecord['iconUrl'],
    'isPublic' => (bool)$mapRecord['isPublic'],
    'ownerUserId' => $ownerUserId,
    'createdAt' => (int)$mapRecord['createdAt'],
    'sharedUserIds' => $sharedList,
    'editorUserIds' => $editorList,
    'points' => $points,
    'lines' => $lines,
    'defaultCenterLatLng' => $defaultCenterLatLng,
    'defaultZoom' => $defaultZoom,
  ]
]);
