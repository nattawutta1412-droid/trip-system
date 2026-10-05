<?php
require_once 'config.php';

// รับค่าตัวกรองเหมือนหน้า index.php
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$month = trim($_GET['month'] ?? '');

$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(applicant_name LIKE ? OR subject LIKE ? OR destination LIKE ? OR doc_number LIKE ?)";
    $s_term = "%{$search}%";
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ssss";
}

if ($department !== '') {
    $where_clauses[] = "(department = ? OR work_group = ?)";
    $params[] = $department;
    $params[] = $department;
    $types .= "ss";
}

if ($month !== '') {
    $where_clauses[] = "DATE_FORMAT(created_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM official_trips WHERE {$where_sql} ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ตั้งชื่อไฟล์ดาวน์โหลด
$filename = "รายงานการไปราชการ_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// ใส่ BOM เพื่อให้ Excel รองรับภาษาไทยได้ถูกต้อง
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// หัวตาราง
fputcsv($output, [
    'ลำดับ', 
    'เลขที่หนังสือ', 
    'วันที่ยื่น', 
    'ชื่อ-สกุล ผู้ขออนุญาต', 
    'ตำแหน่ง', 
    'กลุ่มสาระ/กลุ่มงาน', 
    'วัตถุประสงค์ (ไปราชการเพื่อ)', 
    'สถานที่ปลายทาง', 
    'ตั้งแต่วันที่', 
    'ถึงวันที่', 
    'พาหนะ', 
    'ทะเบียนรถ', 
    'การเบิกจ่ายงบประมาณ', 
    'สถานะ'
]);

$i = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $i++,
        $row['doc_number'] ?? '-',
        $row['created_date'] ?? '-',
        $row['applicant_name'] ?? '-',
        $row['position'] ?? '-',
        (!empty($row['work_group']) ? $row['work_group'] : $row['department']),
        $row['subject'] ?? '-',
        $row['destination'] ?? '-',
        $row['start_date'] ?? '-',
        $row['end_date'] ?? '-',
        $row['vehicle_type'] ?? '-',
        $row['vehicle_license_plate'] ?? '-',
        $row['expense_type'] ?? '-',
        $row['status'] ?? 'ปกติ'
    ]);
}

fclose($output);
exit();
?>
