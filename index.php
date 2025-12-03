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
            .slow-load-warning {
                display: none;
                background: #fff3cd;
                border: 2px solid #ffc107;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                color: #856404;
                text-align: left;
            }
            .slow-load-warning.show {
                display: block;
                animation: fadeIn 0.3s ease-in;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .slow-load-warning strong {
                display: block;
                margin-bottom: 10px;
                font-size: 18px;
            }
            .slow-load-warning ol {
                margin: 10px 0;
                padding-left: 20px;
            }
            .slow-load-warning li {
                margin: 8px 0;
                line-height: 1.6;
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
            
            <!-- Slow loading detection for LINE browser -->
            <div id="slowLoadWarning" class="slow-load-warning">
                <strong>⚠️ หน้านี้ใช้เวลาโหลดนานเกินไป</strong>
                <p>กรุณาทำตามขั้นตอนด้านล่างเพื่อเปิดในเบราว์เซอร์ภายนอก:</p>
                <ol>
                    <li>กดจุดสามจุด (⋮) หรือเมนูที่มุมขวาบน/ล่าง</li>
                    <li>เลือก "<strong>เปิดในเบราว์เซอร์</strong>" หรือ "<strong>Open in Browser</strong>"</li>
                    <li>เลือก Chrome (Android) หรือ Safari (iOS)</li>
                </ol>
                <p style="margin-top: 15px; font-size: 14px;">
                    <strong>หมายเหตุ:</strong> การเปิดในเบราว์เซอร์ภายนอกจะช่วยให้ GPS ทำงานได้ถูกต้องและเร็วขึ้น
                </p>
            </div>
            
            <?php if ($isAndroid): ?>
                <!-- Android: ใช้ Intent และ fallback -->
                <script>
                    // ฟังก์ชันตรวจสอบว่าเป็น LINE browser หรือไม่
                    function isLineBrowser() {
                        var ua = navigator.userAgent.toLowerCase();
                        return ua.indexOf('line') !== -1;
                    }
                    
                    // เก็บ token ใน localStorage ก่อน redirect
                    localStorage.setItem('smtc_token', '<?php echo $token; ?>');
                    
                    // พยายามเปิดด้วย Intent
                    var intentUrl = "intent://smtchecker.onrender.com/index.php?token=<?php echo urlencode($token); ?>#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=<?php echo urlencode($redirectUrl); ?>;end";
                    
                    var redirectAttempted = false;
                    var warningShown = false;
                    
                    // ลองเปิดด้วย Intent
                    setTimeout(function() {
                        redirectAttempted = true;
                        window.location.href = intentUrl;
                    }, 300);
                    
                    // ถ้า Intent ไม่ทำงาน ให้แสดงปุ่ม fallback
                    setTimeout(function() {
                        document.getElementById('fallback').style.display = 'block';
                    }, 2000);
                    
                    // ตรวจสอบ slow loading (4 วินาที)
                    setTimeout(function() {
                        // ถ้ายังอยู่ใน LINE browser และยังไม่ได้ redirect สำเร็จ
                        if (isLineBrowser() && redirectAttempted && !warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv && document.body) {
                                warningDiv.classList.add('show');
                                warningShown = true;
                            }
                        }
                    }, 4000); // 4 seconds
                    
                    // ตรวจสอบเป็นระยะว่ายังอยู่ใน LINE browser หรือไม่ (ถ้าไม่ใช่ ให้ซ่อน warning)
                    setInterval(function() {
                        if (!isLineBrowser() && warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv) {
                                warningDiv.classList.remove('show');
                            }
                        }
                    }, 1000);
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
                    // ฟังก์ชันตรวจสอบว่าเป็น LINE browser หรือไม่
                    function isLineBrowser() {
                        var ua = navigator.userAgent.toLowerCase();
                        return ua.indexOf('line') !== -1;
                    }
                    
                    // เก็บ token ใน localStorage
                    localStorage.setItem('smtc_token', '<?php echo $token; ?>');
                    
                    var redirectAttempted = false;
                    var opened = null;
                    var warningShown = false;
                    
                    // พยายามเปิดใน Safari
                    try {
                        opened = window.open('<?php echo $redirectUrl; ?>', '_blank');
                        redirectAttempted = true;
                    } catch(e) {
                        redirectAttempted = true;
                    }
                    
                    if (!opened || opened.closed || typeof opened.closed == 'undefined') {
                        // ถ้า popup ถูกบล็อก ให้แสดงปุ่ม
                        document.getElementById('fallback').style.display = 'block';
                    } else {
                        // ถ้าเปิดสำเร็จ ให้ปิดหน้าปัจจุบันหลังจาก 1 วินาที
                        setTimeout(function() {
                            document.body.innerHTML = '<div class="container"><h2>✅ เปิดใน Safari แล้ว</h2><p>กรุณาใช้งานในหน้าต่าง Safari ที่เปิดขึ้นมา</p></div>';
                        }, 1000);
                    }
                    
                    // ตรวจสอบ slow loading (4 วินาที)
                    setTimeout(function() {
                        // ถ้ายังอยู่ใน LINE browser และยังไม่ได้ redirect สำเร็จ
                        if (isLineBrowser() && redirectAttempted && (!opened || opened.closed || typeof opened.closed == 'undefined') && !warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv && document.body) {
                                warningDiv.classList.add('show');
                                warningShown = true;
                            }
                        }
                    }, 4000); // 4 seconds
                    
                    // ตรวจสอบเป็นระยะว่ายังอยู่ใน LINE browser หรือไม่ (ถ้าไม่ใช่ ให้ซ่อน warning)
                    setInterval(function() {
                        if (!isLineBrowser() && warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv) {
                                warningDiv.classList.remove('show');
                            }
                        }
                    }, 1000);
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
                <script>
                    // ฟังก์ชันตรวจสอบว่าเป็น LINE browser หรือไม่
                    function isLineBrowser() {
                        var ua = navigator.userAgent.toLowerCase();
                        return ua.indexOf('line') !== -1;
                    }
                    
                    var warningShown = false;
                    
                    // ตรวจสอบ slow loading (4 วินาที)
                    setTimeout(function() {
                        if (isLineBrowser() && !warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv && document.body) {
                                warningDiv.classList.add('show');
                                warningShown = true;
                            }
                        }
                    }, 4000); // 4 seconds
                    
                    // ตรวจสอบเป็นระยะว่ายังอยู่ใน LINE browser หรือไม่ (ถ้าไม่ใช่ ให้ซ่อน warning)
                    setInterval(function() {
                        if (!isLineBrowser() && warningShown) {
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv) {
                                warningDiv.classList.remove('show');
                            }
                        }
                    }, 1000);
                </script>
                <a href="<?php echo $redirectUrl; ?>" class="btn" target="_blank">เปิดในเบราว์เซอร์</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ถ้าเข้าจาก browser ปกติพร้อม token (presence-based validation only)
if ($token && !empty(trim($token))) {
    // เก็บ token ใน session (presence-based only, no expiration check)
    $_SESSION['token'] = $token;
    $_SESSION['access_token'] = $token;
    
    // เก็บ token ใน cookie เพื่อเป็น backup (long expiry for persistence)
    setcookie('smtc_token', $token, time() + (86400 * 30), '/', '', true, true); // 30 days
    
    // Redirect ไปยังหน้าถัดไป
    if (!isset($_SESSION["user"])) {
        header("Location: login.php?token=" . urlencode($token));
    } else {
        header("Location: user.php");
    }
    exit();
}

// ถ้าไม่มี token ใน URL แต่มีใน cookie (fallback - presence-based only)
if (isset($_COOKIE['smtc_token']) && !empty(trim($_COOKIE['smtc_token']))) {
    $cookieToken = $_COOKIE['smtc_token'];
    $_SESSION['token'] = $cookieToken;
    $_SESSION['access_token'] = $cookieToken;
    
    // Redirect ไปยังหน้าถัดไป
    if (!isset($_SESSION["user"])) {
        header("Location: login.php?token=" . urlencode($cookieToken));
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