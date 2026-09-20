<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: /login');
    exit;
}

$user_id =$_SESSION['user_id'];

// ดึงรายการเอกสารของครูคนนี้
$stmt =$pdo->prepare("SELECT * FROM pa_documents WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$documents =$stmt->fetchAll();

// ดึงข้อเสนอแนะจากผู้ประเมิน
$stmt_comment =$pdo->prepare("
    SELECT e.*, u.fullname as evaluator_name 
    FROM evaluations e 
    JOIN users u ON e.evaluator_id = u.id 
    WHERE e.teacher_id = ? 
    ORDER BY e.created_at DESC
");
$stmt_comment->execute([$user_id]);
$comments =$stmt_comment->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่วนของคุณครู - ระบบประเมิน PA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen p-6">
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Navbar -->
    <div class="flex justify-between items-center bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-xl font-bold text-slate-800">ระบบอัพโหลดเอกสาร PA</h1>
            <p class="text-slate-500 text-sm">ยินดีต้อนรับ: <?= htmlspecialchars($_SESSION['fullname']) ?></p>
        </div>
        <div class="flex gap-2">
            <button onclick="openUploadModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> แนบไฟล์อัพโหลดเอกสาร
            </button>
            <a href="/logout" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>
    </div>

    <!-- Document Cards View -->
    <div class="space-y-3">
        <h2 class="text-lg font-bold text-slate-700">รายการเอกสารของคุณ</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php if (count($documents) === 0): ?>
                <div class="col-span-3 text-center py-12 bg-white rounded-xl border border-slate-200 text-slate-400">
                    ยังไม่มีเอกสารที่อัพโหลด กดปุ่ม "แนบไฟล์อัพโหลดเอกสาร" ด้านบนเพื่อเริ่มต้น
                </div>
            <?php else: ?>
                <?php foreach ($documents as$doc): ?>
                    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-indigo-50 text-indigo-700 text-xs font-semibold px-2.5 py-1 rounded-full uppercase">
                                <?= htmlspecialchars($doc['doc_type']) ?>
                            </span>
                            <i class="<?= $doc['file_type'] === 'file' ? 'fa-solid fa-file-pdf text-rose-500' : 'fa-solid fa-link text-blue-500' ?> text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-slate-800 truncate" title="<?= htmlspecialchars($doc['original_name'] ?:$doc['file_path_or_link']) ?>">
                            <?= htmlspecialchars($doc['original_name'] ?:$doc['file_path_or_link']) ?>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">วันที่อัพโหลด: <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <button onclick="previewFile('<?= htmlspecialchars($doc['file_path_or_link']) ?>', '<?=$doc['file_type'] ?>')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                                <i class="fa-solid fa-eye"></i> ดูตัวอย่างเอกสาร
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Evaluator Comments -->
    <?php if (count($comments) > 0): ?>
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <i class="fa-solid fa-comments text-amber-500"></i> คำแนะนำจากผู้ประเมิน
        </h2>
        <div class="space-y-3">
            <?php foreach ($comments as$c): ?>
                <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-200/60">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['evaluator_name']) ?></span>
                        <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                    </div>
                    <p class="text-slate-600 text-sm"><?= nl2br(htmlspecialchars($c['comments'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-6 space-y-4">
        <h2 class="text-lg font-bold text-slate-800">แนบไฟล์อัพโหลดเอกสาร</h2>
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ประเภทเอกสาร</label>
                <select name="doc_type" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm">
                    <option value="pa2">ข้อตกลงในการประเมิน (PA2)</option>
                    <option value="report">รายงานผลการประเมิน PA</option>
                    <option value="info">ข้อมูลประกอบ (Info)</option>
                    <option value="other">อื่นๆ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รูปแบบการแนบ</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="upload_kind" value="file" checked onclick="toggleUploadType('file')"> ไฟล์เอกสาร</label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" name="upload_kind" value="link" onclick="toggleUploadType('link')"> ลิงก์ URL</label>
                </div>
            </div>

            <div id="fileInputGroup">
                <label class="block text-sm font-medium text-slate-700 mb-1">เลือกไฟล์ (PDF, PNG, JPG ไม่เกิน 15MB)</label>
                <input type="file" id="doc_file" name="doc_file" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-sm p-2">
            </div>

            <div id="linkInputGroup" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">ระบุ URL ลิงก์เอกสาร</label>
                <input type="url" id="doc_link" name="doc_link" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2.5 text-sm">
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="submitUpload()" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">บันทึกไฟล์เอกสาร</button>
            </div>
        </form>
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
function openUploadModal() { document.getElementById('uploadModal').classList.remove('hidden'); document.getElementById('uploadModal').classList.add('flex'); }
function closeUploadModal() { document.getElementById('uploadModal').classList.add('hidden'); document.getElementById('uploadModal').classList.remove('flex'); }
function closePreviewModal() { document.getElementById('previewModal').classList.add('hidden'); document.getElementById('previewModal').classList.remove('flex'); }

function toggleUploadType(type) {
    if(type === 'file') {
        document.getElementById('fileInputGroup').classList.remove('hidden');
        document.getElementById('linkInputGroup').classList.add('hidden');
    } else {
        document.getElementById('fileInputGroup').classList.add('hidden');
        document.getElementById('linkInputGroup').classList.remove('hidden');
    }
}

function submitUpload() {
    const kind = document.querySelector('input[name="upload_kind"]:checked').value;
    const formData = new FormData(document.getElementById('uploadForm'));

    if (kind === 'file') {
        const fileInput = document.getElementById('doc_file');
        const file = fileInput.files[0];
        if (!file) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์เอกสาร' });
            return;
        }
        if (file.size > 15 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'ขนาดไฟล์เกินกำหนด', text: 'ต้องไม่เกิน 15 MB' });
            return;
        }
        const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({ icon: 'error', title: 'ประเภทไฟล์ไม่ถูกต้อง', text: 'รองรับเฉพาะ PDF, PNG, JPG' });
            return;
        }
    } else {
        if (!document.getElementById('doc_link').value) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก URL ลิงก์' });
            return;
        }
    }

    Swal.fire({ title: 'กำลังบันทึกเอกสาร...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('/api/upload_doc', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            closeUploadModal();
            Swal.fire({ icon: 'success', title: 'อัพโหลดสำเร็จ!', text: 'บันทึกไฟล์เอกสารเรียบร้อยแล้ว' }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
        }
    });
}

function previewFile(url, type) {
    const container = document.getElementById('previewContainer');
    if (type === 'link') {
        window.open(url, '_blank');
        return;
    }
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg"></div>`;
    }
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewModal').classList.add('flex');
}
</script>
</body>
</html>