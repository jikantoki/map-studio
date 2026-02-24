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

// 地図の所有者にメール通知を送信
$mapRecord = SQLfind('mapList', 'serverId', $serverId);
if ($mapRecord) {
  $ownerSecretId = $mapRecord['randOwnerUserId'];
  // 投稿者が地図の所有者自身でない場合のみ通知
  if ($ownerSecretId !== $secretId) {
    $ownerMail = secretIdToMailAddress($ownerSecretId);
    if ($ownerMail) {
      $mapName = htmlspecialchars($mapRecord['serverName'] ?? $serverId, ENT_QUOTES, 'UTF-8');
      $commenterName = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
      $commentText = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
      $mapUrl = 'https://map.enoki.xyz/map/' . rawurlencode($serverId);
      $message = "<p>あなたの地図「{$mapName}」に新しいコメントが追加されました。</p>"
        . "<p>地図を確認する: <a href=\"{$mapUrl}\">{$mapName}</a></p>"
        . "<p>コメントしたユーザーID: {$commenterName}</p>"
        . "<p>コメント内容:<br>{$commentText}</p>";
      sendMail($ownerMail, "【Map studio】地図にコメントが追加されました", $message);
    }
  }
}
