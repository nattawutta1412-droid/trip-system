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
$view_mode = trim($_GET['view'] ?? 'card'); // โหมดการแสดงผลเริ่มต้น: card

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

// จัดลำดับ: ผู้บริหารขึ้นก่อน
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

// ดึงข้อมูลใส่ array เพื่อให้แสดงผลซ้ำได้สะดวก
$teacher_list = [];
while ($row = $teachers->fetch_assoc()) {
    $teacher_list[] = $row;
}
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
        
        /* สไตล์การ์ดรูปภาพครู */
        .teacher-card {
            border-radius: 16px;
            border: none;
            transition: all 0.25s ease;
            overflow: hidden;
            background: #fff;
        }
        .teacher-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .teacher-photo-container {
            width: 130px;
            height: 160px;
            margin: 20px auto 10px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .teacher-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .teacher-avatar-fallback {
            font-size: 65px;
            color: #adb5bd;
        }
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

    <!-- เมนูเลือกกลุ่มสาระฯ (คลิกเพื่อดูทำเนียบ) -->
    <h5 class="fw-bold mb-3 text-secondary">
        <i class="bi bi-diagram-3-fill text-primary me-2"></i>เลือกกลุ่มสาระฯ เพื่อดูทำเนียบรูปภาพครู
    </h5>
    <div class="row g-2 mb-4">
        <?php foreach ($standard_groups as $grp): 
            $is_active = ($selected_group === $grp);
            $is_admin_grp = ($grp === 'กลุ่มบริหารสถานศึกษา');
            $is_activity_grp = ($grp === 'กลุ่มกิจกรรมพัฒนาผู้เรียน');
        ?>
            <div class="col-lg-4 col-md-6">
                <a href="index.php?group=<?php echo urlencode($grp); ?>&view=<?php echo $view_mode; ?>" class="text-decoration-none">
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

    <!-- ส่วนค้นหาและแถบสลับโหมดการแสดงผล (การ์ดรูปภาพ / ตาราง) -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ, นามสกุล, ตำแหน่ง, วิชาเอก..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="group" class="form-select">
                        <option value="">-- แสดงทั้งหมด (10 กลุ่มโครงสร้าง) --</option>
                        <?php foreach ($standard_groups as $grp): ?>
                            <option value="<?php echo htmlspecialchars($grp); ?>" <?php echo ($selected_group === $grp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> ค้นหา</button>
                    <a href="index.php?view=<?php echo $view_mode; ?>" class="btn btn-outline-secondary">ล้างค่า</a>
                </div>
            </form>

            <hr class="my-3">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <?php if (!empty($selected_group)): ?>
                        <span class="fs-5 fw-bold text-primary"><i class="bi bi-folder2-open me-1"></i> <?php echo htmlspecialchars($selected_group); ?></span>
                        <span class="badge bg-secondary ms-2"><?php echo count($teacher_list); ?> คน</span>
                    <?php else: ?>
                        <span class="fs-5 fw-bold text-dark"><i class="bi bi-people-fill me-1"></i> รายชื่อบุคลากรทั้งหมด</span>
                        <span class="badge bg-secondary ms-2"><?php echo count($teacher_list); ?> คน</span>
                    <?php endif; ?>
                </div>
                
                <!-- ปุ่มสลับรูปแบบการดู Card vs Table -->
                <div class="btn-group" role="group">
                    <a href="index.php?group=<?php echo urlencode($selected_group); ?>&search=<?php echo urlencode($search); ?>&view=card" 
                       class="btn btn-sm <?php echo ($view_mode === 'card') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-grid-fill me-1"></i> แบบรูปภาพ (การ์ด)
                    </a>
                    <a href="index.php?group=<?php echo urlencode($selected_group); ?>&search=<?php echo urlencode($search); ?>&view=table" 
                       class="btn btn-sm <?php echo ($view_mode === 'table') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-table me-1"></i> แบบตารางรายชื่อ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- โหมดที่ 1: แสดงเป็นการ์ดพร้อมรูปภาพครู (Teacher Profile Cards) -->
    <?php if ($view_mode === 'card'): ?>
        <?php if (!empty($teacher_list)): ?>
            <div class="row g-4 mb-5">
                <?php foreach ($teacher_list as $t): 
                    $is_boss = (strpos($t['position'], 'ผู้อำนวยการ') !== false || $t['department'] === 'กลุ่มบริหารสถานศึกษา');
                    $photo = !empty($t['photo_url']) ? $t['photo_url'] : '';
                ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card teacher-card shadow-sm h-100 text-center p-3 <?php echo $is_boss ? 'border border-warning border-2' : ''; ?>">
                            <!-- รูปภาพครู -->
                            <div class="teacher-photo-container">
                                <?php if (!empty($photo)): ?>
                                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="รูปภาพครู" class="teacher-photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="teacher-avatar-fallback" style="display:none;"><i class="bi bi-person-fill"></i></div>
                                <?php else: ?>
                                    <div class="teacher-avatar-fallback">
                                        <i class="bi <?php echo ($t['gender'] === 'หญิง') ? 'bi-person-standing-dress' : 'bi-person-fill'; ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-2 d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark">
                                        <?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?>
                                    </h5>
                                    <div class="text-primary fw-medium small mb-1"><?php echo htmlspecialchars($t['position']); ?></div>
                                    
                                    <?php if (!empty($t['academic_standing'])): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis mb-2">
                                            วิทยฐานะ<?php echo htmlspecialchars($t['academic_standing']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border mb-2">-</span>
                                    <?php endif; ?>
                                </div>

                                <div class="border-top pt-2 mt-2 text-start small text-muted">
                                    <div class="text-truncate mb-1" title="<?php echo htmlspecialchars($t['department']); ?>">
                                        <i class="bi bi-building me-1 text-secondary"></i> <strong>สังกัด:</strong> <?php echo htmlspecialchars($t['department']); ?>
                                    </div>
                                    <?php if (!empty($t['major_subject'])): ?>
                                        <div class="text-truncate mb-1" title="<?php echo htmlspecialchars($t['major_subject']); ?>">
                                            <i class="bi bi-mortarboard me-1 text-secondary"></i> <strong>วิชาเอก:</strong> <?php echo htmlspecialchars($t['major_subject']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($t['phone_number'])): ?>
                                        <div class="mb-1">
                                            <i class="bi bi-telephone me-1 text-secondary"></i> <strong>โทร:</strong> <a href="tel:<?php echo htmlspecialchars($t['phone_number']); ?>" class="text-decoration-none text-muted"><?php echo htmlspecialchars($t['phone_number']); ?></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 rounded-4 text-center py-5 mb-5 bg-white">
                <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                <h6 class="text-muted mt-2">ไม่พบข้อมูลบุคลากรในกลุ่มสาระฯ หรือเงื่อนไขที่เลือก</h6>
            </div>
        <?php endif; ?>

    <!-- โหมดที่ 2: แสดงเป็นตารางรายชื่อ (Table View) -->
    <?php else: ?>
        <div class="card shadow-sm border-0 rounded-4 mb-5">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">ลำดับ</th>
                                <th>รูปภาพ</th>
                                <th>ชื่อ - สกุล</th>
                                <th>ตำแหน่ง</th>
                                <th>วิทยฐานะ</th>
                                <th>กลุ่มสาระฯ / สังกัด</th>
                                <th>วิชาเอก</th>
                                <th>เบอร์โทรศัพท์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($teacher_list)): ?>
                                <?php $i = 1; foreach ($teacher_list as $t): 
                                    $photo = !empty($t['photo_url']) ? $t['photo_url'] : '';
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td>
                                            <div style="width: 45px; height: 50px; border-radius: 6px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($photo)): ?>
                                                    <img src="<?php echo htmlspecialchars($photo); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-person-fill text-muted fs-4"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($t['prefix'] . $t['first_name'] . ' ' . $t['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($t['position']); ?></td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis"><?php echo htmlspecialchars($t['academic_standing'] ?: '-'); ?></span></td>
                                        <td><?php echo htmlspecialchars($t['department']); ?></td>
                                        <td><?php echo htmlspecialchars($t['major_subject'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($t['phone_number'] ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลบุคลากรในกลุ่มสาระฯ ที่เลือก</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
