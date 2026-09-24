<?php
require_once __DIR__ . '/config/database.php';
$error = '';
$success = '';
$groups = $pdo->query("SELECT * FROM subject_groups ORDER BY id ASC")->fetchAll();

// 1. จัดการการเข้าสู่ระบบ (Login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['subject_group_id'] = $user['subject_group_id'];
            $_SESSION['academic_standing'] = $user['academic_standing'];

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

// 2. จัดการการลงทะเบียนใช้งาน (Register สำหรับครู)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $fullname = trim($_POST['reg_fullname'] ?? '');
    $username = trim($_POST['reg_username'] ?? '');
    $password = trim($_POST['reg_password'] ?? '');
    $subject_group_id = $_POST['reg_subject_group_id'] ?? null;
    $academic_standing = $_POST['reg_academic_standing'] ?? '';

    if (!empty($fullname) && !empty($username) && !empty($password) && !empty($subject_group_id) && !empty($academic_standing)) {
        // เช็คว่า Username ซ้ำหรือไม่
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        
        if ($stmtCheck->rowCount() > 0) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาใช้ชื่อผู้ใช้อื่น';
        } else {
            // บันทึกข้อมูลผู้ใช้ใหม่ (สิทธิ์ 'teacher') พร้อมวิทยฐานะ
            $stmtIns = $pdo->prepare("INSERT INTO users (username, password, fullname, role, subject_group_id, academic_standing) VALUES (?, ?, ?, 'teacher', ?, ?)");
            if ($stmtIns->execute([$username, $password, $fullname, $subject_group_id, $academic_standing])) {
                $newUserId = $pdo->lastInsertId();
                // ล็อกอินให้อัตโนมัติและนำเข้าสู่หน้าครู
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['role'] = 'teacher';
                $_SESSION['subject_group_id'] = $subject_group_id;
                $_SESSION['academic_standing'] = $academic_standing;
                
                header('Location: /teacher');
                exit;
            } else {
                $error = 'เกิดข้อผิดพลาดในการลงทะเบียน กรุณาลองใหม่อีกครั้ง';
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลลงทะเบียนให้ครบทุกช่อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบประเมิน PA - เข้าสู่ระบบ / ลงทะเบียน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-slate-200 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 space-y-6">
    <div class="text-center space-y-2">
        <h1 class="text-2xl font-bold text-slate-800">ระบบบริหารจัดการเอกสาร PA และแบบทดสอบ (PA & Exam DocFlow)</h1>
        <p class="text-slate-500 text-sm" id="formSubtitle">เข้าสู่ระบบเพื่อจัดการและประเมินเอกสาร</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-lg text-sm">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- ฟอร์ม 1: เข้าสู่ระบบ (Login) -->
    <form id="loginForm" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="login">
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
        <div class="text-center pt-2 border-t text-sm text-slate-600">
            ยังไม่มีชื่อในระบบ? 
            <button type="button" onclick="toggleForm('register')" class="text-indigo-600 font-semibold hover:underline">ลงทะเบียนใช้งาน</button>
        </div>
    </form>

    <!-- ฟอร์ม 2: ลงทะเบียนใช้งาน (Register) -->
    <form id="registerForm" method="POST" class="space-y-4 hidden">
        <input type="hidden" name="action" value="register">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
            <input type="text" name="reg_fullname" placeholder="นายสมชาย สายชล" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">วิทยฐานะ</label>
            <select name="reg_academic_standing" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">-- เลือกวิทยฐานะ --</option>
                <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                <option value="ครู (ยังไม่มีวิทยฐานะ)">ครู (ยังไม่มีวิทยฐานะ)</option>
                <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">กลุ่มสาระการเรียนรู้</label>
            <select name="reg_subject_group_id" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">-- เลือกกลุ่มสาระการเรียนรู้ --</option>
                <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ตั้งชื่อผู้ใช้งาน (Username)</label>
            <input type="text" name="reg_username" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ตั้งรหัสผ่าน (Password)</label>
            <input type="password" name="reg_password" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition duration-200">
            ยืนยันการลงทะเบียน
        </button>
        <div class="text-center pt-2 border-t text-sm text-slate-600">
            มีบัญชีผู้ใช้งานอยู่แล้ว? 
            <button type="button" onclick="toggleForm('login')" class="text-indigo-600 font-semibold hover:underline">เข้าสู่ระบบ</button>
        </div>
    </form>
</div>

<script>
function toggleForm(mode) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const subtitle = document.getElementById('formSubtitle');

    if (mode === 'register') {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        subtitle.innerText = 'ลงทะเบียนบัญชีผู้ใช้งานสำหรับครู';
    } else {
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        subtitle.innerText = 'เข้าสู่ระบบเพื่อจัดการและประเมินเอกสาร';
    }
}
</script>
</body>
</html>