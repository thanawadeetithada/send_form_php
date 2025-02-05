<?php
require_once 'db.php';

session_start();
require_once('../includes/connection.php');
require_once('../includes/functions.php');
require_once('../includes/config.php');

require_login();
if (isset($_POST['id'])) {
    $appointments_date_id = $_POST['id'];
    $appointments_date_id = mysqli_real_escape_string($conn, $appointments_date_id);
    $sql = "DELETE FROM appointments_date WHERE appointments_date_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $appointments_date_id);
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'ไม่สามารถลบข้อมูลได้';
        }
        $stmt->close();
    } else {
        echo 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL';
    }
} else {
    echo 'ไม่พบข้อมูลที่ต้องการลบ';
}
$conn->close();
?>
