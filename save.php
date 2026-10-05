<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ตรวจสอบและสร้างคอลัมน์ที่จำเป็นอัตโนมัติ
    $required_columns = [
        'work_group' => 'VARCHAR(150) NULL',
        'head_group_name' => 'VARCHAR(255) NULL',
        'half_day_time' => 'VARCHAR(100) NULL',
        'expense_other' => 'TEXT NULL',
        'expense_specific_details' => 'TEXT NULL'
    ];

    foreach ($required_columns as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM official_trips LIKE '{$col}'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE official_trips ADD COLUMN {$col} {$def}");
        }
    }

    // 2. ตรวจสอบและสร้างตารางเก็บผู้ร่วมเดินทางอัตโนมัติ
    $create_participants_table = "CREATE TABLE IF NOT EXISTS trip_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255) NULL,
        detail VARCHAR(255) NULL,
        type VARCHAR(50) DEFAULT 'teacher',
        INDEX (trip_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($create_participants_table);

    // 3. รับค่าจากฟอร์ม (doc_number เว้นว่างไว้รอสารบรรณออกเลข)
    $doc_number            = '';
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

    // 4. ค่าใช้จ่าย
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
    
    // 5. ยานพาหนะ
    $vehicle_type = "";
    if (!empty($_POST['expense_option_vehicle'])) {
        $vehicle_type = $_POST['vehicle_select'] ?? 'รถยนต์ส่วนตัว';
    }
    $vehicle_license_plate = $_POST['vehicle_license_plate'] ?? '';

    // 6. อื่นๆ
    $expense_other = "";
    if (!empty($_POST['expense_option_other']) && !empty($_POST['expense_other'])) {
        $expense_other = trim($_POST['expense_other']);
        $expense_parts[] = "อื่น ๆ: " . $expense_other;
    }

    $expense_type = !empty($expense_parts) ? implode(" | ", $expense_parts) : "ไม่ขอเบิกค่าใช้จ่าย";
    $expense_specific_details = isset($_POST['specific_items']) ? implode(", ", $_POST['specific_items']) : "";

    // 7. บันทึกคำร้อง
    $stmt = $conn->prepare("INSERT INTO official_trips 
        (doc_number, created_date, applicant_name, position, academic_standing, department, work_group, head_group_name, subject, destination, ref_document, ref_date, start_date, end_date, half_day_time, expense_type, expense_specific_details, vehicle_type, vehicle_license_plate, expense_other) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssssssssss", 
        $doc_number, $created_date, $applicant_name, $position, $academic_standing, $department, $work_group, $head_group_name, $subject, $destination, $ref_document, $ref_date, $start_date, $end_date, $half_day_time, $expense_type, $expense_specific_details, $vehicle_type, $vehicle_license_plate, $expense_other);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;

        // 8. บันทึกรายชื่อผู้ร่วมเดินทาง
        if (!empty($_POST['participants']) && is_array($_POST['participants'])) {
            $p_stmt = $conn->prepare("INSERT INTO trip_participants (trip_id, name, detail, position, type) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['participants'] as $p) {
                $p_name = trim($p['name'] ?? '');
                $p_detail = trim($p['detail'] ?? '');
                $p_type = $p['type'] ?? 'teacher';
                if ($p_name !== '') {
                    $p_stmt->bind_param("issss", $last_id, $p_name, $p_detail, $p_detail, $p_type);
                    $p_stmt->execute();
                }
            }
        }

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
