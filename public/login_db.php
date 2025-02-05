<?php
session_start();
// เริ่มต้นการใช้งาน session เพื่อสามารถจัดเก็บข้อมูลชั่วคราว เช่น สถานะล็อกอินและข้อความ error

require_once('../includes/connection.php');
// เรียกใช้ไฟล์ connection.php เพื่อเชื่อมต่อฐานข้อมูล PDO ผ่านตัวแปร $db

require_once('../includes/functions.php');
// เรียกใช้ไฟล์ functions.php ที่มีฟังก์ชันช่วยเหลือ เช่น set_error_and_redirect(), verify_csrf_token(), remember_user()

// ก่อนเริ่มงานกับฐานข้อมูล ให้ตรวจสอบว่าตัวแปร $db พร้อมใช้งานหรือไม่
if (!isset($db) || !$db instanceof PDO) {
    // หาก $db ไม่พร้อมใช้งาน อาจแสดงข้อความแจ้งเตือนหรือหยุดการทำงาน
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

// กำหนดค่าเกี่ยวกับการล็อกอินผิดและการบล็อกการล็อกอิน
$max_login_attempts = 5; 
// $max_login_attempts = 5 หมายความว่าผู้ใช้สามารถล็อกอินผิดได้ไม่เกิน 5 ครั้ง

$lockout_time = 900; 
// $lockout_time = 900 วินาที (15 นาที) หมายความว่าหากผู้ใช้ล็อกอินผิดครบ 5 ครั้ง
// ระบบจะบล็อกไม่ให้ล็อกอินอีกเป็นเวลา 15 นาที

if (isset($_POST['submit'])) {
    // ตรวจสอบว่าผู้ใช้กดปุ่ม submit ในฟอร์ม login.php หรือไม่

    // ตรวจสอบ CSRF token ที่ได้รับจากฟอร์ม
    // $_POST['csrf_token'] ได้มาจาก input hidden ในฟอร์ม login.php
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        // หาก token ไม่ถูกต้อง แสดงว่าอาจเป็นการโจมตีหรือส่งข้อมูลโดยไม่ได้ผ่านฟอร์มจริง
        // เราจะหยุดการทำงานและส่ง error กลับไปที่ login.php
        set_error_and_redirect("การส่งข้อมูลไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)", 'login.php');
    }

    // ดึง username และ password จากฟอร์ม พร้อม sanitize เพื่อล้างอักขระพิเศษ
    // แม้เราจะใช้ PDO prepare statement อยู่แล้ว แต่การ sanitize ช่วยลดความเสี่ยงจาก XSS หรือข้อมูลแปลก ๆ
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

    // ตรวจสอบ checkbox remember ว่าถูกติ๊กหรือไม่
    $remember = isset($_POST['remember']);

    // ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
    if (empty($username) || empty($password)) {
        set_error_and_redirect("กรุณากรอกข้อมูลให้ครบถ้วน", 'login.php');
    }

    // ตรวจสอบสถานะการล็อกอินผิดพลาดซ้ำ ๆ ก่อน
    // ถ้ามี $_SESSION['login_attempts'] อยู่และค่ามากกว่าหรือเท่ากับ $max_login_attempts (5 ครั้ง)
    // แสดงว่าผู้ใช้พยายามล็อกอินผิดมากเกินไป
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_login_attempts) {
        // ตรวจสอบเพิ่มเติมว่าตอนนี้ยังอยู่ในช่วง lockout หรือไม่
        if (isset($_SESSION['lockout_time']) && (time() - $_SESSION['lockout_time']) < $lockout_time) {
            // คำนวณเวลาที่เหลือที่ผู้ใช้ต้องรอ
            $remaining = $lockout_time - (time() - $_SESSION['lockout_time']);
            // แจ้งผู้ใช้ว่าพยายามล็อกอินมากเกินไปและต้องรออีกกี่นาที
            set_error_and_redirect(
                "คุณพยายามล็อกอินเกิน $max_login_attempts ครั้ง กรุณารอ " . ceil($remaining / 60) . " นาที แล้วลองอีกครั้ง", 
                'login.php'
            );
        } else {
            // ถ้าหมดเวลาบล็อกแล้ว (time() - lockout_time >= lockout_time)
            // รีเซ็ตตัวนับการล็อกอินผิดเป็น 0 และยกเลิก lockout_time
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['lockout_time']);
        }
    }

    // เตรียมคำสั่ง SQL ตรวจสอบว่ามีผู้ใช้ชื่อนี้ในฐานข้อมูลหรือไม่
    $select_stmt = $db->prepare("SELECT username, password FROM users WHERE username = :username LIMIT 1");
    $select_stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $select_stmt->execute();
    $row = $select_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // หากไม่พบ username ในฐานข้อมูล
        // เพิ่มตัวนับจำนวนครั้งล็อกอินผิดใน session
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }
        $_SESSION['login_attempts']++;

        // ถ้าตัวนับมากกว่าหรือเท่ากับ $max_login_attempts (5 ครั้ง) ให้ตั้งเวลา lockout_time
        if ($_SESSION['login_attempts'] >= $max_login_attempts) {
            $_SESSION['lockout_time'] = time();
        }

        // แจ้งข้อผิดพลาดว่าไม่มี username นี้ในระบบ
        set_error_and_redirect("ข้อมูลที่ป้อนไม่ถูกต้อง กรุณาตรวจสอบ username และรหัสผ่านอีกครั้ง", 'login.php');
    }

    // ตรวจสอบรหัสผ่านโดยใช้ password_verify()
    // เนื่องจากในฐานข้อมูลเก็บรหัสผ่านแบบเข้ารหัส hash ไว้
    // password_verify จะเช็ครหัสผ่านที่ผู้ใช้กรอกเทียบกับ hash ใน DB
    if (!password_verify($password, $row['password'])) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }
        $_SESSION['login_attempts']++;

        // หากล็อกอินผิดมากกว่าหรือเท่ากับ $max_login_attempts
        // ให้ตั้ง lockout_time และแจ้งผู้ใช้ให้รอ 15 นาที
        if ($_SESSION['login_attempts'] >= $max_login_attempts) {
            $_SESSION['lockout_time'] = time();
            $minutes = ceil($lockout_time / 60);
            set_error_and_redirect("คุณพยายามล็อกอินเกิน $max_login_attempts ครั้ง กรุณารอ $minutes นาที แล้วลองอีกครั้ง", 'login.php');
        }

        // ถ้าจำนวนครั้งยังไม่ถึงขั้นบล็อก แค่แจ้งว่ารหัสผ่านไม่ถูกต้อง
        set_error_and_redirect("ข้อมูลที่ป้อนไม่ถูกต้อง กรุณาตรวจสอบ username และรหัสผ่านอีกครั้ง", 'login.php');
    }

    // ถ้ารหัสผ่านถูกต้อง แสดงว่าล็อกอินสำเร็จ
    // รีเซ็ตตัวนับการล็อกอินผิดกลับเป็น 0
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['lockout_time']);

    // ถ้ามาถึงจุดนี้ แสดงว่าล็อกอินสำเร็จ
    // ตั้ง session เพื่อบอกว่าอยู่ในสถานะล็อกอิน
    $_SESSION['username'] = $username;
    $_SESSION['is_logged_in'] = true;
    $_SESSION['last_activity'] = time(); // สำหรับตรวจสอบ session timeout

    // ถ้าผู้ใช้เลือก "Remember Me" ให้เรียกฟังก์ชัน remember_user เพื่อสร้าง token และเก็บใน cookie กับ DB
    if ($remember) {
        remember_user($db, $username);
    }

    // หลังจากตั้งค่าทุกอย่างเสร็จ ให้ส่งผู้ใช้ไปหน้า index.php (หน้าหลักของระบบ)
    header('Location: index.php');
    exit();
}
