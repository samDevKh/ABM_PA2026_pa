<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');

switch ($path) {
    case '':
    case '/':
    case '/login':
        require __DIR__ . '/login.php';
        break;

    case '/teacher':
        require __DIR__ . '/teacher_dashboard.php';
        break;

    case '/evaluator':
        require __DIR__ . '/evaluator_dashboard.php';
        break;

    case '/admin':
        require __DIR__ . '/admin_dashboard.php';
        break;

    case '/logout':
        require __DIR__ . '/logout.php';
        break;

    // API Routes
    case '/api/upload_doc':
        require __DIR__ . '/api/upload_doc.php';
        break;

    case '/api/save_comment':
        require __DIR__ . '/api/save_comment.php';
        break;

    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>404 - ไม่พบหน้าเว็บที่ต้องการ</h1>";
        break;
}