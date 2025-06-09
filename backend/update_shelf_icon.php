<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$shelf_id = $_POST['shelf_id'] ?? null;
$icon = $_POST['icon'] ?? null;

if (!$shelf_id || $icon === null || trim($icon) === '') {
  echo json_encode(['success' => false, 'message' => '缺少或空的 icon 參數']);
  exit;
}

try {
  $stmt = $pdo->prepare("UPDATE book_shelf SET icon = ? WHERE shelf_id = ?");
  $result = $stmt->execute([$icon, $shelf_id]);

  if ($result) {
    echo json_encode(['success' => true]);
  } else {
    $errorInfo = $stmt->errorInfo();
    echo json_encode(['success' => false, 'message' => '更新失敗: ' . $errorInfo[2]]);
  }
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>
