<?php
require_once __DIR__ . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'evaluator') {
    header('Location: /login');
    exit;
}

$evaluator_id =$_SESSION['user_id'];
$groups =$pdo->query("SELECT * FROM subject_groups ORDER BY id ASC")->fetchAll();
$selected_group =$_GET['group_id'] ?? 'all';

$sql = "SELECT u.id as teacher_id, u.fullname, u.academic_standing, sg.name as group_name 
        FROM users u 
        LEFT JOIN subject_groups sg ON u.subject_group_id = sg.id 
        WHERE u.role = 'teacher' ";
$params = [];
if ($selected_group !== 'all') {$sql .= " AND u.subject_group_id = ?";
    $params[] =$selected_group;
}
$sql .= " ORDER BY u.fullname ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers =$stmt->fetchAll();

$docStmt =$pdo->query("SELECT * FROM pa_documents WHERE doc_type != 'exam' ORDER BY created_at DESC");
$raw_docs = $docStmt->fetchAll();$teacher_docs = [];
foreach ($raw_docs as$d) {
    if ($d['file_type'] === 'link') {$teacher_docs[$d['user_id']][] =$d;
    } else {
        if (file_exists(__DIR__ . $d['file_path_or_link'])) {$teacher_docs[$d['user_id']][] =$d;
        }
    }
}

$evalStmt =$pdo->prepare("SELECT * FROM evaluations WHERE evaluator_id = ? ORDER BY created_at DESC");
$evalStmt->execute([$evaluator_id]);$raw_evals = $evalStmt->fetchAll();$latest_evals = [];
foreach ($raw_evals as$e) {
    if (!isset($latest_evals[$e['teacher_id']])) {$latest_evals[$e['teacher_id']] =$e;
    }
}

$page_title = "ส่วนผู้ประเมิน PA - ระบบประเมิน PA";
$header_title = "ส่วนผู้ประเมิน PA";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Include PA Criteria Script -->
<script src="js/pa_criteria.js"></script>

<!-- Filter Section -->
<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
  <div class="font-bold text-slate-700 flex items-center gap-2">
    <i class="fa-solid fa-filter text-indigo-600"></i> กรองตามกลุ่มสาระการเรียนรู้:
  </div>
  <form method="GET" class="w-full md:w-auto">
    <select name="group_id" onchange="this.form.submit()" class="w-full md:w-64 border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      <option value="all">-- กลุ่มสาระการเรียนรู้ทั้งหมด --</option>
      <?php foreach ($groups as$g): ?>
        <option value="<?= $g['id'] ?>" <?= $selected_group ==$g['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($g['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- Teacher Cards List -->
<div class="space-y-4 mt-6">
  <?php if (count($teachers) === 0): ?>
    <div class="text-center py-12 bg-white rounded-2xl border border-slate-200 text-slate-400">
      ไม่พบข้อมูลครูตามเงื่อนไขที่เลือก
    </div>
  <?php else: ?>
    <?php foreach ($teachers as$t): ?>
      <?php
        $tid = $t['teacher_id'];$docs = $teacher_docs[$tid] ?? [];
        $standing =$t['academic_standing'] ?: 'ครู (ยังไม่มีวิทยฐานะ)';
        $last_eval = $latest_evals[$tid] ?? null;
      ?>
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition space-y-4">
        <!-- Teacher Header Info -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-slate-100 pb-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($t['fullname']) ?></h3>
              <span class="bg-amber-100 text-amber-800 border border-amber-300 text-xs font-semibold px-3 py-0.5 rounded-full">
                <i class="fa-solid fa-award mr-1"></i> <?= htmlspecialchars($standing) ?>
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">กลุ่มสาระการเรียนรู้: <span class="font-medium text-slate-700"><?= htmlspecialchars($t['group_name'] ?: 'ยังไม่ระบุ') ?></span></p>
          </div>
          <button onclick="openEvaluationModal(<?= $tid ?>, '<?= htmlspecialchars($t['fullname']) ?>', '<?= htmlspecialchars($standing) ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-xl transition flex items-center gap-2 font-medium">
            <i class="fa-solid fa-clipboard-check"></i>
            <?= $last_eval ? 'แก้ไขการประเมิน / ให้คำแนะนำ' : 'ประเมิน / ให้คำแนะนำ' ?>
          </button>
        </div>

        <!-- Teacher Documents Grid -->
        <div class="space-y-2">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">เอกสารที่อัพโหลด:</span>
          <?php if (count($docs) === 0): ?>
            <p class="text-xs text-slate-400 italic">ยังไม่มีการอัพโหลดเอกสาร</p>
          <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
              <?php foreach ($docs as$d): ?>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex justify-between items-center text-xs">
                  <div class="truncate pr-2">
                    <span class="font-bold text-indigo-700 uppercase block text-[10px]"><?= $d['doc_type'] ?></span>
                    <span class="truncate text-slate-700 block font-medium"><?= htmlspecialchars($d['original_name'] ?:$d['file_path_or_link']) ?></span>
                  </div>
                  <button onclick="previewFile('<?= htmlspecialchars($d['file_path_or_link']) ?>', '<?=$d['file_type'] ?>')" class="text-indigo-600 hover:text-indigo-800 bg-white border border-slate-200 p-2 rounded-lg shadow-sm">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Last Evaluation Summary -->
        <?php if ($last_eval): ?>
          <div class="bg-indigo-50/60 p-3.5 rounded-xl border border-indigo-100 text-xs space-y-1">
            <div class="flex justify-between items-center text-indigo-900 font-semibold">
              <span>คะแนนประเมินล่าสุด: <strong class="text-indigo-700 text-sm"><?= number_format($last_eval['total_score'], 2) ?> / 100</strong></span>
              <span class="text-slate-400 text-[11px]"><?= date('d/m/Y H:i', strtotime($last_eval['created_at'])) ?></span>
            </div>
            <?php if (!empty($last_eval['comments'])): ?>
              <p class="text-slate-600 truncate">คำแนะนำ: <?= htmlspecialchars($last_eval['comments']) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal: ฟอร์มแบบประเมินตามวิทยฐานะ -->
<div id="evalModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 overflow-y-auto">
  <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl p-6 space-y-5 my-8 max-h-[90vh] flex flex-col">
    <!-- Modal Header -->
    <div class="flex justify-between items-center border-b pb-3">
      <div>
        <h2 class="text-lg font-bold text-slate-800" id="modalTeacherName">แบบประเมิน PA2</h2>
        <p class="text-xs text-amber-700 font-semibold" id="modalTeacherStanding"></p>
      </div>
      <button onclick="closeEvalModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>

    <!-- Dynamic Form Content -->
    <form id="evalForm" class="space-y-6 overflow-y-auto pr-2 flex-1">
      <input type="hidden" name="teacher_id" id="formTeacherId">

      <!-- ส่วนที่ 1: ข้อตกลงตามมาตรฐานตำแหน่ง (60 คะแนน) -->
      <div class="space-y-3">
        <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 flex justify-between items-center">
          <h3 class="font-bold text-indigo-900 text-sm">ส่วนที่ 1: ข้อตกลงในการพัฒนางานตามมาตรฐานตำแหน่ง (60 คะแนน)</h3>
          <span class="text-xs bg-indigo-600 text-white px-2.5 py-1 rounded-lg font-medium">คะแนนเต็ม 60</span>
        </div>
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-left text-xs text-slate-700">
            <thead class="bg-slate-100 text-slate-800 font-bold uppercase border-b border-slate-200">
              <tr>
                <th class="p-3 w-6/12">ลักษณะงานที่ปฏิบัติตามมาตรฐานตำแหน่ง (15 ตัวชี้วัด)</th>
                <th class="p-3 w-6/12 text-center">
                  ระดับการปฏิบัติที่คาดหวัง<br>
                  <div class="grid grid-cols-4 gap-1 mt-1 text-[11px] font-semibold text-slate-600">
                    <span class="text-rose-600">1 (ต่ำกว่ามาก)</span>
                    <span class="text-amber-600">2 (ต่ำกว่า)</span>
                    <span class="text-blue-600">3 (ตามระดับ)</span>
                    <span class="text-emerald-600">4 (สูงกว่า)</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody id="criteriaTableBody" class="divide-y divide-slate-100">
              <!-- Dynamic Rows inserted via JS -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ส่วนที่ 2: ประเด็นท้าทาย (40 คะแนน) -->
      <!-- ส่วนที่ 2: ประเด็นท้าทาย (40 คะแนน) -->
    <div class="space-y-3">
    <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 flex justify-between items-center">
        <h3 class="font-bold text-indigo-900 text-sm">ส่วนที่ 2: ข้อตกลงในการพัฒนางานที่เป็นประเด็นท้าทาย (40 คะแนน)</h3>
        <span class="text-xs bg-indigo-600 text-white px-2.5 py-1 rounded-lg font-medium">คะแนนเต็ม 40</span>
    </div>
    
    <div class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
        
        <!-- ข้อ 1. วิธีดำเนินการ -->
        <div class="space-y-2 bg-white p-3.5 rounded-xl border border-slate-200">
        <span class="font-bold text-slate-800 text-sm block" id="lblSec2MethodTitle">1. วิธีดำเนินการ (20 คะแนน)</span>
        <p class="text-slate-600 pl-4 leading-relaxed" id="lblSec2MethodDesc">
            พิจารณาจากการดำเนินการที่ถูกต้อง ครบถ้วน เป็นไปตามระยะเวลาที่กำหนดไว้ในข้อตกลง และสะท้อนให้เห็นถึงระดับการปฏิบัติที่คาดหวังตามตำแหน่งและวิทยฐานะ
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-2">
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
            <input type="radio" name="sec2_score1" value="5" onchange="calculateScores()"> 1 - ต่ำกว่ามาก (5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
            <input type="radio" name="sec2_score1" value="10" onchange="calculateScores()"> 2 - ต่ำกว่า (10)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-indigo-50 border-indigo-200">
            <input type="radio" name="sec2_score1" value="15" checked onchange="calculateScores()"> 3 - ตามระดับ (15)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-emerald-50 border-emerald-200">
            <input type="radio" name="sec2_score1" value="20" onchange="calculateScores()"> 4 - สูงกว่า (20)
            </label>
        </div>
        </div>

        <!-- ข้อ 2. ผลลัพธ์การเรียนรู้ของผู้เรียนที่คาดหวัง -->
        <div class="space-y-3 bg-white p-3.5 rounded-xl border border-slate-200">
        <span class="font-bold text-slate-800 text-sm block" id="lblSec2HeaderTitle">2. ผลลัพธ์การเรียนรู้ของผู้เรียนที่คาดหวัง (20 คะแนน)</span>
        
        <!-- 2.1 เชิงปริมาณ -->
        <div class="space-y-2 pl-3 border-l-2 border-indigo-200">
            <span class="font-bold text-slate-800 block" id="lblSec2Q1Title">2.1 เชิงปริมาณ (10 คะแนน)</span>
            <p class="text-slate-600 pl-4 leading-relaxed" id="lblSec2Q1Desc">
            พิจารณาจากการบรรลุเป้าหมายเชิงปริมาณได้ครบถ้วนตามข้อตกลง และมีความถูกต้อง เชื่อถือได้
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-1">
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
                <input type="radio" name="sec2_score2" value="2.5" onchange="calculateScores()"> 1 - ต่ำกว่ามาก (2.5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
                <input type="radio" name="sec2_score2" value="5" onchange="calculateScores()"> 2 - ต่ำกว่า (5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-indigo-50 border-indigo-200">
                <input type="radio" name="sec2_score2" value="7.5" checked onchange="calculateScores()"> 3 - ตามระดับ (7.5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-emerald-50 border-emerald-200">
                <input type="radio" name="sec2_score2" value="10" onchange="calculateScores()"> 4 - สูงกว่า (10)
            </label>
            </div>
        </div>

        <!-- 2.2 เชิงคุณภาพ -->
        <div class="space-y-2 pl-3 border-l-2 border-indigo-200 pt-2">
            <span class="font-bold text-slate-800 block" id="lblSec2Q2Title">2.2 เชิงคุณภาพ (10 คะแนน)</span>
            <p class="text-slate-600 pl-4 leading-relaxed" id="lblSec2Q2Desc">
            พิจารณาจากการบรรลุเป้าหมายเชิงคุณภาพได้ครบถ้วน ถูกต้อง เชื่อถือได้ และปรากฏผลต่อคุณภาพผู้เรียนได้ตามข้อตกลง
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-1">
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
                <input type="radio" name="sec2_score3" value="2.5" onchange="calculateScores()"> 1 - ต่ำกว่ามาก (2.5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100">
                <input type="radio" name="sec2_score3" value="5" onchange="calculateScores()"> 2 - ต่ำกว่า (5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-indigo-50 border-indigo-200">
                <input type="radio" name="sec2_score3" value="7.5" checked onchange="calculateScores()"> 3 - ตามระดับ (7.5)
            </label>
            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:bg-emerald-50 border-emerald-200">
                <input type="radio" name="sec2_score3" value="10" onchange="calculateScores()"> 4 - สูงกว่า (10)
            </label>
            </div>
        </div>

        </div>

    </div>
    </div>

      <!-- สรุปผลคะแนนรวม -->
      <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 flex flex-col md:flex-row justify-between items-center gap-3 text-sm">
        <div>
          <span class="font-bold text-amber-900">สรุปผลคะแนนรวม:</span>
          <span class="text-xs text-amber-700 block">ส่วนที่ 1 (<span id="txtSec1">45</span>/60) + ส่วนที่ 2 (<span id="txtSec2">30</span>/40)</span>
        </div>
        <div class="text-right">
          <span class="text-2xl font-bold text-amber-800" id="txtTotalScore">75.00</span>
          <span class="text-slate-500 font-normal text-xs">/ 100 คะแนน</span>
          <span id="badgePass" class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">ผ่านเกณฑ์ (≥70%)</span>
        </div>
      </div>

      <!-- ข้อเสนอแนะ / จุดเด่น / จุดควรพัฒนา -->
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">1. จุดเด่น</label>
          <textarea name="stregnth_comment" rows="2" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="ระบุจุดเด่นของผู้รับการประเมิน..."></textarea>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">2. จุดที่ควรพัฒนา</label>
          <textarea name="improvement_comment" rows="2" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="ระบุสิ่งที่ควรพัฒนาเพิ่มเติม..."></textarea>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">3. ข้อคิดเห็น / คำแนะนำเพิ่มเติม</label>
          <textarea name="comments" rows="3" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="กรอกข้อเสนอแนะสำหรับการพัฒนางาน..."></textarea>
        </div>
      </div>
    </form>

    <!-- Modal Footer Actions -->
    <div class="flex justify-end gap-2 pt-3 border-t">
      <button type="button" onclick="closeEvalModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-xl">ยกเลิก</button>
      <button type="button" onclick="submitEvaluation()" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-medium">บันทึกผลการประเมิน</button>
    </div>
  </div>
</div>

<!-- Modal: Preview File -->
<div id="previewModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
  <div class="bg-white w-full max-w-4xl h-[85vh] rounded-2xl shadow-xl p-4 flex flex-col">
    <div class="flex justify-between items-center mb-3">
      <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-file-lines text-indigo-600"></i> ตัวอย่างเอกสาร</h3>
      <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <div id="previewContainer" class="flex-1 border border-slate-200 rounded-xl bg-slate-100 overflow-hidden relative"></div>
  </div>
</div>

<script>
// JavaScript Functions for Modal Handling and Score Calculation
function openEvaluationModal(teacherId, teacherName, academicStanding) {
    document.getElementById('formTeacherId').value = teacherId;
    document.getElementById('modalTeacherName').innerText = 'แบบประเมิน PA2 - ' + teacherName;
    document.getElementById('modalTeacherStanding').innerText = 'วิทยฐานะ: ' + academicStanding;

    const criteriaData = typeof PA_CRITERIA !== 'undefined' ? (PA_CRITERIA[academicStanding] || PA_CRITERIA['ครู (ยังไม่มีวิทยฐานะ)']) : null;
    const tbody = document.getElementById('criteriaTableBody');
    tbody.innerHTML = '';

    // 1. สร้างตารางข้อ 1.1 - 3.3 ส่วนที่ 1
    if (criteriaData && criteriaData.items) {
        criteriaData.items.forEach((itemText, idx) => {
            const itemNum = idx + 1;
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50';
            row.innerHTML = `
                <td class="p-3 text-slate-700 leading-relaxed font-medium">${itemText}</td>
                <td class="p-3 text-center">
                    <div class="grid grid-cols-4 gap-1 items-center justify-items-center">
                        <label class="cursor-pointer p-1.5 rounded-lg hover:bg-rose-50 flex flex-col items-center">
                            <input type="radio" name="sec1_item_${itemNum}" value="1" onchange="calculateScores()" class="w-4 h-4 text-rose-600 focus:ring-rose-500">
                            <span class="text-[10px] text-slate-500 mt-0.5">1</span>
                        </label>
                        <label class="cursor-pointer p-1.5 rounded-lg hover:bg-amber-50 flex flex-col items-center">
                            <input type="radio" name="sec1_item_${itemNum}" value="2" onchange="calculateScores()" class="w-4 h-4 text-amber-600 focus:ring-amber-500">
                            <span class="text-[10px] text-slate-500 mt-0.5">2</span>
                        </label>
                        <label class="cursor-pointer p-1.5 rounded-lg hover:bg-indigo-50 flex flex-col items-center">
                            <input type="radio" name="sec1_item_${itemNum}" value="3" checked onchange="calculateScores()" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-[10px] text-slate-500 mt-0.5">3</span>
                        </label>
                        <label class="cursor-pointer p-1.5 rounded-lg hover:bg-emerald-50 flex flex-col items-center">
                            <input type="radio" name="sec1_item_${itemNum}" value="4" onchange="calculateScores()" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-[10px] text-slate-500 mt-0.5">4</span>
                        </label>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // 2. แสดงข้อความส่วนที่ 2 ตรงตามรูปภาพ
    if (criteriaData && criteriaData.sec2_desc) {
        document.getElementById('lblSec2MethodTitle').innerText = criteriaData.sec2_desc.m_title;
        document.getElementById('lblSec2MethodDesc').innerText = criteriaData.sec2_desc.m_desc;
        document.getElementById('lblSec2HeaderTitle').innerText = criteriaData.sec2_desc.h_title;
        document.getElementById('lblSec2Q1Title').innerText = criteriaData.sec2_desc.q1_title;
        document.getElementById('lblSec2Q1Desc').innerText = criteriaData.sec2_desc.q1_desc;
        document.getElementById('lblSec2Q2Title').innerText = criteriaData.sec2_desc.q2_title;
        document.getElementById('lblSec2Q2Desc').innerText = criteriaData.sec2_desc.q2_desc;
    }

    calculateScores();
    const modal = document.getElementById('evalModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEvalModal() {
    const modal = document.getElementById('evalModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function calculateScores() {
    let sumSec1Raw = 0;
    for (let i = 1; i <= 15; i++) {
        const checkedRadio = document.querySelector(`input[name="sec1_item_${i}"]:checked`);
        if (checkedRadio) {
            sumSec1Raw += parseFloat(checkedRadio.value || 0);
        }
    }
    const scoreSec1 = sumSec1Raw;

    const radioSec2_1 = document.querySelector('input[name="sec2_score1"]:checked');
    const radioSec2_2 = document.querySelector('input[name="sec2_score2"]:checked');
    const radioSec2_3 = document.querySelector('input[name="sec2_score3"]:checked');

    const sec2_1 = parseFloat(radioSec2_1 ? radioSec2_1.value : 0);
    const sec2_2 = parseFloat(radioSec2_2 ? radioSec2_2.value : 0);
    const sec2_3 = parseFloat(radioSec2_3 ? radioSec2_3.value : 0);
    const scoreSec2 = sec2_1 + sec2_2 + sec2_3;

    const totalScore = scoreSec1 + scoreSec2;
    document.getElementById('txtSec1').innerText = scoreSec1.toFixed(2);
    document.getElementById('txtSec2').innerText = scoreSec2.toFixed(2);
    document.getElementById('txtTotalScore').innerText = totalScore.toFixed(2);

    const badge = document.getElementById('badgePass');
    if (totalScore >= 70) {
        badge.className = 'ml-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800';
        badge.innerText = 'ผ่านเกณฑ์ (≥70%)';
    } else {
        badge.className = 'ml-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800';
        badge.innerText = 'ไม่ผ่านเกณฑ์ (<70%)';
    }
}

function submitEvaluation() {
    const form = document.getElementById('evalForm');
    const formData = new FormData(form);
    const data = {
        teacher_id: formData.get('teacher_id'),
        score_section1: document.getElementById('txtSec1').innerText,
        score_section2: document.getElementById('txtSec2').innerText,
        total_score: document.getElementById('txtTotalScore').innerText,
        stregnth_comment: formData.get('stregnth_comment'),
        improvement_comment: formData.get('improvement_comment'),
        comments: formData.get('comments')
    };

    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('api/save_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resData => {
        if (resData.success) {
            closeEvalModal();
            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: 'บันทึกผลการประเมินเรียบร้อยแล้ว' })
            .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: resData.message });
        }
    });
}

function previewFile(url, type) {
    const container = document.getElementById('previewContainer');
    if (!container) return;
    if (type === 'link') { window.open(url, '_blank'); return; }
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
    } else {
        container.innerHTML = `<div class="w-full h-full flex items-center justify-center p-4 bg-slate-900/10"><img src="${url}" class="max-h-full max-w-full object-contain rounded-lg"></div>`;
    }
    const previewModal = document.getElementById('previewModal');
    previewModal.classList.remove('hidden');
    previewModal.classList.add('flex');
}

function closePreviewModal() {
    const previewModal = document.getElementById('previewModal');
    previewModal.classList.add('hidden');
    previewModal.classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>