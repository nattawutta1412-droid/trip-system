<?php
require_once 'config.php';

// ดึงข้อมูลครูพร้อมคำนวณวันเกษียณอายุราชการ (30 กันยายน ของปีที่อายุครบ 60 ปีบริบูรณ์)
// เกิดหลัง 1 ต.ค. (ตั้งแต่ 2 ต.ค. เป็นต้นไป) เกษียณปีถัดไป (+61 จากปีเกิด)
$sql = "SELECT *, 
        CASE 
            WHEN birth_date IS NOT NULL THEN
                CASE 
                    WHEN DATE_FORMAT(birth_date, '%m%d') > '1001' 
                    THEN STR_TO_DATE(CONCAT(YEAR(birth_date) + 61, '-09-30'), '%Y-%m-%d')
                    ELSE STR_TO_DATE(CONCAT(YEAR(birth_date) + 60, '-09-30'), '%Y-%m-%d')
                END
            ELSE NULL 
        END as retirement_date
        FROM teachers 
        WHERE status='ปฏิบัติหน้าที่'
        ORDER BY 
            CASE WHEN retirement_date IS NULL THEN 1 ELSE 0 END ASC,
            retirement_date ASC,
            id ASC";

$result = $conn->query($sql);
$teachers = [];
$today = new DateTime();

while ($row = $result->fetch_assoc()) {
    // 1. คำนวณวันเกษียณและเวลานับถอยหลัง
    if (!empty($row['retirement_date'])) {
        $ret_date = new DateTime($row['retirement_date']);
        $diff = $today->diff($ret_date);
        
        $row['retire_year_th'] = (int)$ret_date->format('Y') + 543;
        $row['retire_date_th'] = "30 ก.ย. " . $row['retire_year_th'];
        
        if ($ret_date < $today) {
            $row['remaining_text'] = '<span class="badge bg-secondary">เกษียณอายุแล้ว</span>';
            $row['is_retired'] = true;
        } else {
            $row['remaining_text'] = "อีก {$diff->y} ปี {$diff->m} เดือน {$diff->d} วัน";
            $row['is_retired'] = false;
        }
    } else {
        $row['retire_year_th'] = '-';
        $row['retire_date_th'] = 'ยังไม่ระบุวันเกิด';
        $row['remaining_text'] = '<span class="text-muted">-</span>';
        $row['is_retired'] = false;
    }

    // 2. คำนวณอายุราชการ (ถ้ามีวันบรรจุ)
    if (!empty($row['start_date'])) {
        $s_date = new DateTime($row['start_date']);
        $diff_service = $today->diff($s_date);
        $row['service_text'] = "{$diff_service->y} ปี {$diff_service->m} เดือน";
    } else {
        $row['service_text'] = "-";
    }

    $teachers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สารสนเทศการเกษียณอายุราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .school-logo { width: 55px; height: auto; object-fit: contain; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid px-4">
    <!-- ส่วนหัว -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 bg-white p-3 rounded-4 shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <img src="../logo.png" alt="โลโก้โรงเรียน" class="school-logo">
            <div>
                <h3 class="fw-bold mb-0 text-dark">สารสนเทศการเกษียณอายุราชการ</h3>
                <span class="text-muted fw-medium">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ (เรียงตามลำดับผู้เกษียณก่อน)</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-primary"><i class="bi bi-people me-1"></i> ทำเนียบครู</a>
            <a href="admin_upload.php" class="btn btn-warning fw-medium"><i class="bi bi-shield-lock me-1"></i> จัดการข้อมูล (Admin)</a>
        </div>
    </div>

    <!-- ตารางลำดับการเกษียณ -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th class="text-center" style="width: 5%;">ลำดับ</th>
                            <th>รูป</th>
                            <th>ชื่อ - สกุล</th>
                            <th>ตำแหน่ง / วิทยฐานะ</th>
                            <th>กลุ่มสาระการเรียนรู้</th>
                            <th>วันเกิด</th>
                            <th>อายุราชการ</th>
                            <th class="text-center text-primary">ปี พ.ศ. ที่เกษียณ</th>
                            <th class="text-center">วันที่เกษียณอายุ</th>
                            <th class="text-center text-danger">ระยะเวลาคงเหลือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teachers)): ?>
                            <?php $i = 1; foreach ($teachers as $t): 
                                $b_date_th = !empty($t['birth_date']) ? date('d/m/', strtotime($t['birth_date'])) . (date('Y', strtotime($t['birth_date'])) + 543) : '-';
                                $is_near = (isset($t['is_retired']) && !$t['is_retired'] && !empty($t['retire_year_th']) && $t['retire_year_th'] != '-' && ($t['retire_year_th'] - (date('Y') + 543) <= 2));
                            ?>
                                <tr class="<?php echo $is_near ? 'table-warning-subtle' : ''; ?>">
                                    <td class="text-center fw-bold"><?php echo $i++; ?></td>
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
                                    <td>
                                        <?php echo htmlspecialchars($t['position']); ?>
                                        <?php if (!empty($t['academic_standing'])): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis ms-1"><?php echo htmlspecialchars($t['academic_standing']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                                    <td><?php echo $b_date_th; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $t['service_text']; ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-3 py-1">พ.ศ. <?php echo $t['retire_year_th']; ?></span>
                                    </td>
                                    <td class="text-center fw-medium"><?php echo $t['retire_date_th']; ?></td>
                                    <td class="text-center fw-bold text-danger"><?php echo $t['remaining_text']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">ยังไม่มีข้อมูลบุคลากรในระบบ</td>
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
