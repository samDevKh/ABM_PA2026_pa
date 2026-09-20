<!-- แถบ Filter และการให้ข้อเสนอแนะสำหรับผู้ประเมิน -->
<div class="max-w-6xl mx-auto space-y-6 p-6">
    <!-- Filter -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
        <h2 class="font-bold text-slate-700">กรองข้อมูลตามสาระการเรียนรู้:</h2>
        <select id="groupFilter" onchange="filterTeachers()" class="border border-slate-300 rounded-lg p-2 text-sm w-64">
            <option value="">ทั้งหมด</option>
            <option value="1">วิทยาศาสตร์และเทคโนโลยี</option>
            <option value="2">คณิตศาสตร์</option>
        </select>
    </div>

    <!-- รายการครูและเอกสาร (Card View) -->
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <div>
                <h3 class="font-bold text-slate-800">นายสมชาย เข็มกลัด</h3>
                <span class="text-xs text-slate-500">กลุ่มสาระฯ วิทยาศาสตร์และเทคโนโลยี</span>
            </div>
            <button onclick="openCommentModal(1)" class="bg-amber-500 hover:bg-amber-600 text-white text-xs px-3 py-2 rounded-lg font-medium flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square"></i> ให้คำแนะนำ
            </button>
        </div>
        
        <!-- รายการไฟล์ครู -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-slate-50 p-3 rounded-lg border text-xs">
                <p class="font-semibold text-slate-700">PA2</p>
                <button onclick="previewFile('uploads/pa2_example.pdf', 'file')" class="text-indigo-600 font-medium mt-2 hover:underline">ดูเอกสาร</button>
            </div>
        </div>
    </div>
</div>

<script>
// ฟังก์ชั่นบันทึกคำแนะนำผู้ประเมิน
function openCommentModal(teacherId) {
    Swal.fire({
        title: 'ระบุคำแนะนำการประเมิน PA',
        input: 'textarea',
        inputPlaceholder: 'กรอกคำแนะนำหรือข้อเสนอแนะเพิ่มเติม...',
        showCancelButton: true,
        confirmButtonText: 'บันทึกคำแนะนำ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#4F46E5',
        preConfirm: (text) => {
            if (!text) {
                Swal.showValidationMessage('กรุณากรอกข้อความคำแนะนำ');
            }
            return text;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // ยิง API บันทึกคำแนะนำลงระบบ
            fetch('api/save_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ teacher_id: teacherId, comment: result.value })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('บันทึกเรียบร้อย!', 'บันทึกคำแนะนำการประเมินแล้ว', 'success');
                }
            });
        }
    });
}
</script>