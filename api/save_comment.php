<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'evaluator') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$teacher_id = $input['teacher_id'] ?? null;
$comment = trim($input['comment'] ?? '');

if ($teacher_id && !empty($comment)) {
    $stmt = $pdo->prepare("INSERT INTO evaluations (teacher_id, evaluator_id, comments) VALUES (?, ?, ?)");
    $stmt->execute([$teacher_id, $_SESSION['user_id'], $comment]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
}