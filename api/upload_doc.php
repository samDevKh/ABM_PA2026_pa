<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$user_id = $_SESSION['user_id'];
$docTypes = ['pa2', 'info', 'report'];
$uploadedCount = 0;
$errors = [];

// กำหนดโฟลเดอร์เก็บไฟล์บน Server และสร้างให้อัตโนมัติหากยังไม่มี
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach ($docTypes as $type) {
    $kind = $_POST["kind_{$type}"] ?? 'file';

    if ($kind === 'file') {
        if (isset($_FILES["file_{$type}"]) && $_FILES["file_{$type}"]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES["file_{$type}"];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'png', 'jpg', 'jpeg'];

            // 1. ตรวจสอบประเภทไฟล์
            if (!in_array($ext, $allowedExts)) {
                $errors[] = "ไฟล์รายการ {$type} ต้องเป็น PDF, PNG หรือ JPG เท่านั้น";
                continue;
            }

            // 2. ตรวจสอบขนาดไฟล์ (ไม่เกิน 15 MB)
            if ($file['size'] > 15 * 1024 * 1024) {
                $errors[] = "ไฟล์รายการ {$type} มีขนาดใหญ่เกิน 15MB";
                continue;
            }

            // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ
            $newFileName = 'pa_' . $user_id . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            // ย้ายไฟล์ชั่วคราวไปยังโฟลเดอร์ uploads/
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = '/uploads/' . $newFileName;

                // ลบรายการเอกสารเดิมประเภทเดียวกันของครูคนนี้ออกก่อน
                $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtDel->execute([$user_id, $type]);

                // บันทึกรายการใหม่ลงฐานข้อมูล
                $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
                $stmt->execute([$user_id, $type, $filePath, $file['name']]);
                $uploadedCount++;
            } else {
                $errors[] = "ไม่สามารถย้ายไฟล์รายการ {$type} ลงโฟลเดอร์ uploads ได้";
            }
        } elseif (isset($_FILES["file_{$type}"]) && $_FILES["file_{$type}"]['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "เกิดข้อผิดพลาดในการรับไฟล์รายการ {$type}";
        }
    } else {
        // กรณีแนบเป็นลิงก์ URL
        $link = trim($_POST["link_{$type}"] ?? '');
        if (!empty($link)) {
            if (filter_var($link, FILTER_VALIDATE_URL)) {
                $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtDel->execute([$user_id, $type]);

                $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link) VALUES (?, ?, 'link', ?)");
                $stmt->execute([$user_id, $type, $link]);
                $uploadedCount++;
            } else {
                $errors[] = "ลิงก์ URL รายการ {$type} ไม่ถูกต้อง";
            }
        }
    }
}

if ($uploadedCount > 0) {
    echo json_encode(['success' => true, 'count' => $uploadedCount]);
} else {
    $msg = count($errors) > 0 ? implode(', ', $errors) : 'กรุณาเลือกไฟล์หรือระบุลิงก์อย่างน้อย 1 รายการก่อนกดบันทึก';
    echo json_encode(['success' => false, 'message' => $msg]);
}