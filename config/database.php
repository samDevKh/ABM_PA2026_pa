<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ดึงค่าการเชื่อมต่อจาก Environment Variables ของระบบ (หรือใช้ค่า Default)
$host = getenv('DB_HOST') ?: 'sql208.infinityfree.com';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'if0_42361149_pa_abm2026';
$user = getenv('DB_USER') ?: 'if0_42361149';
$pass = getenv('DB_PASS') ?: '90LAd2vl1S';


try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    // กรณีเชื่อมต่อ TiDB Cloud หรือฐานข้อมูลที่เปิดใช้ SSL
    if (getenv('DB_SSL') === 'true' || $port == '4000') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die("Database Connection Error: " . $e->getMessage());
}