<?php
require_once 'config.php';

// รับค่าตัวกรองเหมือนหน้า index
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$month = trim($_GET['month'] ?? '');

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
    $where_clauses[] = "(department = ? OR work_group = ?)";
    $params[] = $department;
    $params[] = $department;
    $types .= "ss";
}

if ($month !== '') {
    $where_clauses[] = "DATE_FORMAT(created_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM official_trips WHERE {$where_sql} ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

function thai_date_full($date_str) {
    if (!$date_str) return "-";
    $thai_months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $t = strtotime($date_str);
    return date('j', $t) . ' ' . $thai_months[intval(date('n', $t))] . ' ' . (date('Y', $t) + 543);
}

function thai_date_short($date_str) {
    if (!$date_str) return "-";
    $thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $t = strtotime($date_str);
    return date('j', $t) . ' ' . $thai_months[intval(date('n', $t))] . ' ' . (date('Y', $t) + 543);
}

// ข้อความระบุเงื่อนไขรายงาน
$filter_desc = [];
if (!empty($month)) {
    $m_time = strtotime($month . "-01");
    $m_thai = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    $filter_desc[] = "ประจำเดือน " . $m_thai[intval(date('n', $m_time))] . " " . (date('Y', $m_time) + 543);
} else {
    $filter_desc[] = "ข้อมูลทั้งหมด";
}
if (!empty($department)) {
    $filter_desc[] = "สังกัด/กลุ่มงาน: " . htmlspecialchars($department);
}
if (!empty($search)) {
    $filter_desc[] = "คำค้นหา: \"" . htmlspecialchars($search) . "\"";
}
$filter_title = implode(" | ", $filter_desc);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปการขออนุมัติเดินทางไปราชการ</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap">
    <style>
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New'), local('THSarabunNew'),
                 url('https://cdn.jsdelivr.net/gh/pittss/thai-web-fonts@master/fonts/thsarabunnew/thsarabunnew-webfont.woff2') format('woff2');
            font-weight: normal;
        }
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New Bold'), local('THSarabunNew-Bold'),
                 url('https://cdn.jsdelivr.net/gh/pittss/thai-web-fonts@master/fonts/thsarabunnew/thsarabunnew_bold-webfont.woff2') format('woff2');
            font-weight: bold;
        }

        @page {
            size: A4 landscape;
            margin: 12mm 15mm 12mm 15mm;
        }

        body {
            font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
            font-size: 14pt;
            line-height: 1.2;
            color: #000;
            background-color: #525659;
            margin: 0;
            padding: 20px 0;
        }

        .report-page {
            width: 297mm;
            min-height: 210mm;
            padding: 15mm 20mm;
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
            position: relative;
        }

        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }

        .btn-action {
            padding: 8px 20px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print { background: #0d6efd; color: white; margin-right: 10px; }
        .btn-back { background: #6c757d; color: white; }

        .report-header {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
        }
        .school-logo-img {
            height: 60px;
            width: auto;
            margin-bottom: 6px;
        }
        .report-title {
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.2;
        }
        .report-subtitle {
            font-size: 15pt;
            color: #222;
        }
        .report-filter {
            font-size: 13.5pt;
            color: #444;
            margin-top: 3px;
        }

        /* ตารางรายงาน */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13pt;
            margin-top: 10px;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .report-table th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .text-center { text-align: center; }
        .text-start { text-align: left; }

        /* ส่วนลงชื่อท้ายรายงาน */
        .signature-section {
            margin-top: 25px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 45%;
            text-align: center;
            font-size: 14pt;
            line-height: 1.35;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .report-page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-action btn-print">🖨 สั่งพิมพ์รายงาน (A4 แนวนอน)</button>
    <a href="index.php" class="btn-action btn-back">กลับหน้าหลัก</a>
</div>

<div class="report-page">
    <!-- หัวรายงาน (ใช้ตราประจำโรงเรียน) -->
    <div class="report-header">
        <img src="logo.png" alt="ตราประจำโรงเรียนย่านตาขาวรัฐชนูปถัมภ์" class="school-logo-img" onerror="this.src='https://placehold.co/60x60?text=YKR';"><br>
        <div class="report-title">รายงานสรุปการขออนุมัติเดินทางไปราชการ</div>
        <div class="report-subtitle">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</div>
        <div class="report-filter">เงื่อนไข: <?php echo $filter_title; ?> (ข้อมูล ณ วันที่ <?php echo thai_date_full(date('Y-m-d')); ?>)</div>
    </div>

    <!-- ตารางข้อมูล -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 4%;">ที่</th>
                <th style="width: 11%;">วันที่ยื่น</th>
                <th style="width: 18%;">ชื่อ - สกุล ผู้ขออนุมัติ</th>
                <th style="width: 16%;">กลุ่มสาระฯ / กลุ่มงาน</th>
                <th style="width: 25%;">วัตถุประสงค์และสถานที่ปลายทาง</th>
                <th style="width: 16%;">ช่วงวันที่ไปราชการ</th>
                <th style="width: 10%;">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php 
                $count = 1;
                $active_count = 0;
                $cancel_count = 0;
                while ($row = $result->fetch_assoc()): 
                    $status = $row['status'] ?? 'ปกติ';
                    if ($status === 'ยกเลิก') $cancel_count++; else $active_count++;
                ?>
                <tr>
                    <td class="text-center"><?php echo $count++; ?></td>
                    <td class="text-center"><?php echo thai_date_short($row['created_date'] ?? ''); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['applicant_name'] ?? ''); ?></strong>
                        <?php if (!empty($row['position'])): ?>
                            <br><small style="font-size: 11pt; color: #333;"><?php echo htmlspecialchars($row['position']); ?><?php echo !empty($row['academic_standing']) ? ' ' . htmlspecialchars($row['academic_standing']) : ''; ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['department'] ?? '-'); ?>
                        <?php if (!empty($row['work_group'])): ?>
                            <br><small style="font-size: 11pt; color: #555;">(<?php echo htmlspecialchars($row['work_group']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($row['subject'] ?? '-'); ?></div>
                        <small style="font-size: 11pt; color: #444;">ปลายทาง: <?php echo htmlspecialchars($row['destination'] ?? '-'); ?></small>
                    </td>
                    <td class="text-center">
                        <?php echo thai_date_short($row['start_date'] ?? ''); ?> -<br>
                        <?php echo thai_date_short($row['end_date'] ?? ''); ?>
                    </td>
                    <td class="text-center">
                        <?php echo ($status === 'ยกเลิก') ? '<span style="color: red; font-weight: bold;">ยกเลิก</span>' : 'ปกติ'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="7" style="background-color: #f9f9f9; padding: 6px 10px; font-weight: bold;">
                        รวมรายการทั้งหมด: <?php echo ($count - 1); ?> รายการ (ปกติ: <?php echo $active_count; ?> รายการ | ยกเลิก: <?php echo $cancel_count; ?> รายการ)
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 25px;">ไม่พบข้อมูลตามเงื่อนไขที่กำหนด</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ส่วนลงนามผู้รายงานและผู้อำนวยการ -->
    <div class="signature-section">
        <div class="sign-box">
            ลงชื่อ......................................................................ผู้รายงาน<br>
            ( ...................................................................... )<br>
            ตำแหน่ง เจ้าหน้าที่งานบุคคล<br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
        <div class="sign-box">
            ทราบ / พิจารณา<br><br>
            ลงชื่อ......................................................................<br>
            ( ว่าที่ร้อยโทจักรเพชร์ พรมยศ )<br>
            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์<br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
    </div>
</div>

</body>
</html>
