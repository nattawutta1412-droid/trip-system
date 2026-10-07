<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt =$conn->prepare("SELECT * FROM official_trips WHERE id = ?");
$stmt->bind_param("i", $id);$stmt->execute();
$result =$stmt->get_result();
$trip =$result->fetch_assoc();

if (!$trip) {
    die("ไม่พบข้อมูลเอกสาร");
}

// ดึงข้อมูลผู้ร่วมเดินทาง
$participants = [];
$p_check =$conn->query("SHOW TABLES LIKE 'trip_participants'");
if ($p_check &&$p_check->num_rows > 0) {
    $p_stmt =$conn->prepare("SELECT * FROM trip_participants WHERE trip_id = ? ORDER BY id ASC");
    $p_stmt->bind_param("i", $id);$p_stmt->execute();
    $p_res =$p_stmt->get_result();
    while ($p_row =$p_res->fetch_assoc()) {
        $participants[] =$p_row;
    }
}

function thai_date($date_str) {
    if (!$date_str) return "";
    $thai_months = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];
    $time = strtotime($date_str);$d = date('j', $time);$m = $thai_months[intval(date('n',$time))];
    $y = date('Y',$time) + 543;
    return "$d $m$y";
}

// 1. เลขที่หนังสือ
$doc_number_display = !empty($trip['doc_number']) ? htmlspecialchars($trip['doc_number']) : '...................................................';

// 2. กำหนดการวันเวลา (กรณีครึ่งวัน)
$start_t = thai_date($trip['start_date'] ?? '');
$end_t   = thai_date($trip['end_date'] ?? '');
$half_time = trim($trip['half_day_time'] ?? '');

if (!empty($half_time)) {
    if ($trip['start_date'] === $trip['end_date']) {$schedule_prose = "ในวันที่ {$start_t} ({$half_time})";
    } else {
        $schedule_prose = "ตั้งแต่วันที่ {$start_t} ถึงวันที่ {$end_t}
