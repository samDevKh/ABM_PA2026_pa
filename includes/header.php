<?php
   // header.php
    $site_name = "โรงเรียนอนุบาลบ้านม่วง"; 
    
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ระบบประเมิน PA ข้าราชการครู' ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- FontAwesome Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Font: Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Sarabun', sans-serif; 
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-6">

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Navigation Bar ส่วนหัวสำหรับผู้ใช้งานที่เข้าสู่ระบบแล้ว -->
<div class="max-w-6xl mx-auto mb-6 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800"><?= $header_title ?? 'ระบบประเมิน PA' ?></h1>
        <p class="text-slate-500 text-sm">
            ยินดีต้อนรับ: <span class="font-semibold text-slate-700"><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></span>
            <?php if (isset($_SESSION['role'])): ?>
                <span class="ml-2 text-xs px-2.5 py-0.5 rounded-full font-medium 
                    <?= $_SESSION['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($_SESSION['role'] === 'evaluator' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700') ?>">
                    <?= $_SESSION['role'] === 'admin' ? 'ผู้บริหาร/แอดมิน' : ($_SESSION['role'] === 'evaluator' ? 'กรรมการประเมิน' : 'ครูผู้รับการประเมิน') ?>
                </span>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
        <!-- ปุ่มพิเศษเพิ่มเติมสำหรับแต่ละหน้า (เช่น ปุ่มอัพโหลดเอกสาร หรือ เพิ่มกรรมการ) -->
        <?php if (isset($extra_nav_button)) echo $extra_nav_button; ?>
        
        <a href="/logout" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5">
            <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
        </a>
    </div>
</div>
<?php endif; ?>

<!-- คอนเทนเนอร์หลักสำหรับรองรับเนื้อหาจากหน้าต่างๆ -->
<div class="max-w-6xl mx-auto space-y-6">