<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากแบบฟอร์ม
    $created_date = $_POST['created_date'] ?? date('Y-m-d');
    $applicant_name = $_POST['applicant_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $academic_standing = $_POST['academic_standing'] ?? '';
    $department = $_POST['department'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $ref_document = $_POST['ref_document'] ?? '';
    $ref_date = !empty($_POST['ref_date']) ? $_POST['ref_date'] : NULL;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $half_day_time = $_POST['half_day_time'] ?? '';

    $expense_type = $_POST['expense_type'] ?? 'no_expense';
    $expense_specific_details = $_POST['expense_specific_details'] ?? '';

    $vehicle_type = $_POST['vehicle_type'] ?? 'personal_car';
    $license_plate = '';
    $driver_name = '';

    if ($vehicle_type === 'personal_car') {
        $license_plate = $_POST['vehicle_license_plate'] ?? '';
    } elseif ($vehicle_type === 'official_car') {
        $license_plate = $_POST['official_car_plate'] ?? '';
        $driver_name = $_POST['driver_name'] ?? '';
    } else {
        $driver_name = $_POST['vehicle_other_detail'] ?? '';
    }

    // 1. บันทึกลงตารางหลัก official_trips
    $sql = "INSERT INTO official_trips 
            (created_date, applicant_name, position, academic_standing, department, subject, destination, 
             ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, 
             vehicle_type, vehicle_license_plate, driver_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssss", 
        $created_date, $applicant_name, $position, $academic_standing, $department, $subject, $destination,
        $ref_document, $ref_date, $start_date, $end_date, $half_day_time, $expense_type, $expense_specific_details,
        $vehicle_type, $license_plate, $driver_name
    );

    if ($stmt->execute()) {
        $trip_id = $conn->insert_id; // ได้รหัสคำร้องล่าสุด

        // 2. บันทึกครูที่ร่วมเดินทาง
        if (!empty($_POST['teacher_name'])) {
            $stmt_t = $conn->prepare("INSERT INTO trip_participants (trip_id, participant_type, full_name, info_detail) VALUES (?, 'teacher', ?, ?)");
            foreach ($_POST['teacher_name'] as $i => $t_name) {
                if (trim($t_name) !== '') {
                    $t_pos = $_POST['teacher_pos'][$i] ?? '';
                    $stmt_t->bind_param("iss", $trip_id, $t_name, $t_pos);
                    $stmt_t->execute();
                }
            }
        }

        // 3. บันทึกนักเรียนที่ร่วมเดินทาง
        if (!empty($_POST['student_name'])) {
            $stmt_s = $conn->prepare("INSERT INTO trip_participants (trip_id, participant_type, full_name, info_detail) VALUES (?, 'student', ?, ?)");
            foreach ($_POST['student_name'] as $i => $s_name) {
                if (trim($s_name) !== '') {
                    $s_grade = $_POST['student_grade'][$i] ?? '';
                    $stmt_s->bind_param("iss", $trip_id, $s_name, $s_grade);
                    $stmt_s->execute();
                }
            }
        }

        // แสดงผลหน้าสำเร็จพร้อมปุ่มดูเอกสารตราครุฑ
        echo "<div style='font-family: sans-serif; text-align: center; margin-top: 60px;'>";
        echo "<h2 style='color: #27ae60;'>บันทึกข้อมูลคำร้องสำเร็จแล้ว!</h2>";
        echo "<p style='font-size: 16px; color: #555;'>รหัสคำร้องของคุณคือ: <b>#" . $trip_id . "</b></p><br>";
        
        echo "<a href='print.php?id=" . $trip_id . "' target='_blank' style='display:inline-block; margin-right: 15px; padding: 12px 25px; background: #27ae60; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>📄 ดูและพิมพ์บันทึกข้อความ (ตราครุฑ)</a>";
        echo "<a href='form.php' style='display:inline-block; padding: 12px 20px; background: #7f8c8d; color: white; text-decoration: none; border-radius: 6px; font-size: 16px;'>+ กรอกคำร้องใหม่</a>";
        echo "</div>";

    } else {
        echo "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
    }
}
?>