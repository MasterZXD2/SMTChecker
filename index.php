<?php
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
$token = isset($_GET['token']) ? $_GET['token'] : null;

// ฟังก์ชันสร้าง token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ถ้าเข้าจาก LINE
if (strpos($userAgent, "line") !== false) {
    if (!isset($_SESSION['access_token'])) {
        $_SESSION['access_token'] = generateToken();
    }

    $link = "https://smtchecker.onrender.com/index.php?token=" . $_SESSION['access_token'];

    echo "<html><head><meta charset='utf-8'><title>เลือกเปิดเว็บ</title></head><body>";
    echo "<h3>กรุณาเลือกเปิดเว็บจากเบราว์เซอร์</h3>";
    echo "<p><a href='$link'>🌐 เปิดในเบราว์เซอร์ปกติ</a></p>";

    // Android Chrome (Intent)
    echo "<p><a href='intent://smtchecker.onrender.com/index.php?token=" . $_SESSION['access_token'] . "#Intent;scheme=https;package=com.android.chrome;end'>📱 เปิดด้วย Chrome (Android)</a></p>";

    // iOS Safari (ใช้ URL ธรรมดา)
    echo "<p><a href='$link'>🍏 เปิดด้วย Safari (iOS)</a></p>";

    echo "</body></html>";
    exit();
}

// ถ้าเข้าจาก browser ปกติพร้อม token
if ($token) {
    if (isset($_SESSION['access_token']) && $token === $_SESSION['access_token']) {
        if (!isset($_SESSION["user"])) {
            header("Location: login.php");
        } else {
            header("Location: user.php");
        }
        exit();
    } else {
        echo "❌ Token ไม่ถูกต้อง หรือหมดอายุ";
        exit();
    }
}

echo "กรุณาเปิดจาก LINE ก่อน";
exit();
?>