<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

$message = '';
$error = '';
// 1. จัดการการเพิ่มผู้ประเมินใหม่ (Evaluator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) &&$_POST['action'] === 'add_evaluator') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($fullname) && !empty($username) && !empty($password)) {
        // เช็คว่า username ซ้ำหรือไม่
        $stmtChk =$pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtChk->execute([$username]);
        if ($stmtChk->rowCount() > 0) {$_SESSION['flash_error'] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        } else {
            $stmtIns =$pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'evaluator')");
            if ($stmtIns->execute([$username,$password, $fullname])) {$_SESSION['flash_success'] = 'เพิ่มบัญชีผู้ประเมินเรียบร้อยแล้ว';
            } else {
                $_SESSION['flash_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            }
        }
    } else {
        $_SESSION['flash_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
    header('Location: /admin');
    exit;
}

// 2. ดึงข้อมูลสถิติภาพรวมสำหรับ Chart.js
$totalTeachers =$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$totalEvaluators =$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'evaluator'")->fetchColumn();
$totalDocs =$pdo->query("SELECT COUNT(*) FROM pa_documents")->fetchColumn();

// ดึงข้อมูลครูทุกคนพร้อมกลุ่มสาระ และจำนวนกรรมการที่ประเมินแล้ว
$teachersStmt =$pdo->query("
    SELECT u.id, u.fullname, u.academic_standing, sg.name as group_name,
           (SELECT COUNT(DISTINCT evaluator_id) FROM evaluations WHERE teacher_id = u.id) as eval_count
    FROM users u
    LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id
    WHERE u.role = 'teacher'
    ORDER BY u.fullname ASC
");
$teachers =$teachersStmt->fetchAll();

// สรุปสถิติจำนวนครูตามกลุ่มสาระการเรียนรู้สำหรับแสดงใน Chart
$chartStmt =$pdo->query("
    SELECT sg.name, COUNT(u.id) as total 
    FROM subject_groups sg 
    LEFT JOIN users u ON sg.id = u.subject_group_id AND u.role = 'teacher'
    GROUP BY sg.id
");
$chartData =$chartStmt->fetchAll();
$chartLabels = array_column($chartData, 'name');
$chartValues = array_column($chartData, 'total');

// ดึงรายชื่อผู้ประเมินทั้งหมด
$evaluators =$pdo->query("SELECT * FROM users WHERE role = 'evaluator' ORDER BY id DESC")->fetchAll();

$page_title = "ผู้บริหาร / แอดมิน - ระบบประเมิน PA";
$header_title = "แดชบอร์ดผู้บริหาร / แอดมิน";

require_once __DIR__ . '/includes/header.php';
?>

<!-- Alert Notification -->
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm mb-4">
        <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<!-- Summary Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">จำนวนครูในระบบ</p>
            <h3 class="text-3xl font-bold text-slate-800 mt-1"><?= number_format($totalTeachers) ?> <span class="text-sm font-normal text-slate-500">คน</span></h3>
        </div>
        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">จำนวนผู้ประเมิน</p>
            <h3 class="text-3xl font-bold text-slate-800 mt-1"><?= number_format($totalEvaluators) ?> <span class="text-sm font-normal text-slate-500">คน</span></h3>
        </div>
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-user-check"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">เอกสารอัปโหลดทั้งหมด</p>
            <h3 class="text-3xl font-bold text-slate-800 mt-1"><?= number_format($totalDocs) ?> <span class="text-sm font-normal text-slate-500">ฉบับ</span></h3>
        </div>
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-folder-open"></i>
        </div>
    </div>
</div>

<!-- Chart & Add Evaluator Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    
    <!-- Chart Section (2 Cols) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
            <i class="fa-solid fa-chart-pie text-indigo-600"></i> สถิติจำนวนครูจำแนกตามกลุ่มสาระการเรียนรู้
        </h3>
        <div class="relative h-64 w-full">
            <canvas id="subjectChart"></canvas>
        </div>
    </div>

    <!-- Add Evaluator Form (1 Col) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm border-b pb-3">
            <i class="fa-solid fa-user-plus text-emerald-600"></i> เพิ่มบัญชีกรรมการประเมิน
        </h3>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="add_evaluator">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">ชื่อ-นามสกุล กรรมการ</label>
                <input type="text" name="fullname" placeholder="นายวิชัย ใฝ่เรียนรู้" required class="w-full border border-slate-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" name="username" required class="w-full border border-slate-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">รหัสผ่าน (Password)</label>
                <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium py-2.5 rounded-lg transition">
                + เพิ่มผู้ประเมิน
            </button>
        </form>
    </div>

</div>

<!-- PA3 Summary List Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b border-slate-100 font-bold text-slate-800 flex justify-between items-center">
        <span><i class="fa-solid fa-file-signature text-indigo-600 mr-2"></i> ติดตามและสรุปผลการประเมิน PA3 รายบุคคล</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                <tr>
                    <th class="p-3.5">ชื่อ-นามสกุล / วิทยฐานะ</th>
                    <th class="p-3.5">กลุ่มสาระการเรียนรู้</th>
                    <th class="p-3.5 text-center">สถานะการประเมิน</th>
                    <th class="p-3.5 text-center">พิมพ์เอกสาร</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (count($teachers) === 0): ?>
                    <tr><td colspan="4" class="p-6 text-center text-slate-400">ยังไม่มีข้อมูลครูในระบบ</td></tr>
                <?php else: ?>
                    <?php foreach ($teachers as$t): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-medium text-slate-800">
                                <?= htmlspecialchars($t['fullname']) ?>
                                <span class="block text-xs text-amber-700 font-normal mt-0.5">
                                    <i class="fa-solid fa-award mr-1"></i> <?= htmlspecialchars($t['academic_standing'] ?: 'ครู (ยังไม่มีวิทยฐานะ)') ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-600"><?= htmlspecialchars($t['group_name'] ?: 'ยังไม่ระบุ') ?></td>
                            <td class="p-3.5 text-center">
                                <?php if ($t['eval_count'] >= 3): ?>
                                    <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> ประเมินครบ 3 ท่านแล้ว
                                    </span>
                                <?php else: ?>
                                    <span class="bg-amber-100 text-amber-800 text-xs font-medium px-3 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock"></i> ประเมินแล้ว <?= $t['eval_count'] ?> / 3 ท่าน
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-center">
                                <button onclick="viewPA3Summary(<?= $t['id'] ?>)" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3.5 py-1.5 rounded-xl font-medium transition flex items-center gap-1.5 mx-auto">
                                    <i class="fa-solid fa-file-contract"></i> แบบสรุป PA3
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal สรุปผล PA3 (PA3 Summary View & Print Modal) -->
<div id="pa3Modal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-8 space-y-6 my-8 print:m-0 print:p-0 print:shadow-none print:w-full">
        
        <!-- Header (Non-Print Buttons) -->
        <div class="flex justify-between items-center border-b pb-4 print:hidden">
            <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-file-signature text-indigo-600 mr-2"></i> แบบสรุปผลการประเมิน PA3</h2>
            <div class="flex gap-2">
                <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-medium transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-print"></i> พิมพ์แบบสรุป PA3
                </button>
                <button onclick="closePA3Modal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
        </div>

        <!-- PA3 Printable Content -->
        <div id="pa3PrintContent" class="space-y-6 text-slate-900 font-sarabun p-2">
            <div class="text-center space-y-1">
                <h2 class="text-base font-bold">แบบสรุปผลการประเมินการพัฒนางานตามข้อตกลง (PA)</h2>
                <h3 class="text-sm font-bold">สำหรับข้าราชการครูและบุคลากรทางการศึกษา ตำแหน่ง ครู</h3>
                <p class="text-xs">โรงเรียนอนุบาลบ้านม่วง สำนักงานเขตพื้นที่การศึกษาประถมศึกษาสกลนคร เขต 3</p>
            </div>

            <!-- ข้อมูลผู้รับการประเมิน -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2">
                <div class="grid grid-cols-2 gap-4">
                    <p><strong>ชื่อ-นามสกุล ผู้รับการประเมิน:</strong> <span id="pa3TeacherName">-</span></p>
                    <p><strong>ตำแหน่ง / วิทยฐานะ:</strong> <span id="pa3TeacherStanding">-</span></p>
                </div>
                <p><strong>กลุ่มสาระการเรียนรู้:</strong> <span id="pa3TeacherGroup">-</span></p>
                <p class="pt-1">ภาระงาน: <span class="font-semibold">☑ เป็นไปตามที่ ก.ค.ศ. กำหนด</span></p>
            </div>

            <!-- ตารางคะแนนจากกรรมการ 3 คน -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 text-center font-bold border-b border-slate-300">
                            <th class="p-2.5 border border-slate-300 w-1/2">การประเมินข้อตกลงในการพัฒนางาน</th>
                            <th class="p-2.5 border border-slate-300">คะแนนเต็ม</th>
                            <th class="p-2.5 border border-slate-300">คนที่ 1</th>
                            <th class="p-2.5 border border-slate-300">คนที่ 2</th>
                            <th class="p-2.5 border border-slate-300">คนที่ 3</th>
                        </tr>
                    </thead>
                    <tbody id="pa3TableBody" class="text-center divide-y divide-slate-200">
                        <!-- Dynamic Content Inserted via JS -->
                    </tbody>
                </table>
            </div>

            <!-- สรุปผลผ่าน/ไม่ผ่าน -->
            <div class="p-4 rounded-xl border text-xs space-y-2" id="pa3ResultBox">
                <p class="font-bold text-sm">สรุปผลการประเมินทั้ง 2 ส่วน จากกรรมการ 3 คน:</p>
                <div class="flex gap-6 items-center pt-1" id="pa3StatusCheckboxes">
                    <!-- Checkbox Pass / Fail -->
                </div>
                <p class="text-[11px] text-slate-500 italic">* เกณฑ์การประเมิน: ต้องได้คะแนนจากกรรมการแต่ละคนไม่ต่ำกว่าร้อยละ 70% (70 คะแนนขึ้นไป)</p>
            </div>

            <!-- เซ็นชื่อกรรมการ 3 ท่าน -->
            <div class="pt-8 grid grid-cols-3 gap-4 text-center text-xs space-y-0">
                <div class="space-y-8">
                    <p>(ลงชื่อ).....................................................</p>
                    <p class="font-medium" id="pa3Sign1">( ประธานกรรมการผู้ประเมิน )</p>
                    <p class="text-slate-500">วันที่ ........ เดือน .................... พ.ศ. ......</p>
                </div>
                <div class="space-y-8">
                    <p>(ลงชื่อ).....................................................</p>
                    <p class="font-medium" id="pa3Sign2">( กรรมการผู้ประเมิน )</p>
                    <p class="text-slate-500">วันที่ ........ เดือน .................... พ.ศ. ......</p>
                </div>
                <div class="space-y-8">
                    <p>(ลงชื่อ).....................................................</p>
                    <p class="font-medium" id="pa3Sign3">( กรรมการผู้ประเมิน )</p>
                    <p class="text-slate-500">วันที่ ........ เดือน .................... พ.ศ. ......</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 1. สร้าง Chart.js
const ctx = document.getElementById('subjectChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'จำนวนครู (คน)',
            data: <?= json_encode($chartValues) ?>,
            backgroundColor: '#4F46E5',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// 2. เรียกดูสรุปผล PA3 รายบุคคล
function viewPA3Summary(teacherId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล PA3...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch(`api/get_pa3_summary.php?teacher_id=${teacherId}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            document.getElementById('pa3TeacherName').innerText = data.teacher.fullname;
            document.getElementById('pa3TeacherStanding').innerText = data.teacher.academic_standing;
            document.getElementById('pa3TeacherGroup').innerText = data.teacher.group_name;

            const evs = data.evaluators;
            const tbody = document.getElementById('pa3TableBody');
            tbody.innerHTML = `
                <tr>
                    <td class="p-2.5 border border-slate-300 text-left">ส่วนที่ 1 ข้อตกลงในการพัฒนางานตามมาตรฐานตำแหน่ง</td>
                    <td class="p-2.5 border border-slate-300 font-bold">60</td>
                    <td class="p-2.5 border border-slate-300">${evs[0].sec1}</td>
                    <td class="p-2.5 border border-slate-300">${evs[1].sec1}</td>
                    <td class="p-2.5 border border-slate-300">${evs[2].sec1}</td>
                </tr>
                <tr>
                    <td class="p-2.5 border border-slate-300 text-left">ส่วนที่ 2 ข้อตกลงในการพัฒนางาน ที่เสนอเป็นประเด็นท้าทายฯ</td>
                    <td class="p-2.5 border border-slate-300 font-bold">40</td>
                    <td class="p-2.5 border border-slate-300">${evs[0].sec2}</td>
                    <td class="p-2.5 border border-slate-300">${evs[1].sec2}</td>
                    <td class="p-2.5 border border-slate-300">${evs[2].sec2}</td>
                </tr>
                <tr class="bg-slate-50 font-bold">
                    <td class="p-2.5 border border-slate-300 text-left">รวมคะแนนทั้งหมด</td>
                    <td class="p-2.5 border border-slate-300">100</td>
                    <td class="p-2.5 border border-slate-300 text-indigo-700">${evs[0].total}</td>
                    <td class="p-2.5 border border-slate-300 text-indigo-700">${evs[1].total}</td>
                    <td class="p-2.5 border border-slate-300 text-indigo-700">${evs[2].total}</td>
                </tr>
            `;

            const box = document.getElementById('pa3ResultBox');
            const checks = document.getElementById('pa3StatusCheckboxes');
            
            if (data.completed_count < 3) {
                box.className = "p-4 rounded-xl border border-amber-200 bg-amber-50 text-xs space-y-2";
                checks.innerHTML = `<span class="text-amber-800 font-bold">⚠️ ยังประเมินไม่ครบทั้ง 3 ท่าน (ประเมินแล้ว ${data.completed_count}/3 ท่าน)</span>`;
            } else if (data.is_all_pass) {
                box.className = "p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-xs space-y-2";
                checks.innerHTML = `
                    <span class="font-bold text-emerald-800 text-sm">☑ ผ่านเกณฑ์</span>
                    <span class="text-slate-400 text-sm">☐ ไม่ผ่านเกณฑ์</span>
                `;
            } else {
                box.className = "p-4 rounded-xl border border-rose-200 bg-rose-50 text-xs space-y-2";
                checks.innerHTML = `
                    <span class="text-slate-400 text-sm">☐ ผ่านเกณฑ์</span>
                    <span class="font-bold text-rose-800 text-sm">☑ ไม่ผ่านเกณฑ์</span>
                `;
            }

            document.getElementById('pa3Sign1').innerText = `( ${evs[0].name} )`;
            document.getElementById('pa3Sign2').innerText = `( ${evs[1].name} )`;
            document.getElementById('pa3Sign3').innerText = `( ${evs[2].name} )`;

            const modal = document.getElementById('pa3Modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
        }
    });
}

function closePA3Modal() {
    const modal = document.getElementById('pa3Modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>