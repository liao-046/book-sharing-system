<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

$stmt = $pdo->prepare("SELECT is_admin FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) {
    echo json_encode(['success' => false, 'message' => '非管理者無權限']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$book_ids = $input['book_ids'] ?? [];

if (!is_array($book_ids) || empty($book_ids)) {
    echo json_encode(['success' => false, 'message' => '未提供有效的 book_ids']);
    exit;
}

try {
    $pdo->beginTransaction();
    $in = implode(',', array_fill(0, count($book_ids), '?'));

    $stmt = $pdo->prepare("DELETE FROM book_author WHERE book_id IN ($in)");
    $stmt->execute($book_ids);

    $stmt = $pdo->prepare("DELETE FROM book WHERE book_id IN ($in)");
    $stmt->execute($book_ids);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()]);
}
