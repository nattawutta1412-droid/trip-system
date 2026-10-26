<?php
require_once 'config.php';

// 1. ค้นหาชื่อตารางคำร้องอัตโนมัติ
$target_table = null;
$possible_tables = ['official_trips', 'trip_requests', 'trips', 'trip_records', 'forms', 'requests'];

foreach ($possible_tables as $tbl) {
    $chk = $conn->query("SHOW TABLES LIKE '{$tbl}'");
    if ($chk && $chk->num_rows > 0) {
        $target_table = $tbl;
        break;
    }
}

if (!$target_table) {
    $all_tables = $conn->query("SHOW TABLES");
    if ($all_tables) {
        while ($t_row = $all_tables->fetch_array()) {
            $curr_tbl = $t_row[0];
            if ($curr_tbl !== 'teachers' && $curr_tbl !== 'users' && $curr_tbl !== 'admins') {
                $target_table = $curr_tbl;
                break;
            }
        }
    }
}

if (!$target_table) {
    die("ไม่พบตารางข้อมูลคำร้องในระบบ");
}

// ฟังก์ชันแปลงวันที่ไทยย่อ
function thai_date_short($date_str) {
    if (empty($date_str)) return "-";
    $months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $time = strtotime($date_str);
    if ($time === false) return "-";
    
    $d = date('j', $time);
    $m = $months[(int)date('n', $time)];
    $y = (int)date('Y', $time);
    if ($y < 2400) $y += 543;
    
    return "$d $m $y";
}

// ดึงข้อมูลคำร้องทั้งหมด
$sql = "SELECT * FROM `{$target_table}` ORDER BY id ASC";
$result = $conn->query($sql);

$total_count = 0;
$normal_count = 0;
$cancel_count = 0;
$rows = [];

if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $total_count++;
        if (($r['status'] ?? 'ปกติ') === 'ยกเลิก') {
            $cancel_count++;
        } else {
            $normal_count++;
        }
    }
}

$today_thai = thai_date_short(date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปการขออนุมัติเดินทางไปราชการ - โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</title>
    <style>
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New'), local('THSarabunNew'),
                 url('https://cdn.jsdelivr.net/gh/lazywasabi/thai-web-fonts@v1.0.0/fonts/THSarabunNew/THSarabunNew.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New Bold'), local('THSarabunNew-Bold'),
                 url('https://cdn.jsdelivr.net/gh/lazywasabi/thai-web-fonts@v1.0.0/fonts/THSarabunNew/THSarabunNew-Bold.woff2') format('woff2');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            size: A4 landscape;
            margin: 1.2cm 1.5cm 1.2cm 1.5cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'TH Sarabun New', sans-serif;
            font-size: 15pt;
            line-height: 1.15;
            color: #000;
            background: #525659;
            margin: 0;
            padding: 20px;
        }

        .report-page {
            background: white;
            width: 297mm;
            min-height: 210mm;
            padding: 1.2cm 1.5cm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            position: relative;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

        .logo-img {
            height: 1.8cm;
            width: auto;
            display: block;
            margin: 0 auto 5px auto;
        }

        .header-title {
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.1;
        }
        .header-subtitle {
            font-size: 16pt;
            line-height: 1.15;
        }
        .header-meta {
            font-size: 14pt;
            color: #333;
            margin-bottom: 10px;
        }

        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 14pt;
            margin-top: 8px;
        }
        .table-report th, .table-report td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: top;
        }
        .table-report th {
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
        }

        /* ตารางคุมส่วนลงนามท้ายรายงาน */
        .sign-layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            font-size: 15pt;
        }
        .sign-layout-table td {
            padding: 0;
            vertical-align: top;
        }

        .signature-gap {
            margin-top: 15px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-print {
            padding: 8px 22px;
            font-size: 16px;
            font-family: sans-serif;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            padding: 8px 18px;
            font-size: 16px;
            font-family: sans-serif;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 8px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .report-page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ สั่งพิมพ์รายงาน (A4 แนวนอน)</button>
    <a href="admin.php" class="btn-back">กลับหน้าหลัก</a>
</div>

<div class="report-page">

    <!-- ส่วนหัวรายงาน -->
    <div class="text-center">
        <img src="logo.png" alt="ตราโรงเรียน" class="logo-img" onerror="this.src='../logo.png';">
        <div class="header-title">รายงานสรุปการขออนุมัติเดินทางไปราชการ</div>
        <div class="header-subtitle">โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง</div>
        <div class="header-meta">เงื่อนไข: ข้อมูลทั้งหมด (ข้อมูล ณ วันที่ <?php echo $today_thai; ?>)</div>
    </div>

    <!-- ตารางรายการ -->
    <table class="table-report">
        <thead>
            <tr>
                <th style="width: 5%;">ที่</th>
                <th style="width: 11%;">วันที่ยื่น</th>
                <th style="width: 17%;">ชื่อ - สกุล ผู้ขออนุมัติ</th>
                <th style="width: 15%;">กลุ่มสาระฯ / กลุ่มงาน</th>
                <th style="width: 28%;">วัตถุประสงค์และสถานที่ปลายทาง</th>
                <th style="width: 16%;">ช่วงวันที่ไปราชการ</th>
                <th style="width: 8%;">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php $no = 1; foreach ($rows as $item): ?>
                    <?php 
                        $t_name = $item['applicant_name'] ?? $item['name'] ?? $item['fullname'] ?? '';
                        $t_pos  = $item['position'] ?? 'ครู';
                        $t_dept = $item['department'] ?? '-';
                        
                        // วัตถุประสงค์
                        $t_topic = $item['topic'] ?? $item['purpose'] ?? $item['subject'] ?? $item['title'] ?? '-';
                        // สถานที่ปลายทาง
                        $t_dest  = $item['destination'] ?? $item['location'] ?? $item['place'] ?? '-';
                        
                        // วันที่ยื่น
                        $sub_date = !empty($item['created_at']) ? thai_date_short($item['created_at']) : (!empty($item['created_date']) ? thai_date_short($item['created_date']) : '-');
                        
                        // ช่วงวันเดินทาง
                        $s_date = !empty($item['start_date']) ? thai_date_short($item['start_date']) : '';
                        $e_date = !empty($item['end_date']) ? thai_date_short($item['end_date']) : '';
                        $trip_period = ($s_date && $e_date) ? "{$s_date} -<br>{$e_date}" : ($s_date ?: '-');
                        
                        $status_text = $item['status'] ?? 'ปกติ';
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td class="text-center"><?php echo $sub_date; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($t_name); ?></strong><br>
                            <span style="font-size: 13pt; color: #444;"><?php echo htmlspecialchars($t_pos); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($t_dept); ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($t_topic); ?></div>
                            <div style="font-size: 13pt; color: #444; margin-top: 2px;">
                                <strong>ปลายทาง:</strong> <?php echo htmlspecialchars($t_dest); ?>
                            </div>
                        </td>
                        <td class="text-center"><?php echo $trip_period; ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($status_text); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">ไม่พบข้อมูลคำร้องขออนุมัติเดินทางไปราชการ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="font-size: 14pt; margin-top: 5px; font-weight: bold;">
        รวมรายการทั้งสิ้น: <?php echo $total_count; ?> รายการ (ปกติ: <?php echo $normal_count; ?> รายการ | ยกเลิก: <?php echo $cancel_count; ?> รายการ)
    </div>

    <!-- ส่วนลงลายมือชื่อท้ายรายงาน (ระนาบเริ่มต้นบรรทัดแรกเสมอกันเป๊ะทั้งสองฝั่ง) -->
    <table class="sign-layout-table">
        <tr>
            <!-- ฝั่งซ้าย: ผู้รายงาน -->
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div>ลงชื่อ..........................................................ผู้รายงาน</div>
                <div class="signature-gap">( .......................................................... )</div>
                <div style="margin-top: 2px;">ตำแหน่ง เจ้าหน้าที่งานบุคคล</div>
                <div style="margin-top: 4px;">วันที่ ........ เดือน .................... พ.ศ. ............</div>
            </td>

            <!-- ฝั่งขวา: ผู้อำนวยการโรงเรียน (ตัดคำว่า ทราบ / พิจารณา ออก) -->
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div>ว่าที่ร้อยโท..........................................................</div>
                <div class="signature-gap">(จักรเพชร์ พรมยศ)</div>
                <div style="margin-top: 2px;">ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>
                <div style="margin-top: 4px;">วันที่ ........ เดือน .................... พ.ศ. ............</div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
