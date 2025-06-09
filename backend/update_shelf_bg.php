<?php
require_once 'db.php';

$shelf_id = $_POST['shelf_id'] ?? null;
$bg_url = $_POST['background_url'] ?? null;

if (!$shelf_id || $bg_url === null || trim($bg_url) === '') {
  echo json_encode(['success' => false, 'message' => '缺少參數']);
  exit;
}

try {
  $stmt = $pdo->prepare("UPDATE book_shelf SET background_url = ? WHERE shelf_id = ?");
  $result = $stmt->execute([$bg_url, $shelf_id]);

  if ($result) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'message' => '資料庫更新失敗']);
  }
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
