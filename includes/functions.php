<?php
// ไฟล์นี้รวบรวมฟังก์ชันต่าง ๆ ที่ใช้ประจำในระบบ เช่น ฟังก์ชันตรวจสอบสถานะการล็อกอิน, จัดการ Session Timeout, ระบบจำผู้ใช้ (Remember Me), 
// ฟังก์ชันแสดงข้อความแจ้งเตือน (Flash message), ฟังก์ชันจัดการข้อผิดพลาดในการกรอกข้อมูล และการป้องกัน CSRF

// ------------------------------------
// ฟังก์ชันเกี่ยวกับการล็อกอินและการตรวจสอบสถานะผู้ใช้
// ------------------------------------

// is_user_logged_in()
// จุดประสงค์: ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
// วิธีทำงาน: เช็คค่าใน $_SESSION['is_logged_in'] ว่ามีค่า true หรือไม่
// ผลลัพธ์: return true ถ้าผู้ใช้ล็อกอินอยู่, return false ถ้าผู้ใช้ยังไม่ล็อกอิน
// Flow การใช้งาน:
// 1. หน้าใดก็ตามที่ต้องการตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ สามารถเรียก is_user_logged_in()
// 2. ถ้า return true แสดงว่าผู้ใช้ล็อกอินแล้ว สามารถแสดงข้อมูลส่วนตัวหรือโปรไฟล์ได้
// 3. ถ้า false แสดงว่าผู้ใช้ยังไม่ล็อกอิน ให้ส่งไปหน้า login.php
function is_user_logged_in() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// require_login()
// จุดประสงค์: บังคับให้หน้านี้อนุญาตให้เข้าถึงได้เฉพาะผู้ใช้ที่ล็อกอินแล้วเท่านั้น
// วิธีทำงาน: เรียก is_user_logged_in() ถ้าเป็น false จะ redirect ผู้ใช้ไปที่หน้า login.php
// ประโยชน์: ใช้ในหน้าที่เป็นหน้าส่วนตัว เช่น หน้า profile หรือ หน้า Dashboard
// Flow การใช้งาน:
// 1. ใช้ในหน้าที่ต้องการให้เข้าถึงได้เฉพาะผู้ใช้ที่ล็อกอินแล้ว เช่น profile.php, index.php
// 2. เมื่อโหลดหน้า profile.php ให้เรียก require_login() ทันทีที่ต้นไฟล์
// 3. ถ้าผู้ใช้ยังไม่ล็อกอิน (is_user_logged_in() = false) จะถูก redirect ไปหน้า login.php
// 4. ถ้าล็อกอินแล้ว หน้าโปรไฟล์จะแสดงได้ตามปกติ
function require_login() {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

// ------------------------------------
// ฟังก์ชันจัดการ Session Timeout
// ------------------------------------

// check_session_timeout($timeout_duration = 1800)
// จุดประสงค์: ตรวจสอบว่าเซสชันหมดอายุหรือไม่ ตามเวลาที่กำหนด (ค่าเริ่มต้น 1800 วินาที หรือ 30 นาที)
// วิธีทำงาน: 
// 1. เช็คว่า $_SESSION['last_activity'] มีค่าหรือไม่ และนำเวลาปัจจุบันเทียบกับเวลาล่าสุดที่ผู้ใช้มีปฏิสัมพันธ์กับเว็บ
// 2. ถ้าเวลาปัจจุบัน - last_activity > timeout_duration แสดงว่าเซสชันหมดอายุ ให้เรียก logout_user(true)
// 3. หากไม่หมดอายุ ให้ปรับค่า last_activity = time() เพื่อเริ่มนับเวลาใหม่
// ประโยชน์: ป้องกันไม่ให้ผู้ใช้ที่ทิ้งหน้าเว็บไว้นานเกินไปยังคงล็อกอินอยู่เพื่อความปลอดภัย
// check_session_timeout($timeout_duration = 1800)
// Flow การใช้งาน:
// 1. เรียก check_session_timeout() ในทุกหน้าที่จำเป็น เช่น index.php, profile.php หลัง session_start()
// 2. ฟังก์ชันจะตรวจสอบว่าเวลาปัจจุบันห่างจาก $_SESSION['last_activity'] เกิน timeout_duration (ค่าเริ่มต้น 30 นาที) หรือไม่
// 3. ถ้าเกิน จะเรียก logout_user(true) บังคับออกจากระบบอัตโนมัติ
// 4. ถ้ายังไม่เกิน จะอัปเดต $_SESSION['last_activity'] = time() เพื่อยืดอายุ session ออกไป
// สถานการณ์: ผู้ใช้ล็อกอินค้างไว้โดยไม่ทำอะไร 30 นาที พอกลับมาโหลดหน้าถัดไป จะถูก logout ทันที
function check_session_timeout($timeout_duration = 1800) {
    // ฟังก์ชันนี้มีหน้าที่ตรวจสอบว่าเซสชันผู้ใช้หมดอายุแล้วหรือไม่
    // โดยค่าเริ่มต้น ($timeout_duration) ถูกตั้งไว้ที่ 1800 วินาที หรือ 30 นาที
    // หมายความว่าหากผู้ใช้ไม่มีการเคลื่อนไหว (เช่น ไม่เปลี่ยนหน้า ไม่มีการรีเฟรช) เกิน 30 นาที
    // ระบบจะถือว่าเซสชันหมดอายุและบังคับให้ออกจากระบบอัตโนมัติ

    // ตรวจสอบว่ามีการกำหนดค่าเวลาใช้งานล่าสุดของผู้ใช้หรือไม่
    // $_SESSION['last_activity'] คือตัวแปร session ที่เก็บเวลา timestamp ล่าสุดที่ผู้ใช้มีการเคลื่อนไหว
    // ถ้าเซสชันนี้ถูกสร้างไว้ก่อนหน้านี้ จะมีการอัปเดตค่า last_activity ทุกครั้งที่ผู้ใช้ใช้งานหน้าเว็บ
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // เงื่อนไขนี้หมายถึง:
        // 1. มีค่า $_SESSION['last_activity'] ถูกตั้งไว้ และ
        // 2. เวลาปัจจุบัน (time()) ลบด้วยเวลาล่าสุดที่ใช้งาน (last_activity)
        //    มีค่ามากกว่า $timeout_duration (เช่น 1800 วินาที)
        
        // แปลว่าผู้ใช้ไม่ได้ทำกิจกรรมบนเว็บเกินเวลาที่กำหนด (เช่น เกิน 30 นาที)
        // ดังนั้นให้เรียกฟังก์ชัน logout_user(true) เพื่อออกจากระบบอัตโนมัติ
        // โดยส่งพารามิเตอร์ true เพื่อบอกว่าเป็นการออกจากระบบเนื่องจาก timeout
        logout_user(true);
    } else {
        // กรณีที่ยังไม่หมดอายุเซสชัน
        // อัปเดตเวลา last_activity ให้เป็นเวลาปัจจุบัน (time())
        // เพื่อบันทึกว่าผู้ใช้มีการเคลื่อนไหว ณ ขณะนี้
        $_SESSION['last_activity'] = time();
    }
}


// ------------------------------------
// ฟังก์ชันออกจากระบบ
// ------------------------------------

// logout_user($timeout = false)
// จุดประสงค์: ออกจากระบบผู้ใช้ ลบข้อมูล session, cookie ที่ใช้จดจำการล็อกอิน
// วิธีทำงาน:
// 1. session_unset(), session_destroy() เพื่อลบ session เก่า
// 2. ถ้ามี cookie 'remember_me' อยู่ ให้ลบออก
// 3. หาก $timeout = true ให้ตั้ง flash message บอกว่าระบบ logout อัตโนมัติเนื่องจากไม่มีการใช้งาน
//    ถ้า false ให้บอกว่าผู้ใช้ logout เรียบร้อยแล้ว
// 4. redirect ไปหน้า login.php
// ประโยชน์: ใช้เมื่อต้องการให้ผู้ใช้ออกจากระบบทั้งกรณี timeout และกรณีผู้ใช้กด logout เอง
// Flow การใช้งาน:
// 1. เรียกใช้เมื่อผู้ใช้กดปุ่ม Logout หรือเมื่อ check_session_timeout ตรวจพบว่าผู้ใช้หมดเวลา
// 2. ฟังก์ชันจะล้าง session และ cookie remember_me (ถ้ามี)
// 3. ถ้า $timeout = true แสดงข้อความว่าออกจากระบบเพราะไม่มีการใช้งานนาน
//    ถ้า false แสดงข้อความว่าผู้ใช้ออกจากระบบเรียบร้อยแล้ว
// 4. จากนั้น redirect ไปหน้า login.php
function logout_user($timeout = false) {
    // ลบข้อมูลทั้งหมดที่ถูกเก็บไว้ใน $_SESSION ออก เพื่อไม่ให้คงสถานะการล็อกอินไว้
    session_unset();
    // ทำลายเซสชันปัจจุบันทิ้งไป เพื่อให้แน่ใจว่าไม่มีข้อมูลใด ๆ เหลืออยู่
    session_destroy();
    
    // ตรวจสอบว่ามีคุกกี้ 'remember_me' อยู่หรือไม่
    // ถ้ามี ให้ลบคุกกี้นี้ออกโดยตั้งค่าเวลาหมดอายุย้อนหลัง (time() - 3600)
    // เพราะคุกกี้นี้ใช้เพื่อจำผู้ใช้ไว้แม้จะปิดเบราว์เซอร์ หากไม่ลบออก ผู้ใช้อาจกลับมาโดยยังคงล็อกอินอยู่
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, "/");
    }

    // ตั้งค่าข้อความแจ้งเตือน (Flash message) ผ่านคุกกี้
    // หาก $timeout = true หมายถึงผู้ใช้ถูกบังคับให้ออกจากระบบอัตโนมัติเนื่องจากไม่มีการใช้งานเกินเวลาที่กำหนด
    // เราจึงตั้งข้อความแจ้งเป็นประเภท 'warning'
    if ($timeout) {
        setcookie('flash_message', 'ระบบได้ออกจากระบบโดยอัตโนมัติเนื่องจากไม่มีการใช้งาน', time() + 5, "/");
        setcookie('flash_type', 'warning', time() + 5, "/");
    } else {
        // ในกรณีที่ผู้ใช้กดปุ่ม Logout เอง ให้แสดงข้อความว่าออกจากระบบเรียบร้อยแล้ว และเป็นประเภท 'success'
        setcookie('flash_message', 'คุณได้ออกจากระบบเรียบร้อยแล้ว', time() + 5, "/");
        setcookie('flash_type', 'success', time() + 5, "/");
    }

    // เมื่อทำการออกจากระบบทุกอย่างเสร็จแล้ว ให้ส่งผู้ใช้กลับไปที่หน้า login.php
    header('Location: login.php');
    exit();
}

// ------------------------------------
// ระบบจำผู้ใช้ (Remember Me)
// ------------------------------------

// remember_user($db, $username)
// จุดประสงค์: เมื่อผู้ใช้เลือก "Remember Me" ตอนล็อกอิน ระบบจะสร้าง token เก็บในฐานข้อมูลและ cookie 
// เพื่อที่ครั้งถัดไปหากไม่มี session แต่มี cookie นี้จะล็อกอินให้อัตโนมัติ
// วิธีทำงาน:
// 1. สร้าง token สุ่มด้วย random_bytes() แล้วแปลงเป็น hex ด้วย bin2hex()
// 2. hash token ด้วย sha256 (เก็บในฐานข้อมูล) 
// 3. อัปเดต remember_token ใน users table ด้วย hashed_token
// 4. สร้าง cookie 'remember_me' เก็บ token (raw) ไว้ฝั่ง client อายุ 30 วัน
// Flow การใช้งาน:
// 1. หลังจากตรวจสอบ username/password ถูกต้องใน login_db.php และผู้ใช้เลือก "Remember Me"
// 2. เรียก remember_user($db, $username)
// 3. ฟังก์ชันจะสร้าง token, hash token และอัปเดตลงในฐานข้อมูล รวมถึงสร้างคุกกี้ remember_me ที่ฝั่งไคลเอนต์
// 4. ครั้งถัดไปที่ผู้ใช้เข้ามาโดยไม่มี session แต่มีคุกกี้ ระบบจะสามารถล็อกอินอัตโนมัติได้ (ผ่าน check_remember_me)
function remember_user($db, $username) {
    // ฟังก์ชันนี้มีหน้าที่สร้างระบบจำผู้ใช้ (Remember Me)
    // โดยเมื่อผู้ใช้ติ๊กเลือก Remember Me ขณะล็อกอิน เราจะสร้าง token แบบสุ่ม
    // จากนั้นเก็บ token ในรูปแฮชไว้ในฐานข้อมูล และเก็บ token ดิบไว้ในคุกกี้ฝั่งผู้ใช้
    // เพื่อนำมาใช้ตรวจสอบในอนาคต หากผู้ใช้กลับมาใช้งานเว็บอีกครั้งโดยไม่มี session อยู่
    // แต่ยังมีคุกกี้ remember_me ระบบจะล็อกอินให้อัตโนมัติ

    // สร้าง token แบบสุ่มความยาว 32 ไบต์ (256 บิต) และแปลงเป็นข้อความแบบ hexadecimal
    // random_bytes(32) จะให้บิตที่ปลอดภัยตามมาตรฐานคริปโต (CRNG)
    $token = bin2hex(random_bytes(32));

    // แฮช token ด้วย SHA-256 เพื่อความปลอดภัย
    // เหตุผลที่แฮช: ไม่เก็บ token ดิบในฐานข้อมูล หากฐานข้อมูลรั่ว ผู้ไม่หวังดีจะไม่รู้ token จริงจาก cookie ของผู้ใช้ได้ง่าย
    $hashed_token = hash('sha256', $token);

    // อัปเดต remember_token ในฐานข้อมูลตาราง users ให้เป็นค่า hashed_token สำหรับ user นี้
    // เมื่อผู้ใช้กลับมาพร้อมคุกกี้ remember_me เราจะนำ token ที่ได้จากคุกกี้
    // ไปแฮชแล้วเปรียบเทียบกับค่าในฐานข้อมูล เพื่อยืนยันตัวตน
    $update_stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE username = :username");
    $update_stmt->bindParam(':token', $hashed_token);
    $update_stmt->bindParam(':username', $username);
    $update_stmt->execute();

    // สร้างคุกกี้ชื่อ "remember_me" เพื่อเก็บ token แบบดิบที่ไม่ได้แฮช
    // ฝั่งผู้ใช้ในเครื่อง client จะถือ token นี้ไว้
    // ตั้งอายุคุกกี้ 30 วัน (86400 วินาที = 1 วัน x 30 = 30 วัน)
    // "/" หมายถึงคุกกี้ใช้ได้กับทุก path ในโดเมนนี้
    setcookie("remember_me", $token, time() + (86400 * 30), "/");
}


// check_remember_me($db)
// จุดประสงค์: ถ้าผู้ใช้เข้ามาที่หน้าเว็บอีกครั้งและไม่มี session แต่มี cookie remember_me 
// ให้ตรวจสอบ token ในฐานข้อมูลแล้วล็อกอินให้อัตโนมัติ
// วิธีทำงาน:
// 1. หากไม่ล็อกอินและมี cookie 'remember_me'
// 2. hash token จาก cookie แล้ว query ฐานข้อมูล
// 3. ถ้าพบ username ที่มี remember_token ตรงกัน ให้ตั้ง session['username'], session['is_logged_in'] = true 
//    และอัปเดต last_activity
// Flow การใช้งาน:
// 1. เรียกในหน้าแรกๆ เช่น index.php
// 2. ถ้า session ยังไม่ล็อกอิน แต่มีคุกกี้ remember_me
//    นำ token จากคุกกี้ไป hash แล้วหาในฐานข้อมูล
// 3. ถ้าพบ user ที่จำ token ไว้ ตีความว่าผู้ใช้เคยเลือกจำไว้ก่อนหน้านี้
//    ระบบจะตั้งค่า session['username'], session['is_logged_in'] = true ให้ล็อกอินอัตโนมัติ
// 4. ผู้ใช้เข้าใช้ระบบได้โดยไม่ต้องกรอกรหัสผ่านอีก
function check_remember_me($db) {
    // ฟังก์ชันนี้จะถูกเรียกเพื่อเช็คว่าผู้ใช้ที่เข้ามาหน้านี้ยังไม่ได้ล็อกอินผ่าน session
    // แต่มีคุกกี้ "remember_me" อยู่หรือไม่
    // ถ้ามีคุกกี้และ token ตรงกับข้อมูลในฐานข้อมูล
    // ระบบจะล็อกอินผู้ใช้อัตโนมัติ โดยไม่ต้องให้ผู้ใช้กรอก username/password อีกครั้ง

    // เงื่อนไขแรก: ตรวจสอบว่าผู้ใช้ยังไม่ได้ล็อกอิน (is_user_logged_in() == false)
    // และมีคุกกี้ 'remember_me' อยู่หรือไม่
    if (!is_user_logged_in() && isset($_COOKIE['remember_me'])) {
        // ดึง token จากคุกกี้ remember_me
        $token = $_COOKIE['remember_me'];

        // hash token ด้วย SHA-256 ให้เหมือนกับตอนที่เราบันทึกลงฐานข้อมูลใน remember_user()
        // เหตุผล: เราเก็บค่าแฮชของ token ในฐานข้อมูล ไม่ได้เก็บ token ดิบตรง ๆ เพื่อความปลอดภัย
        $hashed_token = hash('sha256', $token);

        // เตรียมคำสั่ง SQL เพื่อค้นหาผู้ใช้ที่มี remember_token ตรงกับ hashed_token นี้
        $stmt = $db->prepare("SELECT username FROM users WHERE remember_token = :token");
        $stmt->bindParam(':token', $hashed_token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าพบแถวข้อมูล (แสดงว่ามีผู้ใช้ที่ remember_token ตรงกับ hashed_token)
        if ($row) {
            // ตั้งค่า session ให้ผู้ใช้ล็อกอินทันที
            $_SESSION['username'] = $row['username'];
            $_SESSION['is_logged_in'] = true;
            // อัปเดต last_activity เพื่อเริ่มนับเวลา session timeout ใหม่
            $_SESSION['last_activity'] = time();
        }
    }
}


// ------------------------------------
// ฟังก์ชันจัดการข้อผิดพลาดและข้อความแจ้งเตือน
// ------------------------------------

// set_error_and_redirect($msg, $location)
// จุดประสงค์: ใช้เมื่อมีข้อมูลผิดพลาด (เช่น ลืมกรอก password) 
// จะเก็บข้อความไว้ใน $_SESSION['error'] แล้ว redirect ไปหน้าที่กำหนด
// Flow การใช้งาน:
// 1. เมื่อเกิดข้อผิดพลาด เช่น ตรวจสอบข้อมูลฟอร์มแล้วไม่ผ่าน (เช่นรหัสผ่านไม่ตรงกัน, username ซ้ำ)
// 2. เรียก set_error_and_redirect($msg, 'register.php') เพื่อเก็บข้อความ error ใน $_SESSION['error']
// 3. จากนั้น redirect ไปหน้าที่ต้องการ ผู้ใช้โหลดหน้านั้นจะเจอ error จาก show_error_message()
function set_error_and_redirect($msg, $location) {
    $_SESSION['error'] = $msg;
    header("Location: $location");
    exit();
}

// show_error_message()
// จุดประสงค์: แสดงข้อความ error ถ้ามีใน session แล้วลบออกเพื่อไม่ให้ค้าง
// ใช้ในหน้าที่มีฟอร์ม เช่น login.php, register.php เวลาเกิดข้อผิดพลาด
// Flow การใช้งาน:
// 1. ในหน้าเช่น register.php หรือ login.php วาง show_error_message() ไว้ด้านบนของหน้าหลัง session_start()
// 2. ถ้ามี $_SESSION['error'] อยู่ จะแสดงข้อความในรูปแบบ alert แล้ว unset($_SESSION['error'])
// 3. ทำให้ error แสดงเพียงครั้งเดียว แล้วหายไปในการโหลดหน้าใหม่
function show_error_message() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-custom">'.htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8').'</div>';
        unset($_SESSION['error']);
    }
}

// show_flash_message()
// จุดประสงค์: แสดงข้อความแจ้งเตือนแบบชั่วคราว (Flash message) ที่เก็บใน cookie 
// เช่น เมื่อผู้ใช้ออกจากระบบแล้ว redirect มาหน้า login ก็จะแสดงข้อความแจ้งเตือนเพียงครั้งเดียว
// Flow การใช้งาน:
// 1. ในหน้าเช่น register.php หรือ login.php วาง show_error_message() ไว้ด้านบนของหน้าหลัง session_start()
// 2. ถ้ามี $_SESSION['error'] อยู่ จะแสดงข้อความในรูปแบบ alert แล้ว unset($_SESSION['error'])
// 3. ทำให้ error แสดงเพียงครั้งเดียว แล้วหายไปในการโหลดหน้าใหม่
function show_flash_message() {
    if (isset($_COOKIE['flash_message']) && isset($_COOKIE['flash_type'])) {
        echo '<div class="alert alert-success alert-custom alert-' . htmlspecialchars($_COOKIE['flash_type'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($_COOKIE['flash_message'], ENT_QUOTES, 'UTF-8') . '</div>';

        setcookie('flash_message', '', time() - 3600, "/");
        setcookie('flash_type', '', time() - 3600, "/");
    }
}

// ------------------------------------
// ระบบป้องกัน CSRF
// ------------------------------------

// generate_csrf_token()
// จุดประสงค์: สร้าง CSRF token และเก็บใน $_SESSION['csrf_token']
// วิธีทำงาน: ถ้ายังไม่มี token ใน session จะสร้างใหม่
// จากนั้น return token นั้นเพื่อไปใส่ในฟอร์ม 
// Flow การใช้งาน:
// 1. เรียก generate_csrf_token() ในหน้าแสดงฟอร์ม เช่น register.php หรือ login.php เพื่อได้ token
// 2. ฝัง token ใน input hidden ภายในฟอร์ม
// 3. เมื่อผู้ใช้ submit ฟอร์ม token จะถูกส่งไปยัง register_db.php หรือ login_db.php เพื่อตรวจสอบ
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// verify_csrf_token($token)
// จุดประสงค์: ตรวจสอบว่า token จากฟอร์มตรงกับ token ใน session หรือไม่
// วิธีทำงาน: ใช้ hash_equals() เพื่อป้องกันการโจมตี timing attack
// ผลลัพธ์: return true ถ้าตรงกัน, false ถ้าไม่ตรง
// Flow การใช้งาน:
// 1. ในไฟล์ประมวลผลฟอร์ม (เช่น register_db.php, login_db.php) รับ token จาก $_POST['csrf_token']
// 2. เรียก verify_csrf_token($posted_token) เพื่อตรวจสอบกับ $_SESSION['csrf_token']
// 3. ถ้าไม่ตรง return false แสดงว่ามีความเสี่ยง CSRF ให้หยุดการทำงานและแจ้ง error
// 4. ถ้าตรง return true ให้ดำเนินการต่อได้อย่างปลอดภัย
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// reset_csrf_token()
// จุดประสงค์: หากต้องการใช้กรณีพิเศษในการรีเซ็ต token ใหม่ ก็สามารถเรียกฟังก์ชันนี้
// Flow การใช้งาน:
// 1. หากในบางกรณีต้องการรีเซ็ต token ใหม่ หรือหลังจากประมวลผลเสร็จ
// 2. เรียก reset_csrf_token() เพื่อลบ token เก่าออกจาก session
//    ทำให้ครั้งถัดไปที่ generate_csrf_token() ถูกเรียกจะสร้าง token ใหม่
function reset_csrf_token() {
    unset($_SESSION['csrf_token']);
}
