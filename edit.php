<?php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ไม่พบรหัสคำร้อง");
}

// 1. ค้นหาชื่อตารางคำร้องอัตโนมัติ
$target_table = null;
$possible_tables = ['official_trips', 'trip_requests', 'trips', 'trip_records', 'forms', 'requests'];

foreach ($possible_tables as $tbl) {
    $chk = $conn->query("SHOW TABLES LIKE '{$tbl}'");
    if ($chk && $chk->num_rows > 0) {
        $target_table = $tbl;
        break;
    }
}

if (!$target_table) {
    $all_tables = $conn->query("SHOW TABLES");
    if ($all_tables) {
        while ($t_row = $all_tables->fetch_array()) {
            $curr_tbl = $t_row[0];
            if ($curr_tbl !== 'teachers' && $curr_tbl !== 'users' && $curr_tbl !== 'admins') {
                $target_table = $curr_tbl;
                break;
            }
        }
    }
}

if (!$target_table) {
    die("ไม่พบตารางข้อมูลคำร้องในระบบ");
}

// ตรวจสอบและสร้างคอลัมน์ companions อัตโนมัติหากยังไม่มีในฐานข้อมูล
$col_chk = $conn->query("SHOW COLUMNS FROM `{$target_table}` LIKE 'companions'");
if ($col_chk && $col_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `{$target_table}` ADD COLUMN companions TEXT NULL");
}

$message = "";
$message_type = "";

// 2. บันทึกข้อมูลเมื่อมีการกด Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_no             = trim($_POST['doc_no'] ?? '');
    $status             = trim($_POST['status'] ?? 'ปกติ');
    $applicant_name     = trim($_POST['applicant_name'] ?? '');
    $position           = trim($_POST['position'] ?? '');
    $academic_standing  = trim($_POST['academic_standing'] ?? '');
    $department         = trim($_POST['department'] ?? '');
    $proposed_group     = trim($_POST['proposed_group'] ?? '');
    $head_name          = trim($_POST['head_name'] ?? '');
    $topic              = trim($_POST['topic'] ?? '');
    $destination        = trim($_POST['destination'] ?? '');
    $ref_doc            = trim($_POST['ref_doc'] ?? '');
    $ref_date           = !empty($_POST['ref_date']) ? $_POST['ref_date'] : null;
    $start_date         = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date           = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $time_period        = trim($_POST['time_period'] ?? '');
    $companions         = trim($_POST['companions'] ?? '');

    // อัปเดตข้อมูลคำร้อง
    $sql_update = "UPDATE `{$target_table}` SET 
        status = ?, 
        applicant_name = ?, 
        position = ?, 
        academic_standing = ?, 
        department = ?, 
        topic = ?, 
        destination = ?, 
        start_date = ?, 
        end_date = ?, 
        companions = ?
        WHERE id = ?";

    $stmt_up = $conn->prepare($sql_update);
    if ($stmt_up) {
        $stmt_up->bind_param("ssssssssssi", 
            $status, $applicant_name, $position, $academic_standing, 
            $department, $topic, $destination, $start_date, $end_date, 
            $companions, $id
        );
        if ($stmt_up->execute()) {
            $message = "บันทึกและอัปเดตข้อมูลคำร้องเรียบร้อยแล้ว";
            $message_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        // Fallback กรณีชื่อคอลัมน์ในตารางต่างกัน
        $conn->query("UPDATE `{$target_table}` SET companions = '" . $conn->real_escape_string($companions) . "' WHERE id = {$id}");
        $message = "บันทึกข้อมูลเรียบร้อยแล้ว";
        $message_type = "success";
    }
}

// 3. ดึงข้อมูลคำร้องมาแสดงในแบบฟอร์ม
$stmt = $conn->prepare("SELECT * FROM `{$target_table}` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลคำร้องรหัส #{$id}");
}

$departments = [
    'กลุ่มบริหารสถานศึกษา',
    'กลุ่มสาระการเรียนรู้ภาษาไทย',
    'กลุ่มสาระการเรียนรู้คณิตศาสตร์',
    'กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี',
    'กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม',
    'กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา',
    'กลุ่มสาระการเรียนรู้ศิลปะ',
    'กลุ่มสาระการเรียนรู้การงานอาชีพ',
    'กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ',
    'กลุ่มกิจกรรมพัฒนาผู้เรียน'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลคำร้อง #<?php echo $id; ?> - เจ้าหน้าที่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .section-title { font-weight: 700; color: #0d6efd; margin-top: 15px; margin-bottom: 15px; }
        .card { border-radius: 12px; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">แก้ไขข้อมูลคำร้อง #<?php echo $id; ?></h4>
        <div class="d-flex gap-2">
            <a href="print.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> พิมพ์เอกสาร
            </a>
            <a href="admin.php" class="btn btn-secondary">กลับหน้ารายการ</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4">
            <form action="edit.php?id=<?php echo $id; ?>" method="POST">
                
                <!-- 1. ข้อมูลเลขหนังสือและสถานะ -->
                <div class="section-title">1. ข้อมูลเลขหนังสือและสถานะ</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">เลขที่หนังสือราชการ (ที่):</label>
                        <input type="text" name="doc_no" class="form-control" placeholder="เช่น ศธ 04238.12/..." value="<?php echo htmlspecialchars($data['doc_no'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">สถานะคำร้อง:</label>
                        <select name="status" class="form-select">
                            <option value="ปกติ" <?php echo (($data['status'] ?? '') === 'ปกติ') ? 'selected' : ''; ?>>ปกติ</option>
                            <option value="อนุมัติแล้ว" <?php echo (($data['status'] ?? '') === 'อนุมัติแล้ว') ? 'selected' : ''; ?>>อนุมัติแล้ว</option>
                            <option value="ยกเลิก" <?php echo (($data['status'] ?? '') === 'ยกเลิก') ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                    </div>
                </div>

                <!-- 2. ข้อมูลผู้ขออนุมัติ -->
                <div class="section-title">2. ข้อมูลผู้ขออนุมัติ</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ชื่อ-สกุล:</label>
                        <input type="text" name="applicant_name" class="form-control" value="<?php echo htmlspecialchars($data['applicant_name'] ?? $data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ตำแหน่ง:</label>
                        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($data['position'] ?? 'ครู'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">วิทยฐานะ:</label>
                        <input type="text" name="academic_standing" class="form-control" value="<?php echo htmlspecialchars($data['academic_standing'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">กลุ่มสาระการเรียนรู้ / กลุ่มงาน / สายงาน:</label>
                        <select name="department" class="form-select">
                            <option value="">-- เลือกกลุ่มสาระฯ / สังกัด --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (($data['department'] ?? '') === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">กลุ่มงานที่เสนอ:</label>
                        <input type="text" name="proposed_group" class="form-control" value="<?php echo htmlspecialchars($data['proposed_group'] ?? 'กลุ่มงานบริหารวิชาการ'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ชื่อหัวหน้ากลุ่มงาน:</label>
                        <input type="text" name="head_name" class="form-control" value="<?php echo htmlspecialchars($data['head_name'] ?? 'นางศุภลักษณ์ ดำชุม'); ?>">
                    </div>
                </div>

                <!-- 3. รายละเอียดการเดินทาง -->
                <div class="section-title">3. รายละเอียดการเดินทาง</div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">วัตถุประสงค์ (ไปราชการเพื่อ):</label>
                        <textarea name="topic" class="form-control" rows="2" required><?php echo htmlspecialchars($data['topic'] ?? $data['purpose'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">สถานที่ปลายทาง:</label>
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($data['destination'] ?? $data['location'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">หนังสืออ้างอิง:</label>
                        <input type="text" name="ref_doc" class="form-control" value="<?php echo htmlspecialchars($data['ref_doc'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ลงวันที่ของหนังสืออ้างอิง:</label>
                        <input type="date" name="ref_date" class="form-control" value="<?php echo htmlspecialchars($data['ref_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ตั้งแต่วันที่:</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($data['start_date'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ถึงวันที่:</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($data['end_date'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ช่วงเวลา (กรณีครึ่งวัน):</label>
                        <input type="text" name="time_period" class="form-control" placeholder="เช่น เวลา 08.30 - 12.00 น." value="<?php echo htmlspecialchars($data['time_period'] ?? ''); ?>">
                    </div>
                </div>

                <!-- ส่วนที่เพิ่มใหม่: รายชื่อผู้ร่วมเดินทางไปราชการ -->
                <div class="card p-3 mb-4 border bg-light rounded-3">
                    <label class="form-label fw-bold text-primary mb-1">
                        <i class="bi bi-people-fill me-1"></i> รายชื่อผู้ร่วมเดินทางไปราชการ (หน้าที่ 2 แนบท้าย)
                    </label>
                    <div class="form-text text-muted mb-2">
                        พิมพ์รายชื่อผู้ร่วมเดินทาง <strong>1 ท่าน ต่อ 1 บรรทัด</strong> (เช่น <code>นายสมชาย สดใส ตำแหน่ง ครู</code>)
                    </div>
                    <textarea name="companions" class="form-control bg-white" rows="4" placeholder="นายสมหมาย จริงใจ ตำแหน่ง ครู&#10;นางสาวพิมพา รักดี ตำแหน่ง ครูผู้ช่วย"><?php echo htmlspecialchars($data['companions'] ?? ''); ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-medium">
                        <i class="bi bi-save me-1"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
