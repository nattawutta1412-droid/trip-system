<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM official_trips WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    die("ไม่พบข้อมูลเอกสาร");
}

// ดึงข้อมูลผู้ร่วมเดินทาง
$participants = [];
$p_check = $conn->query("SHOW TABLES LIKE 'trip_participants'");
if ($p_check && $p_check->num_rows > 0) {
    $p_stmt = $conn->prepare("SELECT * FROM trip_participants WHERE trip_id = ? ORDER BY id ASC");
    $p_stmt->bind_param("i", $id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    while ($p_row = $p_res->fetch_assoc()) {
        $participants[] = $p_row;
    }
}

function thai_date($date_str) {
    if (!$date_str) return "";
    $thai_months = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];
    $time = strtotime($date_str);
    $d = date('j', $time);
    $m = $thai_months[intval(date('n', $time))];
    $y = date('Y', $time) + 543;
    return "$d $m $y";
}

// 1. เลขที่หนังสือ
$doc_number_display = !empty($trip['doc_number']) ? htmlspecialchars($trip['doc_number']) : '...................................................';

// 2. กำหนดการวันเวลา (กรณีครึ่งวัน)
$start_t = thai_date($trip['start_date'] ?? '');
$end_t   = thai_date($trip['end_date'] ?? '');
$half_time = trim($trip['half_day_time'] ?? '');

if (!empty($half_time)) {
    if ($trip['start_date'] === $trip['end_date']) {
        $schedule_prose = "ในวันที่ {$start_t} ({$half_time})";
    } else {
        $schedule_prose = "ตั้งแต่วันที่ {$start_t} ถึงวันที่ {$end_t} ({$half_time})";
    }
} else {
    if ($trip['start_date'] === $trip['end_date']) {
        $schedule_prose = "ในวันที่ {$start_t}";
    } else {
        $schedule_prose = "ตั้งแต่วันที่ {$start_t} ถึงวันที่ {$end_t}";
    }
}

// 3. ยานพาหนะ และ พนักงานขับรถ
$vehicle = $trip['vehicle_type'] ?? '';
$plate = trim($trip['vehicle_license_plate'] ?? '');
$driver = trim($trip['driver_name'] ?? '');
$vehicle_prose = "";

if (!empty($vehicle)) {
    if ($vehicle === 'รถยนต์ราชการ') {
        $vehicle_prose = "เดินทางไปราชการด้วยรถยนต์ราชการ" . (!empty($plate) ? " หมายเลขทะเบียน {$plate}" : "") . (!empty($driver) ? " โดยมี {$driver} เป็นพนักงานขับรถ" : "");
    } elseif ($vehicle === 'รถยนต์ส่วนตัว') {
        $vehicle_prose = "เดินทางไปราชการด้วยรถยนต์ส่วนตัว" . (!empty($plate) ? " หมายเลขทะเบียน {$plate}" : "");
    } else {
        $vehicle_prose = "เดินทางโดย{$vehicle}" . (!empty($plate) ? " หมายเลขทะเบียน {$plate}" : "");
    }
}

// 4. งบประมาณ
$expense_raw = $trip['expense_type'] ?? '';
$expense_prose_parts = [];

if (strpos($expense_raw, "ไม่ขอเบิกค่าใช้จ่าย") !== false) {
    $expense_prose_parts[] = "ไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการแต่อย่างใด";
}
if (strpos($expense_raw, "ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณ") !== false) {
    $expense_prose_parts[] = "ขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา (ค่ายานพาหนะเดินทาง, ค่าเบี้ยเลี้ยง, ค่าที่พัก) ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ";
}
if (strpos($expense_raw, "ขอเบิกเฉพาะค่าใช้จ่าย") !== false) {
    $sub = trim($trip['expense_specific_details'] ?? '');
    $expense_prose_parts[] = "ขออนุมัติเบิกเฉพาะค่าใช้จ่าย ได้แก่ " . (!empty($sub) ? $sub : "ตามที่เกิดขึ้นจริง");
}
if (!empty($trip['expense_other'])) {
    $expense_prose_parts[] = "และ" . htmlspecialchars($trip['expense_other']);
}

$expense_final_prose = "";
if (!empty($expense_prose_parts)) {
    $expense_final_prose = "โดยข้าพเจ้า" . implode(" อีกทั้ง", $expense_prose_parts);
} else {
    $expense_final_prose = "โดยข้าพเจ้าไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการ";
}

$academic_text = !empty($trip['academic_standing']) ? ' วิทยฐานะ' . htmlspecialchars($trip['academic_standing']) : '';
$ref_text = !empty($trip['ref_document']) ? 'ตามที่ได้มีหนังสือ ' . htmlspecialchars($trip['ref_document']) . (!empty($trip['ref_date']) ? ' ลงวันที่ ' . thai_date($trip['ref_date']) : '') . ' นั้น ' : '';

// ข้อมูลหัวหน้ากลุ่มงาน
$group_name = !empty($trip['work_group']) ? $trip['work_group'] : 'กลุ่มงาน';
$group_title = ($group_name === 'ฝ่ายบริหารสถานศึกษา') ? 'ผู้บริหารสถานศึกษา' : 'หัวหน้า' . $group_name;
$head_group_display = !empty($trip['head_group_name']) ? $trip['head_group_name'] : '.......................................................';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุมัติไปราชการ</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap">
    <style>
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New'), local('THSarabunNew'),
                 url('https://cdn.jsdelivr.net/gh/pittss/thai-web-fonts@master/fonts/thsarabunnew/thsarabunnew-webfont.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'TH Sarabun New';
            src: local('TH Sarabun New Bold'), local('THSarabunNew-Bold'),
                 url('https://cdn.jsdelivr.net/gh/pittss/thai-web-fonts@master/fonts/thsarabunnew/thsarabunnew_bold-webfont.woff2') format('woff2');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
            font-size: 16pt;
            line-height: 1.15;
            background-color: #525659;
            margin: 0;
            padding: 20px 0;
            color: #000;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 20mm 12mm 25mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
            position: relative;
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .header-box {
            position: relative;
            height: 55px;
            margin-bottom: 2px;
            text-align: center;
        }

        .garuda-img {
            position: absolute;
            left: 0;
            top: -2px;
            height: 55px;
            width: auto;
        }

        .doc-title {
            font-size: 29pt;
            font-weight: bold;
            line-height: 50px;
            letter-spacing: 0.5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16pt;
            line-height: 1.18;
        }

        .meta-table td {
            vertical-align: bottom;
            padding: 1px 0;
        }

        .divider-line {
            border: 0;
            border-top: 1.5px solid #000;
            margin: 3px 0 6px 0;
        }

        .to-line {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .prose-body {
            text-align: justify;
            text-justify: inter-cluster;
            text-indent: 2.5cm;
            font-size: 16pt;
            line-height: 1.20;
            margin-top: 2px;
        }

        .applicant-sign-wrap {
            margin-top: 6px;
            margin-left: auto;
            width: 50%;
            text-align: center;
            font-size: 16pt;
            line-height: 1.15;
        }

        .head-opinion-box {
            margin-top: 6px;
            border-top: 1px dashed #777;
            padding-top: 4px;
            font-size: 16pt;
            line-height: 1.15;
        }

        .head-sign-wrap {
            margin-left: auto;
            width: 50%;
            text-align: center;
            margin-top: 2px;
        }

        .director-frame {
            margin-top: 6px;
            border: 1px solid #000;
            padding: 5px 12px;
            font-size: 16pt;
            line-height: 1.15;
        }

        /* กล่องรับเรื่อง/ออกเลขหนังสือ กลุ่มงานบริหารทั่วไป มุมขวาล่าง */
        .admin-stamp-box {
            position: absolute;
            right: 20mm;
            bottom: 10mm;
            width: 65mm;
            border: 1px solid #333;
            padding: 4px 8px;
            font-size: 13pt;
            line-height: 1.25;
            background: #fff;
            box-sizing: border-box;
        }

        .attachment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 15pt;
        }
        .attachment-table th, .attachment-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }
        .attachment-table th {
            text-align: center;
            background-color: #f2f2f2;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .sheet { box-shadow: none; margin: 0; width: 210mm; min-height: 297mm; padding: 12mm 20mm 12mm 25mm; page-break-after: always; }
            .sheet:last-child { page-break-after: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center no-print" style="margin-bottom: 15px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 24px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px; font-weight: bold;">🖨 สั่งพิมพ์เอกสาร (Print)</button>
    <a href="index.php" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; border-radius: 4px; display: inline-block;">หน้ารายการทั้งหมด</a>
</div>

<!-- ================= หน้าที่ 1: บันทึกข้อความ ================= -->
<div class="sheet">
    <div class="header-box">
        <img src="garuda.png" alt="ตราครุฑ" class="garuda-img">
        <span class="doc-title">บันทึกข้อความ</span>
    </div>
    
    <table class="meta-table">
        <tr>
            <td colspan="2"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ โทร. 0-7528-1288</td>
        </tr>
        <tr>
            <td style="width: 58%;"><strong>ที่:</strong> <?php echo $doc_number_display; ?></td>
            <td style="width: 42%;"><strong>วันที่:</strong> <?php echo thai_date($trip['created_date'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</td>
        </tr>
    </table>
    
    <div class="divider-line"></div>

    <div class="to-line">เรียน &nbsp; ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>

    <div class="prose-body">
        <?php echo $ref_text; ?>ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?><?php echo $academic_text; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department'] ?? ''); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($trip['subject'] ?? ''); ?> ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?> พร้อมคณะ โดยมีกำหนดการ<?php echo htmlspecialchars($schedule_prose); ?> <?php echo !empty($vehicle_prose) ? "ในการนี้จะ" . htmlspecialchars($vehicle_prose) . " " : ""; ?><?php echo htmlspecialchars($expense_final_prose); ?> (รายละเอียดดังบัญชีรายชื่อแนบท้าย)
    </div>

    <div class="prose-body">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ลำดับที่ 1: ลายเซ็นผู้ขออนุมัติ -->
    <div class="applicant-sign-wrap">
        ลงชื่อ......................................................................<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>

    <!-- ลำดับที่ 2: ความเห็นและลายเซ็นของหัวหน้ากลุ่มงาน -->
    <div class="head-opinion-box">
        <strong>ความเห็นของ<?php echo htmlspecialchars($group_title); ?>:</strong><br>
        ..................................................................................................................................................................................<br>
        <div class="head-sign-wrap">
            ลงชื่อ......................................................................<br>
            ( <?php echo htmlspecialchars($head_group_display); ?> )<br>
            <?php echo htmlspecialchars($group_title); ?><br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
    </div>

    <!-- ลำดับที่ 3: คำสั่งและการอนุมัติของผู้อำนวยการสถานศึกษา -->
    <div class="director-frame">
        <strong>คำสั่ง / การพิจารณาของผู้อำนวยการสถานศึกษา:</strong><br>
        [ &nbsp; ] อนุมัติ &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก ..............................................................<br>
        <div style="text-align: center; margin-top: 3px;">
            ลงชื่อ......................................................................<br>
            ( ว่าที่ร้อยโทจักรเพชร์ พรมยศ )<br>
            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์<br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
    </div>

    <!-- กล่องบันทึกรับเรื่อง/ออกเลขหนังสือราชการ มุมขวาล่าง -->
    <div class="admin-stamp-box">
        <div style="font-weight: bold; text-align: center; border-bottom: 0.5px solid #666; margin-bottom: 3px; padding-bottom: 1px;">
            กลุ่มงานบริหารทั่วไป (งานสารบรรณ)
        </div>
        <div>เลขที่รับ / ออกเลข: ...................................</div>
        <div>วันที่ขอเลข: ........./........./..........................</div>
        <div>ผู้ลงบันทึก: .............................................</div>
    </div>
</div>

<!-- ================= หน้าที่ 2: บัญชีรายชื่อผู้ร่วมเดินทางแนบท้าย ================= -->
<div class="sheet">
    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="font-weight: bold; margin-bottom: 5px;">บัญชีรายชื่อผู้ขออนุมัติเดินทางไปราชการแนบท้าย</h3>
        <div>แนบท้ายบันทึกข้อความ ลงวันที่ <?php echo thai_date($trip['created_date'] ?? ''); ?></div>
    </div>

    <table class="attachment-table">
        <thead>
            <tr>
                <th style="width: 10%;">ลำดับ</th>
                <th style="width: 45%;">ชื่อ - สกุล</th>
                <th style="width: 30%;">ตำแหน่ง / ระดับชั้น</th>
                <th style="width: 15%;">หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td><?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($trip['position'] ?? ''); ?></td>
                <td style="text-align: center;">ผู้ขออนุมัติ</td>
            </tr>
            <?php if (!empty($participants)): ?>
                <?php $i = 2; foreach ($participants as $p): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['detail'] ?? ($p['position'] ?? '-')); ?></td>
                    <td style="text-align: center;"><?php echo ($p['type'] ?? '') === 'student' ? 'นักเรียน' : 'ผู้ร่วมเดินทาง'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td>..............................................................................</td>
                    <td>...................................................</td>
                    <td style="text-align: center;">-</td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td>..............................................................................</td>
                    <td>...................................................</td>
                    <td style="text-align: center;">-</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; margin-left: auto; width: 50%; text-align: center;">
        รับรองข้อมูลถูกต้อง<br><br><br>
        ลงชื่อ......................................................................<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>
</div>

</body>
</html>
