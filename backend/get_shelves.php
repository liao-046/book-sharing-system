<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_GET['book_id'] ?? null;

if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '未登入']);
  exit;
}

$sql = "
  SELECT s.shelf_id, s.name,
    EXISTS (
      SELECT 1 FROM bookshelf_record r
      WHERE r.shelf_id = s.shelf_id" .
      ($book_id ? " AND r.book_id = ?" : "") . "
    ) AS already_added
  FROM book_shelf s
  WHERE s.user_id = ?
";

$params = [];
if ($book_id) $params[] = $book_id;
$params[] = $user_id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ 將 already_added 字串轉為布林
$shelves = array_map(function ($shelf) {
  $shelf['already_added'] = $shelf['already_added'] == 1;
  return $shelf;
}, $shelves);

echo json_encode(['success' => true, 'shelves' => $shelves]);
