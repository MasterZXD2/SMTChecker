<?php
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
$token = isset($_GET['token']) ? $_GET['token'] : null;
$isLineBrowser = (strpos($userAgent, "line") !== false);

// ฟังก์ชันสร้าง token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ถ้าเข้าจาก LINE
if ($isLineBrowser) {
    // สร้าง token ถ้ายังไม่มี
    if (!isset($_SESSION['access_token'])) {
        $_SESSION['access_token'] = generateToken();
    }
    
    $token = $_SESSION['access_token'];
    $baseUrl = "https://smtchecker.onrender.com";
    $redirectUrl = $baseUrl . "/index.php?token=" . urlencode($token);
    
    // ตรวจสอบว่าเป็น Android หรือ iOS
    $isAndroid = (strpos($userAgent, "android") !== false);
    $isIOS = (strpos($userAgent, "iphone") !== false || strpos($userAgent, "ipad") !== false || strpos($userAgent, "ipod") !== false);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>กำลังเปิดในเบราว์เซอร์ภายนอก...</title>
        <style>
            body {
                font-family: 'Noto Sans Thai', sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                background: #f5f5f5;
                text-align: center;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 400px;
            }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #00C300;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-size: 18px;
                margin: 10px 0;
                font-weight: bold;
            }
            .btn:hover {
                background: #00A000;
            }
            .info {
                margin: 20px 0;
                color: #666;
                line-height: 1.6;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #00C300;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 20px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔐 กำลังเปิดในเบราว์เซอร์ภายนอก...</h2>
            <div class="spinner"></div>
            <div class="info">
                <p>เพื่อให้สามารถใช้งาน GPS ได้อย่างถูกต้อง</p>
                <p>กรุณาเปิดเว็บไซต์ในเบราว์เซอร์ภายนอก (Chrome/Safari)</p>
            </div>
            
            <?php if ($isAndroid): ?>
                <!-- Android: ใช้ Intent และ fallback -->
                <script>
                    // เก็บ token ใน localStorage ก่อน redirect
                    localStorage.setItem('smtc_token', '<?php echo $token; ?>');
                    
                    // พยายามเปิดด้วย Intent
                    var intentUrl = "intent://smtchecker.onrender.com/index.php?token=<?php echo urlencode($token); ?>#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=<?php echo urlencode($redirectUrl); ?>;end";
                    
                    // ลองเปิดด้วย Intent
                    setTimeout(function() {
                        window.location.href = intentUrl;
                    }, 300);
                    
                    // ถ้า Intent ไม่ทำงาน ให้แสดงปุ่ม fallback
                    setTimeout(function() {
                        document.getElementById('fallback').style.display = 'block';
                    }, 2000);
                </script>
                <div id="fallback" style="display: none;">
                    <p class="info">ถ้าไม่เปิดอัตโนมัติ กรุณากดปุ่มด้านล่าง:</p>
                    <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank">เปิดใน Chrome</a>
                    <p class="info" style="font-size: 14px; margin-top: 15px;">
                        หรือ:<br>
                        กดจุดสามจุด (⋮) มุมขวาบน → เลือก "เปิดในเบราว์เซอร์"
                    </p>
                </div>
                
            <?php elseif ($isIOS): ?>
                <!-- iOS: ใช้ window.open และ fallback -->
                <script>
                    // เก็บ token ใน localStorage
                    localStorage.setItem('smtc_token', '<?php echo $token; ?>');
                    
                    // พยายามเปิดใน Safari
                    var opened = window.open('<?php echo $redirectUrl; ?>', '_blank');
                    
                    if (!opened || opened.closed || typeof opened.closed == 'undefined') {
                        // ถ้า popup ถูกบล็อก ให้แสดงปุ่ม
                        document.getElementById('fallback').style.display = 'block';
                    } else {
                        // ถ้าเปิดสำเร็จ ให้ปิดหน้าปัจจุบันหลังจาก 1 วินาที
                        setTimeout(function() {
                            document.body.innerHTML = '<div class="container"><h2>✅ เปิดใน Safari แล้ว</h2><p>กรุณาใช้งานในหน้าต่าง Safari ที่เปิดขึ้นมา</p></div>';
                        }, 1000);
                    }
                </script>
                <div id="fallback" style="display: none;">
                    <p class="info">กรุณากดปุ่มด้านล่างเพื่อเปิดใน Safari:</p>
                    <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank" rel="noopener noreferrer">เปิดใน Safari</a>
                    <p class="info" style="font-size: 14px; margin-top: 15px;">
                        หรือ:<br>
                        กดไอคอน Share (□↑) → เลือก "Safari" หรือ "เปิดในเบราว์เซอร์"
                    </p>
                </div>
                
            <?php else: ?>
                <!-- Fallback สำหรับ platform อื่นๆ -->
                <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank">เปิดในเบราว์เซอร์</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ถ้าเข้าจาก browser ปกติพร้อม token
if ($token) {
    // ตรวจสอบ token จาก URL
    $_SESSION['token'] = $token;
    
    // เก็บ token ใน session เพื่อใช้ในหน้าอื่น
    if (!isset($_SESSION['access_token'])) {
        $_SESSION['access_token'] = $token;
    }
    
    // เก็บ token ใน cookie เพื่อเป็น backup (expires in 1 hour)
    setcookie('smtc_token', $token, time() + 3600, '/', '', true, true);
    
    // Redirect ไปยังหน้าถัดไป
    if (!isset($_SESSION["user"])) {
        header("Location: login.php");
    } else {
        header("Location: user.php");
    }
    exit();
}

// ถ้าไม่มี token ใน URL แต่มีใน cookie (fallback)
if (isset($_COOKIE['smtc_token']) && !isset($_SESSION['token'])) {
    $_SESSION['token'] = $_COOKIE['smtc_token'];
    if (!isset($_SESSION['access_token'])) {
        $_SESSION['access_token'] = $_COOKIE['smtc_token'];
    }
    // Redirect ไปยังหน้าถัดไป
    if (!isset($_SESSION["user"])) {
        header("Location: login.php");
    } else {
        header("Location: user.php");
    }
    exit();
}

// ถ้าไม่มี token และไม่ใช่ LINE browser
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title></head><body>";
echo "<h2>❌ กรุณาเปิดจาก LINE ก่อน</h2>";
echo "<p>เว็บไซต์นี้ต้องเปิดผ่านลิงก์จาก LINE เพื่อความปลอดภัย</p>";
echo "</body></html>";
exit();
?>