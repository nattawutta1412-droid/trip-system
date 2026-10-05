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

// ดึงข้อมูลผู้ร่วมเดินทาง (ถ้ามี)
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

// แปลงคำศัพท์พาหนะเป็นภาษาเขียนร้อยแก้ว
$vehicle = $trip['vehicle_type'] ?? '';
if ($vehicle === 'personal_car') {
    $vehicle_text = 'รถยนต์ส่วนบุคคล';
} elseif ($vehicle === 'school_bus') {
    $vehicle_text = 'รถยนต์ส่วนกลางของสถานศึกษา';
} elseif ($vehicle === 'public_transport') {
    $vehicle_text = 'รถโดยสารประจำทาง';
} else {
    $vehicle_text = !empty($vehicle) ? $vehicle : 'รถยนต์ส่วนบุคคล';
}

$license_text = !empty($trip['vehicle_license_plate']) ? ' หมายเลขทะเบียน ' . htmlspecialchars($trip['vehicle_license_plate']) : '';

// แปลงคำศัพท์งบประมาณเป็นภาษาเขียนร้อยแก้ว
$expense = $trip['expense_type'] ?? '';
if ($expense === 'no_expense' || $expense === 'ไม่ขอเบิกงบประมาณ') {
    $expense_text = 'โดยไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการแต่อย่างใด';
} elseif ($expense === 'school_budget' || $expense === 'ขอเบิกจากต้นสังกัด') {
    $expense_text = 'โดยขออนุมัติเบิกจ่ายค่าใช้จ่ายในการเดินทางไปราชการตามระเบียบจากทางโรงเรียน';
} elseif ($expense === 'organizer_budget' || $expense === 'ขอเบิกจากผู้จัด') {
    $expense_text = 'โดยขอเบิกจ่ายค่าใช้จ่ายในการเดินทางไปราชการจากหน่วยงานผู้จัด';
} else {
    $expense_text = !empty($expense) ? 'โดย' . htmlspecialchars($expense) : 'โดยไม่ขอเบิกค่าใช้จ่ายในการเดินทางไปราชการ';
}

$academic_text = !empty($trip['academic_standing']) ? ' วิทยฐานะ' . htmlspecialchars($trip['academic_standing']) : '';
$ref_text = !empty($trip['ref_document']) ? 'ตามที่ได้มีหนังสือ ' . htmlspecialchars($trip['ref_document']) . (!empty($trip['ref_date']) ? ' ลงวันที่ ' . thai_date($trip['ref_date']) : '') . ' นั้น ' : '';

// ข้อความระบุผู้ร่วมเดินทางในร้อยแก้ว
$participant_summary_text = "";
if (!empty($participants)) {
    $p_count = count($participants);
    $participant_summary_text = " พร้อมด้วยคณะครูและบุคลากร/นักเรียน จำนวน {$p_count} คน (รายละเอียดดังบัญชีรายชื่อแนบท้าย) ";
}

$head_title = !empty($trip['head_department']) ? "หัวหน้า" . $trip['head_department'] : "หัวหน้าฝ่าย";
$head_name_display = !empty($trip['head_name']) ? $trip['head_name'] : '.......................................................';
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
            height: 297mm;
            max-height: 297mm;
            padding: 12mm 20mm 12mm 25mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            page-break-after: always;
        }

        .header-box {
            position: relative;
            height: 55px;
            margin-bottom: 2px;
            text-align: center;
        }

        /* ดึงรูปครุฑ garuda.png ในโฟลเดอร์ของระบบโดยตรง */
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
            line-height: 1.22;
            margin-top: 2px;
        }

        /* 1. ลายเซ็นผู้ขออนุมัติ */
        .applicant-sign-wrap {
            margin-top: 8px;
            margin-left: auto;
            width: 50%;
            text-align: center;
            font-size: 16pt;
            line-height: 1.18;
        }

        /* 2. ความเห็นและลายเซ็นหัวหน้าฝ่าย */
        .head-opinion-box {
            margin-top: 6px;
            border-top: 1px dashed #777;
            padding-top: 4px;
            font-size: 16pt;
            line-height: 1.18;
        }

        .head-sign-wrap {
            margin-left: auto;
            width: 50%;
            text-align: center;
            margin-top: 3px;
        }

        /* 3. คำสั่งและการอนุมัติของผู้อำนวยการ */
        .director-frame {
            margin-top: 6px;
            border: 1px solid #000;
            padding: 6px 14px;
            font-size: 16pt;
            line-height: 1.18;
        }

        /* ตารางแนบท้ายรายชื่อผู้ร่วมเดินทาง */
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
            .sheet { box-shadow: none; margin: 0; width: 210mm; height: 297mm; padding: 12mm 20mm 12mm 25mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center no-print" style="margin-bottom: 15px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 24px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px; font-weight: bold;">🖨️ สั่งพิมพ์เอกสาร (Print)</button>
    <a href="index.php" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; border-radius: 4px; display: inline-block;">หน้ารายการทั้งหมด</a>
    <?php if (!empty($trip['approved_file'])): ?>
        <a href="uploads/<?php echo htmlspecialchars($trip['approved_file']); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; font-size: 16px; background: #198754; color: white; border-radius: 4px; display: inline-block;">📥 ดาวน์โหลดคำสั่งที่อนุมัติแล้ว</a>
    <?php endif; ?>
</div>

<!-- หน้าที่ 1: บันทึกข้อความขออนุมัติ -->
<div class="sheet">
    <div class="header-box">
        <!-- ชี้ไปที่ garuda.png ในโฟลเดอร์หลักโดยตรง -->
        <img src="garuda.png" alt="ตราครุฑ" class="garuda-img">
        <span class="doc-title">บันทึกข้อความ</span>
    </div>
    
    <table class="meta-table">
        <tr>
            <td colspan="2"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ โทร. 0-7528-1288</td>
        </tr>
        <tr>
            <td style="width: 58%;"><strong>ที่:</strong> <?php echo htmlspecialchars($trip['doc_number'] ?? ''); ?></td>
            <td style="width: 42%;"><strong>วันที่:</strong> <?php echo thai_date($trip['created_date'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</td>
        </tr>
    </table>
    
    <div class="divider-line"></div>

    <div class="to-line">เรียน &nbsp; ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>

    <!-- เนื้อความร้อยแก้ว -->
    <div class="prose-body">
        <?php echo $ref_text; ?>ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?><?php echo $academic_text; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department'] ?? ''); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($trip['subject'] ?? ''); ?> ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?><?php echo $participant_summary_text; ?>มีกำหนดการตั้งแต่วันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?> ถึงวันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?> ในการนี้จะเดินทางโดย<?php echo htmlspecialchars($vehicle_text) . $license_text; ?> <?php echo $expense_text; ?>
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

    <!-- ลำดับที่ 2: ความเห็นและลายเซ็นของหัวหน้าฝ่าย -->
    <div class="head-opinion-box">
        <strong>ความเห็นของ<?php echo htmlspecialchars($head_title); ?>:</strong><br>
        ..................................................................................................................................................................................<br>
        <div class="head-sign-wrap">
            ลงชื่อ......................................................................<br>
            ( <?php echo htmlspecialchars($head_name_display); ?> )<br>
            <?php echo htmlspecialchars($head_title); ?><br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
    </div>

    <!-- ลำดับที่ 3: คำสั่งและการอนุมัติของผู้อำนวยการ -->
    <div class="director-frame">
        <strong>คำสั่ง / การพิจารณาของผู้อำนวยการสถานศึกษา:</strong><br>
        [ &nbsp; ] อนุมัติ &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก ..............................................................<br>
        <div style="text-align: center; margin-top: 4px;">
            ลงชื่อ......................................................................<br>
            ( ...................................................................... )<br>
            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์<br>
            วันที่ ........ เดือน ........................ พ.ศ. ............
        </div>
    </div>
</div>

<!-- หน้าที่ 2: บัญชีรายชื่อผู้ร่วมเดินทางแนบท้าย (แสดงเฉพาะกรณีมีผู้ร่วมเดินทาง) -->
<?php if (!empty($participants)): ?>
<div class="sheet">
    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="font-weight: bold; margin-bottom: 5px;">บัญชีรายชื่อผู้ขออนุมัติเดินทางไปราชการแนบท้าย</h3>
        <div>แนบท้ายบันทึกข้อความ ที่ <?php echo htmlspecialchars($trip['doc_number'] ?? '-'); ?> ลงวันที่ <?php echo thai_date($trip['created_date'] ?? ''); ?></div>
    </div>

    <table class="attachment-table">
        <thead>
            <tr>
                <th style="width: 8%;">ลำดับ</th>
                <th style="width: 40%;">ชื่อ - สกุล</th>
                <th style="width: 32%;">ตำแหน่ง / ระดับชั้น</th>
                <th style="width: 20%;">หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <!-- รายชื่อผู้ขอหลัก (ลำดับที่ 1) -->
            <tr>
                <td style="text-align: center;">1</td>
                <td><?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($trip['position'] ?? ''); ?></td>
                <td style="text-align: center;">ผู้ขออนุมัติ</td>
            </tr>
            <!-- รายชื่อผู้ร่วมเดินทาง -->
            <?php 
            $i = 2;
            foreach ($participants as $p): 
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['detail'] ?? ($p['position'] ?? '-')); ?></td>
                <td style="text-align: center;"><?php echo ($p['type'] ?? '') === 'student' ? 'นักเรียน' : 'ผู้ร่วมเดินทาง'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; margin-left: auto; width: 50%; text-align: center;">
        รับรองข้อมูลถูกต้อง<br><br>
        ลงชื่อ......................................................................<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>
