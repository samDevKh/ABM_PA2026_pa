<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

// สถิติตามกลุ่มสาระการเรียนรู้
$chartQuery = "
    SELECT sg.name, COUNT(DISTINCT u.id) as total_teachers,
           COUNT(DISTINCT CASE WHEN d.id IS NOT NULL THEN u.id END) as uploaded_teachers
    FROM subject_groups sg
    LEFT JOIN users u ON u.subject_group_id = sg.id AND u.role = 'teacher'
    LEFT JOIN pa_documents d ON d.user_id = u.id
    GROUP BY sg.id
";
$chartData = $pdo->query($chartQuery)->fetchAll();

// สถานะครูทั้งหมด
$teachersQuery = "
    SELECT u.id, u.fullname, sg.name as group_name,
           COUNT(d.id) as doc_count,
           COUNT(DISTINCT d.doc_type) as unique_doc_types
    FROM users u
    LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id
    LEFT JOIN pa_documents d ON d.user_id = u.id
    WHERE u.role = 'teacher'
    GROUP BY u.id
";
$teachersList = $pdo->query($teachersQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้บริหาร/แอดมิน - ระบบประเมิน PA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen p-6">
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex justify-between items-center bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-xl font-bold text-slate-800">ระบบติดตามและสรุปผล PA (ผู้บริหาร/แอดมิน)</h1>
            <p class="text-slate-500 text-sm">ผู้ใช้งาน: <?= htmlspecialchars($_SESSION['fullname']) ?></p>
        </div>
        <a href="/logout" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1">
            <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
        </a>
    </div>

    <!-- Chart -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h2 class="font-bold text-slate-700 mb-4">รายงานครูที่อัพโหลดเอกสารจำแนกตามกลุ่มสาระการเรียนรู้</h2>
        <div class="h-64">
            <canvas id="paChart"></canvas>
        </div>
    </div>

    <!-- Status Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-slate-50">
            <h2 class="font-bold text-slate-700">สถานะการส่งเอกสารของข้าราชการครูทั้งหมด</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                    <tr>
                        <th class="p-3.5">ชื่อ-นามสกุล</th>
                        <th class="p-3.5">กลุ่มสาระฯ</th>
                        <th class="p-3.5">จำนวนไฟล์ที่ส่ง</th>
                        <th class="p-3.5">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($teachersList as $row): ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3.5 font-medium text-slate-800"><?= htmlspecialchars($row['fullname']) ?></td>
                            <td class="p-3.5"><?= htmlspecialchars($row['group_name'] ?: 'ไม่ได้ระบุ') ?></td>
                            <td class="p-3.5"><?= $row['doc_count'] ?> ไฟล์</td>
                            <td class="p-3.5">
                                <?php if ($row['doc_count'] >= 3): ?>
                                    <span class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-semibold">เอกสารครบถ้วน</span>
                                <?php elseif ($row['doc_count'] > 0): ?>
                                    <span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-full font-semibold">เอกสารไม่ครบ</span>
                                <?php else: ?>
                                    <span class="bg-rose-100 text-rose-700 text-xs px-2.5 py-1 rounded-full font-semibold">ยังไม่ส่ง</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const labels = <?= json_encode(array_column($chartData, 'name')) ?>;
const uploadedData = <?= json_encode(array_column($chartData, 'uploaded_teachers')) ?>;
const totalData = <?= json_encode(array_column($chartData, 'total_teachers')) ?>;

const ctx = document.getElementById('paChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'ส่งเอกสารแล้ว', data: uploadedData, backgroundColor: '#4F46E5' },
            { label: 'จำนวนครูทั้งหมด', data: totalData, backgroundColor: '#CBD5E1' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
</body>
</html>