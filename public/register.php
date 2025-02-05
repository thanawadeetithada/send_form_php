<?php

// ป้องกันไม่ให้หน้านี้ถูก cache โดย Browser
// การตั้ง Header แบบนี้ช่วยให้เมื่อผู้ใช้ logout แล้วกด Back จะไม่กลับมาหน้าเดิมพร้อม session เก่า
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
// เริ่มต้นการใช้งานเซสชัน เพื่อให้สามารถใช้งานตัวแปร $_SESSION ในการจัดเก็บค่าชั่วคราวได้

require_once('../includes/config.php');
// เรียกใช้ไฟล์ config.php เพื่อดึง RECAPTCHA_SITE_KEY

require_once('../includes/functions.php');
// เรียกไฟล์ functions.php เพื่อใช้งานฟังก์ชันอำนวยความสะดวก เช่น show_error_message(), generate_csrf_token()

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
// ถ้าล็อกอินแล้วไม่จำเป็นต้องเห็นหน้าล็อกอินอีก จึง redirect ไปหน้าหลัก (index.php)
if (is_user_logged_in()) {
    header('Location: index.php');
    exit();
}

// สร้างหรือดึง CSRF token จาก session
// เมื่อโหลดหน้าฟอร์มสมัครสมาชิก จะสร้าง token ขึ้นมาเพื่อตรวจสอบในขั้นตอน register_db.php อีกครั้ง
$csrf_token = generate_csrf_token();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <!-- เรียกใช้ reCAPTCHA JavaScript ของ Google -->
    <!-- โดยเปลี่ยน YOUR_SITE_KEY เป็น key ของคุณเอง -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" integrity="sha512-YWzhKL2whUzgiheMoBFwW8CKV4qpHQAEuvilg9FAn5VJUDwKZZxkJNuGM4XkWuk94WCrrwslk8yWNGmY1EduTA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="login-background-blue">
    <div class="flex-login-form">
        <h1 class="text-white mb-5">สมัครสมาชิก</h1>

        <?php show_error_message(); ?>
        <!-- หากมีข้อความ error ค้างอยู่ใน $_SESSION['error'] จากหน้าก่อนๆ 
             ฟังก์ชัน show_error_message() จะแสดงข้อความดังกล่าวแล้วลบออกจาก session -->

        <form class="p-5 card login-card-custom" action="register_db.php" method="post">
            <!-- ฝัง CSRF token ลงในฟอร์มด้วย input hidden -->
            <!-- เมื่อผู้ใช้กดปุ่ม "Register" ระบบจะส่ง token นี้ไปให้ register_db.php เพื่อตรวจสอบว่าเป็นการส่งข้อมูลจากฟอร์มนี้จริงๆ -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-outline mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required />
            </div>

            <div class="form-outline mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required />
            </div>

            <div class="form-outline mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required />
            </div>

            <?php
            // เช็คว่าเปิดใช้ reCAPTCHA หรือไม่
            if (ENABLE_RECAPTCHA) {
                // เรียกไฟล์ add-on
                require_once('../includes/addons/recaptcha/addon_recaptcha.php');
                renderRecaptchaWidget();
            }
            ?>

            <div class="row">
                <p class="text-center">Is a member? <a href="login.php">Login</a></p>
            </div>

            <button type="submit" class="btn login-btn-blue btn-block text-white" name="submit">Register</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>

</html>