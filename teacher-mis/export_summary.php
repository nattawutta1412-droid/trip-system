<?php
require_once 'config.php';

// ตั้งค่า Header ให้ดาวน์โหลดเป็นไฟล์ Excel CSV รองรับ UTF-8 (เปิดใน Excel แล้วภาษาไทยไม่เป็นต่างดาว)
$filename = "teacher_summary_report_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// ใส่ UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// หัวรายงาน
fputcsv($output, ['รายงานสรุปข้อมูลและทำเนียบบุคลากรทางการศึกษา']);
fputcsv($output, ['โรงเรียนย่านตาขาวรัฐชนูปถัมภ์']);
fputcsv($output, ['ข้อมูล ณ วันที่', date('d/m/Y H:i:s')]);
fputcsv($output, []); // บรรทัดว่าง

// 1. สรุปสถิติภาพรวม
$total_teachers = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];
fputcsv($output, ['=== สรุปภาพรวม ===']);
fputcsv($output, ['จำนวนครูและบุคลากรที่ปฏิบัติหน้าที่ทั้งหมด (คน)', $total_teachers]);
fputcsv($output, []);

// 2. สรุปตามกลุ่มสาระการเรียนรู้
fputcsv($output, ['=== สรุปจำนวนแยกตามกลุ่มสาระการเรียนรู้ / กลุ่มงาน ===']);
fputcsv($output, ['ลำดับ', 'กลุ่มสาระการเรียนรู้ / สังกัด', 'จำนวน (คน)']);
$dept_query = $conn->query("SELECT department, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY department ORDER BY count DESC");
$d_no = 1;
while ($d = $dept_query->fetch_assoc()) {
    fputcsv($output, [$d_no++, $d['department'], $d['count']]);
}
fputcsv($output, []);

// 3. สรุปตามวิทยฐานะ
fputcsv($output, ['=== สรุปจำนวนแยกตามวิทยฐานะ ===']);
fputcsv($output, ['ลำดับ', 'วิทยฐานะ', 'จำนวน (คน)']);
$standing_query = $conn->query("SELECT IF(academic_standing IS NULL OR academic_standing='', 'ไม่มีวิทยฐานะ / ครูผู้ช่วย', academic_standing) as standing, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY standing ORDER BY count DESC");
$s_no = 1;
while ($s = $standing_query->fetch_assoc()) {
    fputcsv($output, [$s_no++, $s['standing'], $s['count']]);
}
fputcsv($output, []);

// 4. รายชื่อทำเนียบบุคลากรทั้งหมด
fputcsv($output, ['=== รายชื่อทำเนียบบุคลากรทั้งหมด ===']);
fputcsv($output, ['ลำดับ', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ตำแหน่ง', 'วิทยฐานะ', 'กลุ่มสาระการเรียนรู้', 'กลุ่มงานบริหาร', 'วิชาเอก', 'ระดับการศึกษา', 'เบอร์โทรศัพท์', 'อีเมล', 'สถานะ']);

$list_query = $conn->query("SELECT * FROM teachers WHERE status='ปฏิบัติหน้าที่' ORDER BY id ASC");
$i = 1;
while ($row = $list_query->fetch_assoc()) {
    fputcsv($output, [
        $i++,
        $row['prefix'],
        $row['first_name'],
        $row['last_name'],
        $row['position'],
        $row['academic_standing'] ?: '-',
        $row['department'],
        $row['work_group'] ?: '-',
        $row['major_subject'] ?: '-',
        $row['education_level'] ?: '-',
        $row['phone_number'] ?: '-',
        $row['email'] ?: '-',
        $row['status']
    ]);
}

fclose($output);
exit();
?>
