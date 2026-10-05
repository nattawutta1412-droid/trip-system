<?php
$host = "gateway01.ap-northeast-1.prod.aws.tidbcloud.com"; 
$user = "2mQpYdJ16hxQqLJ.root";            
$pass = 'BMe2F64bvUYGg2WK';
$db   = "test";
$port = "4000";

// TiDB Cloud บังคับใช้การเชื่อมต่อแบบเข้ารหัส SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
