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

// ดึงคอลัมน์ที่มีอยู่จริงในตาราง
$existing_columns = [];
$res_cols = $conn->query("SHOW COLUMNS FROM `{$target_table}`");
while ($c = $res_cols->fetch_assoc()) {
    $existing_columns[] = $c['Field'];
}

// 2. รับค่าจากฟอร์ม
$raw_inputs = [
    'applicant_name'    => trim($_POST['applicant_name'] ?? ''),
    'name'              => trim($_POST['applicant_name'] ?? ''),
    'position'          => trim($_POST['position'] ?? 'ครู'),
    'academic_standing' => trim($_POST['academic_standing'] ?? ''),
    'department'        => trim($_POST['department'] ?? ''),
    'proposed_group'    => trim($_POST['proposed_group'] ?? 'กลุ่มงานบริหารวิชาการ'),
    'head_name'         => trim($_POST['head_name'] ?? 'นางศุภลักษณ์ ดำชุม'),
    'topic'             => trim($_POST['topic'] ?? ''),
    'title'             => trim($_POST['topic'] ?? ''),
    'purpose'           => trim($_POST['topic'] ?? ''),
    'destination'       => trim($_POST['destination'] ?? ''),
    'location'          => trim($_POST['destination'] ?? ''),
    'ref_doc'           => trim($_POST['ref_doc'] ?? ''),
    'time_period'       => trim($_POST['time_period'] ?? ''),
    'companions'        => trim($_POST['companions'] ?? ''),
    'budget_type'       => trim($_POST['budget_type'] ?? 'ไม่ขอเบิกงบประมาณ'),
    'vehicle_type'      => trim($_POST['vehicle_type'] ?? ''),
    'vehicle'           => trim($_POST['vehicle_type'] ?? ''),
    'vehicle_license'   => trim($_POST['vehicle_license'] ?? ''),
    'status'            => 'ปกติ'
];

$insert_cols = [];
$insert_vals = [];

foreach ($raw_inputs as $col => $val) {
    if (in_array($col, $existing_columns)) {
        $insert_cols[] = "`{$col}`";
        $insert_vals[] = "'" . $conn->real_escape_string($val) . "'";
    }
}

// รายการค่าใช้จ่าย
if (in_array('expense_types', $existing_columns)) {
    $expense_types = isset($_POST['expense_types']) && is_array($_POST['expense_types']) ? implode(',', $_POST['expense_types']) : '';
    $insert_cols[] = "`expense_types`";
    $insert_vals[] = "'" . $conn->real_escape_string($expense_types) . "'";
}

// วันที่
if (!empty($_POST['start_date']) && in_array('start_date', $existing_columns)) {
    $insert_cols[] = "`start_date`";
    $insert_vals[] = "'" . $conn->real_escape_string($_POST['start_date']) . "'";
}
if (!empty($_POST['end_date']) && in_array('end_date', $existing_columns)) {
    $insert_cols[] = "`end_date`";
    $insert_vals[] = "'" . $conn->real_escape_string($_POST['end_date']) . "'";
}
if (!empty($_POST['ref_date']) && in_array('ref_date', $existing_columns)) {
    $insert_cols[] = "`ref_date`";
    $insert_vals[] = "'" . $conn->real_escape_string($_POST['ref_date']) . "'";
}

$sql = "INSERT INTO `{$target_table}` (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $insert_vals) . ")";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;
    header("Location: print.php?id={$new_id}");
    exit;
} else {
    die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error);
}
?>
