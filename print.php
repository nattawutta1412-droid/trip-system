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

$head_title = !empty($trip['head_department']) ? "หัวหน้า" . $trip['head_department'] : "หัวหน้าฝ่าย";
$head_name_display = !empty($trip['head_name']) ? $trip['head_name'] : '.......................................................';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุมัติไปราชการ</title>
    <!-- ฝังชุดฟอนต์ TH Sarabun New มาตรฐาน -->
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
            padding: 15mm 20mm 15mm 25mm; /* ตั้งขอบตามระเบียบสารบรรณ เป๊ะใน 1 หน้า A4 */
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .header-box {
            position: relative;
            height: 60px;
            margin-bottom: 5px;
            text-align: center;
        }

        .garuda-img {
            position: absolute;
            left: 0;
            top: -5px;
            width: 55px;
            height: auto;
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
            line-height: 1.2;
        }

        .meta-table td {
            vertical-align: bottom;
            padding: 1px 0;
        }

        .divider-line {
            border: 0;
            border-top: 1.5px solid #000;
            margin: 3px 0 8px 0;
        }

        .to-line {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .prose-body {
            text-align: justify;
            text-justify: inter-cluster;
            text-indent: 2.5cm;
            font-size: 16pt;
            line-height: 1.25;
            margin-top: 4px;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 16pt;
            line-height: 1.2;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }

        .director-frame {
            margin-top: 14px;
            margin-left: auto;
            width: 58%;
            border: 1px solid #000;
            padding: 8px 14px;
            font-size: 16pt;
            line-height: 1.25;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .sheet {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 297mm;
                padding: 15mm 20mm 15mm 25mm;
            }
            .no-print {
                display: none !important;
            }
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

<div class="sheet">
    <!-- ตราครุฑและหัวเรื่องบันทึกข้อความ (ใช้ตราครุฑ SVG Vector คมชัด 100% ไม่มีปัญหาไฟล์หาย) -->
    <div class="header-box">
        <svg class="garuda-img" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 0 C45 10 38 12 30 14 C33 22 36 28 39 33 C33 34 26 36 15 34 C18 42 22 47 28 50 C21 53 14 55 5 54 C11 63 19 68 28 71 C23 76 17 80 8 83 C18 89 29 91 40 88 C38 92 35 96 32 100 C40 98 46 95 50 90 C54 95 60 98 68 100 C65 96 62 92 60 88 C71 91 82 89 92 83 C83 80 77 76 72 71 C81 68 89 63 95 54 C86 55 79 53 72 50 C78 47 82 42 85 34 C74 36 67 34 61 33 C64 28 67 22 70 14 C62 12 55 10 50 0 Z" fill="#000000"/>
        </svg>
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

    <!-- ย่อหน้าที่ 1: เนื้อความร้อยแก้วต่อเนื่องเป็นผืนเดียว -->
    <div class="prose-body">
        <?php echo $ref_text; ?>ด้วยข้าพเจ้า <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?><?php echo $academic_text; ?> กลุ่มสาระการเรียนรู้/กลุ่มงาน <?php echo htmlspecialchars($trip['department'] ?? ''); ?> มีความประสงค์ขออนุมัติเดินทางไปราชการเพื่อ<?php echo htmlspecialchars($trip['subject'] ?? ''); ?> ณ <?php echo htmlspecialchars($trip['destination'] ?? ''); ?> มีกำหนดการตั้งแต่วันที่ <?php echo thai_date($trip['start_date'] ?? ''); ?> ถึงวันที่ <?php echo thai_date($trip['end_date'] ?? ''); ?> ในการนี้จะเดินทางโดย<?php echo htmlspecialchars($vehicle_text) . $license_text; ?> <?php echo $expense_text; ?>
    </div>

    <!-- ย่อหน้าที่ 2: ลงท้าย -->
    <div class="prose-body">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ช่องลายเซ็นหัวหน้าฝ่าย และ ผู้ขออนุมัติ (ล็อกสัดส่วนตาราง) -->
    <table class="sign-table">
        <tr>
            <!-- ฝั่งซ้าย: ความเห็นและลายเซ็นหัวหน้าฝ่าย -->
            <td>
                ความเห็นของ<?php echo htmlspecialchars($head_title); ?><br>
                ...................................................................<br><br>
                ลงชื่อ.......................................................<br>
                ( <?php echo htmlspecialchars($head_name_display); ?> )<br>
                <?php echo htmlspecialchars($head_title); ?>
            </td>

            <!-- ฝั่งขวา: ลายเซ็นผู้ขออนุมัติ -->
            <td>
                <br><br>
                ลงชื่อ.......................................................<br>
                ( <?php echo htmlspecialchars($trip['applicant_name'] ?? ''); ?> )<br>
                ตำแหน่ง <?php echo htmlspecialchars($trip['position'] ?? ''); ?>
            </td>
        </tr>
    </table>

    <!-- ส่วนคำสั่งและการอนุมัติของผู้อำนวยการโรงเรียน -->
    <div class="director-frame">
        คำสั่ง / คำอนุมัติ:<br>
        [ &nbsp; ] อนุมัติ &emsp;&emsp;&emsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก .............................<br><br>
        <div style="text-align: center;">
            ลงชื่อ.......................................................<br>
            ( ....................................................... )<br>
            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
        </div>
    </div>
</div>

</body>
</html>
