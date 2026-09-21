<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูล fullname เพื่อใช้เป็นชื่อโฟลเดอร์ส่วนตัว (id-fullname)
$stmtUser = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้งาน']);
    exit;
}

$folderName = $user_id . '-' . $user['fullname'];
$uploadDir = __DIR__ . '/../uploads/' . $folderName . '/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ตรวจสอบว่าเป็นการแก้ไขไฟล์เดียวหรือไม่
$singleType = $_POST['single_type'] ?? null;
$docTypes = $singleType ? [$singleType] : ['pa2', 'info', 'report'];

$uploadedCount = 0;
$errors = [];

foreach ($docTypes as $type) {
    $kind = $_POST["kind_{$type}"] ?? 'file';

    if ($kind === 'file') {
        if (isset($_FILES["file_{$type}"]) && $_FILES["file_{$type}"]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES["file_{$type}"];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'png', 'jpg', 'jpeg'];

            if (!in_array($ext, $allowedExts)) {
                $errors[] = "ไฟล์รายการ ({$type}) ต้องเป็น PDF, PNG หรือ JPG เท่านั้น";
                continue;
            }

            if ($file['size'] > 15 * 1024 * 1024) {
                $errors[] = "ไฟล์รายการ ({$type}) มีขนาดใหญ่เกิน 15MB";
                continue;
            }

            $newFileName = 'pa_' . $type . '_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = '/uploads/' . $folderName . '/' . $newFileName;

                // ดึงข้อมูลไฟล์เดิมมาลบไฟล์จริงออกจากเครื่องก่อน
                $stmtOld = $pdo->prepare("SELECT file_type, file_path_or_link FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtOld->execute([$user_id, $type]);
                $oldDoc = $stmtOld->fetch();
                if ($oldDoc && $oldDoc['file_type'] === 'file') {
                    $oldPath = __DIR__ . '/..' . $oldDoc['file_path_or_link'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // ลบ Record เดิมออก แล้วเพิ่มข้อมูลใหม่
                $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtDel->execute([$user_id, $type]);

                $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
                $stmt->execute([$user_id, $type, $filePath, $file['name']]);
                $uploadedCount++;
            } else {
                $errors[] = "ไม่สามารถย้ายไฟล์รายการ {$type} ลงโฟลเดอร์ได้";
            }
        }
    } else {
        $link = trim($_POST["link_{$type}"] ?? '');
        if (!empty($link)) {
            if (filter_var($link, FILTER_VALIDATE_URL)) {
                // ลบไฟล์เดิมถ้ามี
                $stmtOld = $pdo->prepare("SELECT file_type, file_path_or_link FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtOld->execute([$user_id, $type]);
                $oldDoc = $stmtOld->fetch();
                if ($oldDoc && $oldDoc['file_type'] === 'file') {
                    $oldPath = __DIR__ . '/..' . $oldDoc['file_path_or_link'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

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
    $msg = count($errors) > 0 ? implode(', ', $errors) : 'กรุณาเลือกไฟล์หรือระบุลิงก์อย่างน้อย 1 รายการ';
    echo json_encode(['success' => false, 'message' => $msg]);
}