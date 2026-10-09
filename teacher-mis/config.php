<?php
$host = "localhost";
$user = "root";       // ปรับตามค่าโฮสต์ของคุณ
$pass = "";           // ปรับตามรหัสผ่านของคุณ
$dbname = "school_mis"; // ปรับเป็นชื่อฐานข้อมูลของคุณ

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
