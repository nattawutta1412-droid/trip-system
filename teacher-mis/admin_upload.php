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
    fputcsv($output, ['id_card', 'prefix', 'first_name', 'last_name', 'gender', 'position', 'academic_standing', 'department', 'work_group', 'education_level', 'major_subject', 'phone_number', 'email', 'photo_url', 'sort_order']);
    fputcsv($output, ['1929900123456', 'นาย', 'สมศักดิ์', 'รักเรียน', 'ชาย', 'ครู (หัวหน้ากลุ่มสาระฯ)', 'ชำนาญการ', 'กลุ่มสาระการเรียนรู้คณิตศาสตร์', 'กลุ่มงานบริหารวิชาการ', 'ปริญญาโท', 'การสอนคณิตศาสตร์', '0812345678', 'somsak@example.com', 'https://example.com/photo.jpg', '1']);
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

// 3. ฟังก์ชัน: ลบข้อมูลครูรายบุคคล
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $del_id = intval($_GET['id']);
    $stmt_del = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt_del->bind_param("i", $del_id);
    if ($stmt_del->execute()) {
        $message = "ลบข้อมูลบุคลากรเรียบร้อยแล้ว";
        $message_type = "success";
    } else {
        $message = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
        $message_type = "danger";
    }
}

// 4. ฟังก์ชัน: บันทึกข้อมูลครู (เพิ่มใหม่ หรือ แก้ไข)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_single_teacher'])) {
    $teacher_id        = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    $id_card           = trim($_POST['id_card'] ?? '');
    $prefix            = trim($_POST['prefix'] ?? '');
    $first_name        = trim($_POST['first_name'] ?? '');
    $last_name         = trim($_POST['last_name'] ?? '');
    $gender            = trim($_POST['gender'] ?? 'ชาย');
    $position          = trim($_POST['position'] ?? '');
    $academic_standing = trim($_POST['academic_standing'] ?? '');
    $department        = trim($_POST['department'] ?? '');
    $work_group        = trim($_POST['work_group'] ?? '');
    $education_level   = trim($_POST['education_level'] ?? '');
    $major_subject     = trim($_POST['major_subject'] ?? '');
    $phone_number      = trim($_POST['phone_number'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $photo_url         = trim($_POST['photo_url'] ?? '');
    $sort_order        = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 999;

    if (empty($id_card) || empty($first_name) || empty($last_name)) {
        $message = "กรุณากรอกเลขบัตรประชาชน, ชื่อ และนามสกุล ให้ครบถ้วน";
        $message_type = "danger";
    } else {
        if ($teacher_id) {
            // อัปเดตข้อมูลเดิม
            $stmt = $conn->prepare("UPDATE teachers SET 
                id_card=?, prefix=?, first_name=?, last_name=?, gender=?, position=?, 
                academic_standing=?, department=?, work_group=?, education_level=?, 
                major_subject=?, phone_number=?, email=?, photo_url=?, sort_order=? WHERE id=?");
            $stmt->bind_param("ssssssssssssssii", 
                $id_card, $prefix, $first_name, $last_name, $gender, $position,
                $academic_standing, $department, $work_group, $education_level,
                $major_subject, $phone_number, $email, $photo_url, $sort_order, $teacher_id
            );
            if ($stmt->execute()) {
                $message = "อัปเดตข้อมูลคุณครู {$first_name} {$last_name} เรียบร้อยแล้ว";
                $message_type = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
                $message_type = "danger";
            }
        } else {
            // เพิ่มข้อมูลใหม่
            $stmt = $conn->prepare("INSERT INTO teachers 
                (id_card, prefix, first_name, last_name, gender, position, academic_standing, department, work_group, education_level, major_subject, phone_number, email, photo_url, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                prefix=VALUES(prefix), first_name=VALUES(first_name), last_name=VALUES(last_name),
                gender=VALUES(gender), position=VALUES(position), academic_standing=VALUES(academic_standing),
                department=VALUES(department), work_group=VALUES(work_group), education_level=VALUES(education_level),
                major_subject=VALUES(major_subject), phone_number=VALUES(phone_number), email=VALUES(email),
                photo_url=VALUES(photo_url), sort_order=VALUES(sort_order)");
            $stmt->bind_param("ssssssssssssssi", 
                $id_card, $prefix, $first_name, $last_name, $gender, $position,
                $academic_standing, $department, $work_group, $education_level,
                $major_subject, $phone_number, $email, $photo_url, $sort_order
            );
            if ($stmt->execute()) {
                $message = "บันทึกข้อมูลคุณครู {$first_name} {$last_name} สำเร็จเรียบร้อย";
                $message_type = "success";
            } else {
                $message = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// 5. นำเข้าไฟล์ CSV พร้อม sort_order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (!empty($file) && is_uploaded_file($file)) {
        $handle = fopen($file, "r");
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        fgetcsv($handle);

        $success_count = 0;
        $stmt = $conn->prepare("INSERT INTO teachers 
            (id_card, prefix, first_name, last_name, gender, position, academic_standing, department, work_group, education_level, major_subject, phone_number, email, photo_url, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            prefix=VALUES(prefix), first_name=VALUES(first_name), last_name=VALUES(last_name),
            gender=VALUES(gender), position=VALUES(position), academic_standing=VALUES(academic_standing),
            department=VALUES(department), work_group=VALUES(work_group), education_level=VALUES(education_level),
            major_subject=VALUES(major_subject), phone_number=VALUES(phone_number), email=VALUES(email),
            photo_url=VALUES(photo_url), sort_order=VALUES(sort_order)");

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
            $photo_url         = trim($row[13] ?? '');
            $sort_order        = (!empty($row[14]) && is_numeric($row[14])) ? intval($row[14]) : 999;

            $stmt->bind_param("ssssssssssssssi", 
                $id_card, $prefix, $first_name, $last_name, $gender, 
                $position, $academic_standing, $department, $work_group, 
                $education_level, $major_subject, $phone_number, $email, $photo_url, $sort_order
            );
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        fclose($handle);
        $message = "นำเข้าข้อมูลจาก CSV สำเร็จเรียบร้อยจำนวน {$success_count} รายการ";
        $message_type = "success";
    } else {
        $message = "เกิดข้อผิดพลาด: กรุณาเลือกไฟล์ .CSV ที่ถูกต้อง";
        $message_type = "danger";
    }
}

// 6. ดึงข้อมูลรายการครูเพื่อแสดงและจัดการ เรียงตาม sort_order ก่อน
$admin_search = trim($_GET['admin_search'] ?? '');
$sql_list = "SELECT * FROM teachers";
if (!empty($admin_search)) {
    $sql_list .= " WHERE first_name LIKE ? OR last_name LIKE ? OR department LIKE ? OR id_card LIKE ?";
    $stmt_l = $conn->prepare($sql_list . " ORDER BY sort_order ASC, id ASC");
    $s_term = "%{$admin_search}%";
    $stmt_l->bind_param("ssss", $s_term, $s_term, $s_term, $s_term);
    $stmt_l->execute();
    $all_teachers = $stmt_l->get_result();
} else {
    $all_teachers = $conn->query($sql_list . " ORDER BY sort_order ASC, id ASC");
}

$total_current = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];

$standard_depts = [
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
    <title>ระบบจัดการข้อมูลบุคลากร (Admin) - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 14px; border: none; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid px-4">
    <!-- แถบด้านบน Admin -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger px-3 py-2 fs-6"><i class="bi bi-shield-lock-fill"></i> ระบบจัดการหลังบ้าน (Admin)</span>
            <span class="text-muted small">บุคลากรปัจจุบัน: <strong><?php echo number_format($total_current); ?></strong> คน</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teacherModal" onclick="resetForm()">
                <i class="bi bi-person-plus-fill"></i> เพิ่มข้อมูลครู (รายคน)
            </button>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-house-door"></i> หน้าหลัก</a>
            <a href="admin_upload.php?action=logout" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- กล่องอัปโหลด CSV -->
    <div class="accordion mb-4" id="uploadAccordion">
        <div class="accordion-item rounded-4 border-0 shadow-sm overflow-hidden">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed bg-white fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#csvCollapse">
                    <i class="bi bi-cloud-arrow-up-fill me-2 fs-5"></i> นำเข้าข้อมูลแบบยกชุดด้วยไฟล์ CSV (คลิกเพื่อเปิด/ปิด)
                </button>
            </h2>
            <div id="csvCollapse" class="accordion-collapse collapse" data-bs-parent="#uploadAccordion">
                <div class="accordion-body bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <form action="admin_upload.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                <button type="submit" class="btn btn-success text-nowrap"><i class="bi bi-upload"></i> อัปโหลด CSV</button>
                            </form>
                        </div>
                        <div class="col-md-5 text-md-end mt-2 mt-md-0">
                            <a href="admin_upload.php?action=download_template" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-download"></i> ดาวน์โหลดเทมเพลต CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- รายการตารางจัดการข้อมูลครู -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>รายชื่อบุคลากรในระบบ (เรียงตามลำดับแสดงผล)</h5>
                
                <form method="GET" action="admin_upload.php" class="d-flex gap-2" style="max-width: 360px;">
                    <input type="text" name="admin_search" class="form-control form-control-sm" placeholder="ค้นหาชื่อ, กลุ่มสาระฯ..." value="<?php echo htmlspecialchars($admin_search); ?>">
                    <button type="submit" class="btn btn-sm btn-primary">ค้นหา</button>
                    <?php if (!empty($admin_search)): ?>
                        <a href="admin_upload.php" class="btn btn-sm btn-outline-secondary">ล้าง</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 7%;" class="text-center">ลำดับ</th>
                            <th style="width: 5%;">รูป</th>
                            <th>ชื่อ - สกุล</th>
                            <th>ตำแหน่ง</th>
                            <th>วิทยฐานะ</th>
                            <th>กลุ่มสาระการเรียนรู้</th>
                            <th>เบอร์โทร</th>
                            <th class="text-center" style="width: 15%;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_teachers && $all_teachers->num_rows > 0): ?>
                            <?php while ($t = $all_teachers->fetch_assoc()): 
                                $t_json = htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8');
                                $order_val = ($t['sort_order'] == 999) ? '-' : $t['sort_order'];
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-primary border fs-6"><?php echo $order_val; ?></span>
                                    </td>
                                    <td>
                                        <div style="width: 40px; height: 45px; border-radius: 6px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($t['photo_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($t['photo_url']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill text-muted fs-5"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis"><?php echo htmlspecialchars($t['academic_standing'] ?: '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                                    <td><?php echo htmlspecialchars($t['phone_number'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning me-1" onclick='editTeacher(<?php echo $t_json; ?>)' title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i> แก้ไข
                                        </button>
                                        <a href="admin_upload.php?action=delete&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันที่จะลบข้อมูลของ <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?> ใช่หรือไม่?');" title="ลบ">
                                            <i class="bi bi-trash"></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลบุคลากร</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับเพิ่ม / แก้ไขข้อมูลครู -->
<div class="modal fade" id="teacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-person-plus text-primary me-2"></i>เพิ่มข้อมูลครู</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_upload.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="save_single_teacher" value="1">
                    <input type="hidden" name="teacher_id" id="teacher_id" value="">

                    <!-- ลำดับก่อน-หลัง (ใส่เลขน้อยให้อยู่บนสุด เช่น 1 สำหรับหัวหน้ากลุ่มสาระ) -->
                    <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                        <span><strong>การจัดลำดับ:</strong> กำหนดตัวเลขน้อย (เช่น <strong>1</strong>) เพื่อให้แสดงอยู่บนสุด เช่น หัวหน้ากลุ่มสาระ หรือหัวหน้างาน</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-primary">ลำดับการแสดงผล (1, 2, ...)</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control border-primary" value="999" min="1">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">เลขบัตรประชาชน (13 หลัก) <span class="text-danger">*</span></label>
                            <input type="text" name="id_card" id="id_card" class="form-control" required maxlength="13">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">เพศ</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">คำนำหน้า <span class="text-danger">*</span></label>
                            <input type="text" name="prefix" id="prefix" class="form-control" required placeholder="นาย/นาง/นางสาว">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ตำแหน่ง <span class="text-danger">*</span></label>
                            <input type="text" name="position" id="position" class="form-control" required placeholder="เช่น ครู, หัวหน้ากลุ่มสาระฯ, ผู้อำนวยการ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">วิทยฐานะ</label>
                            <input type="text" name="academic_standing" id="academic_standing" class="form-control" placeholder="เช่น ชำนาญการ, ชำนาญการพิเศษ">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">กลุ่มสาระการเรียนรู้ / สังกัด <span class="text-danger">*</span></label>
                            <select name="department" id="department" class="form-select" required>
                                <option value="">-- เลือกกลุ่มสาระฯ / สังกัด --</option>
                                <?php foreach ($standard_depts as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">กลุ่มงานบริหาร</label>
                            <input type="text" name="work_group" id="work_group" class="form-control" placeholder="เช่น กลุ่มงานบริหารวิชาการ">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">วิชาเอก</label>
                            <input type="text" name="major_subject" id="major_subject" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ระดับการศึกษาสูงสุด</label>
                            <input type="text" name="education_level" id="education_level" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">อีเมล</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">URL รูปภาพครู</label>
                            <input type="url" name="photo_url" id="photo_url" class="form-control" placeholder="https://example.com/photo.jpg">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus text-primary me-2"></i>เพิ่มข้อมูลครู (รายคน)';
    document.getElementById('teacher_id').value = '';
    document.getElementById('sort_order').value = '999';
    document.getElementById('id_card').value = '';
    document.getElementById('prefix').value = '';
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('gender').value = 'ชาย';
    document.getElementById('position').value = '';
    document.getElementById('academic_standing').value = '';
    document.getElementById('department').value = '';
    document.getElementById('work_group').value = '';
    document.getElementById('major_subject').value = '';
    document.getElementById('education_level').value = '';
    document.getElementById('phone_number').value = '';
    document.getElementById('email').value = '';
    document.getElementById('photo_url').value = '';
}

function editTeacher(t) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>แก้ไขข้อมูลครู';
    document.getElementById('teacher_id').value = t.id || '';
    document.getElementById('sort_order').value = t.sort_order || '999';
    document.getElementById('id_card').value = t.id_card || '';
    document.getElementById('prefix').value = t.prefix || '';
    document.getElementById('first_name').value = t.first_name || '';
    document.getElementById('last_name').value = t.last_name || '';
    document.getElementById('gender').value = t.gender || 'ชาย';
    document.getElementById('position').value = t.position || '';
    document.getElementById('academic_standing').value = t.academic_standing || '';
    document.getElementById('department').value = t.department || '';
    document.getElementById('work_group').value = t.work_group || '';
    document.getElementById('major_subject').value = t.major_subject || '';
    document.getElementById('education_level').value = t.education_level || '';
    document.getElementById('phone_number').value = t.phone_number || '';
    document.getElementById('email').value = t.email || '';
    document.getElementById('photo_url').value = t.photo_url || '';

    var myModal = new bootstrap.Modal(document.getElementById('teacherModal'));
    myModal.show();
}
</script>
</body>
</html>
