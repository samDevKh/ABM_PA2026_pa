<?php
require_once __DIR__ . '/config/database.php';
if (!isset($_SESSION['user_id']) ||$_SESSION['role'] !== 'teacher') {
    header('Location: /login');
    exit;
}

$user_id =$_SESSION['user_id'];

// ดึงรายการเอกสารทั้งหมดของครูท่านนี้ (ทั้งที่เป็นไฟล์และลิงก์)[cite: 1, 2]
$stmt =$pdo->prepare("SELECT * FROM pa_documents WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);$raw_documents = $stmt->fetchAll();$documents = [];

foreach ($raw_documents as$doc) {
    if ($doc['file_type'] === 'link') {
        $documents[] =$doc;
    } else {
        $realPath = __DIR__ .$doc['file_path_or_link'];
        if (file_exists($realPath)) {
            $documents[] =$doc;
        }
    }
}

// ดึงข้อเสนอแนะจากผู้ประเมิน[cite: 2]
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

// วางปุ่ม "ส่งข้อสอบ" ด้านขวาของปุ่ม "แนบไฟล์ / แนบลิงก์เอกสาร"
$extra_nav_button = '
<div class="flex items-center gap-2">
    <button onclick="openUploadModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2">
        <i class="fa-solid fa-cloud-arrow-up"></i> แนบไฟล์ / แนบลิงก์เอกสาร
    </button>
    <button onclick="openExamModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2">
        <i class="fa-solid fa-file-circle-check"></i> ส่งข้อสอบ
    </button>
</div>';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Document Cards View -->
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-folder-closed text-indigo-600"></i> รายการเอกสารของคุณ
        </h2>
        <span class="text-xs text-slate-500">ทั้งหมด <?= count($documents) ?> รายการ</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (count($documents) === 0): ?>
            <div class="col-span-full text-center py-12 bg-white rounded-2xl border border-slate-200 text-slate-400 space-y-3">
                <i class="fa-solid fa-folder-open text-4xl text-slate-300"></i>
                <p>ยังไม่มีเอกสารที่อัพโหลด กดปุ่มด้านบนเพื่อเริ่มต้น</p>
            </div>
        <?php else: ?>
            <?php foreach ($documents as$doc): ?>
                <?php $isLink = ($doc['file_type'] === 'link'); ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition space-y-3 flex flex-col justify-between">
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-start gap-2">
                            <span class="bg-indigo-50 text-indigo-700 text-xs font-semibold px-2.5 py-1 rounded-full uppercase border border-indigo-100">
                                <?php
                                $docTypeLabel = [
                                    'pa1' => '1. แบบบันทึกข้อตกลงในการพัฒนางาน (PA1)',
                                    'pa2' => '2. แบบประเมินผลการปฏิบัติงาน (PA2)',
                                    'pa3' => '3. สรุปผลการประเมิน (PA3)',
                                    'info' => '4. รายงาน (Infographic)',
                                    'report' => '5. รายงานผลการปฏิบัติงานตามข้อตกลง (≤20หน้า)',
                                    'salary' => '6. รายงานผลการปฏิบัติงานเพื่อเลื่อนเงินเดือน',
                                    'other' => '7. อื่น ๆ',
                                    'exam' => 'เอกสารข้อสอบ'
                                ];
                                echo htmlspecialchars($docTypeLabel[$doc['doc_type']] ?? $doc['doc_type']);
                                ?>
                            </span>

                            <?php if ($isLink): ?>
                                <span class="bg-sky-50 text-sky-600 border border-sky-200 text-[11px] font-medium px-2 py-0.5 rounded-md flex items-center gap-1 shrink-0">
                                    <i class="fa-solid fa-link"></i> ลิงก์ภายนอก
                                </span>
                            <?php else: ?>
                                <span class="bg-rose-50 text-rose-600 border border-rose-200 text-[11px] font-medium px-2 py-0.5 rounded-md flex items-center gap-1 shrink-0">
                                    <i class="fa-solid fa-file-pdf"></i> ไฟล์เอกสาร
                                </span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm truncate" title="<?= htmlspecialchars($doc['original_name'] ?:$doc['file_path_or_link']) ?>">
                                <?= htmlspecialchars($doc['original_name'] ?:$doc['file_path_or_link']) ?>
                            </h3>
                            
                            <?php if ($isLink): ?>
                                <a href="<?= htmlspecialchars($doc['file_path_or_link']) ?>" target="_blank" class="text-xs text-sky-600 hover:underline truncate block mt-1 flex items-center gap-1">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> <?= htmlspecialchars($doc['file_path_or_link']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <p class="text-[11px] text-slate-400">วันที่อัพโหลด: <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>

                        <div class="flex items-center justify-between text-xs font-medium pt-1">
                            <button onclick="previewFile('<?= htmlspecialchars($doc['file_path_or_link']) ?>', '<?=$doc['file_type'] ?>')" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                                <?php if ($isLink): ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> เปิดลิงก์
                                <?php else: ?>
                                    <i class="fa-solid fa-eye"></i> ดูตัวอย่าง
                                <?php endif; ?>
                            </button>

                            <div class="flex items-center gap-2">
                                <?php if ($doc['doc_type'] !== 'exam'): ?>
                                    <button onclick="openEditModal('<?= $doc['doc_type'] ?>')" class="text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                    </button>
                                <?php endif; ?>
                                <button onclick="deleteDoc(<?= $doc['id'] ?>)" class="text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2 py-1.5 rounded-lg transition flex items-center gap-1" title="ลบเอกสาร">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: ส่งข้อสอบ (เพิ่มช่องแนบไฟล์ได้ไม่เกิน 5 ช่อง) -->
<div id="examModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-6 space-y-5 my-8">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-check text-emerald-600"></i> ส่งข้อสอบ
            </h2>
            <button onclick="closeExamModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        
        <form id="examForm" enctype="multipart/form-data" class="space-y-4">
            <div id="examInputsContainer" class="space-y-3">
                <!-- ช่องแนบไฟล์ที่ 1 (เริ่มต้น) -->
                <div class="exam-input-item p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                    <label class="block text-xs font-bold text-slate-700">ไฟล์ข้อสอบที่ 1</label>
                    <input type="file" name="exam_files[]" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white" required>
                </div>
            </div>

            <!-- ปุ่มเพิ่มช่องแนบไฟล์ -->
            <div class="flex justify-between items-center pt-2">
                <button type="button" id="btnAddExamInput" onclick="addExamInput()" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold px-3 py-2 rounded-lg border border-emerald-200 transition flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> เพิ่มช่องแนบไฟล์ (<span id="examFileCountText">1</span>/5)
                </button>
                <span class="text-[11px] text-slate-400">* แนบไฟล์ได้สูงสุด 5 ไฟล์</span>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" onclick="closeExamModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="submitExam()" class="px-5 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">บันทึกส่งข้อสอบ</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 1: อัพโหลดเอกสาร PA รวม (7 รายการ) -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl p-6 space-y-5 my-8">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-lg font-bold text-slate-800">แนบอัพโหลดเอกสารประเมิน PA</h2>
            <button onclick="closeUploadModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            
            <!-- 1. PA1 -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">1</span> แบบบันทึกข้อตกลงในการพัฒนางาน (PA1)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa1" value="file" checked onclick="toggleType('pa1', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa1" value="link" onclick="toggleType('pa1', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_pa1_file"><input type="file" name="file_pa1" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_pa1_link" class="hidden"><input type="url" name="link_pa1" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 2. PA2 -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">2</span> แบบประเมินผลการปฏิบัติงาน (PA2)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa2" value="file" checked onclick="toggleType('pa2', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa2" value="link" onclick="toggleType('pa2', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_pa2_file"><input type="file" name="file_pa2" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_pa2_link" class="hidden"><input type="url" name="link_pa2" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 3. PA3 -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">3</span> สรุปผลการประเมิน (PA3)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa3" value="file" checked onclick="toggleType('pa3', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_pa3" value="link" onclick="toggleType('pa3', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_pa3_file"><input type="file" name="file_pa3" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_pa3_link" class="hidden"><input type="url" name="link_pa3" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 4. Infographic -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">4</span> รายงาน (Infographic)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_info" value="file" checked onclick="toggleType('info', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_info" value="link" onclick="toggleType('info', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_info_file"><input type="file" name="file_info" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_info_link" class="hidden"><input type="url" name="link_info" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 5. รายงานผล PA (ไม่เกิน 20 หน้า) -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">5</span> รายงานผลการปฏิบัติงานตามข้อตกลง (ไม่เกิน 20 หน้า)
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_report" value="file" checked onclick="toggleType('report', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_report" value="link" onclick="toggleType('report', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_report_file"><input type="file" name="file_report" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_report_link" class="hidden"><input type="url" name="link_report" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 6. รายงานผลการปฏิบัติงานเพื่อประกอบการพิจารณาเลื่อนเงินเดือน -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">6</span> รายงานผลการปฏิบัติงานเพื่อประกอบการพิจารณาเลื่อนเงินเดือน
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_salary" value="file" checked onclick="toggleType('salary', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_salary" value="link" onclick="toggleType('salary', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_salary_file"><input type="file" name="file_salary" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_salary_link" class="hidden"><input type="url" name="link_salary" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <!-- 7. อื่น ๆ -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-bold text-slate-700 text-sm flex items-center gap-2">
                        <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">7</span> อื่น ๆ
                    </label>
                    <div class="flex gap-3 text-xs">
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_other" value="file" checked onclick="toggleType('other', 'file')"> ไฟล์</label>
                        <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="kind_other" value="link" onclick="toggleType('other', 'link')"> ลิงก์</label>
                    </div>
                </div>
                <div id="input_other_file"><input type="file" name="file_other" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white"></div>
                <div id="input_other_link" class="hidden"><input type="url" name="link_other" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2 text-xs bg-white"></div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="submitUpload()" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">บันทึกอัพโหลด</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ดูตัวอย่างเอกสาร -->
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
// --- ส่วนควบคุม Modal ส่งข้อสอบ (ไม่เกิน 5 ช่อง) ---
let examInputCount = 1;

function openExamModal() {
    const modal = document.getElementById('examModal');
    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
}

function closeExamModal() {
    const modal = document.getElementById('examModal');
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
}

function addExamInput() {
    if (examInputCount >= 5) {
        Swal.fire({ icon: 'warning', title: 'ถึงขีดจำกัด', text: 'สามารถเพิ่มช่องแนบไฟล์ได้สูงสุด 5 ช่องเท่านั้น' });
        return;
    }
    
    examInputCount++;
    const container = document.getElementById('examInputsContainer');
    const div = document.createElement('div');
    div.className = 'exam-input-item p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 relative';
    div.id = `exam_item_${examInputCount}`;
    div.innerHTML = `
        <div class="flex justify-between items-center">
            <label class="block text-xs font-bold text-slate-700">ไฟล์ข้อสอบที่ ${examInputCount}</label>
            <button type="button" onclick="removeExamInput(${examInputCount})" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">
                <i class="fa-solid fa-trash-can"></i> ลบ
            </button>
        </div>
        <input type="file" name="exam_files[]" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-xs p-2 bg-white" required>
    `;
    container.appendChild(div);
    document.getElementById('examFileCountText').innerText = examInputCount;
}

function removeExamInput(id) {
    const item = document.getElementById(`exam_item_${id}`);
    if (item) {
        item.remove();
        examInputCount--;
        document.getElementById('examFileCountText').innerText = examInputCount;
    }
}

function submitExam() {
    const form = document.getElementById('examForm');
    const formData = new FormData(form);
    const fileInputs = form.querySelectorAll('input[type="file"]');
    
    let hasFile = false;
    fileInputs.forEach(input => {
        if (input.files.length > 0) hasFile = true;
    });

    if (!hasFile) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ข้อสอบอย่างน้อย 1 ไฟล์' });
        return;
    }

    Swal.fire({ title: 'กำลังอัพโหลดข้อสอบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch('api/upload_exam.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeExamModal();
            Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message })
            .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' }));
}

// --- ส่วนเดิมสำหรับควบคุมอัปโหลดเอกสาร PA ---
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
function toggleType(dockey, type) {
    const fileGroup = document.getElementById(`input_${dockey}_file`);
    const linkGroup = document.getElementById(`input_${dockey}_link`);
    if (type === 'file') {
        if (fileGroup) fileGroup.classList.remove('hidden');
        if (linkGroup) linkGroup.classList.add('hidden');
    } else {
        if (fileGroup) fileGroup.classList.add('hidden');
        if (linkGroup) linkGroup.classList.remove('hidden');
    }
}

function previewFile(url, type) {
    if (type === 'link') { 
        window.open(url, '_blank'); 
        return; 
    }
    const container = document.getElementById('previewContainer');
    if (!container) return;
    const ext = url.split('.').pop().toLowerCase();
    
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4 bg-slate-900/10"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg"></div>`;
    }
    
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }
}

function deleteDoc(docId) {
    Swal.fire({
        title: 'ยืนยันการลบเอกสาร?',
        text: 'เมื่อลบแล้วไฟล์และข้อมูลเอกสารนี้จะถูกนำออกจากระบบทันที',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_doc.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ doc_id: docId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'ลบสำเร็จ!', text: 'ลบเอกสารเรียบร้อยแล้ว' })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                }
            });
        }
    });
}

function submitUpload() {
    const formData = new FormData(document.getElementById('uploadForm'));
    const docTypes = ['pa1', 'pa2', 'pa3', 'info', 'report', 'salary', 'other'];
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
            if (linkInput && linkInput.value.trim() !== "") {
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
    fetch('api/upload_doc.php', { method: 'POST', body: formData })
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