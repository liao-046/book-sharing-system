<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_GET['book_id'] ?? null;

if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '未登入']);
  exit;
}

$params = [$user_id];
$sql = "
  SELECT s.shelf_id, s.name,
    EXISTS (
      SELECT 1 FROM bookshelf_record r
      WHERE r.shelf_id = s.shelf_id
      " . ($book_id ? " AND r.book_id = ?" : "") . "
    ) AS already_added
  FROM book_shelf s
  WHERE s.user_id = ?
";
if ($book_id) $params[] = $book_id; // 替換順序

$stmt = $pdo->prepare($sql);
$stmt->execute(array_reverse($params));
$shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'shelves' => $shelves]);
