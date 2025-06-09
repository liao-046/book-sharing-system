<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$review_id = $_POST['review_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$user_id || !$review_id || !$rating || $rating < 1 || $rating > 5) {
  echo json_encode(['success' => false, 'message' => '資料不完整']);
  exit;
}

$stmt = $pdo->prepare("UPDATE review SET rating = ?, comment = ? WHERE review_id = ? AND user_id = ?");
$success = $stmt->execute([$rating, $comment, $review_id, $user_id]);

echo json_encode(['success' => $success]);
