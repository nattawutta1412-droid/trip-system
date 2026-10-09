<?php
session_start();
if (!isset($_SESSION['teacher_admin_logged_in']) || $_SESSION['teacher_admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

require_once 'config.php';

$message = "";
$message_type = "info";

// 1. ดาวน์โหลดเทมเพลตมาตรฐาน
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=teacher_template.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['id_card', 'prefix', 'first_name', 'last_name', 'gender', 'position', 'academic_standing', 'department', 'work_group', 'education_level', 'major_subject', 'phone_number', 'email']);
    fputcsv($output, ['1929900123456', 'นาย', 'สมศักดิ์', 'รักเรียน', 'ชาย', 'ครู', 'ชำนาญการ', 'กลุ่มสาระการเรียนรู้คณิตศาสตร์', 'กลุ่มงานบริหารวิชาการ', 'ปริญญาโท', 'การสอนคณิตศาสตร์', '0812345678', 'somsak@example.com']);
    fclose($output);
    exit();
}

// 2. ออกจากระบบ
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['teacher_admin_logged_in']);
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// 3. ประมวลผลนำเข้าไฟล์ CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (!empty($file) && is_uploaded_file($file)) {
        $handle = fopen($file, "r");
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        fgetcsv($handle); // ข้ามหัวตาราง

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
            if (empty($row[0])) continue;

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
        $message = "อัปเดตและบันทึกข้อมูลบุคลากรเข้าสู่ระบบแล้ว {$success_count} รายการ";
        $message_type = "success";
    } else {
        $message = "เกิดข้อผิดพลาด: กรุณาเลือกไฟล์ .CSV ที่ถูกต้อง";
        $message_type = "danger";
    }
}

$total_current = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการหลังบ้าน (Admin Dashboard) - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="py-4">

<div class="container" style="max-width: 800px;">
    <!-- แถบด้านบนของ Admin -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger px-3 py-2 fs-6"><i class="bi bi-shield-lock"></i> ส่วนงานผู้ดูแลระบบ (Admin)</span>
            <span class="text-muted small">ปัจจุบันมีข้อมูล: <strong><?php echo number_format($total_current); ?></strong> คน</span>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">ดูหน้าเว็บไซต์หลัก</a>
            <a href="admin_upload.php?action=logout" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
        </div>
    </div>

    <!-- การ์ดฟอร์มอัปโหลด -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
            <h4 class="fw-bold mb-3"><i class="bi bi-cloud-arrow-up text-primary"></i> นำเข้า / อัปเดตข้อมูลบุคลากรทางการศึกษา</h4>
            <p class="text-muted small">ใช้สำหรับอัปโหลดข้อมูลครูและบุคลากรจาก Google Sheets หรือ Excel (ระบบใช้เลขบัตรประชาชนตรวจสอบข้อมูลซ้ำเพื่ออัปเดตอัตโนมัติ)</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="p-3 bg-light rounded-3 mb-4 border">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong class="text-dark small d-block"><i class="bi bi-filetype-csv"></i> รูปแบบไฟล์เทมเพลตมาตรฐาน</strong>
                        <span class="text-muted small">กรุณาจัดเรียงคอลัมน์ตามเทมเพลตที่ระบบกำหนด</span>
                    </div>
                    <a href="admin_upload.php?action=download_template" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> ดาวน์โหลดเทมเพลต CSV
                    </a>
                </div>
            </div>

            <form action="admin_upload.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-bold">เลือกไฟล์ข้อมูล (.CSV):</label>
                    <input type="file" name="csv_file" class="form-control form-control-lg" accept=".csv" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-medium">
                        <i class="bi bi-upload"></i> อัปโหลดและประมวลผลข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
