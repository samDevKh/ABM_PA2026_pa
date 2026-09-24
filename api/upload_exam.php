<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึง username ของครูท่านนี้
$stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$teacher = $stmtUser->fetch();

if (!$teacher) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้งาน']);
    exit;
}

$username = $teacher['username'];

// กำหนดโฟลเดอร์ปลายทาง: uploads/{user_id}-{username}/exam_doc/
$targetFolderRelative = '/uploads/' . $user_id . '-' . $username . '/exam_doc/';
$targetFolderAbsolute = __DIR__ . '/..' . $targetFolderRelative;

// สร้างโฟลเดอร์หากยังไม่มีอยู่
if (!file_exists($targetFolderAbsolute)) {
    mkdir($targetFolderAbsolute, 0777, true);
}

$uploadedCount = 0;

try {
    if (isset($_FILES['exam_files']) && is_array($_FILES['exam_files']['name'])) {
        $files = $_FILES['exam_files'];
        $totalFiles = count($files['name']);

        // จำกัดไม่เกิน 5 ไฟล์
        if ($totalFiles > 5) {
            echo json_encode(['success' => false, 'message' => 'สามารถส่งไฟล์ได้ไม่เกิน 5 ไฟล์']);
            exit;
        }

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $originalName = $files['name'][$i];
                $tmpName = $files['tmp_name'][$i];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                
                // ตั้งชื่อไฟล์สุ่มป้องกันซ้ำ
                $newFileName = 'exam_' . time() . '_' . $i . '_' . rand(1000, 9999) . '.' . $ext;
                $destination = $targetFolderAbsolute . $newFileName;
                $dbPath = $targetFolderRelative . $newFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    // บันทึกลงตาราง pa_documents โดยระบุ doc_type = 'exam'
                    $stmtIns = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, 'exam', 'file', ?, ?)");
                    $stmtIns->execute([$user_id, $dbPath, $originalName]);
                    $uploadedCount++;
                }
            }
        }
    }

    if ($uploadedCount > 0) {
        echo json_encode(['success' => true, 'message' => "อัพโหลดข้อสอบสำเร็จ {$uploadedCount} ไฟล์"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'กรุณาเลือกไฟล์ข้อสอบอย่างน้อย 1 ไฟล์']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}