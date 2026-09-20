<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบ PA - สรุปรายการเอกสาร (ครู)</title>
    <!-- Tailwind CSS & SweetAlert2 & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans p-6">

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header & Action -->
    <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">ระบบประเมิน PA - จัดการเอกสาร</h1>
            <p class="text-slate-500 text-sm">ยินดีต้อนรับ: ครูสมชาย สายชล</p>
        </div>
        <button onclick="openUploadModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-cloud-arrow-up"></i> แนบไฟล์อัพโหลดเอกสาร
        </button>
    </div>

    <!-- Document Card View -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card: PA2 -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">เอกสาร PA2</span>
                <i class="fa-solid fa-file-pdf text-red-500 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-slate-800 truncate">ข้อตกลงในการประเมิน (PA2).pdf</h3>
            <p class="text-xs text-slate-400 mt-1">อัพโหลดเมื่อ: 10 ก.ย. 2026</p>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
                <button onclick="previewFile('uploads/pa2_example.pdf', 'file')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                    <i class="fa-solid fa-eye"></i> ดูตัวอย่าง
                </button>
            </div>
        </div>

        <!-- Card: Report -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full">รายงาน PA</span>
                <i class="fa-solid fa-link text-blue-500 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-slate-800 truncate">https://drive.google.com/...</h3>
            <p class="text-xs text-slate-400 mt-1">อัพโหลดเมื่อ: 12 ก.ย. 2026</p>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
                <button onclick="previewFile('https://drive.google.com', 'link')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> เปิดลิงก์
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal อัพโหลดเอกสาร -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-lg p-6 space-y-4">
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
                <label class="block text-sm font-medium text-slate-700 mb-1">รูปแบบการอัพโหลด</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="upload_kind" value="file" checked onclick="toggleUploadType('file')"> ไฟล์ (PDF, PNG, JPG)</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="upload_kind" value="link" onclick="toggleUploadType('link')"> ลิงก์ภายนอก</label>
                </div>
            </div>

            <div id="fileInputGroup">
                <label class="block text-sm font-medium text-slate-700 mb-1">เลือกไฟล์ (ไม่เกิน 15 MB)</label>
                <input type="file" id="doc_file" name="doc_file" accept=".pdf,.png,.jpg,.jpeg" class="w-full border border-slate-300 rounded-lg text-sm p-2">
            </div>

            <div id="linkInputGroup" class="hidden">
                <label class="block text-sm font-medium text-slate-700 mb-1">URL ลิงก์เอกสาร</label>
                <input type="url" id="doc_link" name="doc_link" placeholder="https://..." class="w-full border border-slate-300 rounded-lg p-2.5 text-sm">
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">ยกเลิก</button>
                <button type="button" onclick="submitUpload()" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">บันทึกไฟล์เอกสาร</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ดูตัวอย่างเอกสาร -->
<div id="previewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl h-[85vh] rounded-xl shadow-lg p-4 flex flex-col">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold text-slate-800">ตัวอย่างเอกสาร</h3>
            <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div id="previewContainer" class="flex-1 border rounded-lg bg-slate-100 overflow-hidden">
            <!-- Iframe/Image Preview Dynamic rendering -->
        </div>
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

// ตรวจสอบไฟล์ และส่งข้อมูลด้วย AJAX + SweetAlert2
function submitUpload() {
    const kind = document.querySelector('input[name="upload_kind"]:checked').value;
    const formData = new FormData(document.getElementById('uploadForm'));

    if (kind === 'file') {
        const fileInput = document.getElementById('doc_file');
        const file = fileInput.files[0];
        
        if (!file) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์', text: 'โปรดเลือกไฟล์ก่อนกดบันทึก' });
            return;
        }

        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 15MB = 15 * 1024 * 1024 bytes)
        const maxSize = 15 * 1024 * 1024;
        if (file.size > maxSize) {
            Swal.fire({ icon: 'error', title: 'ไฟล์มีขนาดใหญ่เกินไป', text: 'ขนาดไฟล์ต้องไม่เกิน 15 MB' });
            return;
        }

        // ตรวจสอบประเภทนามสกุลไฟล์
        const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({ icon: 'error', title: 'ประเภทไฟล์ไม่ถูกต้อง', text: 'รองรับเฉพาะไฟล์ PDF, PNG และ JPG เท่านั้น' });
            return;
        }
    } else {
        const link = document.getElementById('doc_link').value;
        if (!link) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก URL ลิงก์' });
            return;
        }
    }

    // ยิง API ไปยังเซิร์ฟเวอร์
    fetch('api/upload_doc.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            closeUploadModal();
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'อัพโหลดไฟล์เอกสารเรียบร้อยแล้ว',
                confirmButtonColor: '#4F46E5'
            }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
        }
    });
}

// แสดงพรีวิวไฟล์ใน Modal
function previewFile(url, type) {
    const container = document.getElementById('previewContainer');
    if (type === 'link') {
        window.open(url, '_blank');
        return;
    }
    
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else if (['png', 'jpg', 'jpeg'].includes(ext)) {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg"></div>`;
    }
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewModal').classList.add('flex');
}
</script>
</body>
</html>