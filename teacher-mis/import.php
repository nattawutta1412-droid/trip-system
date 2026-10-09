<?php
require_once 'config.php';

$message = "";
$message_type = "info";

// 1. จัดการดาวน์โหลดไฟล์เทมเพลตมาตรฐาน
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=teacher_template.csv');
    $output = fopen('php://output', 'w');
    // เขียน BOM เพื่อให้ภาษาไทยใน Excel แสดงผลถูกต้อง ไม่เป็นภาษาต่างดาว
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // หัวตาราง
    fputcsv($output, ['id_card', 'prefix', 'first_name', 'last_name', 'gender', 'position', 'academic_standing', 'department', 'work_group', 'education_level', 'major_subject', 'phone_number', 'email']);
    // ตัวอย่างข้อมูล
    fputcsv($output, ['1929900123456', 'นาย', 'สมศักดิ์', 'รักเรียน', 'ชาย', 'ครู', 'ชำนาญการ', 'กลุ่มสาระการเรียนรู้คณิตศาสตร์', 'กลุ่มงานบริหารวิชาการ', 'ปริญญาโท', 'การสอนคณิตศาสตร์', '0812345678', 'somsak@example.com']);
    fclose($output);
    exit();
}

// 2. ประมวลผลการอัปโหลดไฟล์ CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (!empty($file) && is_uploaded_file($file)) {
        $handle = fopen($file, "r");

        // ตรวจสอบและข้าม BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // ข้ามบรรทัดที่ 1 (หัวตาราง)
        fgetcsv($handle);

        $success_count = 0;
        $stmt = $conn->prepare("INSERT INTO teachers 
            (id_card, prefix, first_name, last_name, gender, position, academic_standing, department, work_group, education_level, major_subject, phone_number, email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            prefix=VALUES(prefix), first_name=VALUES(first_name), last_name=VALUES(last_name),
            gender=VALUES(gender), position=VALUES(position), academic_standing=VALUES(academic_standing),
            department=VALUES(department), work_group=VALUES(work_group), education_level=VALUES(education_level),
            major_subject=VALUES(major_subject), phone_number=VALUES(phone_number), email=VALUES(email)");

        while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
            if (empty($row[0])) continue; // ข้ามบรรทัดว่าง

            $id_card           = trim($row[0]);
            $prefix            = trim($row[1] ?? '');
            $first_name        = trim($row[2] ?? '');
            $last_name         = trim($row[3] ?? '');
            $gender            = trim($row[4] ?? 'ชาย');
            $position          = trim($row[5] ?? '');
            $academic_standing = trim($row[6] ?? '');
            $department        = trim($row[7] ?? '');
            $work_group        = trim($row[8] ?? '');
            $education_level   = trim($row[9] ?? '');
            $major_subject     = trim($row[10] ?? '');
            $phone_number      = trim($row[11] ?? '');
            $email             = trim($row[12] ?? '');

            $stmt->bind_param("sssssssssssss", 
                $id_card, $prefix, $first_name, $last_name, $gender, 
                $position, $academic_standing, $department, $work_group, 
                $education_level, $major_subject, $phone_number, $email
            );

            if ($stmt->execute()) {
                $success_count++;
            }
        }
        fclose($handle);
        $message = "นำเข้าและปรับปรุงข้อมูลบุคลากรเรียบร้อยแล้วทั้งหมด {$success_count} รายการ";
        $message_type = "success";
    } else {
        $message = "กรุณาเลือกไฟล์ .CSV ที่ถูกต้อง";
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าข้อมูลบุคลากร (CSV) - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body class="py-5">
<div class="container" style="max-width: 700px;">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet text-success"></i> นำเข้าข้อมูลครูผ่าน CSV / Google Sheets</h4>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info border-0 rounded-3">
                <h6 class="fw-bold mb-1"><i class="bi bi-info-circle"></i> คำแนะนำการเตรียมไฟล์:</h6>
                <ol class="small mb-2 ps-3">
                    <li>หากใช้ Google Sheets หรือ Excel ให้กรอกข้อมูลตามเทมเพลตมาตรฐาน</li>
                    <li>เมื่อกรอกเสร็จ เลือก <strong>File &gt; Download &gt; Comma Separated Values (.csv)</strong></li>
                    <li>ระบบจะใช้ <strong>เลขบัตรประชาชน</strong> ตรวจสอบ หากมีอยู่แล้วจะอัปเดตข้อมูลให้อัตโนมัติ</li>
                </ol>
                <a href="import.php?action=download_template" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> ดาวน์โหลดเทมเพลตไฟล์ CSV
                </a>
            </div>

            <form action="import.php" method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">เลือกไฟล์ .CSV เพื่อนำเข้า:</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">กลับหน้ารายการ</a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-cloud-arrow-up"></i> เริ่มนำเข้าข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
