<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
// เริ่มต้น session เพื่อให้สามารถเข้าถึงข้อมูลใน $_SESSION ได้

require_once('../includes/functions.php');
// เรียกใช้ไฟล์ functions.php ซึ่งมีฟังก์ชัน logout_user() สำหรับจัดการการออกจากระบบ

// เรียกใช้งาน logout_user() เพื่อออกจากระบบ
// logout_user(false) หมายถึงออกจากระบบโดยผู้ใช้กดปุ่ม Logout เอง ไม่ใช่เกิดจาก Timeout
// ฟังก์ชันนี้จะทำหน้าที่:
// 1. ล้างข้อมูล session ออกจากระบบ
// 2. ลบ cookie remember_me ถ้ามี
// 3. ตั้ง flash message บอกผู้ใช้ว่าออกจากระบบเรียบร้อยแล้ว
// 4. redirect ผู้ใช้กลับไปหน้า login.php
logout_user(false);
