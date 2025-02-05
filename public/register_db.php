<?php
session_start();
// เริ่มต้นการใช้งาน session เพื่อให้สามารถใช้ $_SESSION ในการเก็บข้อมูลชั่วคราว เช่น ข้อความ error หรือสถานะการล็อกอิน

require_once('../includes/config.php');
// ดึง config.php เพื่อเข้าถึง RECAPTCHA_SECRET_KEY

require_once('../includes/connection.php');
// เรียกใช้ไฟล์ connection.php เพื่อเชื่อมต่อฐานข้อมูล PDO ผ่านตัวแปร $db

require_once('../includes/functions.php');
// เรียกใช้ไฟล์ functions.php ที่มีฟังก์ชันช่วยเหลือ เช่น set_error_and_redirect(), verify_csrf_token()

// ก่อนเริ่มงานกับฐานข้อมูล ให้ตรวจสอบว่าตัวแปร $db พร้อมใช้งานหรือไม่
if (!isset($db) || !$db instanceof PDO) {
    // หาก $db ไม่พร้อมใช้งาน อาจแสดงข้อความแจ้งเตือนหรือหยุดการทำงาน
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

if (isset($_POST['submit'])) {
    // ตรวจสอบว่าผู้ใช้กดปุ่ม "Register" ใน register.php หรือไม่

    // ดึง CSRF token จากฟอร์ม (input hidden ใน register.php)
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        // หาก token ไม่ตรงกับที่เก็บใน session แสดงว่ามีความเสี่ยงจากการโจมตีแบบ CSRF
        // หยุดการทำงานและแสดง error กลับไปที่ register.php
        set_error_and_redirect("การส่งข้อมูลไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)", 'register.php');
    }

    // ถ้าเปิด reCAPTCHA
    if (ENABLE_RECAPTCHA) {
        require_once('../includes/addons/recaptcha/addon_recaptcha.php');

        // อ่านค่า g-recaptcha-response
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

        // เรียกฟังก์ชัน verify
        if (!verifyRecaptcha($recaptchaResponse)) {
            // ถ้า reCAPTCHA ไม่ผ่าน
            set_error_and_redirect("กรุณาติ๊ก reCAPTCHA ให้ถูกต้อง", "register.php");
            exit();
        }
    }

    // รับข้อมูลจากฟอร์มและกรองข้อมูลด้วย filter_input เพื่อความปลอดภัยเบื้องต้น
    // FILTER_SANITIZE_SPECIAL_CHARS จะลบอักขระพิเศษออกจากสตริงเพื่อลดความเสี่ยง XSS 
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_SPECIAL_CHARS);

    // ตรวจสอบว่ากรอกครบทุกช่องหรือไม่
    if (empty($username) || empty($password) || empty($confirm_password)) {
        set_error_and_redirect("กรุณากรอกข้อมูลให้ครบถ้วน", 'register.php');
    }

    // ตรวจสอบรูปแบบของ Username (ปรับตามความต้องการ)
    // เช่น อนุญาตให้มีอักขระ a-z, A-Z, 0-9, underscore และความยาว 3-20 ตัวอักษร
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        set_error_and_redirect("ชื่อผู้ใช้ควรเป็นตัวอักษรหรือตัวเลข 3-20 ตัว และอาจมีเครื่องหมาย _ ได้", 'register.php');
    }

    // ตรวจสอบว่ารหัสผ่านทั้งสองช่องตรงกันหรือไม่
    if ($password !== $confirm_password) {
        set_error_and_redirect("กรุณากรอกรหัสผ่านให้ตรงกัน", 'register.php');
    }

    // ตรวจสอบความยาวและความปลอดภัยของรหัสผ่าน
    // เหตุผล: รหัสผ่านสั้นเกินไปอาจถูกเดาได้ง่าย
    // การกำหนดขั้นต่ำ 8 ตัวอักษรเป็นมาตรฐานพื้นฐานของความปลอดภัย
    if (strlen($password) < 8) {
        set_error_and_redirect("รหัสผ่านควรมีความยาวอย่างน้อย 8 ตัวอักษร", 'register.php');
    }

    // ต้องขึ้นต้นด้วยตัวพิมพ์ใหญ่
    // เหตุผล: เป็นกฎเพิ่มเติมที่ช่วยเพิ่มความซับซ้อน และบังคับให้ผู้ใช้ไม่ตั้งรหัสผ่านง่ายๆ
    // เช่น "Password" แทน "password" สร้างความหลากหลายของรูปแบบมากขึ้น
    if (!preg_match('/^[A-Z]/', $password)) {
        set_error_and_redirect("รหัสผ่านตัวแรกต้องเป็นตัวอักษรพิมพ์ใหญ่", 'register.php');
    }

    // ต้องมีตัวพิมพ์ใหญ่ อย่างน้อย 1 ตัว
    // เหตุผล: การมีตัวพิมพ์ใหญ่ (A-Z) อย่างน้อย 1 ตัว ทำให้รหัสผ่านแข็งแกร่งขึ้น
    // ช่วยลดความเสี่ยงการถูกเดาง่าย เพราะต้องผสมตัวอักษรหลากหลายรูปแบบ
    if (!preg_match('/[A-Z]/', $password)) {
        set_error_and_redirect("รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่ อย่างน้อย 1 ตัว", 'register.php');
    }

    // ต้องมีตัวพิมพ์เล็ก อย่างน้อย 1 ตัว
    // เหตุผล: การผสมผสานตัวพิมพ์เล็ก (a-z) ทำให้รหัสผ่านหลากหลายมากขึ้น
    // รหัสผ่านที่มีทั้งพิมพ์ใหญ่และพิมพ์เล็กยากต่อการคาดเดามากขึ้น
    if (!preg_match('/[a-z]/', $password)) {
        set_error_and_redirect("รหัสผ่านต้องมีตัวอักษรพิมพ์เล็ก อย่างน้อย 1 ตัว", 'register.php');
    }

    // ต้องมีตัวเลข อย่างน้อย 1 ตัว
    // เหตุผล: การเพิ่มตัวเลข (0-9) เข้าไปในรหัสผ่านทำให้ความซับซ้อนเพิ่มขึ้น
    // ช่วยลดโอกาสที่รหัสผ่านจะเป็นคำศัพท์ง่าย ๆ หรือคำในพจนานุกรม
    if (!preg_match('/[0-9]/', $password)) {
        set_error_and_redirect("รหัสผ่านต้องมีตัวเลข อย่างน้อย 1 ตัว", 'register.php');
    }

    // ต้องมีอักขระพิเศษ อย่างน้อย 1 ตัว เช่น !@#$%^&*()
    // เหตุผล: อักขระพิเศษทำให้รหัสผ่านมีตัวอักษรหลากหลายมากขึ้น
    // ช่วยยากต่อการเดาและลดโอกาสที่รหัสผ่านจะเป็นคำหรือรูปแบบยอดนิยมที่แฮกเกอร์ใช้เดา
    if (!preg_match('/[\!\@\#\$\%\^\&\*\(\)\_\+\-\=\?\>\<\,\.]/', $password)) {
        set_error_and_redirect("รหัสผ่านต้องมีอักขระพิเศษ อย่างน้อย 1 ตัว เช่น !@#$%^&*()", 'register.php');
    }

    // ตรวจสอบว่ามี username นี้ในระบบแล้วหรือไม่
    // หากมีอยู่แล้วต้องแจ้งผู้ใช้ว่าชื่อนี้ถูกใช้ไปแล้ว
    $stmt = $db->prepare("SELECT 1 FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->fetch()) {
        // ถ้าพบว่ามี username นี้แล้ว
        set_error_and_redirect("มี username นี้ในระบบ", 'register.php');
    }

    // ถ้ายังไม่มีชื่อผู้ใช้นี้ในระบบ
    // เข้ารหัสรหัสผ่านด้วย password_hash() เพื่อความปลอดภัยในการเก็บรหัสผ่านในฐานข้อมูล
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // เตรียมคำสั่ง SQL สำหรับเพิ่มผู้ใช้ใหม่ลงในฐานข้อมูล
    // กำหนด role เป็น 'user' เป็นค่าเริ่มต้น สามารถปรับเปลี่ยนตามความต้องการ
    $role = 'user';
    $insert_stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
    $insert_stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $insert_stmt->bindParam(':role', $role, PDO::PARAM_STR);

    // รันคำสั่ง INSERT
    if ($insert_stmt->execute()) {
        // หาก INSERT สำเร็จ แสดงว่าการสมัครสมาชิกสำเร็จ
        // ตั้งค่า session เพื่อบอกว่าเป็นผู้ใช้ล็อกอินแล้ว (ถ้าต้องการให้ล็อกอินอัตโนมัติหลังสมัคร)
        $_SESSION['username'] = $username;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['last_activity'] = time(); // เก็บเวลาเข้าสู่ระบบล่าสุด สำหรับตรวจ session timeout

        // ส่งผู้ใช้ไปหน้า index.php หน้าหลักของระบบ
        header('Location: index.php');
        exit();
    } else {
        // ถ้า INSERT ล้มเหลว แจ้งข้อผิดพลาด
        // ใน production อาจพิจารณา log ข้อผิดพลาดลงไฟล์เพื่อการ debug
        set_error_and_redirect("เกิดข้อผิดพลาด ไม่สามารถสมัครสมาชิกได้", 'register.php');
    }
}
