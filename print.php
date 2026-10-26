<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ไม่พบรหัสคำร้อง");
}

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

$stmt = $conn->prepare("SELECT * FROM {$target_table} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลคำร้องรหัส #{$id}");
}

// ฟังก์ชันแปลงวันที่ไทย (แก้ปัญหาปี พ.ศ. บวกซ้ำสองรอบ)
function thai_date($date_str) {
    if (empty($date_str)) return "-";
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $time = strtotime($date_str);
    if ($time === false) return "-";
    
    $d = date('j', $time);
    $m = $months[(int)date('n', $time)];
    $y = (int)date('Y', $time);
    
    // ตรวจสอบ: ถ้าปียังเป็น ค.ศ. (น้อยกว่า 2400) ให้บวก 543 แต่ถ้าเป็น พ.ศ. อยู่แล้วไม่ต้องบวกเพิ่ม
    if ($y < 2400) {
        $y += 543;
    }
    
    return "$d $m $y";
}

$applicant_name    = $data['applicant_name'] ?? $data['name'] ?? $data['fullname'] ?? $data['teacher_name'] ?? '';
$position_name     = $data['position'] ?? 'ครู';
$academic_standing = $data['academic_standing'] ?? '';
$department_name   = $data['department'] ?? '';

// ตรวจสอบฟิลด์วัตถุประสงค์ (ไปราชการเพื่อ) จากทุกชื่อคอลัมน์ที่เป็นไปได้
$topic_name = '';
$topic_fields = ['topic', 'objective', 'subject', 'purpose', 'title', 'reason', 'details', 'trip_topic'];
foreach ($topic_fields as $tf) {
    if (!empty($data[$tf])) {
        $topic_name = trim($data[$tf]);
        break;
    }
}

// ตรวจสอบฟิลด์สถานที่ปลายทาง
$destination_name = '';
$dest_fields = ['destination', 'location', 'place', 'target', 'travel_place'];
foreach ($dest_fields as $df) {
    if (!empty($data[$df])) {
        $destination_name = trim($data[$df]);
        break;
    }
}

$created_date_th   = !empty($data['created_at']) ? thai_date($data['created_at']) : thai_date(date('Y-m-d'));
$start_date_th     = !empty($data['start_date']) ? thai_date($data['start_date']) : (!empty($data['go_date']) ? thai_date($data['go_date']) : '');
$end_date_th       = !empty($data['end_date']) ? thai_date($data['end_date']) : (!empty($data['back_date']) ? thai_date($data['back_date']) : '');

// ประมวลผลเรื่องงบประมาณ
$budget_type   = trim($data['budget_type'] ?? '');
$expense_types = trim($data['expense_types'] ?? '');

$is_no_budget = (empty($budget_type) || $budget_type === 'ไม่ขอเบิกงบประมาณ' || $budget_type === 'ไม่เบิก' || $budget_type === 'ไม่ขอเบิก');

$budget_text = "";
if ($is_no_budget) {
    $budget_text = "โดยข้าพเจ้าไม่ขอเบิกค่าใช้จ่ายใดๆ ในการเดินทางไปราชการครั้งนี้ (รายละเอียดดังบัญชีรายชื่อแนบท้าย)";
} else {
    $budget_source = ($budget_type === 'เงินงบประมาณสถานศึกษา') ? "เงินงบประมาณของสถานศึกษา" : (($budget_type === 'เงินนอกงบประมาณ') ? "เงินนอกงบประมาณของสถานศึกษา" : $budget_type);
    $expense_detail = !empty($expense_types) ? " ได้แก่ " . str_replace(',', ', ', $expense_types) : "";
    $budget_text = "โดยข้าพเจ้าขอเบิกค่าใช้จ่ายตามสิทธิจาก{$budget_source} ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ อีกทั้งขออนุมัติเบิกเฉพาะค่าใช้จ่าย{$expense_detail} (รายละเอียดดังบัญชีรายชื่อแนบท้าย)";
}

// ประมวลผลรายชื่อผู้ร่วมเดินทาง
$raw_companions = '';
$check_fields = ['companions', 'passengers', 'members', 'co_travelers', 'travelers', 'other_teachers', 'colleagues'];
foreach ($check_fields as $fld) {
    if (!empty($data[$fld])) {
        $raw_companions = $data[$fld];
        break;
    }
}

$companions = [];
if (!empty($raw_companions)) {
    $decoded = json_decode($raw_companions, true);
    if (is_array($decoded)) {
        foreach ($decoded as $item) {
            if (is_array($item)) {
                $companions[] = [
                    'name' => $item['name'] ?? $item['teacher_name'] ?? reset($item),
                    'position' => $item['position'] ?? 'ครู'
                ];
            } else if (!empty($item)) {
                $companions[] = ['name' => $item, 'position' => 'ครู'];
            }
        }
    } else {
        $clean_text = str_replace(["\r\n", "\r", ","], "\n", $raw_companions);
        $lines = explode("\n", $clean_text);
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^[0-9]+[\.\)\s\-]+/', '', $line);
            if (!empty($line)) {
                $pos = 'ครู';
                if (strpos($line, 'ตำแหน่ง') !== false) {
                    $parts = explode('ตำแหน่ง', $line);
                    $c_name = trim($parts[0]);
                    $pos = trim($parts[1]) ?: 'ครู';
                } else {
                    $c_name = $line;
                }
                $companions[] = ['name' => $c_name, 'position' => $pos];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุมัติเดินทางไปราชการ - <?php echo htmlspecialchars($applicant_name); ?></title>
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
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'TH Sarabun New', sans-serif;
            font-size: 16pt;
            line-height: 1.18;
            color: #000;
            background: #525659;
            margin: 0;
            padding: 20px;
        }

        .page {
            background: white;
            width: 210mm;
            height: 297mm;
            padding: 1.5cm 2cm 1cm 2.5cm;
            margin: 0 auto 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .memo-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .memo-header-table td {
            padding: 0;
            vertical-align: bottom;
        }
        .garuda-cell {
            width: 1.5cm;
            vertical-align: top !important;
        }
        .garuda-img {
            height: 1.5cm;
            width: auto;
            display: block;
        }
        .title-memo {
            font-size: 29pt;
            font-weight: bold;
            text-align: center;
            line-height: 1;
            padding-right: 1.5cm;
        }

        .header-info {
            width: 100%;
            border-collapse: collapse;
            font-size: 16pt;
            margin-bottom: 2px;
        }
        .header-info td {
            padding: 0;
            vertical-align: bottom;
        }

        .content-body {
            text-align: justify;
            text-justify: inter-cluster;
            text-indent: 2.5cm;
            font-size: 16pt;
            line-height: 1.2;
            margin-top: 2px;
            margin-bottom: 2px;
        }

        .sign-block {
            text-align: center;
            float: right;
            width: 58%;
            margin-top: 4px;
            line-height: 1.1;
        }
        .clear { clear: both; }

        .comment-section {
            margin-top: 3px;
            line-height: 1.1;
        }

        .order-box {
            border: 1px solid #000;
            padding: 4px 10px;
            margin-top: 4px;
            line-height: 1.1;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .table-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 16pt;
        }
        .table-list th, .table-list td {
            border: 1px solid #000;
            padding: 4px 8px;
            text-align: left;
        }
        .table-list th {
            text-align: center;
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-print {
            padding: 8px 24px;
            font-size: 16px;
            font-family: sans-serif;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .page {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 297mm;
                padding: 1.5cm 2cm 1cm 2.5cm;
            }
            .page-break {
                page-break-before: always;
                break-before: page;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">พิมพ์เอกสาร</button>
</div>

<!-- ========================= หน้าที่ 1: บันทึกข้อความ ========================= -->
<div class="page">
    <table class="memo-header-table">
        <tr>
            <td class="garuda-cell">
                <img src="garuda.png" alt="ครุฑ" class="garuda-img" onerror="this.src='../garuda.png';">
            </td>
            <td>
                <div class="title-memo">บันทึกข้อความ</div>
            </td>
        </tr>
    </table>

    <table class="header-info">
        <tr>
            <td colspan="2"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</td>
        </tr>
        <tr>
            <td style="width: 58%;"><strong>ที่:</strong> ............................................................................</td>
            <td style="width: 42%;"><strong>วันที่:</strong> <?php echo $created_date_th; ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรียน:</strong> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</td>
        </tr>
    </table>

    <div class="content-body">
        ด้วยข้าพเจ้า <strong><?php echo htmlspecialchars($applicant_name); ?></strong> 
        ตำแหน่ง <?php echo htmlspecialchars($position_name); ?> 
        <?php if (!empty($academic_standing)): ?>
            วิทยฐานะ<?php echo htmlspecialchars($academic_standing); ?> 
        <?php endif; ?>
        กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($department_name); ?> 
        มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($topic_name); ?> 
        ณ <?php echo htmlspecialchars($destination_name); ?> 
        โดยมีกำหนดการตั้งแต่วันที่ <?php echo $start_date_th; ?> 
        ถึงวันที่ <?php echo $end_date_th; ?> 
        <?php echo $budget_text; ?>
    </div>

    <div class="content-body" style="margin-top: 1px; margin-bottom: 1px;">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ลายเซ็นผู้ขอ -->
    <div class="sign-block">
        <div>ลงชื่อ..........................................................</div>
        <div style="margin-top: 1px;">( <?php echo htmlspecialchars($applicant_name); ?> )</div>
        <div>ตำแหน่ง <?php echo htmlspecialchars($position_name); ?></div>
    </div>
    <div class="clear"></div>

    <!-- ความเห็นของหัวหน้ากลุ่มงานบริหารวิชาการ -->
    <div class="comment-section">
        <div><strong>ความเห็นของหัวหน้ากลุ่มงานบริหารวิชาการ:</strong></div>
        <div style="border-bottom: 1px dotted #000; height: 14px;"></div>
        <div class="sign-block" style="margin-top: 2px;">
            <div>ลงชื่อ..........................................................</div>
            <div style="margin-top: 1px;">( นางศุภลักษณ์ ดำชุม )</div>
            <div>หัวหน้ากลุ่มงานบริหารวิชาการ</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- ความเห็นของรองผู้อำนวยการกลุ่มงานบริหารบุคคล -->
    <div class="comment-section">
        <div><strong>ความเห็นของรองผู้อำนวยการกลุ่มงานบริหารบุคคล:</strong></div>
        <div style="border-bottom: 1px dotted #000; height: 14px;"></div>
        <div class="sign-block" style="margin-top: 2px;">
            <div>ลงชื่อ..........................................................</div>
            <div style="margin-top: 1px;">( นางโรสนานีย์ บุญณะ )</div>
            <div>รองผู้อำนวยการกลุ่มบริหารงานบุคคล</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- คำสั่ง / การพิจารณา ผอ. -->
    <div class="order-box">
        <div><strong>คำสั่ง / การพิจารณา:</strong></div>
        <table style="width: 100%; margin-top: 1px;">
            <tr>
                <td style="width: 25%; vertical-align: top;">[ &nbsp; ] อนุมัติ</td>
                <td style="width: 75%; vertical-align: top;">[ &nbsp; ] ไม่อนุมัติ เนื่องจาก ............................................................</td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 4px; margin-left: 20%;">
            <div>ว่าที่ร้อยโท..........................................................</div>
            <div style="margin-top: 1px;">(จักเพชร์ พรมยศ)</div>
            <div>ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>
        </div>
    </div>
</div>

<!-- ========================= หน้าที่ 2: บัญชีรายชื่อผู้ร่วมเดินทางแนบท้าย ========================= -->
<div class="page page-break">
    <div style="text-align: center; margin-bottom: 15px;">
        <h3 style="font-size: 20pt; font-weight: bold; margin: 0;">บัญชีรายชื่อผู้ขออนุมัติเดินทางไปราชการแนบท้าย</h3>
        <div style="font-size: 16pt; margin-top: 4px;">
            ตามบันทึกข้อความ ที่ ................................................. วันที่ <?php echo $created_date_th; ?>
        </div>
        <div style="font-size: 16pt; margin-top: 2px;">
            เรื่อง ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($topic_name); ?>
        </div>
        <div style="font-size: 16pt;">
            ณ <?php echo htmlspecialchars($destination_name); ?>
        </div>
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th style="width: 10%;">ลำดับ</th>
                <th style="width: 45%;">ชื่อ - สกุล</th>
                <th style="width: 25%;">ตำแหน่ง</th>
                <th style="width: 20%;">หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td><?php echo htmlspecialchars($applicant_name); ?></td>
                <td><?php echo htmlspecialchars($position_name); ?></td>
                <td style="text-align: center;">ผู้ขออนุมัติ</td>
            </tr>

            <?php if (!empty($companions)): ?>
                <?php $idx = 2; foreach ($companions as $c): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $idx++; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['position']); ?></td>
                        <td style="text-align: center;">ผู้ร่วมเดินทาง</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td>...........................................................................</td>
                    <td>...................................</td>
                    <td style="text-align: center;">ผู้ร่วมเดินทาง</td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td>...........................................................................</td>
                    <td>...................................</td>
                    <td style="text-align: center;">ผู้ร่วมเดินทาง</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="sign-block" style="margin-top: 35px; float: right; width: 50%;">
        <div>(ลงชื่อ)............................................................ผู้ขออนุมัติ</div>
        <div style="margin-top: 2px;">( <?php echo htmlspecialchars($applicant_name); ?> )</div>
        <div>ตำแหน่ง <?php echo htmlspecialchars($position_name); ?></div>
    </div>
    <div class="clear"></div>
</div>

</body>
</html>
