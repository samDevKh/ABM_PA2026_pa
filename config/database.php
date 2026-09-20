<?php
$host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$db   = getenv('DB_NAME') ?: 'pa_system';
$user = getenv('DB_USER') ?: '3HASw3ZZG31bSpm.root';
$pass = getenv('DB_PASS') ?: '4NgLXjROEKTfMzcA';
$port = getenv('DB_PORT') ?: '4000';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // หากเป็น MySQL บน Cloud บางเจ้าอาจต้องใช้ SSL
    if (getenv('DB_SSL') === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die("Database Connection Error: " . $e->getMessage());
}