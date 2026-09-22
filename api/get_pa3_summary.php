<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$teacher_id = $_GET['teacher_id'] ?? null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'ไม่ระบุครูผู้รับการประเมิน']);
    exit;
}

// 1. ดึงข้อมูลครู
$stmtTeacher = $pdo->prepare("
    SELECT u.*, sg.name as group_name 
    FROM users u 
    LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id 
    WHERE u.id = ? AND u.role = 'teacher'
");
$stmtTeacher->execute([$teacher_id]);
$teacher = $stmtTeacher->fetch();

if (!$teacher) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลครู']);
    exit;
}

// 2. ดึงผลการประเมินจากกรรมการทุกคน (จำกัดไว้ไม่เกิน 3 ท่านล่าสุด หรือดึงกรรมการคนที่ 1, 2, 3)
$stmtEval = $pdo->prepare("
    SELECT e.*, u.fullname as evaluator_name 
    FROM evaluations e
    JOIN users u ON e.evaluator_id = u.id
    WHERE e.teacher_id = ?
    ORDER BY e.created_at ASC
    LIMIT 3
");
$stmtEval->execute([$teacher_id]);
$evaluations = $stmtEval->fetchAll();

// จัดรูปแบบโครงสร้างคะแนนของกรรมการ 3 คน
$evaluators_data = [];
$is_all_pass = true;
$completed_count = count($evaluations);

for ($i = 0; $i < 3; $i++) {
    if (isset($evaluations[$i])) {
        $e = $evaluations[$i];
        $s1 = floatval($e['score_section1']);
        $s2 = floatval($e['score_section2']);
        $total = floatval($e['total_score']);
        $is_pass = ($total >= 70);

        if (!$is_pass) {
            $is_all_pass = false;
        }

        $evaluators_data[] = [
            'name' => $e['evaluator_name'],
            'sec1' => number_format($s1, 2),
            'sec2' => number_format($s2, 2),
            'total' => number_format($total, 2),
            'is_pass' => $is_pass
        ];
    } else {
        // กรณีผลประเมินยังไม่ครบ 3 คน
        $is_all_pass = false;
        $evaluators_data[] = [
            'name' => 'กรรมการคนที่ ' . ($i + 1) . ' (ยังไม่ประเมิน)',
            'sec1' => '-',
            'sec2' => '-',
            'total' => '-',
            'is_pass' => false
        ];
    }
}

// ถ้ายังประเมินไม่ครบ 3 คน ให้สถานะผ่านเป็น false
if ($completed_count < 3) {
    $is_all_pass = false;
}

echo json_encode([
    'success' => true,
    'teacher' => [
        'fullname' => $teacher['fullname'],
        'academic_standing' => $teacher['academic_standing'] ?: 'ครู (ยังไม่มีวิทยฐานะ)',
        'group_name' => $teacher['group_name'] ?: 'ไม่ระบุ'
    ],
    'completed_count' => $completed_count,
    'evaluators' => $evaluators_data,
    'is_all_pass' => $is_all_pass
]);