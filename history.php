<?php
require_once 'config.php';
$result = $conn->query("SELECT * FROM official_trips ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติคำร้องขออนุญาตไปราชการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }</style>
</head>
<body class="py-4">
<div class="container">
    <div class="card p-4 border-0 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-primary">ประวัติการขออนุญาตไปราชการ</h4>
            <a href="form.php" class="btn btn-success">+ ยื่นคำร้องใหม่</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">ที่</th>
                        <th>วันที่ยื่น</th>
                        <th>ผู้ขออนุญาต</th>
                        <th>สังกัด/กลุ่มสาระ</th>
                        <th>เรื่องราชการ</th>
                        <th>ช่วงวันที่ไป</th>
                        <th class="text-center" style="width: 120px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?php echo $row['id']; ?></td>
                            <td><?php echo $row['created_date']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo $row['start_date'] . ' ถึง ' . $row['end_date']; ?></td>
                            <td class="text-center">
                                <a href="print.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">พิมพ์เอกสาร</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีประวัติการยื่นคำร้อง</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
