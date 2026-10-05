<?php
require_once 'config.php';

// 1. ตรวจสอบและสร้างคอลัมน์ status อัตโนมัติหากยังไม่มี
$check_status = $conn->query("SHOW COLUMNS FROM official_trips LIKE 'status'");
if ($check_status && $check_status->num_rows == 0) {
    $conn->query("ALTER TABLE official_trips ADD COLUMN status VARCHAR(50) DEFAULT 'ปกติ'");
}

$message = "";
$message_type = "success";

// 2. จัดการคำสั่ง ยกเลิกคำร้อง / ลบคำร้อง / คืนสถานะคำร้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;

    if ($trip_id > 0) {
        if ($action === 'cancel') {
            // ยกเลิกคำร้อง (เปลี่ยนสถานะเป็น ยกเลิก)
            $stmt = $conn->prepare("UPDATE official_trips SET status = 'ยกเลิก' WHERE id = ?");
            $stmt->bind_param("i", $trip_id);
            if ($stmt->execute()) {
                $message = "ยกเลิกคำร้องเรียบร้อยแล้ว";
            } else {
                $message = "เกิดข้อผิดพลาดในการยกเลิก: " . $conn->error;
                $message_type = "danger";
            }
        } elseif ($action === 'restore') {
            // คืนค่าสถานะให้กลับมาเป็นปกติ
            $stmt = $conn->prepare("UPDATE official_trips SET status = 'ปกติ' WHERE id = ?");
            $stmt->bind_param("i", $trip_id);
            if ($stmt->execute()) {
                $message = "คืนสถานะคำร้องเป็นปกติเรียบร้อยแล้ว";
            } else {
                $message = "เกิดข้อผิดพลาด: " . $conn->error;
                $message_type = "danger";
            }
        } elseif ($action === 'delete') {
            // ลบคำร้องและข้อมูลผู้ร่วมเดินทางออกจากระบบถาวร
            $conn->query("DELETE FROM trip_participants WHERE trip_id = {$trip_id}");
            $stmt = $conn->prepare("DELETE FROM official_trips WHERE id = ?");
            $stmt->bind_param("i", $trip_id);
            if ($stmt->execute()) {
                $message = "ลบข้อมูลคำร้องออกจากระบบถาวรเรียบร้อยแล้ว";
            } else {
                $message = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// 3. ดึงรายการคำร้องทั้งหมดมาแสดง
$sql = "SELECT * FROM official_trips ORDER BY id DESC";
$result = $conn->query($sql);

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
    <title>ระบบจัดการหลังบ้าน - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; color: #333; }
        .header-panel { background: #fff; padding: 18px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .main-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .table th { background-color: #f8f9fa; white-space: nowrap; font-size: 14.5px; }
        .table td { font-size: 14px; vertical-align: middle; }
    </style>
</head>
<body class="py-3">

<div class="container-fluid px-4">
    <!-- แถบด้านบน -->
    <div class="header-panel d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="logo.png" alt="Logo" style="height: 50px;" onerror="this.src='https://placehold.co/50x50?text=YKR';">
            <div>
                <h4 class="fw-bold mb-0 text-dark">ระบบจัดการคำร้องหลังบ้าน (สำหรับเจ้าหน้าที่)</h4>
                <small class="text-muted">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</small>
            </div>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary px-3 py-2 fw-medium">
                <i class="bi bi-arrow-left"></i> กลับหน้ารายการหลัก
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ตารางคำร้องและเครื่องมือจัดการ -->
    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-table"></i> รายการคำร้องและสถานะการดำเนินการ</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>วันที่ยื่น</th>
                        <th>ผู้ขออนุญาต</th>
                        <th>กลุ่มสาระ/กลุ่มงาน</th>
                        <th>เรื่อง / ปลายทาง</th>
                        <th>ช่วงวันที่</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width: 240px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $status = $row['status'] ?? 'ปกติ';
                            $is_cancelled = ($status === 'ยกเลิก');
                        ?>
                            <tr class="<?php echo $is_cancelled ? 'table-light text-muted' : ''; ?>">
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo thai_date_short($row['created_date'] ?? ''); ?></td>
                                <td>
                                    <strong class="<?php echo $is_cancelled ? 'text-decoration-line-through text-muted' : 'text-dark'; ?>">
                                        <?php echo htmlspecialchars($row['applicant_name'] ?? ''); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['department'] ?? '-'); ?></div>
                                    <small class="text-primary"><?php echo htmlspecialchars($row['work_group'] ?? ''); ?></small>
                                </td>
                                <td style="max-width: 280px;">
                                    <div class="text-truncate fw-medium" title="<?php echo htmlspecialchars($row['subject'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row['subject'] ?? ''); ?>
                                    </div>
                                    <small class="text-muted text-truncate d-block">
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
                                    <?php if ($is_cancelled): ?>
                                        <span class="badge bg-danger">ยกเลิกแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- ปุ่มพิมพ์เอกสาร -->
                                        <a href="print.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="เปิดพิมพ์บันทึกข้อความ">
                                            <i class="bi bi-printer"></i>
                                        </a>

                                        <!-- ปุ่มสลับสถานะ ยกเลิก / คืนสถานะ -->
                                        <?php if (!$is_cancelled): ?>
                                            <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('ยืนยันที่จะยกเลิกคำร้องของ <?php echo htmlspecialchars($row['applicant_name']); ?> หรือไม่?');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="trip_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="ยกเลิกคำร้องนี้">
                                                    <i class="bi bi-x-circle"></i> ยกเลิก
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('ต้องการคืนค่าสถานะคำร้องนี้ให้เป็นปกติหรือไม่?');">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="trip_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="คืนสถานะให้เป็นปกติ">
                                                    <i class="bi bi-arrow-counterclockwise"></i> คืนสถานะ
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- ปุ่มลบคำร้องถาวร -->
                                        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('คำเตือน: คุณต้องการลบคำร้องนี้ออกจากระบบอย่างถาวรใช่หรือไม่? (ไม่สามารถกู้คืนได้)');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="trip_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบข้อมูลถาวร">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                ยังไม่มีข้อมูลคำร้องในระบบ
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
