<?php

// ป้องกันไม่ให้หน้านี้ถูก cache โดย Browser
// การตั้ง Header แบบนี้ช่วยให้เมื่อผู้ใช้ logout แล้วกด Back จะไม่กลับมาหน้าเดิมพร้อม session เก่า
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
// เริ่มต้นเซสชัน เพื่อให้สามารถใช้งาน $_SESSION ในการจัดเก็บข้อมูลชั่วคราวได้

require_once('../includes/functions.php');
// เรียกใช้ไฟล์ functions.php ซึ่งมีฟังก์ชันช่วยเหลือ เช่น is_user_logged_in(), show_error_message(), CSRF functions ฯลฯ

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
// ถ้าล็อกอินแล้วไม่จำเป็นต้องเห็นหน้าล็อกอินอีก จึง redirect ไปหน้าหลัก (index.php)
if (is_user_logged_in()) {
    header('Location: index.php');
    exit();
}

// สร้างหรือดึง CSRF token จาก session
// เมื่อผู้ใช้โหลดหน้านี้ ระบบจะสร้าง token หนึ่งชุดเก็บไว้ใน session และส่งไปในฟอร์มด้วย
// เมื่อผู้ใช้ส่งฟอร์มกลับมา login_db.php จะตรวจสอบว่า token ตรงกันหรือไม่ ป้องกันการโจมตี CSRF
$csrf_token = generate_csrf_token();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" integrity="sha512-YWzhKL2whUzgiheMoBFwW8CKV4qpHQAEuvilg9FAn5VJUDwKZZxkJNuGM4XkWuk94WCrrwslk8yWNGmY1EduTA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-background-blue">
    <div class="flex-login-form">
        <h1 class="text-white mb-5">เข้าสู่ระบบ</h1>
        
        <?php show_error_message(); ?>
        <!-- หากมีข้อความ error ที่ถูกตั้งใน session จากหน้าก่อนหน้า (เช่น login_db.php ถ้าพบข้อผิดพลาด)
             ฟังก์ชัน show_error_message() จะแสดงผล และลบออกจาก session ให้เรียบร้อย -->

        <?php show_flash_message(); ?>
        <!-- แสดงข้อความชั่วคราว (Flash message) ที่เก็บใน Cookie เช่น ตอน logout แล้ว redirect กลับมา
             จะเห็นข้อความแจ้งเตือนครั้งเดียว -->

        <form class="p-5 card login-card-custom" action="login_db.php" method="post">
            <!-- ใช้ method="post" ในการส่งข้อมูลเพื่อไม่ให้ข้อมูล (เช่น รหัสผ่าน) ปรากฏใน URL -->

            <!-- ฝัง CSRF token ลงในฟอร์ม -->
            <!-- ค่านี้มาจาก $csrf_token ที่เราสร้างด้านบน -->
            <!-- เมื่อผู้ใช้กด Submit ฟอร์ม ข้อมูล csrf_token จะถูกส่งไปที่ login_db.php เพื่อตรวจสอบความถูกต้อง -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-outline mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required />
            </div>

            <div class="form-outline mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required />
            </div>

            <div class="form-check mb-3">
                <!-- Checkbox "Remember Me" -->
                <!-- หากผู้ใช้ติ๊กเลือก ระบบจะจำ username ไว้ในคุกกี้ (ผ่าน login_db.php) -->
                <label class="form-check-label" for="rememberMe">Remember Me</label>
                <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
            </div>

            <div class="row">
                <p class="text-center">No account? <a href="register.php">Register</a></p>
            </div>

            <button type="submit" name="submit" class="btn login-btn-blue btn-block text-white">Login</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
