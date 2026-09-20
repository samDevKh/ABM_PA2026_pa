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

$page_title = "ส่วนของคุณครู - ระบบประเมิน PA";
$header_title = "ระบบอัพโหลดเอกสาร PA";
$extra_nav_button = '
    <button onclick="openUploadModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
        <i class="fa-solid fa-cloud-arrow-up"></i> แนบไฟล์อัพโหลดเอกสาร
    </button>';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Document Cards View -->
<div class="space-y-3">
    <h2 class="text-lg font-bold text-slate-700">รายการเอกสารของคุณ</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if (count($documents) === 0): ?>
            <div class="col-span-3 text-center py-12 bg-white rounded-2xl border border-slate-200 text-slate-400">
                ยังไม่มีเอกสารที่อัพโหลด กดปุ่ม "แนบไฟล์อัพโหลดเอกสาร" ด้านบนเพื่อเริ่มต้น
            </div>
        <?php else: ?>
            <?php foreach ($documents as$doc): ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition">
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
                    <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['evaluator_num']) ?></span>
                    <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                </div>
                <p class="text-slate-600 text-sm"><?= nl2br(htmlspecialchars($c['comments'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal อัพโหลดเอกสาร (เลือกได้ 3 ไฟล์) -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl p-6 space-y-5 my-8">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-lg font-bold text-slate-800">แนบอัพโหลดเอกสารประเมิน PA (3 รายการ)</h2>
            <button onclick="closeUploadModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">
            <!-- 1. เอกสาร PA2 -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">1</span> ข้อตกลงในการประเมิน (PA2)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa2" value="file" checked onclick="toggleType('pa2', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa2" value="link" onclick="toggleType('pa2', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_pa2_file">
                    <input type="file" name="file_pa2" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white">
                </div>
                <div id="input_pa2_link" class="hidden">
                    <input type="url" name="link_pa2" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white">
                </div>
            </div>

            <!-- 2. ข้อมูลประกอบ (Info) -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">2</span> ข้อมูลประกอบ (Info)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_info" value="file" checked onclick="toggleType('info', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_info" value="link" onclick="toggleType('info', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_info_file">
                    <input type="file" name="file_info" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white">
                </div>
                <div id="input_info_link" class="hidden">
                    <input type="url" name="link_info" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white">
                </div>
            </div>

            <!-- 3. รายงานผลการประเมิน PA -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">3</span> รายงานผลการประเมิน PA (รายงาน PA)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_report" value="file" checked onclick="toggleType('report', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_report" value="link" onclick="toggleType('report', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_report_file">
                    <input type="file" name="file_report" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white">
                </div>
                <div id="input_report_link" class="hidden">
                    <input type="url" name="link_report" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white">
                </div>
            </div>

            <p class="text-xs text-slate-400">* หมายเหตุ: รองรับไฟล์ PDF, PNG, JPG ขนาดไม่เกิน 15MB ต่อไฟล์ (สามารถเลือกแนบเฉพาะบางรายการได้)</p>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="submitUpload()" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">บันทึกไฟล์เอกสารทั้งหมด</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal แสดงตัวอย่างเอกสาร (Preview Modal) -->
<div id="previewModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-4xl h-[85vh] rounded-2xl shadow-xl p-4 flex flex-col">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-indigo-600"></i> ตัวอย่างเอกสาร
            </h3>
            <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div id="previewContainer" class="flex-1 border border-slate-200 rounded-xl bg-slate-100 overflow-hidden relative"></div>
    </div>
</div>

<script>
function openUploadModal() { 
    const modal = document.getElementById('uploadModal');
    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
}

function closeUploadModal() { 
    const modal = document.getElementById('uploadModal');
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
}

function closePreviewModal() { 
    const modal = document.getElementById('previewModal');
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
}

function toggleType(docKey, type) {
    const fileGroup = document.getElementById(`input_${docKey}_file`);
    const linkGroup = document.getElementById(`input_${docKey}_link`);
    if (type === 'file') {
        if (fileGroup) fileGroup.classList.remove('hidden');
        if (linkGroup) linkGroup.classList.add('hidden');
    } else {
        if (fileGroup) fileGroup.classList.add('hidden');
        if (linkGroup) linkGroup.classList.remove('hidden');
    }
}

function previewFile(url, type) {
    const container = document.getElementById('previewContainer');
    if (!container) return;
    
    if (type === 'link') { 
        window.open(url, '_blank'); 
        return; 
    }
    
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4 bg-slate-900/10"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg shadow-md"></div>`;
    }
    
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }
}

function submitUpload() {
    const formData = new FormData(document.getElementById('uploadForm'));
    const docTypes = ['pa2', 'info', 'report'];
    let hasData = false;
    let isValid = true;

    docTypes.forEach(type => {
        const kindInput = document.querySelector(`input[name="kind_${type}"]:checked`);
        if (!kindInput) return;
        const kind = kindInput.value;

        if (kind === 'file') {
            const fileInput = document.querySelector(`input[name="file_${type}"]`);
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                hasData = true;
                if (file.size > 15 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'ไฟล์มีขนาดใหญ่เกินไป', text: `ไฟล์ในรายการ ${type.toUpperCase()} มีขนาดเกิน 15 MB` });
                    isValid = false;
                }
            }
        } else {
            const linkInput = document.querySelector(`input[name="link_${type}"]`);
            if (linkInput && linkInput.value.trim() !== '') {
                hasData = true;
            }
        }
    });

    if (!isValid) return;

    if (!hasData) {
        Swal.fire({ icon: 'warning', title: 'กรุณาแนบเอกสารอย่างน้อย 1 รายการ' });
        return;
    }

    Swal.fire({ title: 'กำลังอัพโหลดเอกสาร...', text: 'โปรดรอสักครู่', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('/api/upload_doc', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeUploadModal();
            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: 'อัพโหลดเอกสารเรียบร้อยแล้ว' }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' }));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>