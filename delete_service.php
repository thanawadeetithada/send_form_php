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
    // รับค่า ID ของบริการที่ต้องการลบ
    $service_id = $_POST['id'];

    // ตรวจสอบว่า ID มีค่าหรือไม่
    if (!empty($service_id)) {
        // เตรียมคำสั่ง SQL สำหรับการลบข้อมูล
        $sql = "DELETE FROM appointments_service WHERE service_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $service_id);

        // ตรวจสอบการลบข้อมูล
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "ไม่มี ID ที่จะลบ";
    }
}
?>