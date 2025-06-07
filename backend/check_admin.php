<?php
require_once 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['isAdmin' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT is_admin FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo json_encode([
    'isAdmin' => $user && $user['is_admin'] == 1
]);
