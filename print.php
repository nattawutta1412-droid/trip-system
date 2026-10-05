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

// แปลงคำศัพท์พาหนะ
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

// แปลงคำศัพท์งบประมาณ
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

// หัวหน้ากลุ่มงาน
$group_title = !empty($trip['work_group']) ? "หัวหน้า" . $trip['work_group'] : "หัวหน้ากลุ่มงาน";
$head_group_display = !empty($trip['head_group_name']) ? $trip['head_group_name'] : '.......................................................';

// หัวหน้าฝ่าย
$dept_title = !empty($trip['head_department']) ? "หัวหน้า" . $trip['head_department'] : "หัวหน้าฝ่าย";
$head_dept_display = !empty($trip['head_name']) ? $trip['head_name'] : '.......................................................';

$director_name = "ว่าที่ร้อยโทจักรเพชร์ พรมยศ";
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
            font-size: 15pt;
            line-height: 1.12;
            background-color: #525659;
            margin: 0;
            padding: 15px 0;
            color: #000;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 18mm 10mm 22mm;
            margin: 0 auto 15px auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
            position: relative;
            page-break-after: always;
        }

        .header-box {
            position: relative;
            height: 52px;
            margin-bottom: 2px;
            text-align: center;
        }

        .garuda-img {
            position: absolute;
            left: 0;
            top: -2px;
            height: 52px;
            width: auto;
        }

        .doc-title {
            font-size: 28pt;
            font-weight: bold;
            line-height: 48px;
            letter-spacing: 0.5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15pt;
            line-height: 1.15;
        }

        .meta-table td {
            vertical-align: bottom;
            padding: 1px 0;
        }

        .divider-line {
            border: 0;
            border-top: 1.5px solid #000;
            margin: 2px 0 5px 0;
        }

        .to-line {
            font-size: 15pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .prose-body {
            text-align: justify;
            text-justify: inter-cluster;
            text-indent: 2.2cm;
            font-size: 15pt;
            line-height: 1.18;
            margin-top: 2px;
        }

        .applicant-sign-wrap {
            margin-top: 5px;
            margin-left: auto;
            width: 48%;
            text-align: center;
            font-size: 15pt;
            line-height: 1.15;
        }

        /* ตารางแบ่ง 2 ช่องสำหรับ หัวหน้ากลุ่มงาน และ หัวหน้าฝ่าย */
        .middle-sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 14.5pt;
            line-height: 1.15;
        }
        .middle-sign-table td {
            width: 50%;
            vertical-align: top;
            padding: 3px 6px;
            border-top: 1px dashed #777;
        }

        .director-frame {
            margin-top: 5px;
            border: 1px solid #000;
            padding: 5px 12px;
            font-size: 14.5pt;
            line-height: 1.15;
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
            .sheet { box-shadow: none; margin: 0; width: 210mm; min-height: 297mm; padding: 10mm 18mm 10mm 22mm; page-break-after: always; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center no-print" style="margin-bottom: 15px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 24px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px; font-weight: bold;">🖨️️ สั่งพิมพ์เอกสาร (Print)</button>
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
            <td style="width: 58%;"><strong>ที่:</strong> <?php echo htmlspecialchars($trip['doc_number'] ?? ''); ?></td>
            <td style="width: 42%;"><strong>วันที่:</strong> <?php echo thai_date($trip['created_date'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>เรื่อง:</strong> ขออนุมัติเดินทางไปราชการ</td>
        </tr>
    </table>
    
    <div class="divider-line"></div>

    <div class="to-line">เรียน &nbsp; ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>

    <div class="prose-body">
        <?php echo $ref_text; ?>ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?><?php echo $academic_text; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department'] ?? ''); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($trip['subject'] ?? ''); ?> ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?> พร้อมคณะ มีกำหนดการตั้งแต่วันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?> ถึงวันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?> ในการนี้จะเดินทางโดย<?php echo htmlspecialchars($vehicle_text) . $license_text; ?> <?php echo $expense_text; ?> (รายละเอียดดังบัญชีรายชื่อและกำหนดการแนบท้าย)
    </div>

    <div class="prose-body">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ลำดับที่ 1: ลายเซ็นผู้ขออนุมัติ (เยื้องขวา) -->
    <div class="applicant-sign-wrap">
        ลงชื่อ......................................................................<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>

    <!-- ลำดับที่ 2 และ 3: ความเห็นหัวหน้ากลุ่มงาน (ซ้าย) และ หัวหน้าฝ่าย (ขวา) -->
    <table class="middle-sign-table">
        <tr>
            <td>
                <strong>ความเห็นของ<?php echo htmlspecialchars($group_title); ?>:</strong><br>
                ...................................................................................<br>
                <div style="text-align: center; margin-top: 3px;">
                    ลงชื่อ............................................................<br>
                    ( <?php echo htmlspecialchars($head_group_display); ?> )<br>
                    <?php echo htmlspecialchars($group_title); ?><br>
                    วันที่ ........ เดือน .................... พ.ศ. ........
                </div>
            </td>
            <td>
                <strong>ความเห็นของ<?php echo htmlspecialchars($dept_title); ?>:</strong><br>
                ...................................................................................<br>
                <div style="text-align: center; margin-top: 3px;">
                    ลงชื่อ............................................................<br>
                    ( <?php echo htmlspecialchars($head_dept_display); ?> )<br>
                    <?php echo htmlspecialchars($dept_title); ?><br>
                    วันที่ ........ เดือน .................... พ.ศ. ........
                </div>
            </td>
        </tr>
    </table>

    <!-- ลำดับที่ 4: ผู้อำนวยการโรงเรียน (ล่างสุด) -->
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
</div>

<!-- ================= หน้าที่ 2: บัญชีรายชื่อผู้ร่วมเดินทางแนบท้าย ================= -->
<div class="sheet">
    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="font-weight: bold; margin-bottom: 5px;">บัญชีรายชื่อผู้ขออนุมัติเดินทางไปราชการแนบท้าย</h3>
        <div>แนบท้ายบันทึกข้อความ ที่ <?php echo htmlspecialchars($trip['doc_number'] ?? '-'); ?> ลงวันที่ <?php echo thai_date($trip['created_date'] ?? ''); ?></div>
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
        </tbody>
    </table>

    <div style="margin-top: 40px; margin-left: auto; width: 50%; text-align: center;">
        รับรองข้อมูลถูกต้อง<br><br><br>
        ลงชื่อ......................................................................<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>
</div>

<!-- ================= หน้าที่ 3: กำหนดการเดินทางไปราชการแนบท้าย ================= -->
<div class="sheet">
    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="font-weight: bold; margin-bottom: 5px;">กำหนดการเดินทางไปราชการ</h3>
        <div>แนบท้ายบันทึกข้อความ ที่ <?php echo htmlspecialchars($trip['doc_number'] ?? '-'); ?> ลงวันที่ <?php echo thai_date($trip['created_date'] ?? ''); ?></div>
        <div style="margin-top: 5px;">เรื่อง: <?php echo htmlspecialchars($trip['subject'] ?? ''); ?></div>
        <div>ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?></div>
    </div>

    <div style="font-size: 16pt; line-height: 1.6; margin-top: 25px;">
        <p><strong>กำหนดการเดินทางระหว่างวันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?> ถึงวันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?></strong></p>
        
        <p style="text-indent: 1.5cm;">
            <strong>วันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?></strong><br>
            - เดินทางออกจากโรงเรียนย่านตาขาวรัฐชนูปถัมภ์ ไปยัง <?php echo htmlspecialchars($trip['destination'] ?? ''); ?><br>
            - ปฏิบัติภารกิจราชการตามที่ได้รับมอบหมาย
        </p>

        <p style="text-indent: 1.5cm;">
            <strong>วันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?></strong><br>
            - เสร็จสิ้นการปฏิบัติภารกิจราชการ<br>
            - เดินทางกลับถึงโรงเรียนย่านตาขาวรัฐชนูปถัมภ์ โดยสวัสดิภาพ
        </p>

        <p style="color: #666; font-size: 14pt; margin-top: 20px;">
            <em>* หมายเหตุ: กำหนดการอาจมีการเปลี่ยนแปลงตามความเหมาะสมของภารกิจงานราชการ</em>
        </p>
    </div>

    <div style="margin-top: 50px; margin-left: auto; width: 50%; text-align: center;">
        ลงชื่อ......................................................................ผู้รายงาน<br>
        ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
        ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
    </div>
</div>

</body>
</html>
