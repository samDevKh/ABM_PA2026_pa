<?php
require_once __DIR__ . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'evaluator') {
    header('Location: /login');
    exit;
}

// ดึงกลุ่มสาระทั้งหมดมาใส่ตัวกรอง
$groups =$pdo->query("SELECT * FROM subject_groups")->fetchAll();

$selected_group =$_GET['subject_group'] ?? '';

// ดึงข้อมูลครูพร้อมเอกสาร
$sql = "SELECT u.id, u.fullname, sg.name as group_name 
        FROM users u 
        LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id 
        WHERE u.role = 'teacher'";
$params = [];

if (!empty($selected_group)) {$sql .= " AND u.subject_group_id = ?";
    $params[] =$selected_group;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers =$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้ประเมิน - ระบบประเมิน PA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen p-6">
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-xl font-bold text-slate-800">ส่วนผู้ประเมิน PA</h1>
            <p class="text-slate-500 text-sm">ผู้ประเมิน: <?= htmlspecialchars($_SESSION['fullname']) ?></p>
        </div>
        <a href="/logout" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1">
            <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between gap-4">
        <label class="font-semibold text-slate-700 text-sm">กรองตามสาระการเรียนรู้:</label>
        <select name="subject_group" onchange="this.form.submit()" class="border border-slate-300 rounded-lg p-2 text-sm w-72">
            <option value="">ทั้งหมด</option>
            <?php foreach ($groups as$g): ?>
                <option value="<?= $g['id'] ?>" <?= $selected_group ==$g['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Teacher List Card View -->
    <div class="space-y-4">
        <?php foreach ($teachers as$t): ?>
            <?php
            // ดึงเอกสารของครูแต่ละคน
            $stmt_doc =$pdo->prepare("SELECT * FROM pa_documents WHERE user_id = ?");
            $stmt_doc->execute([$t['id']]);
            $docs =$stmt_doc->fetchAll();
            ?>
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($t['fullname']) ?></h3>
                        <span class="text-xs text-slate-500">กลุ่มสาระฯ: <?= htmlspecialchars($t['group_name'] ?: 'ไม่ได้ระบุ') ?></span>
                    </div>
                    <button onclick="openCommentModal(<?= $t['id'] ?>)" class="bg-amber-500 hover:bg-amber-600 text-white text-xs px-3.5 py-2 rounded-lg font-medium transition flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square"></i> ให้คำแนะนำ
                    </button>
                </div>

                <!-- Document Cards Inside -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php if (count($docs) === 0): ?>
                        <p class="text-xs text-slate-400 col-span-4">ยังไม่มีการอัพโหลดเอกสาร</p>
                    <?php else: ?>
                        <?php foreach ($docs as$d): ?>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase">
                                    <?= htmlspecialchars($d['doc_type']) ?>
                                </span>
                                <p class="text-xs font-medium text-slate-700 truncate" title="<?= htmlspecialchars($d['original_name'] ?:$d['file_path_or_link']) ?>">
                                    <?= htmlspecialchars($d['original_name'] ?:$d['file_path_or_link']) ?>
                                </p>
                                <button onclick="previewFile('<?= htmlspecialchars($d['file_path_or_link']) ?>', '<?=$d['file_type'] ?>')" class="text-xs text-indigo-600 hover:underline font-medium">
                                    ดูตัวอย่าง
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-4xl h-[85vh] rounded-2xl shadow-xl p-4 flex flex-col">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-slate-800">ตัวอย่างเอกสาร</h3>
            <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div id="previewContainer" class="flex-1 border rounded-xl bg-slate-100 overflow-hidden"></div>
    </div>
</div>

<script>
function closePreviewModal() { document.getElementById('previewModal').classList.add('hidden'); document.getElementById('previewModal').classList.remove('flex'); }

function previewFile(url, type) {
    const container = document.getElementById('previewContainer');
    if (type === 'link') { window.open(url, '_blank'); return; }
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg"></div>`;
    }
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewModal').classList.add('flex');
}

function openCommentModal(teacherId) {
    Swal.fire({
        title: 'คำแนะนำ/ข้อเสนอแนะการประเมิน',
        input: 'textarea',
        inputPlaceholder: 'พิมพ์คำแนะนำที่นี่...',
        showCancelButton: true,
        confirmButtonText: 'บันทึกคำแนะนำ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#4F46E5',
        preConfirm: (text) => {
            if (!text) { Swal.showValidationMessage('กรุณากรอกข้อความ'); }
            return text;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/api/save_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ teacher_id: teacherId, comment: result.value })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกคำแนะนำเรียบร้อยแล้ว' });
                }
            });
        }
    });
}
</script>
</body>
</html>