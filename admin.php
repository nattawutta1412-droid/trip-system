<?php
session_start();
require_once 'config.php';

// รหัสผ่านเข้าหลังบ้าน
define('ADMIN_PASSWORD', 'admin1234');

// ตรวจสอบการ Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_admin']);
    header('Location: admin.php');
    exit;
}

// ตรวจสอบการ Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'รหัสผ่านไม่ถูกต้อง';
    }
}

// แสดงหน้า Login ถ้ายังไม่ได้ล็อกอิน
if (empty($_SESSION['is_admin'])) {
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบเจ้าหน้าที่หลังบ้าน</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Sarabun", sans-serif; background: #eceff1; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 340px; text-align: center; }
        .login-card h2 { margin-top: 0; color: #2c3e50; font-size: 20px; }
        .login-card input[type="password"] { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        .login-card button { width: 100%; padding: 12px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; }
        .login-card button:hover { background: #c0392b; }
        .error { color: #e74c3c; font-size: 14px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🔒 เข้าสู่ระบบเจ้าหน้าที่</h2>
        <p style="color: #7f8c8d; font-size: 14px; margin-top: -5px;">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</p>
        <?php if ($login_error): ?><div class="error"><?php echo $login_error; ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="ใส่รหัสผ่านเจ้าหน้าที่..." required autofocus>
            <button type="submit" name="login_btn">เข้าสู่ระบบหลังบ้าน</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php" style="color: #3498db; text-decoration: none; font-size: 13px;">← กลับไปหน้ารวมคำร้อง</a></p>
    </div>
</body>
</html>
<?php
    exit;
}

// -------------------------------------------------------------
// ระบบที่ 1: จัดการอัปโหลดไฟล์คำสั่งอนุมัติ
// -------------------------------------------------------------
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_approved_action'])) {
    $trip_id = intval($_POST['trip_id']);
    
    if (isset($_FILES['approved_document']) && $_FILES['approved_document']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['approved_document']['tmp_name'];
        $file_name = $_FILES['approved_document']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_extensions)) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            // ตั้งชื่อไฟล์ป้องกันชื่อซ้ำ: order_ไอดีคำร้อง_เวลา.นามสกุล
            $new_file_name = 'order_' . $trip_id . '_' . time() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $stmt_up = $conn->prepare("UPDATE official_trips SET approved_file = ?, approved_uploaded_at = NOW() WHERE id = ?");
                $stmt_up->bind_param("si", $new_file_name, $trip_id);
                $stmt_up->execute();
                header("Location: admin.php?msg=upload_success");
                exit;
            } else {
                $upload_message = "ไม่สามารถบันทึกไฟล์ลงโฟลเดอร์ uploads ได้";
            }
        } else {
            $upload_message = "อนุญาตเฉพาะไฟล์นามสกุล PDF, JPG, PNG เท่านั้น";
        }
    }
}

// -------------------------------------------------------------
// ระบบที่ 2: ฟังก์ชันยกเลิกคำขอ
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_action'])) {
    $trip_id = intval($_POST['trip_id']);
    $cancel_reason = trim($_POST['cancel_reason'] ?? 'เจ้าหน้าที่ยกเลิกคำขอ');

    $stmt_cancel = $conn->prepare("UPDATE official_trips SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE id = ?");
    $stmt_cancel->bind_param("si", $cancel_reason, $trip_id);
    $stmt_cancel->execute();

    header("Location: admin.php");
    exit;
}

// ดึงรายการคำร้องทั้งหมด
$sql = "SELECT t.* FROM official_trips t ORDER BY t.id DESC";
$result = $conn->query($sql);

function thai_date_short($date_str) {
    if (!$date_str || $date_str == '0000-00-00') return "-";
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $time = strtotime($date_str);
    return date('j', $time) . " " . $months[date('n', $time)] . " " . ((date('Y', $time) + 543) % 100);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการหลังบ้าน (Admin) - อัปโหลดคำสั่งและจัดการคำขอ</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Sarabun", sans-serif; background: #f4f6f9; margin: 0; padding: 25px; color: #333; }
        .container { max-width: 1300px; margin: 0 auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; background: white; padding: 18px 25px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .table-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f8f9fa; padding: 12px 14px; text-align: left; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
        td { padding: 12px 14px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        tr:hover { background: #fbfcfd; }
        
        .badge-pending { background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-cancelled { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-has-file { background: #e8f8f5; color: #16a085; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        
        .btn-cancel { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-cancel:hover { background: #c0392b; }
        .btn-upload { background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-upload:hover { background: #219150; }
        .btn-viewfile { background: #3498db; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; display: inline-block; margin-bottom: 5px; }
        .btn-logout { background: #95a5a6; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: bold; }

        /* Modal อัปโหลดไฟล์ */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 8px; width: 420px; max-width: 90%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-header { font-size: 17px; font-weight: bold; margin-bottom: 15px; color: #2c3e50; }
        .modal-footer { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div>
            <h2 style="margin: 0; color: #2c3e50;">🛠️ แผงควบคุมเจ้าหน้าที่ (Admin)</h2>
            <p style="margin: 4px 0 0 0; color: #7f8c8d; font-size: 14px;">อัปโหลดหนังสือคำสั่งที่อนุมัติแล้ว & จัดการคำขอ</p>
        </div>
        <div>
            <a href="index.php" style="margin-right: 15px; text-decoration: none; color: #3498db; font-size: 14px; font-weight: bold;">← ดูหน้าสรุปข้อมูลคำร้อง</a>
            <a href="admin.php?action=logout" class="btn-logout">ออกจากระบบ</a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'upload_success'): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            ✅ อัปโหลดหนังสือคำสั่งอนุมัติเรียบร้อยแล้ว! เจ้าของเรื่องสามารถดาวน์โหลดได้ทันที
        </div>
    <?php endif; ?>

    <?php if ($upload_message): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            ⚠️ <?php echo $upload_message; ?>
        </div>
    <?php endif; ?>

    <div class="table-card">
        <h3 style="margin-top: 0; margin-bottom: 15px;">รายการคำร้องขอไปราชการทั้งหมด</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>เลขคำร้อง</th>
                        <th>วันที่ยื่น</th>
                        <th>ชื่อผู้ขอ</th>
                        <th>กลุ่มสาระ/ฝ่าย</th>
                        <th>เรื่อง / ปลายทาง</th>
                        <th>สถานะคำขอ</th>
                        <th>หนังสือคำสั่งที่อนุมัติแล้ว</th>
                        <th style="text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $is_cancelled = ($row['status'] === 'cancelled'); ?>
                            <tr style="<?php if ($is_cancelled) echo 'background: #fff8f8; opacity: 0.75;'; ?>">
                                <td><b>#<?php echo $row['id']; ?></b></td>
                                <td><?php echo thai_date_short($row['created_date']); ?></td>
                                <td>
                                    <b><?php echo htmlspecialchars($row['applicant_name']); ?></b><br>
                                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($row['position']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td>
                                    <b><?php echo htmlspecialchars($row['subject']); ?></b><br>
                                    <small style="color: #7f8c8d;">ณ <?php echo htmlspecialchars($row['destination']); ?></small>
                                </td>
                                <td>
                                    <?php if ($is_cancelled): ?>
                                        <span class="badge-cancelled">🚫 ยกเลิกแล้ว</span>
                                        <?php if (!empty($row['cancel_reason'])): ?>
                                            <br><small style="color: #c62828;">(<?php echo htmlspecialchars($row['cancel_reason']); ?>)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-pending">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['approved_file'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($row['approved_file']); ?>" target="_blank" class="btn-viewfile">📄 เปิดดูเอกสารแนบ</a><br>
                                        <button class="btn-upload" style="background:#7f8c8d;" onclick="openUploadModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['applicant_name']); ?>')">🔄 เปลี่ยนไฟล์</button>
                                    <?php else: ?>
                                        <?php if (!$is_cancelled): ?>
                                            <button class="btn-upload" onclick="openUploadModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['applicant_name']); ?>')">📤 อัปโหลดคำสั่ง</button>
                                        <?php else: ?>
                                            <span style="color: #aaa; font-size: 12px;">คำขอยกเลิกแล้ว</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!$is_cancelled): ?>
                                        <button class="btn-cancel" onclick="openCancelModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['applicant_name']); ?>')">🚫 ยกเลิก</button>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">ยกเลิกเมื่อ <?php echo thai_date_short($row['cancelled_at']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:#999;">ยังไม่มีคำร้องในระบบ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal สำหรับเลือกไฟล์อัปโหลด -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">📤 อัปโหลดหนังสือคำสั่งที่อนุมัติแล้ว</div>
        <form method="POST" enctype="multipart/form-data" action="admin.php">
            <input type="hidden" name="upload_approved_action" value="1">
            <input type="hidden" name="trip_id" id="modal_trip_id">
            
            <p id="modal_trip_info" style="font-size: 14px; color: #555; margin-bottom: 15px;"></p>
            
            <label style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 6px;">เลือกไฟล์คำสั่ง (PDF, JPG, PNG):</label>
            <input type="file" name="approved_document" accept=".pdf, .jpg, .jpeg, .png" required style="width: 100%; font-size: 14px; margin-bottom: 15px;">

            <div class="modal-footer">
                <button type="button" onclick="closeUploadModal()" style="padding: 8px 15px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">ยกเลิก</button>
                <button type="submit" style="padding: 8px 15px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">บันทึกและอัปโหลด</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal(tripId, applicantName) {
    document.getElementById('modal_trip_id').value = tripId;
    document.getElementById('modal_trip_info').innerText = "คำร้อง #" + tripId + " ของ " + applicantName;
    document.getElementById('uploadModal').style.display = 'flex';
}

function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}

function openCancelModal(tripId, applicantName) {
    let reason = prompt("คุณต้องการยกเลิกคำขอ #" + tripId + " ของ " + applicantName + " หรือไม่?\n\nกรุณาระบุเหตุผลการยกเลิก (ถ้ามี):", "ยกเลิกตามความประสงค์ / ติดภารกิจอื่น");
    if (reason !== null) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin.php';

        let inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'cancel_action';
        inputAction.value = '1';

        let inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'trip_id';
        inputId.value = tripId;

        let inputReason = document.createElement('input');
        inputReason.type = 'hidden';
        inputReason.name = 'cancel_reason';
        inputReason.value = reason;

        form.appendChild(inputAction);
        form.appendChild(inputId);
        form.appendChild(inputReason);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>