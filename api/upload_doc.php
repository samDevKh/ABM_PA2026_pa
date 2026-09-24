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

// เพิ่มรายการ 'salary' เข้าไปในอาร์เรย์รายการเอกสาร
$docTypes = ['pa1', 'pa2', 'pa3', 'info', 'report', 'salary', 'other'];

// กำหนดโฟลเดอร์สำหรับจัดเก็บไฟล์
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uploadedCount = 0;

try {
    // 1. กรณีส่งแบบแก้ไขเอกสารเฉพาะรายการ (single_type)
    if (isset($_POST['single_type'])) {
        $type = $_POST['single_type'];
        $kind = $_POST["kind_{$type}"] ?? 'file';

        if ($kind === 'file' && isset($_FILES["file_{$type}"]) && $_FILES["file_{$type}"]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES["file_{$type}"];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = $user_id . '_' . $type . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $newFileName;
            $dbPath = '/uploads/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // ลบข้อมูลเดิมของประเภทนี้ก่อนอัปเดตใหม่
                $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtDel->execute([$user_id, $type]);

                // บันทึกข้อมูลใหม่
                $stmtIns = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
                $stmtIns->execute([$user_id, $type, $dbPath, $file['name']]);
                $uploadedCount++;
            }
        } elseif ($kind === 'link' && !empty($_POST["link_{$type}"])) {
            $link = trim($_POST["link_{$type}"]);
            
            $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
            $stmtDel->execute([$user_id, $type]);

            $stmtIns = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'link', ?, ?)");
            $stmtIns->execute([$user_id, $type, $link, $link]);
            $uploadedCount++;
        }
    } 
    // 2. กรณีการอัปโหลดรวมทุกรายการผ่านแบบฟอร์มหลัก
    else {
        foreach ($docTypes as $type) {
            $kind = $_POST["kind_{$type}"] ?? 'file';

            if ($kind === 'file' && isset($_FILES["file_{$type}"]) && $_FILES["file_{$type}"]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES["file_{$type}"];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFileName = $user_id . '_' . $type . '_' . time() . '.' . $ext;
                $destination = $uploadDir . $newFileName;
                $dbPath = '/uploads/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                    $stmtDel->execute([$user_id, $type]);

                    $stmtIns = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
                    $stmtIns->execute([$user_id, $type, $dbPath, $file['name']]);
                    $uploadedCount++;
                }
            } elseif ($kind === 'link' && !empty($_POST["link_{$type}"])) {
                $link = trim($_POST["link_{$type}"]);

                $stmtDel = $pdo->prepare("DELETE FROM pa_documents WHERE user_id = ? AND doc_type = ?");
                $stmtDel->execute([$user_id, $type]);

                $stmtIns = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'link', ?, ?)");
                $stmtIns->execute([$user_id, $type, $link, $link]);
                $uploadedCount++;
            }
        }
    }

    if ($uploadedCount > 0) {
        echo json_encode(['success' => true, 'message' => 'บันทึกอัปโหลดเอกสารเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'กรุณาเลือกไฟล์หรือระบุลิงก์อย่างน้อย 1 รายการ']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()]);
}