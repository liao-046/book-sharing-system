<?php
require_once 'db.php';

$book_id = $_GET['book_id'] ?? null;
if (!$book_id) {
  echo json_encode(['success' => false, 'message' => '缺少書籍 ID']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT r.user_id, u.name AS user_name, r.rating, r.comment, r.create_time
  FROM review r
  JOIN user u ON r.user_id = u.user_id
  WHERE r.book_id = ?
  ORDER BY r.create_time DESC
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'reviews' => $reviews]);
