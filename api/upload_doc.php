<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$user_id = $_SESSION['user_id'];
$doc_type = $_POST['doc_type'] ?? '';
$upload_kind = $_POST['upload_kind'] ?? 'file';

if ($upload_kind === 'file') {
    if (!isset($_FILES['doc_file'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ที่อัพโหลด']);
        exit;
    }

    $file = $_FILES['doc_file'];
    
    // อัพโหลดไฟล์ไปยัง Cloudinary ผ่าน API (หรือใช้ cURL)
    $cloudinaryUrl = "https://api.cloudinary.com/v1_1/" . getenv('CLOUDINARY_CLOUD_NAME') . "/auto/upload";
    
    $cFile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    $postData = [
        'file' => $cFile,
        'upload_preset' => getenv('CLOUDINARY_PRESET') // ตั้งค่า Preset ใน Cloudinary เป็น Unsigned
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $cloudinaryUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);

    if (isset($result['secure_url'])) {
        $fileUrl = $result['secure_url'];

        // บันทึก URL ของไฟล์ลง MySQL
        $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link, original_name) VALUES (?, ?, 'file', ?, ?)");
        $stmt->execute([$user_id, $doc_type, $fileUrl, $file['name']]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'อัพโหลดไฟล์ไปยัง Cloud Storage ไม่สำเร็จ']);
    }
} else {
    // กรณีเป็น ลิงก์
    $link = filter_var($_POST['doc_link'] ?? '', FILTER_VALIDATE_URL);
    if (!$link) {
        echo json_encode(['success' => false, 'message' => 'URL ไม่ถูกต้อง']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO pa_documents (user_id, doc_type, file_type, file_path_or_link) VALUES (?, ?, 'link', ?)");
    $stmt->execute([$user_id, $doc_type, $link]);

    echo json_encode(['success' => true]);
}