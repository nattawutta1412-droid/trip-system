<?php
session_start();
require_once 'config.php';

// ฟังก์ชันแปลงวันที่ให้แสดงเป็น วัน/เดือน/ปี พ.ศ. อย่างถูกต้อง
function format_date_th($date_str) {
    if (empty($date_str) || in_array(trim($date_str), ['0000-00-00', '-', 'null', 'NULL', ''])) {
        return "-";
    }

    $date_str = trim($date_str);
    $d = '';
    $m = '';
    $y = 0;

    if (strpos($date_str, '/') !== false) {
        $parts = explode('/', $date_str);
        if (count($parts) === 3) {
            $d = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
            $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
            $y = intval(trim($parts[2]));
        }
    } elseif (strpos($date_str, '-') !== false) {
        $parts = explode('-', $date_str);
        if (count($parts) === 3) {
            if (strlen(trim($parts[0])) === 4) {
                $y = intval(trim($parts[0]));
                $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
                $d = str_pad(trim($parts[2]), 2, '0', STR_PAD_LEFT);
            } else {
                $d = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
                $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
                $y = intval(trim($parts[2]));
            }
        }
    }

    if (!empty($d) && !empty($m) && $y > 0) {
        // หากเป็น ค.ศ. (1900 - 2100) ให้แปลงเป็น พ.ศ. ด้วยการ +543
        if ($y >= 1900 && $y <= 2100) {
            $y += 543;
        }
        return "{$d}/{$m}/{$y}";
    }

    return htmlspecialchars($date_str);
}

// ตรวจสอบชื่อตารางบุคลากร
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

// ตรวจสอบคอลัมน์ทั้งหมดที่มีจริงในตาราง
$table_cols = [];
$c_res = $conn->query("SHOW COLUMNS FROM `{$target_table}`");
if ($c_res) {
    while ($c = $c_res->fetch_assoc()) {
        $table_cols[] = $c['Field'];
    }
}

$message = "";
$message_type = "";

// จัดการการลบข้อมูล
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt_del = $conn->prepare("DELETE FROM `{$target_table}` WHERE id = ?");
    $stmt_del->bind_param("i", $del_id);
    if ($stmt_del->execute()) {
        $message = "ลบข้อมูลบุคลากรเรียบร้อยแล้ว";
        $message_type = "success";
    } else {
        $message = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
        $message_type = "danger";
    }
}

// รับค่าคำค้นหา
$search = trim($_GET['admin_search'] ?? $_GET['search'] ?? '');
$sql = "SELECT * FROM `{$target_table}`";
if (!empty($search)) {
    $s_esc = $conn->real_escape_string($search);
    $conds = [];
    foreach ($table_cols as $tc) {
        if (!in_array($tc, ['id', 'image', 'photo', 'sort_order'])) {
            $conds[] = "`{$tc}` LIKE '%{$s_esc}%'";
        }
    }
    if (!empty($conds)) {
        $sql .= " WHERE " . implode(' OR ', $conds);
    }
}

// การจัดเรียงลำดับ
if (in_array('sort_order', $table_cols)) {
    $sql .= " ORDER BY sort_order ASC, id ASC";
} else {
    $sql .= " ORDER BY id ASC";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลบุคลากร (Admin) - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 12px; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
        .table th { background-color: #f8f9fa; font-weight: 600; }
        .user-avatar { width: 34px; height: 34px; object-fit: cover; border-radius: 50%; }
        .badge-academic { font-weight: normal; font-size: 0.85em; }
    </style>
</head>
<body class="pb-5">

<!-- แถบเมนูด้านบน (Navbar) -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4 py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="admin_upload.php">
            <i class="bi bi-person-workspace fs-4 me-2"></i> ระบบจัดการสารสนเทศบุคลากร
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <div class="navbar-nav ms-auto d-flex flex-wrap gap-2 align-items-center mt-2 mt-lg-0">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-house-door me-1"></i> หน้าสารสนเทศ
                </a>
                <a href="retirement.php" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-hourglass-split me-1"></i> ตารางเกษียณอายุ
                </a>
                <a href="export_summary.php" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> สรุปข้อมูล
                </a>
                <a href="export_word.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-word me-1"></i> ส่งออก Word
                </a>
                <a href="admin_users.php" class="btn btn-sm btn-outline-warning text-dark">
                    <i class="bi bi-shield-lock me-1"></i> จัดการผู้ดูแล
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- แถบนำเข้าข้อมูล CSV -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2">
            <a class="text-decoration-none text-primary fw-bold small" data-bs-toggle="collapse" href="#importCollapse" role="button" aria-expanded="false">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> นำเข้าข้อมูลแบบยกชุดด้วยไฟล์ CSV (คลิกเพื่อเปิด/ปิด)
            </a>
        </div>
        <div class="collapse" id="importCollapse">
            <div class="card-body p-3">
                <form action="import.php" method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">อัปโหลด CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ข้อความแจ้งเตือน -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show py-2">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ตารางรายชื่อบุคลากร -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people-fill text-primary me-1"></i> รายชื่อบุคลากรในระบบ (เรียงตามลำดับแสดงผล)
            </h6>
            <form action="admin_upload.php" method="GET" class="d-flex gap-2">
                <input type="text" name="admin_search" class="form-control form-control-sm" placeholder="ค้นหาชื่อ, กลุ่มสาระฯ..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm px-3">ค้นหา</button>
                <?php if (!empty($search)): ?>
                    <a href="admin_upload.php" class="btn btn-outline-secondary btn-sm">ล้างค่า</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 5%;">ลำดับ</th>
                            <th style="width: 5%;">รูป</th>
                            <th class="text-start" style="width: 20%;">ชื่อ - สกุล</th>
                            <th class="text-start" style="width: 18%;">ตำแหน่ง / วิทยฐานะ</th>
                            <th class="text-start" style="width: 18%;">กลุ่มสาระการเรียนรู้</th>
                            <th style="width: 10%;">วันเกิด</th>
                            <th style="width: 10%;">วันบรรจุ</th>
                            <th style="width: 10%;">เบอร์โทร</th>
                            <th style="width: 14%;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    // 1. ตรวจสอบชื่อ-สกุล
                                    $name_val = '';
                                    foreach (['name', 'fullname', 'teacher_name', 'first_name'] as $k) {
                                        if (!empty($row[$k]) && !preg_match('/[0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4}/', $row[$k])) {
                                            $name_val = $row[$k];
                                            break;
                                        }
                                    }

                                    // 2. ตรวจสอบตำแหน่งและวิทยฐานะ
                                    $pos_val = 'ครู';
                                    foreach (['position', 'pos'] as $k) {
                                        if (!empty($row[$k]) && !preg_match('/[0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4}/', $row[$k])) {
                                            $pos_val = $row[$k];
                                            break;
                                        }
                                    }
                                    $academic_val = $row['academic_standing'] ?? $row['academic_position'] ?? $row['academic'] ?? '';

                                    // 3. ตรวจสอบกลุ่มสาระฯ
                                    $dept_val = '-';
                                    foreach (['department', 'dept', 'learning_area', 'group_name'] as $k) {
                                        if (!empty($row[$k]) && !in_array($row[$k], ['ครู', 'ครูผู้ช่วย'])) {
                                            $dept_val = $row[$k];
                                            break;
                                        }
                                    }

                                    // 4. ตรวจสอบวันเกิดและวันบรรจุ (ป้องกันการดึงค่าสลับกับตำแหน่ง)
                                    $birth_val = '';
                                    foreach (['birth_date', 'birthday', 'dob'] as $k) {
                                        if (!empty($row[$k])) { $birth_val = $row[$k]; break; }
                                    }
                                    $assign_val = '';
                                    foreach (['assign_date', 'start_date', 'hire_date', 'appoint_date'] as $k) {
                                        if (!empty($row[$k])) { $assign_val = $row[$k]; break; }
                                    }

                                    // 5. ตรวจสอบเบอร์โทร
                                    $phone_val = '-';
                                    foreach (['phone', 'tel', 'mobile', 'telephone'] as $k) {
                                        if (!empty($row[$k])) { $phone_val = $row[$k]; break; }
                                    }

                                    $img_src = $row['image'] ?? $row['photo'] ?? $row['picture'] ?? '';
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['sort_order'] ?? $row['id']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($img_src)): ?>
                                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="avatar" class="user-avatar border" onerror="this.src='../garuda.png';">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle text-muted fs-4"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start">
                                        <strong><?php echo htmlspecialchars($name_val ?: '-'); ?></strong>
                                    </td>
                                    <td class="text-start">
                                        <?php echo htmlspecialchars($pos_val); ?>
                                        <?php if (!empty($academic_val)): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 badge-academic ms-1">
                                                <?php echo htmlspecialchars($academic_val); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start"><?php echo htmlspecialchars($dept_val); ?></td>
                                    
                                    <!-- วันเกิด พ.ศ. -->
                                    <td class="text-center">
                                        <?php echo format_date_th($birth_val); ?>
                                    </td>

                                    <!-- วันบรรจุ พ.ศ. -->
                                    <td class="text-center">
                                        <?php echo format_date_th($assign_val); ?>
                                    </td>

                                    <td class="text-center"><?php echo htmlspecialchars($phone_val); ?></td>
                                    <td class="text-center">
                                        <a href="edit_teacher.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning py-0 px-2" title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i> แก้ไข
                                        </a>
                                        <a href="admin_upload.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('ยืนยันที่จะลบข้อมูลบุคลากรท่านนี้?');" title="ลบ">
                                            <i class="bi bi-trash"></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูลบุคลากร</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
