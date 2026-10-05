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
    $half_day_time         = $_POST['half_day_time'] ?? '';
    $expense_type          = $_POST['expense_type'] ?? '';
    $expense_specific_details = $_POST['expense_specific_details'] ?? '';
    $vehicle_type          = $_POST['vehicle_type'] ?? '';
    $vehicle_license_plate = $_POST['vehicle_license_plate'] ?? '';
    $driver_name           = $_POST['driver_name'] ?? '';

    $stmt = $conn->prepare("INSERT INTO official_trips 
        (doc_number, created_date, applicant_name, position, academic_standing, department, work_group, head_group_name, subject, destination, ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, vehicle_type, vehicle_license_plate, driver_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssss", 
        $doc_number, $created_date, $applicant_name, $position, $academic_standing, $department, $work_group, $head_group_name, $subject, $destination, $ref_document, $ref_date, $start_date, $end_date, $half_day_time, $expense_type, $expense_specific_details, $vehicle_type, $vehicle_license_plate, $driver_name);

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
