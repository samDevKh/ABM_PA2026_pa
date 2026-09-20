<?php
require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // หมายเหตุ: ตัวอย่างนี้ใช้ password_verify หรือเปรียบเทียบตรงกรณีข้อมูลจำลอง
        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['subject_group_id'] = $user['subject_group_id'];

            // แยกไปตามสิทธิ์การใช้งาน
            if ($user['role'] === 'teacher') {
                header('Location: /teacher');
            } elseif ($user['role'] === 'evaluator') {
                header('Location: /evaluator');
            } elseif ($user['role'] === 'admin') {
                header('Location: /admin');
            }
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบประเมิน PA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-bold text-slate-800">ระบบประเมิน PA ข้าราชการครู</h1>
            <p class="text-slate-500 text-sm">เข้าสู่ระบบเพื่อจัดการและประเมินเอกสาร</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-lg text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" name="username" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน (Password)</label>
                <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg transition duration-200">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>
</body>
</html>