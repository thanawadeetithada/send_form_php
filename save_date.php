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
    $appointments_date = $_POST['appointments_date'];
    
    // ตรวจสอบว่า date ไม่ว่าง
    if (!empty($appointments_date)) {
        // ใช้คำสั่ง SQL INSERT เพื่อบันทึกข้อมูล
        $sql = "INSERT INTO appointments_date (appointments_date) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $appointments_date); // "s" คือ type ของตัวแปรที่เป็น string

        // ตรวจสอบว่า INSERT สำเร็จหรือไม่
        if ($stmt->execute()) {
            echo "บันทึกวันที่นัดหมายสำเร็จ!";
        } else {
            echo "ไม่สามารถบันทึกข้อมูลได้: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        echo "กรุณากรอกวันที่นัดหมาย!";
    }
} else {
    echo "ไม่สามารถทำการนี้ได้";
}

$conn->close();
?>
