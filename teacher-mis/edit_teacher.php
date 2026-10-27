<?php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ไม่พบรหัสบุคลากร");
}

// 1. ตรวจสอบชื่อตารางบุคลากร
$target_table = 'teachers';
$chk_tbl = $conn->query("SHOW TABLES LIKE 'teachers'");
if (!$chk_tbl || $chk_tbl->num_rows == 0) {
    $possible_tables = ['teacher_profiles', 'personnel', 'users', 'staff'];
    foreach ($possible_tables as $pt) {
        $chk = $conn->query("SHOW TABLES LIKE '{$pt}'");
        if ($chk && $chk->num_rows > 0) {
            $target_table = $pt;
            break;
        }
    }
}

// ดึงรายชื่อคอลัมน์ทั้งหมดที่มีจริงในตาราง
$existing_columns = [];
$res_cols = $conn->query("SHOW COLUMNS FROM `{$target_table}`");
if ($res_cols) {
    while ($c = $res_cols->fetch_assoc()) {
        $existing_columns[] = $c['Field'];
    }
}

$message = "";
$message_type = "";

// 2. จัดการบันทึกข้อมูลเมื่อกด Submit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_val      = trim($_POST['name'] ?? '');
    $position_val  = trim($_POST['position'] ?? 'ครู');
    $academic_val  = trim($_POST['academic_standing'] ?? '');
    $dept_val      = trim($_POST['department'] ?? '');
    $phone_val     = trim($_POST['phone'] ?? '');
    $sort_val      = intval($_POST['sort_order'] ?? 0);
    $birth_val     = trim($_POST['birth_date'] ?? '');
    $assign_val    = trim($_POST['assign_date'] ?? '');

    $raw_inputs = [
        'name'              => $name_val,
        'fullname'          => $name_val,
        'teacher_name'      => $name_val,
        'position'          => $position_val,
        'academic_standing' => $academic_val,
        'department'        => $dept_val,
        'phone'             => $phone_val,
        'tel'               => $phone_val,
        'mobile'            => $phone_val,
        'sort_order'        => $sort_val,
        'birth_date'        => $birth_val,
        'birthday'          => $birth_val,
        'assign_date'       => $assign_val,
        'start_date'        => $assign_val
    ];

    $update_fields = [];
    foreach ($raw_inputs as $col => $val) {
        if (in_array($col, $existing_columns)) {
            $update_fields[] = "`{$col}` = '" . $conn->real_escape_string($val) . "'";
        }
    }

    if (!empty($update_fields)) {
        $sql_up = "UPDATE `{$target_table}` SET " . implode(', ', $update_fields) . " WHERE id = {$id}";
        if ($conn->query($sql_up)) {
            $message = "บันทึกและอัปเดตข้อมูลบุคลากรเรียบร้อยแล้ว";
            $message_type = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// 3. ดึงข้อมูลบุคลากรมาแสดงในฟอร์ม
$stmt = $conn->prepare("SELECT * FROM `{$target_table}` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลบุคลากรหมายเลข #{$id}");
}

// ตรวจหาค่าฟิลด์ต่างๆ รองรับหลายชื่อ
$cur_name = $data['name'] ?? $data['fullname'] ?? $data['teacher_name'] ?? '';
$cur_pos = $data['position'] ?? 'ครู';
$cur_academic = $data['academic_standing'] ?? '';
$cur_dept = $data['department'] ?? '';
$cur_phone = $data['phone'] ?? $data['tel'] ?? $data['mobile'] ?? '';
$cur_sort = $data['sort_order'] ?? $data['id'] ?? 0;
$cur_birth = $data['birth_date'] ?? $data['birthday'] ?? $data['dob'] ?? '';
$cur_assign = $data['assign_date'] ?? $data['start_date'] ?? $data['hire_date'] ?? '';

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
    <title>แก้ไขข้อมูลบุคลากร - <?php echo htmlspecialchars($cur_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 12px; }
        .section-title { font-weight: 700; color: #0d6efd; margin-bottom: 15px; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-pencil-square text-primary me-2"></i>แก้ไขข้อมูลบุคลากร #<?php echo $id; ?>
        </h4>
        <a href="admin_upload.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับหน้ารายชื่อ
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form action="edit_teacher.php?id=<?php echo $id; ?>" method="POST">
                
                <div class="section-title">ข้อมูลทั่วไป</div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">ลำดับแสดงผล:</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($cur_sort); ?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label small fw-bold">ชื่อ - สกุล: <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cur_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ตำแหน่ง:</label>
                        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($cur_pos); ?>" placeholder="เช่น ครู, ครูผู้ช่วย">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">วิทยฐานะ:</label>
                        <input type="text" name="academic_standing" class="form-control" value="<?php echo htmlspecialchars($cur_academic); ?>" placeholder="เช่น ชำนาญการ, ชำนาญการพิเศษ">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">กลุ่มสาระการเรียนรู้ / กลุ่มงาน:</label>
                        <select name="department" class="form-select">
                            <option value="">-- เลือกกลุ่มสาระฯ / สังกัด --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($cur_dept === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">เบอร์โทรศัพท์:</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($cur_phone); ?>" placeholder="เช่น 0812345678">
                    </div>
                </div>

                <div class="section-title mt-4">ข้อมูลประวัติและวันเวลา</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">วันเกิด (เช่น 08/02/2525 หรือ 1982-02-08):</label>
                        <input type="text" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($cur_birth); ?>" placeholder="วว/ดด/ปปปป (พ.ศ.)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">วันบรรจุ (เช่น 12/08/2549 หรือ 2006-08-12):</label>
                        <input type="text" name="assign_date" class="form-control" value="<?php echo htmlspecialchars($cur_assign); ?>" placeholder="วว/ดด/ปปปป (พ.ศ.)">
                    </div>
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
