<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: form.php");
    exit;
}

// 1. ค้นหาชื่อตารางคำร้องอัตโนมัติ
$target_table = null;
$possible_tables = ['official_trips', 'trip_requests', 'trips', 'trip_records', 'forms', 'requests'];

foreach ($possible_tables as $tbl) {
    $chk = $conn->query("SHOW TABLES LIKE '{$tbl}'");
    if ($chk && $chk->num_rows > 0) {
        $target_table = $tbl;
        break;
    }
}

if (!$target_table) {
    $all_tables = $conn->query("SHOW TABLES");
    if ($all_tables) {
        while ($t_row = $all_tables->fetch_array()) {
            $curr_tbl = $t_row[0];
            if ($curr_tbl !== 'teachers' && $curr_tbl !== 'users' && $curr_tbl !== 'admins') {
                $target_table = $curr_tbl;
                break;
            }
        }
    }
}

if (!$target_table) {
    die("ไม่พบตารางข้อมูลคำร้องในระบบ");
}

// ตรวจสอบและสร้างคอลัมน์ companions อัตโนมัติหากยังไม่มี
$col_chk = $conn->query("SHOW COLUMNS FROM `{$target_table}` LIKE 'companions'");
if ($col_chk && $col_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `{$target_table}` ADD COLUMN companions TEXT NULL");
}

// ดึงรายละเอียดคอลัมน์ทั้งหมดในตาราง
$columns_info = [];
$res_cols = $conn->query("SHOW COLUMNS FROM `{$target_table}`");
while ($c = $res_cols->fetch_assoc()) {
    $columns_info[$c['Field']] = [
        'null'    => $c['Null'],
        'default' => $c['Default'],
        'key'     => $c['Key']
    ];
}
$existing_columns = array_keys($columns_info);

// 2. รับค่าจากฟอร์ม
$applicant_name = trim($_POST['applicant_name'] ?? '');
$topic_text     = trim($_POST['topic'] ?? '');
$dest_text      = trim($_POST['destination'] ?? '');
$position_val   = trim($_POST['position'] ?? 'ครู');
$academic_val   = trim($_POST['academic_standing'] ?? '');
$dept_val       = trim($_POST['department'] ?? '');
$group_val      = trim($_POST['proposed_group'] ?? 'กลุ่มงานบริหารวิชาการ');
$head_val       = trim($_POST['head_name'] ?? 'นางศุภลักษณ์ ดำชุม');
$ref_doc_val    = trim($_POST['ref_doc'] ?? '');
$time_val       = trim($_POST['time_period'] ?? '');
$companions_val = trim($_POST['companions'] ?? '');
$budget_val     = trim($_POST['budget_type'] ?? 'ไม่ขอเบิกงบประมาณ');
$vehicle_val    = trim($_POST['vehicle_type'] ?? '');
$license_val    = trim($_POST['vehicle_license'] ?? '');

$raw_inputs = [
    // ชื่อ-สกุล
    'applicant_name'    => $applicant_name,
    'name'              => $applicant_name,
    'fullname'          => $applicant_name,
    'teacher_name'      => $applicant_name,
    // เรื่อง / วัตถุประสงค์
    'topic'             => $topic_text,
    'subject'           => $topic_text,
    'title'             => $topic_text,
    'purpose'           => $topic_text,
    'objective'         => $topic_text,
    'details'           => $topic_text,
    'reason'            => $topic_text,
    'trip_topic'        => $topic_text,
    // สถานที่
    'destination'       => $dest_text,
    'location'          => $dest_text,
    'place'             => $dest_text,
    'target'            => $dest_text,
    // สังกัด/ตำแหน่ง
    'position'          => $position_val,
    'academic_standing' => $academic_val,
    'department'        => $dept_val,
    'proposed_group'    => $group_val,
    'head_name'         => $head_val,
    // รายละเอียดอื่นๆ
    'ref_doc'           => $ref_doc_val,
    'time_period'       => $time_val,
    'companions'        => $companions_val,
    'budget_type'       => $budget_val,
    'budget'            => $budget_val,
    'budget_source'     => $budget_val,
    'vehicle_type'      => $vehicle_val,
    'vehicle'           => $vehicle_val,
    'vehicle_license'   => $license_val,
    'status'            => 'ปกติ'
];

$insert_data = [];
foreach ($raw_inputs as $col => $val) {
    if (in_array($col, $existing_columns)) {
        $insert_data[$col] = "'" . $conn->real_escape_string($val) . "'";
    }
}

// วันที่สร้างข้อมูล
$now_datetime = date('Y-m-d H:i:s');
$now_date = date('Y-m-d');
$date_created_fields = ['created_date', 'created_at', 'create_time', 'date_created'];
foreach ($date_created_fields as $df) {
    if (in_array($df, $existing_columns)) {
        $insert_data[$df] = ($df === 'created_date' || $df === 'date_created') ? "'{$now_date}'" : "'{$now_datetime}'";
    }
}

// รายการค่าใช้จ่าย
if (in_array('expense_types', $existing_columns)) {
    $expense_types = isset($_POST['expense_types']) && is_array($_POST['expense_types']) ? implode(',', $_POST['expense_types']) : '';
    $insert_data['expense_types'] = "'" . $conn->real_escape_string($expense_types) . "'";
}

// วันที่เดินทางและหนังสืออ้างอิง
if (!empty($_POST['start_date']) && in_array('start_date', $existing_columns)) {
    $insert_data['start_date'] = "'" . $conn->real_escape_string($_POST['start_date']) . "'";
}
if (!empty($_POST['end_date']) && in_array('end_date', $existing_columns)) {
    $insert_data['end_date'] = "'" . $conn->real_escape_string($_POST['end_date']) . "'";
}
if (!empty($_POST['ref_date']) && in_array('ref_date', $existing_columns)) {
    $insert_data['ref_date'] = "'" . $conn->real_escape_string($_POST['ref_date']) . "'";
}

// ตรวจสอบคอลัมน์ NOT NULL ที่เหลือเพื่อป้องกัน SQL Error ทุกกรณี
foreach ($columns_info as $col_name => $info) {
    if ($info['key'] === 'PRI') continue; // ข้าม Primary key id
    if ($info['null'] === 'NO' && $info['default'] === null && !isset($insert_data[$col_name])) {
        // ให้ค่าว่างปลอดภัยไว้ก่อนเพื่อไม่ให้ติด Fatal error
        $insert_data[$col_name] = "''";
    }
}

$fields_str = "`" . implode("`, `", array_keys($insert_data)) . "`";
$values_str = implode(", ", array_values($insert_data));

$sql = "INSERT INTO `{$target_table}` ({$fields_str}) VALUES ({$values_str})";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;
    header("Location: print.php?id={$new_id}");
    exit;
} else {
    die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error);
}
?>
