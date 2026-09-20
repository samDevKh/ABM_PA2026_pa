<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$user_id = $_SESSION['user_id'];
$doc_type = $_POST['doc_type'] ?? 'other';
$upload_kind = $_POST['upload_kind'] ?? 'file';

if ($upload_kind === 'file') {
    if (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการรับไฟล์']);
        exit;
    }

    $file = $_FILES['doc_file'];
    
    // ตั้งค่า Cloudinary (ดึงจาก Environment Variable)
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $uploadPreset = getenv('CLOUDINARY_UPLOAD_PRESET');

    if (!$cloudName || !$uploadPreset) {
        echo json_encode(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Cloudinary ใน Environment Variables']);
        exit;
    }

    $cFile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    $postData = [
        'file' => $cFile,
        'upload_preset' => $uploadPreset,
        'folder' => 'pa_documents'
    ];

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['secure_url'])) {
        $fileUrl = $response['secure_url'];

        $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
        $stmt->execute([$user_id, $doc_type, $fileUrl, $file['name']]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัพโหลดไฟล์ไปยัง Cloud Storage ได้']);
    }
} else {
    $link = filter_var($_POST['doc_link'] ?? '', FILTER_VALIDATE_URL);
    if (!$link) {
        echo json_encode(['success' => false, 'message' => 'URL ลิงก์ไม่ถูกต้อง']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link) VALUES (?, ?, 'link', ?)");
    $stmt->execute([$user_id, $doc_type, $link]);

    echo json_encode(['success' => true]);
}