<?php
require_once 'config.php'; 
// เรียกใช้ไฟล์ config.php เพื่อดึงค่าการเชื่อมต่อฐานข้อมูล (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)

// ตรวจสอบว่าค่าการเชื่อมต่อถูกกำหนดหรือไม่
// หากไม่ครบ จะหยุดการทำงานและแจ้งเตือน
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
    die("Database configuration is incomplete.");
}

try {
    // สร้างออบเจกต์ PDO เพื่อติดต่อกับฐานข้อมูล MySQL
    // รูปแบบการเชื่อมต่อ: "mysql:host=...;dbname=...;charset=utf8mb4"
    // ใช้ charset=utf8mb4 เพื่อรองรับอักขระสากล
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD);

    // ตั้งค่าให้ PDO โยนข้อผิดพลาดแบบ Exception
    // สิ่งนี้ช่วยให้เราจับข้อผิดพลาดได้ผ่าน try...catch
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // บันทึกข้อความข้อผิดพลาดลงใน error_log ของเซิร์ฟเวอร์
    error_log("Connection failed: " . $e->getMessage(), 0);

    // แสดงข้อความทั่วไปให้ผู้ใช้เพื่อไม่เผยรายละเอียดภายใน
    die("Database connection failed. Please contact administrator.");
}

// หากไม่มีข้อผิดพลาด ตัวแปร $db จะพร้อมใช้งานสำหรับการเรียกใช้งานคำสั่ง SQL ในไฟล์อื่น ๆ
