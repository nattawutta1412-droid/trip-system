<?php
session_start();
require_once 'config.php';

// ค้นหาชื่อตารางบุคลากร
$target_table = 'teachers';
$chk_tbl = $conn->query("SHOW TABLES LIKE 'teachers'");
if (!$chk_tbl || $chk_tbl->num_rows == 0) {
    $possible_tables = ['teacher_profiles', 'personnel', 'users', 'staff'];
    foreach ($possible_tables as $pt) {
        $chk = $conn->query("SHOW TABLES LIKE '{$pt}'");
        if ($chk && $chk->num_rows > 0) {
            $target_table = $pt;
            break;
        }
    }
}

// ฟังก์ชันทำความสะอาดและแปลงวันที่ให้อยู่ในฟอร์แมต YYYY-MM-DD สำหรับเก็บลง MySQL
function parse_date_to_db($date_str) {
    $date_str = trim($date_str);
    if (empty($date_str) || in_array($date_str, ['-', '0000-00-00', 'null', 'NULL'])) {
        return null;
    }

    $d = ''; $m = ''; $y = 0;

    if (strpos($date_str, '/') !== false) {
        $parts = explode('/', $date_str);
        if (count($parts) === 3) {
            $d = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
            $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
            $y = intval(trim($parts[2]));
        }
    } elseif (strpos($date_str, '-') !== false) {
        $parts = explode('-', $date_str);
        if (count($parts) === 3) {
            if (strlen(trim($parts[0])) === 4) {
                $y = intval(trim($parts[0]));
                $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
                $d = str_pad(trim($parts[2]), 2, '0', STR_PAD_LEFT);
            } else {
                $d = str_pad(trim($parts[0]), 2, '0', STR_PAD_LEFT);
                $m = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
                $y = intval(trim($parts[2]));
            }
        }
    }

    if (!empty($d) && !empty($m) && $y > 0) {
        // หากผู้ใช้กรอกเป็น พ.ศ. (มากกว่า 2400) ให้แปลงเป็น ค.ศ. ก่อนเก็บลง DB
        if ($y > 2400) {
            $y -= 543;
        }
        return "{$y}-{$m}-{$d}";
    }

    $t = strtotime($date_str);
    if ($t !== false) {
        return date('Y-m-d', $t);
    }

    return null;
}

$message = "";
$message_type = "";
$count_success = 0;

// ตรวจสอบเมื่อมีการอัปโหลดไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            // อ่านบรรทัดแรก (BOM & Header)
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headers = fgetcsv($handle, 1000, ",");
            if ($headers !== false) {
                // ล้างหัวคอลัมน์
                $clean_headers = array_map(function($h) {
                    return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                }, $headers);

                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    // ข้ามแถวว่าง
                    if (empty(array_filter($row))) continue;

                    // แมปข้อมูลตามตำแหน่งคอลัมน์มาตรฐาน
                    // ลำดับ (0), เลขบัตร (1), คำนำหน้า (2), ชื่อ (3), นามสกุล (4), ตำแหน่ง (5), วิทยฐานะ (6), กลุ่มสาระ (7), วันเกิด (8), วันบรรจุ (9), เบอร์โทร (10)
                    $cid        = trim($row[1] ?? '');
                    $prefix     = trim($row[2] ?? '');
                    $fname      = trim($row[3] ?? '');
                    $lname      = trim($row[4] ?? '');
                    
                    // รวมชื่อ-สกุลเต็ม
                    $fullname   = trim("{$prefix}{$fname} {$lname}");
                    if (empty($fullname)) {
                        $fullname = trim($row[3] ?? $row[2] ?? '');
                    }

                    $position   = trim($row[5] ?? 'ครู');
                    $academic   = trim($row[6] ?? '');
                    $department = trim($row[7] ?? '');
                    $birth_date = parse_date_to_db($row[8] ?? '');
                    $assign_date= parse_date_to_db($row[9] ?? '');
                    $phone      = trim($row[10] ?? '');
                    $sort_order = intval($row[0] ?? 0);

                    // ==============================================================
                    // ป้องกันการบันทึกซ้ำซ้อน: เช็คว่ามีข้อมูลเดิมอยู่แล้วหรือไม่
                    // (ตรวจสอบจาก เลขบัตรประชาชน หรือ ชื่อ-สกุล)
                    // ==============================================================
                    $existing_id = 0;
                    if (!empty($cid)) {
                        $chk = $conn->prepare("SELECT id FROM `{$target_table}` WHERE id_card = ? OR cid = ? LIMIT 1");
                        if ($chk) {
                            $chk->bind_param("ss", $cid, $cid);
                            $chk->execute();
                            $res = $chk->get_result();
                            if ($r = $res->fetch_assoc()) {
                                $existing_id = $r['id'];
                            }
                        }
                    }

                    if ($existing_id === 0 && !empty($fullname)) {
                        $chk_name = $conn->prepare("SELECT id FROM `{$target_table}` WHERE name = ? OR fullname = ? LIMIT 1");
                        if ($chk_name) {
                            $chk_name->bind_param("ss", $fullname, $fullname);
                            $chk_name->execute();
                            $res_n = $chk_name->get_result();
                            if ($rn = $res_n->fetch_assoc()) {
                                $existing_id = $rn['id'];
                            }
                        }
                    }

                    // ถ้ามีอยู่แล้ว -> ทำการ UPDATE (ไม่สร้างแถวใหม่)
                    if ($existing_id > 0) {
                        $stmt_up = $conn->prepare("UPDATE `{$target_table}` SET 
                            name = ?, fullname = ?, position = ?, academic_standing = ?, 
                            department = ?, birth_date = ?, assign_date = ?, phone = ?, sort_order = ?
                            WHERE id = ?");
                        if ($stmt_up) {
                            $stmt_up->bind_param("ssssssssii", $fullname, $fullname, $position, $academic, $department, $birth_date, $assign_date, $phone, $sort_order, $existing_id);
                            $stmt_up->execute();
                            $count_success++;
                        }
                    } 
                    // ถ้ายังไม่มี -> ทำการ INSERT ใหม่
                    else {
                        $stmt_in = $conn->prepare("INSERT INTO `{$target_table}` 
                            (name, fullname, position, academic_standing, department, birth_date, assign_date, phone, sort_order) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt_in) {
                            $stmt_in->bind_param("ssssssssi", $fullname, $fullname, $position, $academic, $department, $birth_date, $assign_date, $phone, $sort_order);
                            $stmt_in->execute();
                            $count_success++;
                        }
                    }
                }
                fclose($handle);
                $message = "นำเข้าและปรับปรุงข้อมูลบุคลากรเรียบร้อยแล้วทั้งหมด {$count_success} รายการ";
                $message_type = "success";
            }
        } else {
            $message = "กรุณาอัปโหลดไฟล์ที่มีนามสกุล .csv เท่านั้น";
            $message_type = "danger";
        }
    } else {
        $message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าข้อมูลบุคลากร (CSV) - Teacher MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 12px; }
    </style>
</head>
<body class="py-5">

<div class="container" style="max-width: 650px;">

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>นำเข้าข้อมูลครูผ่าน CSV / Google Sheets
            </h5>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info border-0 rounded-3 small mb-4">
                <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> คำแนะนำการเตรียมไฟล์:</div>
                <ol class="ps-3 mb-0">
                    <li>จัดเรียงหัวคอลัมน์มาตรฐาน: ลำดับ, เลขบัตรประชาชน, คำนำหน้า, ชื่อ, นามสกุล, ตำแหน่ง, วิทยฐานะ, กลุ่มสาระ, วันเกิด, วันบรรจุ, เบอร์โทร</li>
                    <li>เมื่อกรอกเสร็จ เลือก <strong>File > Download > Comma Separated Values (.csv)</strong></li>
                    <li>ระบบจะตรวจสอบจาก <strong>เลขบัตรประชาชน หรือ ชื่อ-สกุล</strong> หากมีข้อมูลอยู่แล้วจะอัปเดตทับทันที (ไม่บันทึกซ้ำซ้อน)</li>
                </ol>
            </div>

            <form action="import.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label small fw-bold">เลือกไฟล์ .CSV เพื่อนำเข้า:</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="admin_upload.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> กลับหน้ารายการ
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> เริ่มนำเข้าข้อมูล
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
