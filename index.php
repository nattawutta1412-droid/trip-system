<?php
require_once 'config.php';

// รับค่าตัวกรองจาก URL (GET)
$keyword       = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dept_filter   = isset($_GET['department']) ? trim($_GET['department']) : '';
$month_filter  = isset($_GET['month']) ? trim($_GET['month']) : '';
$exp_filter    = isset($_GET['expense_type']) ? trim($_GET['expense_type']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// สร้างเงื่อนไขการค้นหา SQL
$where_clauses = [];
$params = [];
$param_types = '';

if ($keyword !== '') {
    $where_clauses[] = "(t.applicant_name LIKE ? OR t.subject LIKE ? OR t.destination LIKE ? OR t.position LIKE ?)";
    $kw_param = '%' . $keyword . '%';
    $params[] = $kw_param;
    $params[] = $kw_param;
    $params[] = $kw_param;
    $params[] = $kw_param;
    $param_types .= 'ssss';
}

if ($dept_filter !== '') {
    $where_clauses[] = "t.department = ?";
    $params[] = $dept_filter;
    $param_types .= 's';
}

if ($month_filter !== '') {
    $where_clauses[] = "t.created_date LIKE ?";
    $params[] = $month_filter . '%';
    $param_types .= 's';
}

if ($exp_filter !== '') {
    $where_clauses[] = "t.expense_type = ?";
    $params[] = $exp_filter;
    $param_types .= 's';
}

if ($status_filter !== '') {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 1. ดึงรายการกลุ่มสาระทั้งหมด
$dept_list_res = $conn->query("SELECT DISTINCT department FROM official_trips WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");

// 2. ดึงรายการคำร้องตามเงื่อนไข
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM trip_participants p WHERE p.trip_id = t.id) as participant_count 
        FROM official_trips t 
        $where_sql 
        ORDER BY t.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$filtered_count = $result->num_rows;

// 3. สถิติรวม
$total_trips_res = $conn->query("SELECT COUNT(*) AS total FROM official_trips WHERE status != 'cancelled'");
$total_trips = $total_trips_res->fetch_assoc()['total'];

$current_month = date('Y-m');
$month_trips_res = $conn->query("SELECT COUNT(*) AS total FROM official_trips WHERE status != 'cancelled' AND created_date LIKE '{$current_month}%'");
$month_trips = $month_trips_res->fetch_assoc()['total'];

$cancelled_trips_res = $conn->query("SELECT COUNT(*) AS total FROM official_trips WHERE status = 'cancelled'");
$cancelled_trips = $cancelled_trips_res->fetch_assoc()['total'];

function thai_date_short($date_str) {
    if (!$date_str || $date_str == '0000-00-00') return "-";
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $time = strtotime($date_str);
    $d = date('j', $time);
    $m = $months[date('n', $time)];
    $y = (date('Y', $time) + 543) % 100;
    return "$d $m $y";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปข้อมูลการไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Sarabun", sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 25px;
            color: #333;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex-wrap: wrap;
            gap: 15px;
        }
        .brand-container {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .school-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
            display: block;
        }
        .brand-text h2 { 
            margin: 0; 
            color: #2c3e50; 
            font-size: 22px; 
            line-height: 1.3;
        }
        .brand-text p { 
            margin: 4px 0 0 0; 
            color: #7f8c8d; 
            font-size: 14px; 
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-create {
            background-color: #27ae60;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(39, 174, 96, 0.2);
            transition: background 0.2s;
        }
        .btn-create:hover { background-color: #219150; }

        .btn-admin {
            background-color: #c0392b;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(192, 57, 43, 0.2);
            transition: background 0.2s;
        }
        .btn-admin:hover { background-color: #a93226; }

        /* การ์ดสถิติ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            border-left: 5px solid #3498db;
        }
        .stat-card.green { border-left-color: #2ecc71; }
        .stat-card.red { border-left-color: #e74c3c; }
        .stat-card.purple { border-left-color: #9b59b6; }
        .stat-card h3 { margin: 0; font-size: 14px; color: #7f8c8d; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 8px; }

        /* ตัวกรอง */
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 13px; font-weight: bold; color: #555; }
        .filter-group input, .filter-group select {
            padding: 9px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
            outline: none;
        }
        .filter-actions { display: flex; gap: 8px; }
        .btn-filter {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            flex: 1;
        }
        .btn-filter:hover { background-color: #2980b9; }
        .btn-reset {
            background-color: #ecf0f1;
            color: #7f8c8d;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* ตาราง */
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background-color: #f8f9fa; color: #495057; text-align: left; padding: 12px 14px; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
        td { padding: 12px 14px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        
        tr.row-cancelled { background-color: #fff8f8; color: #888; }
        tr:hover { background-color: #fbfcfd; }

        .badge-participants { background: #e3f2fd; color: #1565c0; margin-left: 4px; padding: 3px 6px; border-radius: 4px; font-size: 12px; }
        .badge-cancelled { background: #ffebee; color: #c62828; font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 12px; }

        .btn-view {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            white-space: nowrap;
        }
        .btn-view:hover { background: #2980b9; }

        .btn-download-order {
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }
        .btn-download-order:hover { background: #219150; }
        .text-waiting {
            color: #95a5a6;
            font-size: 13px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <!-- ส่วนแสดงโลโก้พร้อมชื่อโรงเรียน -->
        <div class="brand-container">
            <img src="logo.png" alt="ตราโรงเรียน" class="school-logo" onerror="this.style.display='none';">
            <div class="brand-text">
                <h2>ระบบบริหารการขออนุญาตไปราชการ</h2>
                <p>โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="admin.php" class="btn-admin">🔐 จัดการหลังบ้าน (เจ้าหน้าที่)</a>
            <a href="form.php" class="btn-create">✍️ เขียนคำร้องใหม่</a>
        </div>
    </div>

    <!-- การ์ดตัวเลขสรุป -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>คำร้องที่ใช้งาน (ไม่รวมยกเลิก)</h3>
            <div class="number"><?php echo number_format($total_trips); ?> รายการ</div>
        </div>
        <div class="stat-card green">
            <h3>คำร้องเดือนนี้</h3>
            <div class="number"><?php echo number_format($month_trips); ?> รายการ</div>
        </div>
        <div class="stat-card red">
            <h3>คำร้องที่ยกเลิกแล้ว</h3>
            <div class="number"><?php echo number_format($cancelled_trips); ?> รายการ</div>
        </div>
        <div class="stat-card purple">
            <h3>ผลการค้นหาตามตัวกรอง</h3>
            <div class="number"><?php echo number_format($filtered_count); ?> รายการ</div>
        </div>
    </div>

    <!-- กล่องตัวกรองและค้นหา -->
    <div class="filter-card">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #2c3e50;">🔍 ค้นหาและกรองคำร้อง</h3>
        <form method="GET" action="index.php" class="filter-form">
            <div class="filter-group">
                <label>คำค้นหา</label>
                <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="ชื่อ / เรื่อง / สถานที่...">
            </div>

            <div class="filter-group">
                <label>กลุ่มสาระ / กลุ่มงาน</label>
                <select name="department">
                    <option value="">-- ทั้งหมด --</option>
                    <?php if ($dept_list_res): ?>
                        <?php while ($d = $dept_list_res->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php if ($dept_filter === $d['department']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($d['department']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>เดือนที่ยื่น</label>
                <input type="month" name="month" value="<?php echo htmlspecialchars($month_filter); ?>">
            </div>

            <div class="filter-group">
                <label>สถานะคำร้อง</label>
                <select name="status">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="pending" <?php if ($status_filter === 'pending') echo 'selected'; ?>>ปกติ / ใช้งานได้</option>
                    <option value="cancelled" <?php if ($status_filter === 'cancelled') echo 'selected'; ?>>ยกเลิกแล้ว</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter">ค้นหา</button>
                <a href="index.php" class="btn-reset">ล้างค่า</a>
            </div>
        </form>
    </div>

    <!-- ตารางแสดงรายการคำร้อง -->
    <div class="table-card">
        <div class="table-header">
            <h3 style="margin: 0; color: #2c3e50;">รายการคำร้องขอไปราชการ</h3>
            <span style="font-size: 13px; color: #7f8c8d;">พบทั้งหมด <b><?php echo number_format($filtered_count); ?></b> รายการ</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>เลขคำร้อง</th>
                        <th>วันที่ยื่น</th>
                        <th>ชื่อผู้ขออนุญาต</th>
                        <th>กลุ่มสาระ/ฝ่าย</th>
                        <th>เรื่อง / สถานที่</th>
                        <th>ช่วงวันที่ไปราชการ</th>
                        <th style="text-align: center;">บันทึกข้อความ</th>
                        <th style="text-align: center;">หนังสือคำสั่งอนุมัติ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $is_cancelled = ($row['status'] === 'cancelled'); ?>
                            <tr class="<?php echo $is_cancelled ? 'row-cancelled' : ''; ?>">
                                <td><b>#<?php echo $row['id']; ?></b></td>
                                <td><?php echo thai_date_short($row['created_date']); ?></td>
                                <td>
                                    <b><?php echo htmlspecialchars($row['applicant_name']); ?></b><br>
                                    <small><?php echo htmlspecialchars($row['position']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td>
                                    <b><?php echo htmlspecialchars($row['subject']); ?></b><br>
                                    <small>ณ <?php echo htmlspecialchars($row['destination']); ?></small>
                                    <?php if ($row['participant_count'] > 0): ?>
                                        <span class="badge badge-participants">+<?php echo $row['participant_count']; ?> ผู้ร่วมเดินทาง</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo thai_date_short($row['start_date']); ?> - <?php echo thai_date_short($row['end_date']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($is_cancelled): ?>
                                        <span class="badge-cancelled">🚫 ยกเลิกแล้ว</span>
                                    <?php else: ?>
                                        <a href="print.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-view">📄 ใบขอไปราชการ</a>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($row['approved_file'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($row['approved_file']); ?>" target="_blank" class="btn-download-order">📥 ดาวน์โหลดคำสั่ง</a>
                                    <?php else: ?>
                                        <?php if ($is_cancelled): ?>
                                            <span style="color:#aaa;">-</span>
                                        <?php else: ?>
                                            <span class="text-waiting">⏳ รอเอกสารอนุมัติ</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 40px; color: #999;">ไม่พบข้อมูลคำร้องที่ตรงกับเงื่อนไขการค้นหา</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>