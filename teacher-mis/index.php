<?php
require_once 'config.php';

// รายชื่อ 10 กลุ่มโครงสร้างหลักของโรงเรียน
$standard_groups = [
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

// 1. ดึงสถิติจำนวนรวมบุคลากรที่ปฏิบัติหน้าที่
$total_teachers = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE status='ปฏิบัติหน้าที่'")->fetch_assoc()['total'];

// 2. ดึงสถิตินับจำนวนแยกตาม 10 กลุ่มมาตรฐาน
$group_counts = [];
foreach ($standard_groups as $grp) {
    if ($grp === 'กลุ่มบริหารสถานศึกษา') {
        $stmt_c = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' AND (department LIKE '%กลุ่มบริหารสถานศึกษา%' OR position LIKE '%ผู้อำนวยการ%')");
        $stmt_c->execute();
    } elseif ($grp === 'กลุ่มกิจกรรมพัฒนาผู้เรียน') {
        // รองรับทั้งชื่อกลุ่มกิจกรรมพัฒนาผู้เรียน, กิจกรรมพัฒนาผู้เรียน, แนะแนว
        $stmt_c = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' AND (department LIKE '%พัฒนาผู้เรียน%' OR department LIKE '%แนะแนว%' OR major_subject LIKE '%แนะแนว%')");
        $stmt_c->execute();
    } else {
        $stmt_c = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' AND department LIKE ?");
        $like_grp = "%" . $grp . "%";
        $stmt_c->bind_param("s", $like_grp);
        $stmt_c->execute();
    }
    $group_counts[$grp] = $stmt_c->get_result()->fetch_assoc()['count'];
}

// 3. ดึงสถิติแยกตามวิทยฐานะ
$academic_stats = $conn->query("SELECT IF(academic_standing IS NULL OR academic_standing='', 'ไม่มีวิทยฐานะ/ครูผู้ช่วย', academic_standing) as standing, COUNT(*) as count FROM teachers WHERE status='ปฏิบัติหน้าที่' GROUP BY standing ORDER BY count DESC");

// 4. เงื่อนไขการค้นหาและกรองข้อมูล
$search = trim($_GET['search'] ?? '');
$selected_group = trim($_GET['group'] ?? '');

$where = ["status='ปฏิบัติหน้าที่'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR id_card LIKE ? OR major_subject LIKE ? OR position LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
    $types .= "sssss";
}

if (!empty($selected_group)) {
    if ($selected_group === 'กลุ่มบริหารสถานศึกษา') {
        $where[] = "(department LIKE '%กลุ่มบริหารสถานศึกษา%' OR position LIKE '%ผู้อำนวยการ%')";
    } elseif ($selected_group === 'กลุ่มกิจกรรมพัฒนาผู้เรียน') {
        $where[] = "(department LIKE '%พัฒนาผู้เรียน%' OR department LIKE '%แนะแนว%' OR major_subject LIKE '%แนะแนว%')";
    } else {
        $where[] = "department LIKE ?";
        $g_param = "%" . $selected_group . "%";
        $params[] = $g_param;
        $types .= "s";
    }
}

$where_sql = implode(" AND ", $where);

// จัดลำดับ: กลุ่มบริหารขึ้นก่อน ตามด้วยกลุ่มสาระ และชื่อ
$sql = "SELECT * FROM teachers WHERE {$where_sql} ORDER BY 
        CASE 
            WHEN position LIKE '%ผู้อำนวยการเชี่ยวชาญ%' THEN 1
            WHEN position LIKE '%ผู้อำนวยการ%' AND position NOT LIKE '%รอง%' THEN 2
            WHEN position LIKE '%รองผู้อำนวยการ%' THEN 3
            WHEN department LIKE '%กลุ่มบริหารสถานศึกษา%' THEN 4
            ELSE 5 
        END ASC, id ASC";

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
        .stat-card { border-radius: 14px; border: none; transition: all 0.2s ease-in-out; }
        .group-card { cursor: pointer; border-radius: 12px; transition: transform 0.15s, box-shadow 0.15s; border-left: 4px solid #0d6efd; }
        .group-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .group-card.active { background-color: #e7f1ff; border-left-color: #0b5ed7; font-weight: bold; }
        .school-logo { width: 55px; height: auto; object-fit: contain; }
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
            <a href="admin_login.php" class="btn btn-warning fw-medium text-dark">
                <i class="bi bi-shield-lock"></i> จัดการข้อมูล (Admin)
            </a>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> ไประบบขอไปราชการ
            </a>
        </div>
    </div>

    <!-- สรุปสถิติภาพรวม -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white shadow-sm p-3 h-100 justify-content-center">
                <div class="small opacity-75">บุคลากรที่ปฏิบัติหน้าที่ทั้งหมด</div>
                <div class="fs-2 fw-bold"><?php echo number_format($total_teachers); ?> <span class="fs-6 fw-normal">คน</span></div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card stat-card bg-white shadow-sm p-3 h-100">
                <div class="small fw-bold text-muted mb-2">สัดส่วนตามวิทยฐานะ:</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php while ($ac = $academic_stats->fetch_assoc()): ?>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <?php echo htmlspecialchars($ac['standing']); ?>: <strong class="text-primary"><?php echo $ac['count']; ?></strong> คน
                        </span>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ทำเนียบ 10 กลุ่ม: 1 ผู้บริหาร + 8 กลุ่มสาระฯ + 1 กิจกรรมพัฒนาผู้เรียน (แนะแนว) -->
    <h5 class="fw-bold mb-3 text-secondary">
        <i class="bi bi-diagram-3-fill text-primary me-2"></i>ทำเนียบผู้บริหาร, 8 กลุ่มสาระการเรียนรู้ และกิจกรรมพัฒนาผู้เรียน
    </h5>
    <div class="row g-2 mb-4">
        <?php foreach ($standard_groups as $grp): 
            $is_active = ($selected_group === $grp);
            $is_admin_grp = ($grp === 'กลุ่มบริหารสถานศึกษา');
            $is_activity_grp = ($grp === 'กลุ่มกิจกรรมพัฒนาผู้เรียน');
        ?>
            <div class="col-lg-4 col-md-6">
                <a href="index.php?group=<?php echo urlencode($grp); ?>" class="text-decoration-none">
                    <div class="card p-3 shadow-sm group-card <?php echo $is_active ? 'active' : 'bg-white'; ?> <?php echo $is_admin_grp ? 'border-warning border-start-4' : ($is_activity_grp ? 'border-success border-start-4' : ''); ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="<?php echo $is_admin_grp ? 'text-danger fw-bold' : ($is_activity_grp ? 'text-success fw-bold' : 'text-dark'); ?>">
                                <i class="bi <?php 
                                    if ($is_admin_grp) echo 'bi-person-badge-fill text-warning';
                                    elseif ($is_activity_grp) echo 'bi-compass-fill text-success';
                                    else echo 'bi-book'; 
                                ?> me-2"></i>
                                <?php echo $grp; ?> <?php echo $is_activity_grp ? '<small class="text-muted fw-normal">(แนะแนว)</small>' : ''; ?>
                            </span>
                            <span class="badge <?php 
                                if ($is_admin_grp) echo 'bg-warning text-dark';
                                elseif ($is_activity_grp) echo 'bg-success-subtle text-success';
                                else echo 'bg-primary-subtle text-primary'; 
                            ?> rounded-pill px-3 py-1 fs-6">
                                <?php echo $group_counts[$grp] ?? 0; ?> คน
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ตารางรายชื่อและตัวกรองค้นหา -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-2 mb-4 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ, นามสกุล, ตำแหน่ง, วิชาเอก..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="group" class="form-select">
                        <option value="">-- แสดงทั้งหมด (10 กลุ่มโครงสร้าง) --</option>
                        <?php foreach ($standard_groups as $grp): ?>
                            <option value="<?php echo htmlspecialchars($grp); ?>" <?php echo ($selected_group === $grp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grp); ?> <?php echo ($grp === 'กลุ่มกิจกรรมพัฒนาผู้เรียน') ? '(แนะแนว)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> ค้นหา</button>
                    <a href="index.php" class="btn btn-outline-secondary">ล้างค่า</a>
                </div>
            </form>

            <?php if (!empty($selected_group)): ?>
                <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
                    <span>กำลังแสดงข้อมูลของ: <strong><?php echo htmlspecialchars($selected_group); ?></strong></span>
                    <a href="index.php" class="btn btn-sm btn-close"></a>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">ลำดับ</th>
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
                            <?php $i = 1; while ($t = $teachers->fetch_assoc()): 
                                $is_boss = (strpos($t['position'], 'ผู้อำนวยการ') !== false || $t['department'] === 'กลุ่มบริหารสถานศึกษา');
                            ?>
                                <tr class="<?php echo $is_boss ? 'table-warning-subtle' : ''; ?>">
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                        <?php if ($is_boss): ?>
                                            <span class="badge bg-warning text-dark ms-1 small">ผู้บริหาร</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis">
                                            <?php echo htmlspecialchars($t['academic_standing'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                                    <td><?php echo htmlspecialchars($t['work_group'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['major_subject'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['phone_number'] ?: '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    ไม่พบข้อมูลบุคลากรในเงื่อนไขที่เลือก
                                </td>
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
