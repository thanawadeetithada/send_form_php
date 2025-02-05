<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $service_id = $_POST['service'] ?? '';  // เปลี่ยนจาก $service เป็น $service_id
    $appointment_time = $_POST['appointment_time'] ?? '';
    $appointment_date = isset($_POST['appointment_date']) ? DateTime::createFromFormat('d/m/Y', $_POST['appointment_date']) : false;

    if ($appointment_date) {
        $appointment_date = $appointment_date->format('Y-m-d');
    } else {
        echo json_encode(["error" => "❌ รูปแบบวันที่ไม่ถูกต้อง"]);
        exit;
    }

    // ใช้ $service_id แทน $service ใน SQL
    $sql = "INSERT INTO appointments (firstname, lastname, phone, service_id, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // เปลี่ยนจาก $service เป็น $service_id ในการ bind parameter
        $stmt->bind_param("sssiss", $firstname, $lastname, $phone, $service_id, $appointment_date, $appointment_time);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["error" => "❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL"]);
    }

    $conn->close();
} else {
    echo json_encode(["error" => "❌ ไม่มีข้อมูลจากฟอร์ม"]);
}
?>
