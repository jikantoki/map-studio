<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定

// ページネーションパラメータ
$page = isset($_SERVER['HTTP_PAGE']) ? max(1, (int)$_SERVER['HTTP_PAGE']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 検索キーワード
$search = isset($_SERVER['HTTP_SEARCH']) ? trim($_SERVER['HTTP_SEARCH']) : '';

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

if ($search !== '') {
  $likeParam = '%' . $search . '%';

  $countStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM mapList WHERE isPublic = 1 AND (serverName LIKE ? OR description LIKE ?)'
  );
  $countStmt->execute([$likeParam, $likeParam]);
  $totalCount = (int)$countStmt->fetchColumn();

  $stmt = $pdo->prepare(
    'SELECT serverId, serverName, description, iconUrl, createdAt, randOwnerUserId FROM mapList WHERE isPublic = 1 AND (serverName LIKE ? OR description LIKE ?) ORDER BY createdAt DESC LIMIT ? OFFSET ?'
  );
  $stmt->bindValue(1, $likeParam, PDO::PARAM_STR);
  $stmt->bindValue(2, $likeParam, PDO::PARAM_STR);
  $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(4, $offset, PDO::PARAM_INT);
  $stmt->execute();
} else {
  $countStmt = $pdo->prepare('SELECT COUNT(*) FROM mapList WHERE isPublic = 1');
  $countStmt->execute();
  $totalCount = (int)$countStmt->fetchColumn();

  $stmt = $pdo->prepare(
    'SELECT serverId, serverName, description, iconUrl, createdAt, randOwnerUserId FROM mapList WHERE isPublic = 1 ORDER BY createdAt DESC LIMIT ? OFFSET ?'
  );
  $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(2, $offset, PDO::PARAM_INT);
  $stmt->execute();
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$maps = [];
foreach ($rows as $row) {
  $ownerUserId = secretIdToId($row['randOwnerUserId']) ?? '';
  $maps[] = [
    'serverId'    => $row['serverId'],
    'name'        => $row['serverName'],
    'description' => $row['description'] ?? '',
    'icon'        => $row['iconUrl'] ?? null,
    'createdAt'   => (int)$row['createdAt'],
    'ownerUserId' => $ownerUserId,
  ];
}

echo json_encode([
  'status'     => 'ok',
  'maps'       => $maps,
  'totalCount' => $totalCount,
  'page'       => $page,
  'perPage'    => $perPage,
  'totalPages' => (int)ceil($totalCount / $perPage),
]);
