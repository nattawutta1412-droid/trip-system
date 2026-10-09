<?php
// ดึงการเชื่อมต่อจาก TiDB Cloud (ใช้ชุดเดียวกับระบบไปราชการ)
$host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com"; // หรือ Host TiDB ของคุณ
$user = "xxxxxx.root";                                    // Username TiDB ของคุณ
$pass = "รหัสผ่านTiDBของคุณ";                                // Password ของคุณ
$dbname = "test";                                         // ฐานข้อมูลชื่อ test
$port = 4000;

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
