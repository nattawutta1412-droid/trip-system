<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_number            = $_POST['doc_number'] ?? '';
    $created_date          = $_POST['created_date'] ?? date('Y-m-d');
    $applicant_name        = $_POST['applicant_name'] ?? '';
    $position              = $_POST['position'] ?? '';
    $academic_standing     = $_POST['academic_standing'] ?? '';
    $department            = $_POST['department'] ?? '';
    $head_name             = $_POST['head_name'] ?? '';
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

    // จัดการอัปโหลดไฟล์แนบ
    $attached_file = NULL;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = $_FILES['attachment']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // สุ่มชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำและตัดปัญหาภาษาไทยในชื่อไฟล์
        $new_filename = uniqid('file_', true) . '.' . $file_ext;
        $destination_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $destination_path)) {
            $attached_file = $new_filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO official_trips 
        (doc_number, created_date, applicant_name, position, academic_standing, department, head_name, subject, destination, ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, vehicle_type, vehicle_license_plate, driver_name, attached_file) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssss", 
        $doc_number, $created_date, $applicant_name, $position, $academic_standing, $department, $head_name, $subject, $destination, $ref_document, $ref_date, $start_date, $end_date, $half_day_time, $expense_type, $expense_specific_details, $vehicle_type, $vehicle_license_plate, $driver_name, $attached_file);

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
