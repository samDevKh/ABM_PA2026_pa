<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard ผู้บริหาร / แอดมิน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 p-6">
<div class="max-w-6xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-slate-800">สรุปภาพรวมการอัพโหลดเอกสาร PA</h1>

    <!-- Chart Section -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="font-semibold text-slate-700 mb-4">สถิติการส่งเอกสารจำแนกตามกลุ่มสาระการเรียนรู้</h2>
        <div class="h-64">
            <canvas id="paChart"></canvas>
        </div>
    </div>

    <!-- Status Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
            <h2 class="font-semibold text-slate-700">สถานะการส่งเอกสารของข้าราชการครู</h2>
        </div>
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
                <tr>
                    <th class="p-3">ชื่อ-นามสกุล</th>
                    <th class="p-3">กลุ่มสาระฯ</th>
                    <th class="p-3">สถานะ</th>
                    <th class="p-3">การจัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="p-3 font-medium text-slate-800">นายสมชาย เข็มกลัด</td>
                    <td class="p-3">วิทยาศาสตร์ฯ</td>
                    <td class="p-3"><span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-1 rounded-full font-semibold">เอกสารครบถ้วน</span></td>
                    <td class="p-3"><a href="#" class="text-indigo-600 hover:underline">ดูรายละเอียด</a></td>
                </tr>
                <tr>
                    <td class="p-3 font-medium text-slate-800">นางสาวสมหญิง สุขใจ</td>
                    <td class="p-3">คณิตศาสตร์</td>
                    <td class="p-3"><span class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded-full font-semibold">เอกสารไม่ครบ</span></td>
                    <td class="p-3"><a href="#" class="text-indigo-600 hover:underline">ดูรายละเอียด</a></td>
                </tr>
                <tr>
                    <td class="p-3 font-medium text-slate-800">นายวิชัย ใจดี</td>
                    <td class="p-3">ภาษาไทย</td>
                    <td class="p-3"><span class="bg-rose-100 text-rose-700 text-xs px-2 py-1 rounded-full font-semibold">ยังไม่ส่ง</span></td>
                    <td class="p-3"><a href="#" class="text-indigo-600 hover:underline">ดูรายละเอียด</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Render Chart.js
const ctx = document.getElementById('paChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['วิทยาศาสตร์ฯ', 'คณิตศาสตร์', 'ภาษาไทย', 'ภาษาต่างประเทศ', 'สังคมศึกษา'],
        datasets: [{
            label: 'ส่งครบถ้วน',
            data: [12, 19, 8, 15, 10],
            backgroundColor: '#10B981'
        }, {
            label: 'ยังส่งไม่ครบ/ยังไม่ส่ง',
            data: [3, 2, 5, 1, 4],
            backgroundColor: '#F43F5E'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>