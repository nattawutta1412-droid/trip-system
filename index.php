<?php
require_once 'config.php';

// จัดการการอัปโหลดไฟล์คำสั่งที่อนุมัติแล้ว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_approved') {
    $trip_id = intval($_POST['trip_id']);
    if (isset($_FILES['approved_file']) && $_FILES['approved_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['approved_file']['name'], PATHINFO_EXTENSION));
        $new_filename = 'approved_' . $trip_id . '_' . time() . '.' . $file_ext;

        if (move_uploaded_file($_FILES['approved_file']['tmp_name'], $upload_dir . $new_filename)) {
            $stmt = $conn->prepare("UPDATE official_trips SET approved_file = ?, status = 'อนุมัติแล้ว' WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $trip_id);
            $stmt->execute();
        }
    }
    header("Location: index.php");
    exit();
}

$result = $conn->query("SELECT * FROM official_trips ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบขออนุมัติเดินทางไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; background: #f8f9fa; }</style>
</head>
<body class="py-4">
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary">ระบบขออนุมัติเดินทางไปราชการ (โรงเรียนย่านตาขาวรัฐชนูปถัมภ์)</h3>
        <a href="form.php" class="btn btn-success">+ เขียนขออนุมัติไปราชการ</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ที่</th>
                            <th>วันที่ขอ</th>
                            <th>ผู้ขออนุมัติ</th>
                            <th>ฝ่าย</th>
                            <th>เรื่อง / สถานที่</th>
                            <th>ช่วงวันที่</th>
                            <th>สถานะ</th>
                            <th>คำสั่งที่อนุมัติแล้ว</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['doc_number'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['created_date'] ?? '-'); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['applicant_name'] ?? ''); ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['head_department'] ?? '-'); ?></span></td>
                            <td>
                                <div><?php echo htmlspecialchars($row['subject'] ?? ''); ?></div>
                                <small class="text-muted">ปลายทาง: <?php echo htmlspecialchars($row['destination'] ?? '-'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['start_date'] ?? ''); ?> ถึง <?php echo htmlspecialchars($row['end_date'] ?? ''); ?></td>
                            <td>
                                <?php if (!empty($row['approved_file'])): ?>
                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">รอคำสั่งอนุมัติ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['approved_file'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($row['approved_file']); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        📥 ดาวน์โหลดคำสั่ง
                                    </a>
                                <?php else: ?>
                                    <form action="index.php" method="POST" enctype="multipart/form-data" class="d-flex gap-1">
                                        <input type="hidden" name="action" value="upload_approved">
                                        <input type="hidden" name="trip_id" value="<?php echo $row['id']; ?>">
                                        <input type="file" name="approved_file" class="form-control form-control-sm" accept=".pdf,image/*" required style="max-width: 150px;">
                                        <button type="submit" class="btn btn-sm btn-primary">อัปโหลด</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="print.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">พิมพ์บันทึกข้อความ</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
