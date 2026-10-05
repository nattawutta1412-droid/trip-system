<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $created_date = $_POST['created_date'];
    $department = $_POST['department'];
    $applicant_name = $_POST['applicant_name'];
    $position = $_POST['position'];
    $academic_standing = $_POST['academic_standing'];
    $subject = $_POST['subject'];
    $destination = $_POST['destination'];
    $ref_document = $_POST['ref_document'];
    $ref_date = !empty($_POST['ref_date']) ? $_POST['ref_date'] : NULL;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $half_day_time = $_POST['half_day_time'];
    $expense_type = $_POST['expense_type'];
    $expense_specific_details = $_POST['expense_specific_details'];
    $vehicle_type = $_POST['vehicle_type'];
    $vehicle_license_plate = $_POST['vehicle_license_plate'];
    $driver_name = $_POST['driver_name'];

    $sql = "INSERT INTO official_trips 
            (created_date, department, applicant_name, position, academic_standing, subject, destination, ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, vehicle_type, vehicle_license_plate, driver_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssss", 
        $created_date, $department, $applicant_name, $position, $academic_standing, 
        $subject, $destination, $ref_document, $ref_date, $start_date, $end_date, 
        $half_day_time, $expense_type, $expense_specific_details, $vehicle_type, 
        $vehicle_license_plate, $driver_name
    );

    if ($stmt->execute()) {
        $trip_id = $stmt->insert_id;

        // บันทึกรายชื่อครู
        if (!empty($_POST['teachers'])) {
            $stmt_t = $conn->prepare("INSERT INTO trip_participants (trip_id, participant_type, full_name, info_detail) VALUES (?, 'teacher', ?, ?)");
            foreach ($_POST['teachers'] as $key => $name) {
                if (!empty(trim($name))) {
                    $detail = $_POST['teacher_details'][$key];
                    $stmt_t->bind_param("iss", $trip_id, $name, $detail);
                    $stmt_t->execute();
                }
            }
        }

        // บันทึกรายชื่อนักเรียน
        if (!empty($_POST['students'])) {
            $stmt_s = $conn->prepare("INSERT INTO trip_participants (trip_id, participant_type, full_name, info_detail) VALUES (?, 'student', ?, ?)");
            foreach ($_POST['students'] as $key => $name) {
                if (!empty(trim($name))) {
                    $detail = $_POST['student_details'][$key];
                    $stmt_s->bind_param("iss", $trip_id, $name, $detail);
                    $stmt_s->execute();
                }
            }
        }

        header("Location: print.php?id=" . $trip_id);
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
    }
}
?>
