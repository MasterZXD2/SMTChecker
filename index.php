<?php
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
$token = isset($_GET['token']) ? $_GET['token'] : null;

// ฟังก์ชันสร้าง token แบบสุ่ม
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// กรณีเข้าจาก LINE
if (strpos($userAgent, "line")) {
    if (!isset($_SESSION['access_token'])) {
        $_SESSION['access_token'] = generateToken();
    }

    // redirect ไปยังลิงก์ที่มี token อัตโนมัติ
    $link = "https://smtchecker.onrender.com/index.php?token=" . $_SESSION['access_token'];
    header("Location: $link");
    exit;
}

// กรณีเข้าจาก browser ปกติพร้อม token
if ($token) {
    if (isset($_SESSION['access_token']) && $token === $_SESSION['access_token']) {
        if (!isset($_SESSION["user"])) {
            header("Location: login.php");
            exit;
        } else {
            header("Location: user.php");
            exit;
        }
    } else {
        echo "❌ Token ไม่ถูกต้อง หรือหมดอายุ";
        exit;
    }
}

// ถ้าไม่ใช่ LINE และไม่มี token
echo "กรุณาเปิดจากลิ้งใน LINE ก่อนเพื่อสร้าง token";
exit;