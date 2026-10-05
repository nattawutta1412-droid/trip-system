<?php
require_once 'config.php';

// รับค่าตัวกรองค้นหา
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$month = trim($_GET['month'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// 1. สถิติรวม
$total_active = 0;
$total_month = 0;
$total_canceled = 0;

$stat_sql = "SELECT 
    COUNT(CASE WHEN status != 'ยกเลิก' OR status IS NULL THEN 1 END) as active_count,
    COUNT(CASE WHEN DATE_FORMAT(created_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN 1 END) as month_count,
    COUNT(CASE WHEN status = 'ยกเลิก' THEN 1 END) as cancel_count
FROM official_trips";
$stat_res = $conn->query($stat_sql);
if ($stat_res && $stat_row = $stat_res->fetch_assoc()) {
    $total_active = $stat_row['active_count'] ?? 0;
    $total_month = $stat_row['month_count'] ?? 0;
    $total_canceled = $stat_row['cancel_count'] ?? 0;
}

// 2. เงื่อนไขค้นหา
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(applicant_name LIKE ? OR subject LIKE ? OR destination LIKE ? OR doc_number LIKE ?)";
    $s_term = "%{$search}%";
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ssss";
}

if ($department !== '') {
    $where_clauses[] = "(department = ? OR head_department = ?)";
    $params[] = $department;
    $params[] = $department;
    $types .= "ss";
}

if ($month !== '') {
    $where_clauses[] = "DATE_FORMAT(created_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

if ($status_filter !== '') {
    if ($status_filter === 'approved') {
        $where_clauses[] = "(approved_file IS NOT NULL AND approved_file != '')";
    } elseif ($status_filter === 'pending') {
        $where_clauses[] = "(approved_file IS NULL OR approved_file = '') AND (status != 'ยกเลิก' OR status IS NULL)";
    } elseif ($status_filter === 'canceled') {
        $where_clauses[] = "status = 'ยกเลิก'";
    }
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM official_trips WHERE {$where_sql} ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$filtered_count = $result->num_rows;

// รายชื่อกลุ่มสาระ/ฝ่ายสำหรับ Dropdown
$dept_list = [
    "กลุ่มสาระฯ ภาษาไทย", "กลุ่มสาระฯ คณิตศาสตร์", "กลุ่มสาระฯ วิทยาศาสตร์และเทคโนโลยี",
    "กลุ่มสาระฯ สังคมศึกษา ศาสนา และวัฒนธรรม", "กลุ่มสาระฯ สุขศึกษาและพลศึกษา",
    "กลุ่มสาระฯ ศิลปะ", "กลุ่มสาระฯ การงานอาชีพ", "กลุ่มสาระฯ ภาษาต่างประเทศ",
    "กิจกรรมพัฒนาผู้เรียน", "ฝ่ายบริหารวิชาการ", "ฝ่ายบริหารงบประมาณและแผนงาน",
    "ฝ่ายบริหารงานบุคคล", "ฝ่ายบริหารทั่วไป"
];

function thai_date_short($date_str) {
    if (!$date_str) return "-";
    $m = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $t = strtotime($date_str);
    return date('j', $t) . ' ' . $m[intval(date('n', $t))] . ' ' . (date('Y', $t) + 543);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารการขออนุญาตไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }
        .header-panel {
            background: #fff;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 5px solid;
            height: 100%;
        }
        .stat-card.blue   { border-left-color: #0dcaf0; }
        .stat-card.green  { border-left-color: #198754; }
        .stat-card.red    { border-left-color: #dc3545; }
        .stat-card.purple { border-left-color: #6f42c1; }
        
        .stat-card .title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
        }
        .main-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }
        .table td {
            font-size: 14px;
            vertical-align: middle;
        }
    </style>
</head>
<body class="py-3">

<div class="container-fluid px-4">
    <!-- แถบหัวระบบ + ปุ่มด้านบน -->
    <div class="header-panel d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="logo.png" alt="Logo" style="height: 52px;" onerror="this.src='https://placehold.co/52x52?text=YKR';">
            <div>
                <h4 class="fw-bold mb-0 text-dark">ระบบบริหารการขออนุญาตไปราชการ</h4>
                <small class="text-muted">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</small>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="admin.php" class="btn btn-warning text-white fw-bold d-flex align-items-center gap-1 shadow-sm" style="background-color: #c94a29; border: none;">
                <i class="bi bi-shield-lock-fill"></i> จัดการหลังบ้าน (เจ้าหน้าที่)
            </a>
            <a href="form.php" class="btn btn-success fw-bold d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-pencil-square"></i> เขียนคำร้องใหม่
            </a>
        </div>
    </div>

    <!-- แถวการ์ดสถิติ 4 ใบ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card blue">
                <div class="title">คำร้องที่ใช้งาน (ไม่รวมยกเลิก)</div>
                <div class="number"><?php echo number_format($total_active); ?> <span class="fs-6 fw-normal text-muted">รายการ</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card green">
                <div class="title">คำร้องเดือนนี้</div>
                <div class="number"><?php echo number_format($total_month); ?> <span class="fs-6 fw-normal text-muted">รายการ</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card red">
                <div class="title">คำร้องที่ยกเลิกแล้ว</div>
                <div class="number"><?php echo number_format($total_canceled); ?> <span class="fs-6 fw-normal text-muted">รายการ</span></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card purple">
                <div class="title">ผลการค้นหาตามตัวกรอง</div>
                <div class="number"><?php echo number_format($filtered_count); ?> <span class="fs-6 fw-normal text-muted">รายการ</span></div>
            </div>
        </div>
    </div>

    <!-- แผงค้นหาและกรองคำร้อง -->
    <div class="main-card">
        <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-1">
            <i class="bi bi-search"></i> ค้นหาและกรองคำร้อง
        </h6>
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">คำค้นหา</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="ชื่อ / เรื่อง / สถานที่..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">กลุ่มสาระ / กลุ่มงาน</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">-- ทั้งหมด --</option>
                    <?php foreach ($dept_list as $d): ?>
                        <option value="<?php echo $d; ?>" <?php echo $department === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">เดือนที่ยื่น</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($month); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">สถานะคำร้อง</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="pending"  <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>รอคำสั่งอนุมัติ</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>อนุมัติแล้ว</option>
                    <option value="canceled" <?php echo $status_filter === 'canceled' ? 'selected' : ''; ?>>ยกเลิก</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> ค้นหา</button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">ล้างค่า</a>
            </div>
        </form>
    </div>

    <!-- ตารางรายการคำร้องขอไปราชการ -->
    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">รายการคำร้องขอไปราชการ</h6>
            <small class="text-muted">พบทั้งหมด <?php echo $filtered_count; ?> รายการ</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>เลขคำร้อง</th>
                        <th>วันที่ยื่น</th>
                        <th>ชื่อผู้ขออนุญาต</th>
                        <th>กลุ่มสาระ/ฝ่าย</th>
                        <th>เรื่อง / สถานที่</th>
                        <th>ช่วงวันที่ไปราชการ</th>
                        <th class="text-center">บันทึกข้อความ</th>
                        <th class="text-center">หนังสือคำสั่งอนุมัติ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['doc_number'] ?? '-'); ?></span></td>
                                <td><?php echo thai_date_short($row['created_date'] ?? ''); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['applicant_name'] ?? ''); ?></strong>
                                    <?php if (!empty($row['academic_standing'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['academic_standing']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['department'] ?? '-'); ?></div>
                                    <?php if (!empty($row['head_department'])): ?>
                                        <small class="text-primary"><?php echo htmlspecialchars($row['head_department']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 280px;">
                                    <div class="text-truncate fw-medium" title="<?php echo htmlspecialchars($row['subject'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row['subject'] ?? ''); ?>
                                    </div>
                                    <small class="text-muted text-truncate d-block" title="<?php echo htmlspecialchars($row['destination'] ?? ''); ?>">
                                        ปลายทาง: <?php echo htmlspecialchars($row['destination'] ?? '-'); ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo thai_date_short($row['start_date'] ?? ''); ?> -<br>
                                        <?php echo thai_date_short($row['end_date'] ?? ''); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <a href="print.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-printer"></i> พิมพ์บันทึกข้อความ
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($row['approved_file'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($row['approved_file']); ?>" target="_blank" class="btn btn-sm btn-success">
                                            <i class="bi bi-file-earmark-arrow-down-fill"></i> ดาวน์โหลดคำสั่ง
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> รอคำสั่งอนุมัติ</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                ไม่พบข้อมูลคำร้องที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
