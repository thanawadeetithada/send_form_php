<?php
// ไฟล์ config.php มีหน้าที่กำหนดค่าการเชื่อมต่อฐานข้อมูลพื้นฐาน
// ค่าที่กำหนดไว้ด้านล่างเป็นค่าที่ใช้บ่อยบนเครื่อง Localhost
// ควรเปลี่ยนค่าตามสภาพแวดล้อมจริง เช่นบนเซิร์ฟเวอร์ Hosting

// ชื่อโฮสต์ฐานข้อมูล (โดยทั่วไปบนเครื่อง local ใช้ "localhost")
define('DB_HOST', 'localhost');

// ชื่อผู้ใช้ฐานข้อมูล (บน local มักใช้ "root")
define('DB_USER', 'root');

// รหัสผ่านฐานข้อมูล (บน local มักว่าง ถ้าตั้งรหัสผ่านต้องระบุด้วย)
define('DB_PASSWORD', '');

// ชื่อฐานข้อมูลที่สร้างใน phpMyAdmin หรือเครื่องมืออื่น ๆ
define('DB_NAME', 'login_db');

// เปิด/ปิด reCAPTCHA
define('ENABLE_RECAPTCHA', false);

// เก็บ Secret Key ของ Google reCAPTCHA
define('RECAPTCHA_SECRET_KEY', 'YOUR SECRET KEY');

// เก็บ Site Key ของ Google reCAPTCHA
define('RECAPTCHA_SITE_KEY', 'YOUR SITE KEY');

?>