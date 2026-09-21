<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$doc_id = $input['doc_id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($doc_id) {
    // 1. ค้นหาเอกสารของครูคนนี้
    $stmt = $pdo->prepare("SELECT * FROM pa_documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$doc_id, $user_id]);
    $doc = $stmt->fetch();

    if ($doc) {
        // 2. ลบไฟล์จริงในโฟลเดอร์ (ถ้าเป็นไฟล์)
        if ($doc['file_type'] === 'file') {
            $filePath = __DIR__ . '/..' . $doc['file_path_or_link'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // 3. ลบ Record ออกจากฐานข้อมูล
        $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE id = ?");
        $stmtDel->execute([$doc_id]);

        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลเอกสารที่ต้องการลบ']);