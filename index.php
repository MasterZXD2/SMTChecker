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

    if (strpos($userAgent, "android") !== false) {
        // Android → ใช้ Intent URL
        $intent = "intent://smtchecker.onrender.com/index.php?token=" . $_SESSION['access_token'] . "#Intent;scheme=https;package=com.android.chrome;end";
        header("Location: $intent");
        exit();
    } else {
        // iOS → Safari
        echo "<script>window.location.replace('$link');</script>";
        exit();
        }
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