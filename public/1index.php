<?php
session_start();
// เริ่มต้นการใช้งาน session เพื่อให้สามารถเข้าถึงข้อมูลใน $_SESSION ได้

require_once('../includes/connection.php');
// เรียกใช้ connection.php เพื่อเชื่อมต่อฐานข้อมูลผ่านออบเจกต์ PDO ($db)

require_once('../includes/functions.php');
// เรียกใช้ functions.php ซึ่งมีฟังก์ชันช่วยเหลือ เช่น check_session_timeout(), check_remember_me(), require_login()



// ก่อนเริ่มงานกับฐานข้อมูล ให้ตรวจสอบว่าตัวแปร $db พร้อมใช้งานหรือไม่
if (!isset($db) || !$db instanceof PDO) {
    // หาก $db ไม่พร้อมใช้งาน อาจแสดงข้อความแจ้งเตือนหรือหยุดการทำงาน
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

// ตรวจสอบ Session Timeout ว่าหมดอายุหรือไม่ (ค่า 1800 วินาที = 30 นาที)
// หากหมดอายุ จะเรียก logout_user(true) ในฟังก์ชัน check_session_timeout() ทำให้ผู้ใช้หลุดออกจากระบบ
check_session_timeout(1800);

// ตรวจสอบ remember_me cookie
// หากผู้ใช้ไม่มี session อยู่แต่มี cookie นี้ ระบบจะล็อกอินให้อัตโนมัติ
check_remember_me($db);

// บังคับให้ผู้ใช้ต้องล็อกอินก่อนเข้าถึงหน้านี้
// ถ้าผู้ใช้ยังไม่ล็อกอิน จะ redirect ไปหน้า login.php ทันที
require_login();

// กำหนดหัวเรื่อง (Title)
$pageTitle = "หน้าแรก"; 

require_once('../includes/header.php'); // โหลดส่วน head, nav และเปิด <body>
?>

    <div class="container">
        <h1 class="mt-5">Home Page</h1>
        <div class="card mt-5 text-center">
            <div class="card-body">
                <h1>ยินดีต้อนรับ</h1>
                <!-- แสดงชื่อผู้ใช้ที่ล็อกอินอยู่ผ่าน $_SESSION['username'] -->
                <!-- ใช้ htmlspecialchars เพื่อป้องกันการแสดงอักขระพิเศษและป้องกัน XSS -->
                <h3><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h3>
            </div>
        </div>
    </div>
    
<?php require_once('../includes/footer.php'); // ปิดแท็ก </body> และ </html> พร้อมโหลด script JS ?>