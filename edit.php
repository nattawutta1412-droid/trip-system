<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: admin.php");
    exit();
}

$message = "";
$message_type = "success";

// เมื่อกดบันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_number        = trim($_POST['doc_number'] ?? '');
    $applicant_name    = trim($_POST['applicant_name'] ?? '');
    $position          = trim($_POST['position'] ?? '');
    $academic_standing = trim($_POST['academic_standing'] ?? '');
    $department        = trim($_POST['department'] ?? '');
    $work_group        = trim($_POST['work_group'] ?? '');
    $head_group_name   = trim($_POST['head_group_name'] ?? '');
    $subject           = trim($_POST['subject'] ?? '');
    $destination       = trim($_POST['destination'] ?? '');
    $ref_document      = trim($_POST['ref_document'] ?? '');
    $ref_date          = !empty($_POST['ref_date']) ? $_POST['ref_date'] : NULL;
    $start_date        = $_POST['start_date'] ?? '';
    $end_date          = $_POST['end_date'] ?? '';
    $half_day_time     = trim($_POST['half_day_time'] ?? '');
    $vehicle_type      = $_POST['vehicle_type'] ?? '';
    $vehicle_license_plate = trim($_POST['vehicle_license_plate'] ?? '');
    $driver_name       = trim($_POST['driver_name'] ?? '');
    $sign_mode         = $_POST['sign_mode'] ?? 'director';
    $acting_name       = ($sign_mode === 'acting') ? trim($_POST['acting_name'] ?? '') : '';
    $status            = $_POST['status'] ?? 'ปกติ';

    $update_sql = "UPDATE official_trips SET 
        doc_number = ?, applicant_name = ?, position = ?, academic_standing = ?, 
        department = ?, work_group = ?, head_group_name = ?, subject = ?, 
        destination = ?, ref_document = ?, ref_date = ?, start_date = ?, 
        end_date = ?, half_day_time = ?, vehicle_type = ?, vehicle_license_plate = ?, 
        driver_name = ?, sign_mode = ?, acting_name = ?, status = ?
        WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssssssssssssssssi",
        $doc_number, $applicant_name, $position, $academic_standing,
        $department, $work_group, $head_group_name, $subject,
        $destination, $ref_document, $ref_date, $start_date,
        $end_date, $half_day_time, $vehicle_type, $vehicle_license_plate,
        $driver_name, $sign_mode, $acting_name, $status, $id
    );

    if ($stmt->execute()) {
        $message = "บันทึกการแก้ไขข้อมูลเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
        $message_type = "danger";
    }
}

// ดึงข้อมูลเดิมมาแสดง
$stmt = $conn->prepare("SELECT * FROM official_trips WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("ไม่พบข้อมูลคำร้องที่ระบุ");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลคำร้อง #<?php echo $id; ?> - เจ้าหน้าที่งานบุคคล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; color: #333; }
        .edit-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 40px; }
        .section-header { font-weight: 600; color: #0d6efd; border-bottom: 2px solid #e9ecef; padding-bottom: 6px; margin-bottom: 18px; margin-top: 15px; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">แก้ไขข้อมูลคำร้องไปราชการ #<?php echo $id; ?></h4>
            <small class="text-muted">ผู้ขออนุมัติ: <?php echo htmlspecialchars($trip['applicant_name']); ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="print.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer"></i> ดูบันทึกข้อความ
            </a>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> กลับหลังบ้าน
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="edit-card">
        <form method="POST" action="edit.php?id=<?php echo $id; ?>">
            <div class="section-header">1. ข้อมูลเลขหนังสือและสถานะ</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">เลขที่หนังสือราชการ (ที่):</label>
                    <input type="text" name="doc_number" class="form-control" value="<?php echo htmlspecialchars($trip['doc_number'] ?? ''); ?>" placeholder="เช่น ศธ 04238.12/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">สถานะคำร้อง:</label>
                    <select name="status" class="form-select">
                        <option value="ปกติ" <?php echo ($trip['status'] ?? 'ปกติ') === 'ปกติ' ? 'selected' : ''; ?>>ปกติ</option>
                        <option value="ยกเลิก" <?php echo ($trip['status'] ?? '') === 'ยกเลิก' ? 'selected' : ''; ?>>ยกเลิก</option>
                    </select>
                </div>
            </div>

            <div class="section-header">2. ข้อมูลผู้ขออนุมัติ</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ชื่อ-สกุล:</label>
                    <input type="text" name="applicant_name" class="form-control" value="<?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ตำแหน่ง:</label>
                    <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($trip['position'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">วิทยฐานะ:</label>
                    <input type="text" name="academic_standing" class="form-control" value="<?php echo htmlspecialchars($trip['academic_standing'] ?? ''); ?>" placeholder="เช่น ชำนาญการพิเศษ">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">กลุ่มสาระการเรียนรู้ / สายงาน:</label>
                    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($trip['department'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">กลุ่มงานที่เสนอ:</label>
                    <input type="text" name="work_group" class="form-control" value="<?php echo htmlspecialchars($trip['work_group'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ชื่อหัวหน้ากลุ่มงาน:</label>
                    <input type="text" name="head_group_name" class="form-control" value="<?php echo htmlspecialchars($trip['head_group_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="section-header">3. รายละเอียดการเดินทาง</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">วัตถุประสงค์ (ไปราชการเพื่อ):</label>
                    <textarea name="subject" class="form-control" rows="2" required><?php echo htmlspecialchars($trip['subject'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">สถานที่ปลายทาง:</label>
                    <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($trip['destination'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">หนังสืออ้างอิง:</label>
                    <input type="text" name="ref_document" class="form-control" value="<?php echo htmlspecialchars($trip['ref_document'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ลงวันที่ของหนังสืออ้างอิง:</label>
                    <input type="date" name="ref_date" class="form-control" value="<?php echo htmlspecialchars($trip['ref_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ตั้งแต่วันที่:</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($trip['start_date'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ถึงวันที่:</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($trip['end_date'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ช่วงเวลา (กรณีครึ่งวัน):</label>
                    <input type="text" name="half_day_time" class="form-control" value="<?php echo htmlspecialchars($trip['half_day_time'] ?? ''); ?>" placeholder="เช่น เวลา 08.30 - 12.00 น.">
                </div>
            </div>

            <div class="section-header">4. พาหนะและผู้ลงนาม</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ยานพาหนะ:</label>
                    <input type="text" name="vehicle_type" class="form-control" value="<?php echo htmlspecialchars($trip['vehicle_type'] ?? ''); ?>" placeholder="เช่น รถยนต์ส่วนตัว / รถยนต์ราชการ">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">หมายเลขทะเบียน:</label>
                    <input type="text" name="vehicle_license_plate" class="form-control" value="<?php echo htmlspecialchars($trip['vehicle_license_plate'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">พนักงานขับรถ (กรณีรถราชการ):</label>
                    <input type="text" name="driver_name" class="form-control" value="<?php echo htmlspecialchars($trip['driver_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">รูปแบบการลงนามอนุมัติ:</label>
                    <select name="sign_mode" class="form-select">
                        <option value="director" <?php echo ($trip['sign_mode'] ?? 'director') === 'director' ? 'selected' : ''; ?>>ผู้อำนวยการโรงเรียน (ว่าที่ร้อยโทจักรเพชร์ พรมยศ)</option>
                        <option value="acting" <?php echo ($trip['sign_mode'] ?? '') === 'acting' ? 'selected' : ''; ?>>รองผู้อำนวยการ รักษาการในตำแหน่งผู้อำนวยการฯ</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ชื่อรอง ผอ. ที่รักษาการ:</label>
                    <input type="text" name="acting_name" class="form-control" value="<?php echo htmlspecialchars($trip['acting_name'] ?? ''); ?>" placeholder="ระบุเฉพาะกรณีเป็นรักษาการ">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                    <i class="bi bi-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
                <a href="admin.php" class="btn btn-secondary px-4 py-2 ms-2">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
