<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['appointment_date'])) {
    $date = DateTime::createFromFormat('d/m/Y', $_POST['appointment_date']);
    
    if ($date) {
        $date = $date->format('Y-m-d');
    } else {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['appointment_time'];
    }

    echo json_encode($booked_times);
}

$conn->close();
?>
