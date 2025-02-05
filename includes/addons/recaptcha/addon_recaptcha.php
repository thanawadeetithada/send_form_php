<?php
// addon_recaptcha.php
// ไฟล์นี้ทำหน้าที่แยกโค้ดเกี่ยวกับ Google reCAPTCHA ให้เป็นโมดูลเดียว
// เราสามารถ "require_once" ไฟล์นี้ในหน้าที่ต้องการใช้ reCAPTCHA ได้
//
// หมายเหตุ:
// - ปกติเราจะกำหนด RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY ใน config.php
//   เช่น:
//     define('RECAPTCHA_SITE_KEY', 'your_public_site_key');
//     define('RECAPTCHA_SECRET_KEY', 'your_secret_key');

/**
 * renderRecaptchaWidget()
 * -----------------------------------------------------------------------------
 * ฟังก์ชันแสดง reCAPTCHA Widget (Front-end) ในหน้า HTML
 * ควรเรียกฟังก์ชันนี้ในตำแหน่งที่ผู้ใช้จะเห็นกล่อง "I'm not a robot"
 *
 * หลักการทำงาน:
 *   1) เรียก constant RECAPTCHA_SITE_KEY (จาก config.php)
 *   2) echo สคริปต์ reCAPTCHA ของ Google (https://www.google.com/recaptcha/api.js)
 *   3) สร้าง <div class="g-recaptcha" data-sitekey="..."> เพื่อให้ JavaScript ของ Google สร้าง widget
 *   4) สามารถปรับ data-size="normal" หรือ "compact" หรือใส่ styling/container ได้ตามต้องการ
 */
function renderRecaptchaWidget()
{
    // 1) เรียกใช้ Site Key ซึ่งนิยามไว้ใน config.php
    $siteKey = RECAPTCHA_SITE_KEY;

    // 2) echo สคริปต์ reCAPTCHA ของ Google
    //    - async defer: ให้ script โหลดแบบไม่บล็อกการเรนเดอร์หน้า
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';

    // 3) เปิด container (div) สำหรับห่อ widget reCAPTCHA (กำหนดคลาสตามชอบ เช่น recaptcha-container)
    echo '<div class="recaptcha-container">';

    // 4) สร้าง <div class="g-recaptcha"> ซึ่งเป็น widget ของ reCAPTCHA
    //    - data-sitekey ใช้ Site Key จริงจาก Google reCAPTCHA Admin
    //    - htmlspecialchars() ป้องกันอักขระพิเศษใน $siteKey (กรณีผิดปกติ)
    //    - data-size="normal" => กล่องขนาดปกติ ("compact" => เล็กลง)
    echo '<div class="g-recaptcha" data-size="normal" data-sitekey="' 
         . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8') 
         . '"></div>';

    // 5) ปิด div recaptcha-container
    echo '</div>';
}

/**
 * verifyRecaptcha($recaptchaResponse)
 * -----------------------------------------------------------------------------
 * ฟังก์ชันตรวจสอบ (Validate) reCAPTCHA Token (Back-end) หลังผู้ใช้ Submit
 * @param string $recaptchaResponse => ค่าที่ได้จาก $_POST['g-recaptcha-response']
 * @return bool => true ถ้ายืนยัน reCAPTCHA สำเร็จ, false ถ้าล้มเหลว
 *
 * หลักการทำงาน:
 *   1) ตรวจว่ามีค่า g-recaptcha-response หรือไม่ (ถ้าว่าง => false)
 *   2) ติดต่อ Google API (https://www.google.com/recaptcha/api/siteverify)
 *      โดยส่ง secret + response เพื่อให้ Google ตอบกลับว่า success หรือไม่
 *   3) ถ้า success = true => ถือว่าผ่าน reCAPTCHA
 *      ถ้าไม่ => คืน false
 */
function verifyRecaptcha($recaptchaResponse)
{
    // 1) เช็คก่อนว่าผู้ใช้ติ๊ก reCAPTCHA หรือไม่
    if (empty($recaptchaResponse)) {
        // ยังไม่ได้ติ๊ก => คืน false
        return false;
    }

    // 2) เรียกใช้ Secret Key จาก config.php (ระวังอย่าสลับกับ Site Key)
    $secretKey = RECAPTCHA_SECRET_KEY;

    // 3) ประกอบ URL สำหรับเรียกตรวจสอบกับ Google
    //    ตัวอย่าง: https://www.google.com/recaptcha/api/siteverify?secret=<secret>&response=<token>
    //    ใช้ urlencode() เพื่อความปลอดภัยกรณีมีอักขระพิเศษ
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify?secret=' 
               . urlencode($secretKey) 
               . '&response=' . urlencode($recaptchaResponse);

    // 4) เรียก file_get_contents() ไปยัง $verifyUrl เพื่อตรวจ reCAPTCHA
    //    ใส่ @ เพื่อปิด Warning หากติดต่อเน็ตไม่ได้
    $response = @file_get_contents($verifyUrl);
    if ($response === false) {
        // ติดต่อ Google ล้มเหลว (network error, DNS ผิด)
        return false;
    }

    // 5) decode JSON ที่ได้
    //    ตัวอย่าง JSON: {"success": true, "challenge_ts": "...", "hostname": "..."}
    $result = json_decode($response, true);
    if (!is_array($result) || empty($result['success'])) {
        // ถ้าไม่เป็น array หรือ success ไม่เป็น true => ล้มเหลว
        return false;
    }

    // ถ้า success = true => ผ่าน
    return true;
}
