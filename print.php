<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ไม่พบรหัสคำร้อง");
}

$stmt = $conn->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลคำร้อง");
}

// แปลงวันที่เป็นรูปแบบภาษาไทย
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

$start_date_th = thai_date($data['start_date']);
$end_date_th = thai_date($data['end_date']);
$created_date_th = !empty($data['created_at']) ? thai_date($data['created_at']) : thai_date(date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความขอไปราชการ - <?php echo htmlspecialchars($data['applicant_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 16pt;
            line-height: 1.6;
            color: #000;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 25mm 20mm 20mm 25mm;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .garuda-img {
            width: 1.5in;
            height: auto;
            display: block;
        }
        .title-memo {
            font-size: 29pt;
            font-weight: bold;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .line-dots {
            border-bottom: 1px dotted #000;
            padding-bottom: 2px;
        }
        .content-body {
            text-align: justify;
            text-indent: 2.5cm;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .sign-area {
            float: right;
            width: 52%;
            text-align: center;
            margin-top: 25px;
            line-height: 1.8;
        }
        .clear {
            clear: both;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            padding: 10px 24px;
            font-size: 16px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 20mm 15mm;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">พิมพ์บันทึกข้อความ</button>
    <a href="index.php" style="margin-left: 10px; text-decoration: none; color: #555;">กลับหน้ารายการ</a>
</div>

<div class="page">
    <!-- ส่วนหัวบันทึกข้อความและครุฑ -->
    <table class="header-table">
        <tr>
            <td style="width: 33%; vertical-align: top;">
                <img src="garuda.png" alt="ครุฑ" class="garuda-img" onerror="this.src='../garuda.png';">
            </td>
            <td style="width: 67%; vertical-align: bottom;">
                <div class="title-memo">บันทึกข้อความ</div>
            </td>
        </tr>
    </table>

    <div class="info-row">
        <strong>ส่วนราชการ</strong> โรงเรียนย่านตาขาวรัฐชนูปถัมภ์ อำเภอย่านตาขาว จังหวัดตรัง
    </div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <td style="width: 50%;"><strong>ที่</strong> ศธ ๐๔๒๔๑.๒๐/....................</td>
            <td style="width: 50%;"><strong>วันที่</strong> <?php echo $created_date_th; ?></td>
        </tr>
    </table>
    <div class="info-row">
        <strong>เรื่อง</strong> ขออนุญาตไปราชการ
    </div>
    <div class="info-row" style="margin-bottom: 20px;">
        <strong>เรียน</strong> ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
    </div>

    <!-- ข้อความเนื้อหา -->
    <div class="content-body">
        ด้วยข้าพเจ้า <strong><?php echo htmlspecialchars($data['applicant_name']); ?></strong> 
        ตำแหน่ง <?php echo htmlspecialchars($data['position'] ?? 'ครู'); ?> 
        สังกัด <?php echo htmlspecialchars($data['department'] ?? 'โรงเรียนย่านตาขาวรัฐชนูปถัมภ์'); ?> 
        มีความประสงค์ขออนุญาตไปราชการเพื่อ <strong><?php echo htmlspecialchars($data['topic']); ?></strong> 
        ณ <strong><?php echo htmlspecialchars($data['destination']); ?></strong> 
        ตั้งแต่วันที่ <?php echo $start_date_th; ?> ถึงวันที่ <?php echo $end_date_th; ?>
        <?php if (!empty($data['vehicle'])): ?>
            โดยใช้พาหนะ <?php echo htmlspecialchars($data['vehicle']); ?>
        <?php endif; ?>
        <?php if (!empty($data['budget'])): ?>
            งบประมาณ <?php echo htmlspecialchars($data['budget']); ?>
        <?php endif; ?>
    </div>

    <div class="content-body" style="text-indent: 2.5cm;">
        จึงเรียนมาเพื่อโปรดพิจารณาอนุญาต
    </div>

    <!-- ลายเซ็นผู้ขออนุญาต -->
    <div class="sign-area" style="margin-top: 35px;">
        <div>(ลงชื่อ)............................................................</div>
        <div>(<?php echo htmlspecialchars($data['applicant_name']); ?>)</div>
        <div>ตำแหน่ง <?php echo htmlspecialchars($data['position'] ?? 'ครู'); ?></div>
    </div>
    <div class="clear"></div>

    <!-- ความเห็น / คำสั่ง และลายเซ็นผู้อำนวยการ (จัดรูปแบบสำหรับคำนำหน้ายศ) -->
    <div style="margin-top: 40px; border-top: 1px solid #ccc; padding-top: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div><strong>ความเห็น / ข้อสั่งการ:</strong></div>
                    <div style="margin-top: 10px;">
                        [ &nbsp; ] อนุมัติ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] ไม่อนุมัติ
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="sign-area" style="float: none; width: 100%; margin-top: 0;">
                        <!-- บรรทัดลงชื่อ: คำนำหน้ายศอยู่นอกเส้นจุดข้างหน้า -->
                        <div style="white-space: nowrap;">
                            (ลงชื่อ) ว่าที่ร้อยโท..................................................
                        </div>
                        <!-- ชื่อ-นามสกุลในวงเล็บ (ไม่มีคำนำหน้าซ้ำ) -->
                        <div>
                            (จักเพชร์ พรมยศ)
                        </div>
                        <div>
                            ผู้อำนวยการโรงเรียนย่านตาขาวรัฐชนูปถัมภ์
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
