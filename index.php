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
    // สร้าง token ถ้ายังไม่มี หรือ token หมดอายุ (1 ชั่วโมง)
    $tokenExpiry = 3600; // 1 hour in seconds
    $shouldGenerateNewToken = true;
    
    if (isset($_SESSION['access_token']) && isset($_SESSION['token_created_at'])) {
        $tokenAge = time() - $_SESSION['token_created_at'];
        if ($tokenAge < $tokenExpiry) {
            // Token ยังไม่หมดอายุ
            $shouldGenerateNewToken = false;
        }
    }
    
    if ($shouldGenerateNewToken) {
        $_SESSION['access_token'] = generateToken();
        $_SESSION['token_created_at'] = time();
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
                    }, 500);
                    
                    // ถ้า Intent ไม่ทำงาน ให้แสดงปุ่ม fallback หลังจาก 2.5 วินาที
                    setTimeout(function() {
                        document.getElementById('fallback').style.display = 'block';
                        document.getElementById('autoRedirect').style.display = 'none';
                    }, 2500);
                </script>
                <div id="autoRedirect">
                    <p class="info" style="color: #00C300; font-weight: bold;">
                        ⏳ กำลังเปิดใน Chrome อัตโนมัติ...
                    </p>
                </div>
                <div id="fallback" style="display: none;">
                    <p class="info" style="color: #d32f2f; font-weight: bold; margin-bottom: 20px;">
                        ⚠️ ไม่สามารถเปิดอัตโนมัติได้
                    </p>
                    <p class="info">กรุณาทำตามขั้นตอนด้านล่าง:</p>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                        <p style="margin: 10px 0; font-weight: bold;">วิธีที่ 1: ใช้ปุ่มด้านล่าง</p>
                        <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank" style="display: block; text-align: center;">เปิดใน Chrome</a>
                        <p style="margin: 20px 0 10px 0; font-weight: bold;">วิธีที่ 2: ใช้เมนู LINE</p>
                        <ol style="margin: 0; padding-left: 20px; color: #666;">
                            <li>กดจุดสามจุด (⋮) มุมขวาบนของหน้าจอ</li>
                            <li>เลือก "<strong>เปิดในเบราว์เซอร์</strong>" หรือ "<strong>Open in Browser</strong>"</li>
                            <li>เลือก Chrome</li>
                        </ol>
                    </div>
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
                        setTimeout(function() {
                            document.getElementById('fallback').style.display = 'block';
                            document.getElementById('autoRedirect').style.display = 'none';
                        }, 1500);
                    } else {
                        // ถ้าเปิดสำเร็จ ให้แสดงข้อความ
                        setTimeout(function() {
                            document.getElementById('autoRedirect').innerHTML = '<h2 style="color: #00C300;">✅ เปิดใน Safari แล้ว</h2><p class="info">กรุณาใช้งานในหน้าต่าง Safari ที่เปิดขึ้นมา</p>';
                        }, 1000);
                    }
                </script>
                <div id="autoRedirect">
                    <p class="info" style="color: #007aff; font-weight: bold;">
                        ⏳ กำลังเปิดใน Safari อัตโนมัติ...
                    </p>
                </div>
                <div id="fallback" style="display: none;">
                    <p class="info" style="color: #d32f2f; font-weight: bold; margin-bottom: 20px;">
                        ⚠️ ไม่สามารถเปิดอัตโนมัติได้
                    </p>
                    <p class="info">กรุณาทำตามขั้นตอนด้านล่าง:</p>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                        <p style="margin: 10px 0; font-weight: bold;">วิธีที่ 1: ใช้ปุ่มด้านล่าง</p>
                        <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank" rel="noopener noreferrer" style="display: block; text-align: center;">เปิดใน Safari</a>
                        <p style="margin: 20px 0 10px 0; font-weight: bold;">วิธีที่ 2: ใช้เมนู LINE</p>
                        <ol style="margin: 0; padding-left: 20px; color: #666;">
                            <li>กดไอคอน Share (□↑) มุมขวาบนของหน้าจอ</li>
                            <li>เลือก "<strong>Safari</strong>" หรือ "<strong>เปิดในเบราว์เซอร์</strong>"</li>
                        </ol>
                    </div>
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
    // ตรวจสอบว่า token ถูกต้องและยังไม่หมดอายุ
    $tokenValid = false;
    $tokenExpiry = 3600; // 1 hour
    
    // ตรวจสอบ token จาก session
    if (isset($_SESSION['access_token']) && $_SESSION['access_token'] === $token) {
        // ตรวจสอบอายุ token
        if (isset($_SESSION['token_created_at'])) {
            $tokenAge = time() - $_SESSION['token_created_at'];
            if ($tokenAge < $tokenExpiry) {
                $tokenValid = true;
            }
        } else {
            // ถ้าไม่มี timestamp ให้ถือว่าเป็น token เก่า (backward compatibility)
            // แต่จะสร้าง timestamp ใหม่
            $_SESSION['token_created_at'] = time();
            $tokenValid = true;
        }
    }
    
    if (!$tokenValid) {
        // Token ไม่ถูกต้องหรือหมดอายุ
        session_destroy();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Token Expired</title>
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
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 500px;
                }
                h2 { color: #d32f2f; margin-bottom: 20px; }
                p { color: #666; line-height: 1.6; margin: 10px 0; }
                .icon { font-size: 64px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">⏰</div>
                <h2>❌ Token หมดอายุ</h2>
                <p>Token สำหรับเข้าถึงเว็บไซต์หมดอายุแล้ว</p>
                <p><strong>กรุณาเปิดเว็บไซต์ผ่านลิงก์จาก LINE อีกครั้ง</strong></p>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    // Token ถูกต้อง - ตั้งค่า session
    $_SESSION['token'] = $token;
    
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

// ถ้าไม่มี token ใน URL แต่มีใน cookie (fallback - only if token was set within last hour)
if (isset($_COOKIE['smtc_token']) && !isset($_SESSION['token'])) {
    // Validate that cookie token matches a valid session token and hasn't expired
    if (isset($_SESSION['access_token']) && $_COOKIE['smtc_token'] === $_SESSION['access_token']) {
        // ตรวจสอบอายุ token
        $tokenExpiry = 3600;
        if (isset($_SESSION['token_created_at'])) {
            $tokenAge = time() - $_SESSION['token_created_at'];
            if ($tokenAge < $tokenExpiry) {
                $_SESSION['token'] = $_COOKIE['smtc_token'];
                // Redirect ไปยังหน้าถัดไป
                if (!isset($_SESSION["user"])) {
                    header("Location: login.php");
                } else {
                    header("Location: user.php");
                }
                exit();
            }
        }
    }
}

// ถ้าไม่มี token และไม่ใช่ LINE browser - STRICT: Only allow access from LINE
if (!$token && !$isLineBrowser) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
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
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 500px;
            }
            h2 { color: #d32f2f; margin-bottom: 20px; }
            p { color: #666; line-height: 1.6; margin: 10px 0; }
            .icon { font-size: 64px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🚫</div>
            <h2>❌ ไม่สามารถเข้าถึงได้</h2>
            <p><strong>เว็บไซต์นี้สามารถเข้าถึงได้ผ่าน LINE เท่านั้น</strong></p>
            <p>กรุณาเปิดเว็บไซต์ผ่านลิงก์ที่ส่งใน LINE</p>
            <p style="margin-top: 30px; font-size: 14px; color: #999;">
                หากคุณกำลังใช้ LINE อยู่แล้ว<br>
                กรุณารีเฟรชหน้าเว็บหรือเปิดลิงก์ใหม่อีกครั้ง
            </p>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>