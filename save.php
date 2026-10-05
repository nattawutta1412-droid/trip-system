<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_number            = $_POST['doc_number'] ?? '';
    $created_date          = $_POST['created_date'] ?? date('Y-m-d');
    $applicant_name        = $_POST['applicant_name'] ?? '';
    $position              = $_POST['position'] ?? '';
    $academic_standing     = $_POST['academic_standing'] ?? '';
    $department            = $_POST['department'] ?? '';
    $work_group            = $_POST['work_group'] ?? '';
    $head_group_name       = $_POST['head_group_name'] ?? '';
    $subject               = $_POST['subject'] ?? '';
    $destination           = $_POST['destination'] ?? '';
    $ref_document          = $_POST['ref_document'] ?? '';
    $ref_date              = !empty($_POST['ref_date']) ? $_POST['ref_date'] : NULL;
    $start_date            = $_POST['start_date'] ?? '';
    $end_date              = $_POST['end_date'] ?? '';
    $half_day_time         = trim($_POST['half_day_time'] ?? '');

    // ค่าใช้จ่าย
    $expense_parts = [];
    if (!empty($_POST['expense_option_no'])) {
        $expense_parts[] = "ไม่ขอเบิกค่าใช้จ่าย";
    }
    if (!empty($_POST['expense_option_school'])) {
        $expense_parts[] = "ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา (ค่ายานพาหนะเดินทาง, ค่าเบี้ยเลี้ยง, ค่าที่พัก) ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ";
    }
    if (!empty($_POST['expense_option_specific'])) {
        $specific_items = isset($_POST['specific_items']) ? implode(", ", $_POST['specific_items']) : "";
        $expense_parts[] = "ขอเบิกเฉพาะค่าใช้จ่าย (" . $specific_items . ")";
    }
    
    // ยานพาหนะ
    $vehicle_type = "";
    if (!empty($_POST['expense_option_vehicle'])) {
        $vehicle_type = $_POST['vehicle_select'] ?? 'รถยนต์ส่วนตัว';
    }
    $vehicle_license_plate = $_POST['vehicle_license_plate'] ?? '';

    // อื่นๆ
    $expense_other = "";
    if (!empty($_POST['expense_option_other']) && !empty($_POST['expense_other'])) {
        $expense_other = trim($_POST['expense_other']);
        $expense_parts[] = "อื่น ๆ: " . $expense_other;
    }

    $expense_type = !empty($expense_parts) ? implode(" | ", $expense_parts) : "ไม่ขอเบิกค่าใช้จ่าย";
    $expense_specific_details = isset($_POST['specific_items']) ? implode(", ", $_POST['specific_items']) : "";

    $stmt = $conn->prepare("INSERT INTO official_trips 
        (doc_number, created_date, applicant_name, position, academic_standing, department, work_group, head_group_name, subject, destination, ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, vehicle_type, vehicle_license_plate, expense_other) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssss", 
        $doc_number, $created_date, $applicant_name, $position, $academic_standing, $department, $work_group, $head_group_name, $subject, $destination, $ref_document, $ref_date, $start_date, $end_date, $half_day_time, $expense_type, $expense_specific_details, $vehicle_type, $vehicle_license_plate, $expense_other);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        header("Location: print.php?id=" . $last_id);
        exit();
    } else {
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error);
    }
} else {
    header("Location: form.php");
    exit();
}
?>
