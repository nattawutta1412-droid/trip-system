<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ไม่พบรหัสคำร้อง");
}

// ตรวจสอบชื่อตารางคำร้อง (รองรับทั้ง official_trips, trip_requests หรือ requests เดิม)
$table_name = "official_trips";
$check_table = $conn->query("SHOW TABLES LIKE 'official_trips'");
if ($check_table->num_rows == 0) {
    $check_table2 = $conn->query("SHOW TABLES LIKE 'trip_requests'");
    if ($check_table2->num_rows > 0) {
        $table_name = "trip_requests";
    }
}

$stmt = $conn->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลคำร้องในระบบ");
}

function thai_date($date_str) {
    if (empty($date_str)) return "-";
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $time = strtotime($date_str);
    $d = date('j', $time);
    $m = $months[(int)date('n', $time)];
    $y = (int)date('Y', $time) + 543;
    return "$d $m $y";
}

$created_date_th = !empty($data['created_at']) ? thai_date($data['created_at']) : thai_date(date('Y-m-d'));
$start_date_th = !empty($data['start_date']) ? thai_date($data['start_date']) : '';
$end_date_th = !empty($data['end_date']) ? thai_date($data['end_date']) : '';

// ตรวจสอบชื่อฟิลด์ผู้ขอและรายละเอียดคำร้องเพื่อรองรับทั้งสองแบบ
$applicant_name = $data['applicant_name'] ?? $data['name'] ?? $data['teacher_name'] ?? '';
$position_name = $data['position'] ?? 'ครู';
$academic_standing = $data['academic_standing'] ?? '';
$department_name = $data['department'] ?? '';
$topic_name = $data['topic'] ?? $data['title'] ?? $data['purpose'] ?? '';
$destination_name = $data['destination'] ?? $data['location'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขออนุมัติเดินทางไปราชการ - <?php echo htmlspecialchars($applicant_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 15pt;
            line-height: 1.5;
            color: #000;
            background: #525659;
            margin: 0;
            padding: 20px;
        }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 15mm 25mm;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            box-sizing: border-box;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .garuda-img {
            width: 1.3in;
            height: auto;
        }
        .title-memo {
            font-size: 26pt;
            font-weight: bold;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 5px;
        }
        .content-body {
            text-align: justify;
            text-indent: 2.2cm;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .sign-block {
            text-align: center;
            float: right;
            width: 55%;
            margin-top: 15px;
        }
        .clear { clear: both; }
        .comment-section {
            margin-top: 15px;
        }
        .order-box {
            border: 1px solid #000;
            padding: 10px 15px;
            margin-top: 15px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-print {
            padding: 8px 20px;
            font-size: 15px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; min-height: auto; padding: 15mm 15mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">พิมพ์เอกสาร</button>
</div>

<div class="page">
    <table class="header-table">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <img src="garuda.png" alt="ครุฑ" class="garuda-img" onerror="this.src='../garuda.png';">
            </td>
            <td style="width: 75%; vertical-align: bottom;">
                <div class="title-memo">บันทึกข้อความ</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td colspan="2"><strong>ส่วนราชการ:</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์</td>
        </tr>
        <tr>
            <td style="width: 60%;"><strong>ที่:</strong> ............................................................................</td>
            <td style="width: 40%;"><strong>วันที่:</strong> <?php echo $created_date_th; ?></td>
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
        โดยข้าพเจ้าขอเบิกค่าใช้จ่ายตามสิทธิจากเงินงบประมาณหรือเงินนอกงบประมาณของสถานศึกษา 
        (ค่ายานพาหนะเดินทาง, ค่าเบี้ยเลี้ยง, ค่าที่พัก) ตามระเบียบกระทรวงการคลังว่าด้วยค่าใช้จ่ายในการเดินทางไปราชการ 
        อีกทั้งขออนุมัติเบิกเฉพาะค่าใช้จ่าย ได้แก่ ค่าน้ำมัน, ค่าเบี้ยเลี้ยง, ค่าที่พัก (รายละเอียดดังบัญชีรายชื่อแนบท้าย)
    </div>

    <div class="content-body" style="text-indent: 2.2cm;">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <!-- ลายเซ็นผู้ขอ -->
    <div class="sign-block">
        <div>ลงชื่อ..........................................................</div>
        <div style="margin-top: 3px;">( <?php echo htmlspecialchars($applicant_name); ?> )</div>
        <div style="margin-top: 3px;">ตำแหน่ง <?php echo htmlspecialchars($position_name); ?></div>
    </div>
    <div class="clear"></div>

    <!-- ความเห็นของหัวหน้ากลุ่มสาระฯ / กลุ่มงานบริหาร -->
    <div class="comment-section">
        <div><strong>ความเห็นของหัวหน้ากลุ่มงานบริหารวิชาการ:</strong></div>
        <div style="border-bottom: 1px dotted #000; height: 24px;"></div>
        <div class="sign-block" style="margin-top: 8px;">
            <div>ลงชื่อ..........................................................</div>
            <div style="margin-top: 3px;">( นางศุภลักษณ์ ดำชุม )</div>
            <div style="margin-top: 3px;">หัวหน้ากลุ่มงานบริหารวิชาการ</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- ความเห็นของรองผู้อำนวยการ -->
    <div class="comment-section">
        <div><strong>ความเห็นของรองผู้อำนวยการกลุ่มงานบริหารบุคคล:</strong></div>
        <div style="border-bottom: 1px dotted #000; height: 24px;"></div>
        <div class="sign-block" style="margin-top: 8px;">
            <div>ลงชื่อ..........................................................</div>
            <div style="margin-top: 3px;">( นางโรสนานีย์ บุญณะ )</div>
            <div style="margin-top: 3px;">รองผู้อำนวยการกลุ่มบริหารงานบุคคล</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- คำสั่ง / การพิจารณา ผอ. (ตัดคำว่า 'ลงชื่อ' ออก เหลือเฉพาะยศนำหน้าเส้นจุดตามระเบียบ) -->
    <div class="order-box">
        <div><strong>คำสั่ง / การพิจารณา:</strong></div>
        <table style="width: 100%; margin-top: 6px;">
            <tr>
                <td style="width: 25%; vertical-align: top;">[ &nbsp; ] อนุมัติ</td>
                <td style="width: 75%; vertical-align: top;">[ &nbsp; ] ไม่อนุมัติ เนื่องจาก ............................................................</td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 15px; margin-left: 20%;">
            <div>ว่าที่ร้อยโท..........................................................</div>
            <div style="margin-top: 4px;">(จักเพชร์ พรมยศ)</div>
            <div style="margin-top: 2px;">ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์</div>
        </div>
    </div>

</div>

</body>
</html>
