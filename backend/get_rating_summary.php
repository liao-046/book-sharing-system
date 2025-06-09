<?php
require_once 'db.php';

$book_id = $_GET['book_id'] ?? null;

if (!$book_id) {
  echo json_encode(['success' => false, 'message' => '缺少書籍 ID']);
  exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_reviews, ROUND(AVG(rating), 1) AS avg_rating FROM review WHERE book_id = ?");
$stmt->execute([$book_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  'success' => true,
  'total_reviews' => (int)$row['total_reviews'],
  'avg_rating' => $row['avg_rating'] ? number_format($row['avg_rating'], 1) : 0
]);
