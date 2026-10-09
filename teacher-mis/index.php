<?php
require_once 'config.php';

// 1. ดึงสถิติจำนวนรวม
$total_teachers = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];

// 2. ดึงสถิติแยกตามกลุ่มสาระฯ
$dept_stats = $conn->query("SELECT department, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY department ORDER BY count DESC");

// 3. ดึงสถิติแยกตามวิทยฐานะ
$academic_stats = $conn->query("SELECT IF(academic_standing IS NULL OR academic_standing='', 'ไม่มีวิทยฐานะ/ครูผู้ช่วย', academic_standing) as standing, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY standing ORDER BY count DESC");

// 4. ตัวกรองค้นหา
$search = trim($_GET['search'] ?? '');
$dept_filter = trim($_GET['department'] ?? '');

$where = ["status='ปฏิบัติหน้าที่'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR id_card LIKE ? OR major_subject LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
    $types .= "ssss";
}
if (!empty($dept_filter)) {
    $where[] = "department = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

$where_sql = implode(" AND ", $where);
$sql = "SELECT * FROM teachers WHERE {$where_sql} ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teachers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบสารสนเทศบุคลากรและทำเนียบครู - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .stat-card { border-radius: 12px; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .school-logo { width: 58px; height: auto; object-fit: contain; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid px-4">
    <!-- ส่วนหัวพร้อมโลโก้โรงเรียนและปุ่มเครื่องมือ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 bg-white p-3 rounded-4 shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <img src="../logo.png" alt="โลโก้โรงเรียน" class="school-logo">
            <div>
                <h3 class="fw-bold mb-0 text-dark">ระบบสารสนเทศบุคลากรทางการศึกษา (Teacher MIS)</h3>
                <span class="text-muted fw-medium">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="export_word.php" class="btn btn-info text-white fw-medium">
                <i class="bi bi-file-earmark-word"></i> ส่งออกรายงานสรุป (Word)
            </a>
            <a href="export_summary.php" class="btn btn-primary fw-medium">
                <i class="bi bi-file-earmark-excel"></i> ส่งออกข้อมูล (Excel)
            </a>
            <a href="import.php" class="btn btn-success fw-medium">
                <i class="bi bi-file-earmark-arrow-up"></i> นำเข้าข้อมูล (CSV / Sheets)
            </a>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> ไประบบขอไปราชการ
            </a>
        </div>
    </div>

    <!-- สรุปสถิติ 3 การ์ด -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white shadow-sm p-3 h-100 justify-content-center">
                <div class="small opacity-75">บุคลากรที่ปฏิบัติหน้าที่ทั้งหมด</div>
                <div class="fs-2 fw-bold"><?php echo number_format($total_teachers); ?> <span class="fs-6 fw-normal">คน</span></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card stat-card bg-white shadow-sm p-3 h-100">
                <div class="small fw-bold text-muted mb-2">สัดส่วนวิทยฐานะ:</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php while ($ac = $academic_stats->fetch_assoc()): ?>
                        <span class="badge bg-light text-dark border">
                            <?php echo htmlspecialchars($ac['standing']); ?>: <strong><?php echo $ac['count']; ?></strong>
                        </span>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm p-3 h-100">
                <div class="small fw-bold text-muted mb-2">กลุ่มสาระการเรียนรู้ที่มีครูสูงสุด:</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php 
                    $limit = 0;
                    while ($dp = $dept_stats->fetch_assoc()): 
                        if ($limit++ >= 3) break;
                    ?>
                        <span class="badge bg-secondary-subtle text-secondary small">
                            <?php echo htmlspecialchars($dp['department']); ?> (<?php echo $dp['count']; ?>)
                        </span>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางรายชื่อและตัวกรอง -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ, นามสกุล, วิชาเอก..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="department" class="form-select">
                        <option value="">-- ทุกกลุ่มสาระการเรียนรู้ / กลุ่มงาน --</option>
                        <?php 
                        $dept_list = $conn->query("SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
                        while ($d = $dept_list->fetch_assoc()):
                        ?>
                            <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php echo ($dept_filter === $d['department']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['department']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> ค้นหา</button>
                    <a href="index.php" class="btn btn-outline-secondary">ล้าง</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อ - สกุล</th>
                            <th>ตำแหน่ง</th>
                            <th>วิทยฐานะ</th>
                            <th>กลุ่มสาระฯ / สังกัด</th>
                            <th>กลุ่มงานบริหาร</th>
                            <th>วิชาเอก</th>
                            <th>เบอร์โทรศัพท์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teachers && $teachers->num_rows > 0): ?>
                            <?php $i = 1; while ($t = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis"><?php echo htmlspecialchars($t['academic_standing'] ?: '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                                    <td><?php echo htmlspecialchars($t['work_group'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['major_subject'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['phone_number'] ?: '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">ยังไม่มีข้อมูลบุคลากรในระบบ (กรุณากดปุ่ม "นำเข้าข้อมูล" เพื่อเพิ่มข้อมูล)</td>
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
