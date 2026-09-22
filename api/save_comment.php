<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'evaluator') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$teacher_id = $input['teacher_id'] ?? null;
$evaluator_id = $_SESSION['user_id'];
$score_section1 = floatval($input['score_section1'] ?? 0);
$score_section2 = floatval($input['score_section2'] ?? 0);
$total_score = floatval($input['total_score'] ?? 0);
$stregnth_comment = trim($input['stregnth_comment'] ?? '');
$improvement_comment = trim($input['improvement_comment'] ?? '');
$comments = trim($input['comments'] ?? '');

if ($teacher_id) {
    $stmt = $pdo->prepare("
        INSERT INTO evaluations 
        (evaluator_id, teacher_id, comments, score_section1, score_section2, total_score, stregnth_comment, improvement_comment) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $evaluator_id, 
        $teacher_id, 
        $comments, 
        $score_section1, 
        $score_section2, 
        $total_score, 
        $stregnth_comment, 
        $improvement_comment
    ]);

    if ($success) {
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);