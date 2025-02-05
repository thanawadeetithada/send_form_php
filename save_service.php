<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $service_name = $_POST['service_name'];

    // ตรวจสอบค่าก่อนบันทึก
    if (!empty($service_name)) {
        // เตรียมคำสั่ง SQL
        $sql = "INSERT INTO appointments_service (service_name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $service_name);

        // ตรวจสอบการเพิ่มข้อมูล
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "กรุณากรอกชื่อบริการ";
    }
}
?>