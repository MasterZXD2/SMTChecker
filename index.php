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

    // Android
    if (strpos($userAgent, "android") !== false) {
        $intent = "intent://smtchecker.onrender.com/index.php?token=" . $_SESSION['access_token'] . "#Intent;scheme=https;package=com.android.chrome;end";
        echo "<html><head><meta charset='utf-8'></head><body>";
        echo "<p>กำลังพยายามเปิดใน Chrome...</p>";
        echo "<script>
                setTimeout(function(){
                    window.location.href = '$intent';
                }, 500);
              </script>";
        echo "<p>ถ้าไม่เด้ง <a href='$link' target='_blank'>คลิกที่นี่เพื่อเปิด</a></p>";
        echo "</body></html>";
        exit();
    } else {
        // iOS → ต้องให้กดเอง
        echo "<html><head><meta charset='utf-8'></head><body>";
echo "<h3>กำลังพยายามเปิดใน Safari...</h3>";
echo "<p><a href='$link' target='_blank' rel='noopener noreferrer' style='
        display:inline-block;
        padding:15px 25px;
        background:#007aff;
        color:#fff;
        border-radius:8px;
        text-decoration:none;
        font-size:18px;
    '>ถ้าไม่เด้ง กดที่นี่เพื่อเปิดใน Safari</a></p>";
echo "<p>หรือกดจุดสามจุดมุมขวาล่าง → เลือก <b>เปิดในบราว์เซอร์</b></p>";
echo "</body></html>";
        exit();
        }
}

// ถ้าเข้าจาก browser ปกติพร้อม token
if ($token) {
    if (true){//isset($_SESSION['access_token']) && $token === $_SESSION['access_token']) {
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