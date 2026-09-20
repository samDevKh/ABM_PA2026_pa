<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

$message = '';
$error = '';

// -------------------------------------------------------------
// จัดการการเพิ่มกรรมการประเมินใหม่
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_evaluator') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($fullname) && !empty($username) && !empty($password)) {
        // ตรวจสอบ Username ซ้ำ
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);

        if ($stmtCheck->rowCount() > 0) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานในระบบแล้ว';
        } else {
            // บันทึกข้อมูลกรรมการประเมิน (role = 'evaluator')
            $stmtIns = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'evaluator')");
            if ($stmtIns->execute([$username, $password, $fullname])) {
                $message = 'เพิ่มข้อมูลกรรมการประเมินเรียบร้อยแล้ว';
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    }
}

// -------------------------------------------------------------
// ดึงข้อมูลสำหรับสรุปผลและแสดงรายการ
// -------------------------------------------------------------
// 1. สถิติตามกลุ่มสาระการเรียนรู้
$chartQuery = "
    SELECT sg.name, COUNT(DISTINCT u.id) as total_teachers,
           COUNT(DISTINCT CASE WHEN d.id IS NOT NULL THEN u.id END) as uploaded_teachers
    FROM subject_groups sg
    LEFT JOIN users u ON u.subject_group_id = sg.id AND u.role = 'teacher'
    LEFT JOIN pa_documents d ON d.user_id = u.id
    GROUP BY sg.id
";
$chartData = $pdo->query($chartQuery)->fetchAll();

// 2. สถานะครูทั้งหมด
$teachersQuery = "
    SELECT u.id, u.fullname, sg.name as group_name,
           COUNT(d.id) as doc_count
    FROM users u
    LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id
    WHERE u.role = 'teacher'
    GROUP BY u.id
";
$teachersList = $pdo->query($teachersQuery)->fetchAll();

// 3. รายชื่อกรรมการประเมินทั้งหมดในระบบ
$evaluatorsQuery = "SELECT id, fullname, username FROM users WHERE role = 'evaluator' ORDER BY id DESC";
$evaluatorsList = $pdo->query($evaluatorsQuery)->fetchAll();

$page_title = "ผู้บริหาร/แอดมิน - ระบบประเมิน PA";
$header_title = "ระบบติดตามและสรุปผล PA (ผู้บริหาร/แอดมิน)";
$extra_nav_button = '
    <button onclick="openEvaluatorModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
        <i class="fa-solid fa-user-plus"></i> เพิ่มกรรมการประเมิน
    </button>';

require_once __DIR__ . '/includes/header.php';
?>

<!-- แจ้งเตือนสถานะการบันทึก -->
<?php if ($message): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: '<?= htmlspecialchars($message) ?>' });
        });
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: '<?= htmlspecialchars($error) ?>' });
        });
    </script>
<?php endif; ?>

<!-- Chart Section -->
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <h2 class="font-bold text-slate-700 mb-4">รายงานครูที่อัพโหลดเอกสารจำแนกตามกลุ่มสาระการเรียนรู้</h2>
    <div class="h-64">
        <canvas id="paChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Status Table (ครูทั้งหมด) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
            <h2 class="font-bold text-slate-700 text-sm">สถานะการส่งเอกสารของข้าราชการครู</h2>
            <span class="text-xs text-slate-500">ทั้งหมด <?= count($teachersList) ?> คน</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                    <tr>
                        <th class="p-3">ชื่อ-นามสกุล</th>
                        <th class="p-3">กลุ่มสาระฯ</th>
                        <th class="p-3">จำนวนไฟล์</th>
                        <th class="p-3">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($teachersList) === 0): ?>
                        <tr><td colspan="4" class="p-4 text-center text-slate-400">ยังไม่มีข้อมูลครูในระบบ</td></tr>
                    <?php else: ?>
                        <?php foreach ($teachersList as $row): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-3 font-medium text-slate-800"><?= htmlspecialchars($row['fullname']) ?></td>
                                <td class="p-3 text-xs"><?= htmlspecialchars($row['group_name'] ?: 'ไม่ได้ระบุ') ?></td>
                                <td class="p-3 text-xs"><?= $row['doc_count'] ?> รายการ</td>
                                <td class="p-3">
                                    <?php if ($row['doc_count'] >= 3): ?>
                                        <span class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ครบถ้วน</span>
                                    <?php elseif ($row['doc_count'] > 0): ?>
                                        <span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ส่งบางส่วน</span>
                                    <?php else: ?>
                                        <span class="bg-rose-100 text-rose-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ยังไม่ส่ง</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Evaluators Table (รายชื่อกรรมการประเมิน) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
            <h2 class="font-bold text-slate-700 text-sm flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-indigo-600"></i> กรรมการประเมิน
            </h2>
            <button onclick="openEvaluatorModal()" class="text-xs text-indigo-600 hover:underline font-semibold">+ เพิ่ม</button>
        </div>
        <div class="overflow-y-auto max-h-[350px] p-2 space-y-2">
            <?php if (count($evaluatorsList) === 0): ?>
                <div class="text-center py-8 text-xs text-slate-400">ยังไม่มีกรรมการประเมินในระบบ</div>
            <?php else: ?>
                <?php foreach ($evaluatorsList as $ev): ?>
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-slate-800"><?= htmlspecialchars($ev['fullname']) ?></p>
                            <p class="text-slate-400">Username: <?= htmlspecialchars($ev['username']) ?></p>
                        </div>
                        <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full font-semibold">กรรมการ</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal เพิ่มกรรมการประเมิน -->
<div id="evaluatorModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">เพิ่มกรรมการประเมินใหม่</h3>
            <button onclick="closeEvaluatorModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_evaluator">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล กรรมการ</label>
                <input type="text" name="fullname" placeholder="ดร.วิชัย ประเมินดี" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" name="username" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน (Password)</label>
                <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" onclick="closeEvaluatorModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="submit" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function openEvaluatorModal() {
    const modal = document.getElementById('evaluatorModal');
    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
}

function closeEvaluatorModal() {
    const modal = document.getElementById('evaluatorModal');
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
}

// Render Chart
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