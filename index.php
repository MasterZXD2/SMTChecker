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
    
    // ตรวจสอบว่าเป็น Android, iOS, หรือ iPad
    $isAndroid = (strpos($userAgent, "android") !== false);
    $isIPad = (strpos($userAgent, "ipad") !== false);
    $isIPhone = (strpos($userAgent, "iphone") !== false);
    $isIPod = (strpos($userAgent, "ipod") !== false);
    $isIOS = $isIPhone || $isIPad || $isIPod;
    
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
                padding: 25px;
                margin: 20px 0;
                color: #856404;
                text-align: left;
            }
            #issueExplanation {
                background: #ffe6e6;
                border: 2px solid #f44336;
                color: #c62828;
            }
            #issueExplanation strong {
                font-size: 20px;
                display: block;
                margin-bottom: 15px;
            }
            .status-update {
                background: #e3f2fd;
                border-left: 4px solid #2196f3;
                padding: 15px;
                margin: 15px 0;
                border-radius: 5px;
                color: #1565c0;
            }
            .status-update.warning {
                background: #fff3e0;
                border-left-color: #ff9800;
                color: #e65100;
            }
            .status-update.error {
                background: #ffebee;
                border-left-color: #f44336;
                color: #c62828;
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
            /* Help Popup Modal */
            .help-popup {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                align-items: center;
                justify-content: center;
            }
            .help-popup.show {
                display: flex;
                animation: fadeIn 0.3s ease-in;
            }
            .help-popup-content {
                background: white;
                padding: 30px;
                border-radius: 15px;
                max-width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                position: relative;
            }
            .help-popup-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 20px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .help-popup-close:hover {
                background: #e0e0e0;
            }
            .help-popup h3 {
                margin-top: 0;
                color: #00C300;
                font-size: 22px;
            }
            .help-popup ol {
                margin: 15px 0;
                padding-left: 25px;
            }
            .help-popup li {
                margin: 10px 0;
                line-height: 1.8;
            }
            .help-popup-btn {
                display: inline-block;
                padding: 12px 25px;
                background: #00C300;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                font-weight: bold;
            }
            /* Toast Notification */
            .toast {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: #333;
                color: white;
                padding: 20px 30px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 10001;
                max-width: 90%;
                text-align: center;
                opacity: 0;
                transition: all 0.3s ease;
                font-size: 16px;
            }
            .toast.show {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            .toast.warning {
                background: #ff9800;
            }
            .toast.error {
                background: #f44336;
            }
            .toast.success {
                background: #4caf50;
            }
            .toast-close {
                position: absolute;
                top: 5px;
                right: 10px;
                background: transparent;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                line-height: 1;
            }
            .toast-content {
                padding-right: 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔐 กำลังเปิดในเบราว์เซอร์ภายนอก...</h2>
            <div class="spinner"></div>
            
            <!-- Status Message -->
            <div id="statusMessage" class="info">
                <p><strong>กำลังพยายามเปิดเบราว์เซอร์อัตโนมัติ...</strong></p>
                <p style="font-size: 14px; color: #999; margin-top: 10px;">กรุณารอสักครู่</p>
            </div>
            
            <!-- Issue Explanation Box -->
            <div id="issueExplanation" class="slow-load-warning" style="display: none;">
                <strong>⚠️ เกิดปัญหาในการเปิดเบราว์เซอร์</strong>
                <p id="issueDescription" style="margin: 15px 0; line-height: 1.8;">
                    <!-- Issue description will be inserted by JavaScript -->
                </p>
                <div id="solutionSteps" style="margin-top: 20px;">
                    <!-- Solution steps will be inserted by JavaScript -->
                </div>
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
                <button onclick="showHelpPopup()" style="margin-top: 15px; padding: 10px 20px; background: #007aff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    📱 ต้องการความช่วยเหลือเพิ่มเติม?
                </button>
            </div>
            
            <!-- Help Popup Modal -->
            <div id="helpPopup" class="help-popup">
                <div class="help-popup-content">
                    <button class="help-popup-close" onclick="closeHelpPopup()">×</button>
                    <div id="helpPopupContent">
                        <!-- Content will be inserted by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Toast Notification -->
            <div id="toast" class="toast">
                <button class="toast-close" onclick="closeToast()">×</button>
                <div class="toast-content" id="toastContent"></div>
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
                    var statusUpdated = false;
                    
                    // Update status message
                    function updateStatus(message, type) {
                        var statusDiv = document.getElementById('statusMessage');
                        if (statusDiv) {
                            statusDiv.innerHTML = '<div class="status-update ' + (type || '') + '">' + message + '</div>';
                            statusUpdated = true;
                        }
                    }
                    
                    // Show issue explanation
                    function showIssueExplanation(description, steps) {
                        var issueDiv = document.getElementById('issueExplanation');
                        var descDiv = document.getElementById('issueDescription');
                        var stepsDiv = document.getElementById('solutionSteps');
                        
                        if (issueDiv && descDiv && stepsDiv) {
                            descDiv.innerHTML = description;
                            stepsDiv.innerHTML = steps;
                            issueDiv.style.display = 'block';
                        }
                    }
                    
                    // ลองเปิดด้วย Intent
                    setTimeout(function() {
                        redirectAttempted = true;
                        updateStatus('<strong>⏳ กำลังเปิด Chrome...</strong><br>กรุณารอสักครู่', '');
                        window.location.href = intentUrl;
                    }, 300);
                    
                    // ถ้า Intent ไม่ทำงาน ให้แสดงปุ่ม fallback และอธิบายปัญหา
                    setTimeout(function() {
                        if (isLineBrowser()) {
                            updateStatus('<strong>⚠️ ไม่สามารถเปิดอัตโนมัติได้</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'warning');
                            document.getElementById('fallback').style.display = 'block';
                            
                            showIssueExplanation(
                                'ระบบไม่สามารถเปิด Chrome อัตโนมัติได้ อาจเป็นเพราะ:<br>' +
                                '• LINE ไม่รองรับการเปิดเบราว์เซอร์อัตโนมัติ<br>' +
                                '• Chrome ยังไม่ได้ตั้งเป็นเบราว์เซอร์เริ่มต้น<br>' +
                                '• การตั้งค่าความปลอดภัยบล็อกการเปิดอัตโนมัติ',
                                '<strong>วิธีแก้ไข:</strong><ol>' +
                                '<li><strong>กดจุดสามจุด (⋮)</strong> ที่มุมขวาบนของหน้าจอ LINE</li>' +
                                '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' +
                                '<li>เลือก <strong>Chrome</strong> หรือ <strong>"เปิดในเบราว์เซอร์เริ่มต้น"</strong></li>' +
                                '<li>เมื่อ Chrome เปิดขึ้นมา ให้อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม</li>' +
                                '</ol>'
                            );
                        }
                    }, 2000);
                    
                    var toastShown = false;
                    var startTime = Date.now();
                    
                    // ตรวจสอบ slow loading (4 วินาที)
                    setTimeout(function() {
                        // ถ้ายังอยู่ใน LINE browser และยังไม่ได้ redirect สำเร็จ
                        if (isLineBrowser() && redirectAttempted && !warningShown) {
                            updateStatus('<strong>⏱️ ใช้เวลานานกว่าปกติ</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'warning');
                            
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv && document.body) {
                                warningDiv.classList.add('show');
                                warningShown = true;
                            }
                            // Show help popup after 6 seconds if still stuck
                            setTimeout(function() {
                                if (isLineBrowser()) {
                                    showHelpPopup();
                                }
                            }, 2000); // 2 more seconds = 6 total
                        }
                    }, 4000); // 4 seconds
                    
                    // ตรวจสอบ long loading (1-2 minutes) - Show toast notification and update status
                    setTimeout(function() {
                        if (isLineBrowser() && redirectAttempted && !toastShown) {
                            updateStatus('<strong>⚠️ ใช้เวลานานมาก</strong><br>กรุณาทำตามขั้นตอนด้านล่างเพื่อเปิดเบราว์เซอร์ด้วยตนเอง', 'error');
                            showToast('⚠️ การเปิดเบราว์เซอร์ใช้เวลานาน', 
                                'กรุณากดจุดสามจุด (⋮) → เลือก "เปิดในเบราว์เซอร์" → เลือก Chrome', 
                                'warning');
                            toastShown = true;
                            
                            // Show detailed explanation
                            showIssueExplanation(
                                'การเปิดเบราว์เซอร์ใช้เวลานานมาก อาจเป็นเพราะ:<br>' +
                                '• การเชื่อมต่ออินเทอร์เน็ตช้า<br>' +
                                '• LINE ไม่สามารถเปิดเบราว์เซอร์อัตโนมัติได้<br>' +
                                '• จำเป็นต้องเปิดเบราว์เซอร์ด้วยตนเอง',
                                '<strong>วิธีแก้ไข (ทำตามขั้นตอนนี้):</strong><ol>' +
                                '<li><strong>กดจุดสามจุด (⋮)</strong> ที่มุมขวาบนของหน้าจอ LINE</li>' +
                                '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' +
                                '<li>เลือก <strong>Chrome</strong> หรือ <strong>"เปิดในเบราว์เซอร์เริ่มต้น"</strong></li>' +
                                '<li>เมื่อ Chrome เปิดขึ้นมา ให้อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม</li>' +
                                '</ol><p style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 5px;"><strong>💡 เคล็ดลับ:</strong> ถ้าไม่เห็นเมนู ให้ลองเลื่อนหน้าจอขึ้นลง หรือกดที่มุมขวาล่าง</p>'
                            );
                        }
                    }, 60000); // 1 minute
                    
                    // Check again at 2 minutes
                    setTimeout(function() {
                        if (isLineBrowser() && redirectAttempted) {
                            updateStatus('<strong>❌ ไม่สามารถเปิดเบราว์เซอร์อัตโนมัติได้</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'error');
                            showToast('⚠️ ยังไม่สามารถเปิดเบราว์เซอร์ได้', 
                                'กรุณาทำตามขั้นตอน: กดจุดสามจุด (⋮) → "เปิดในเบราว์เซอร์" → เลือก Chrome', 
                                'error');
                        }
                    }, 120000); // 2 minutes
                    
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
                    <button onclick="showHelpPopup()" style="margin-top: 15px; padding: 10px 20px; background: #007aff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        📱 ต้องการความช่วยเหลือเพิ่มเติม?
                    </button>
                </div>
                
            <?php elseif ($isIOS): ?>
                <!-- iOS/iPadOS: ใช้ window.open และ fallback -->
                <script>
                    // ฟังก์ชันตรวจสอบว่าเป็น LINE browser หรือไม่
                    function isLineBrowser() {
                        var ua = navigator.userAgent.toLowerCase();
                        return ua.indexOf('line') !== -1;
                    }
                    
                    // ตรวจสอบว่าเป็น iPad หรือไม่
                    function isIPad() {
                        var ua = navigator.userAgent.toLowerCase();
                        return ua.indexOf('ipad') !== -1 || (ua.indexOf('macintosh') !== -1 && 'ontouchend' in document);
                    }
                    
                    // เก็บ token ใน localStorage
                    localStorage.setItem('smtc_token', '<?php echo $token; ?>');
                    
                    var redirectAttempted = false;
                    var opened = null;
                    var warningShown = false;
                    var toastShown = false;
                    var startTime = Date.now();
                    
                    // Update status message
                    function updateStatus(message, type) {
                        var statusDiv = document.getElementById('statusMessage');
                        if (statusDiv) {
                            statusDiv.innerHTML = '<div class="status-update ' + (type || '') + '">' + message + '</div>';
                        }
                    }
                    
                    // Show issue explanation
                    function showIssueExplanation(description, steps) {
                        var issueDiv = document.getElementById('issueExplanation');
                        var descDiv = document.getElementById('issueDescription');
                        var stepsDiv = document.getElementById('solutionSteps');
                        
                        if (issueDiv && descDiv && stepsDiv) {
                            descDiv.innerHTML = description;
                            stepsDiv.innerHTML = steps;
                            issueDiv.style.display = 'block';
                        }
                    }
                    
                    // พยายามเปิดใน Safari
                    try {
                        updateStatus('<strong>⏳ กำลังเปิด Safari...</strong><br>กรุณารอสักครู่', '');
                        opened = window.open('<?php echo $redirectUrl; ?>', '_blank');
                        redirectAttempted = true;
                    } catch(e) {
                        redirectAttempted = true;
                        updateStatus('<strong>⚠️ เกิดข้อผิดพลาด</strong><br>ไม่สามารถเปิด Safari อัตโนมัติได้', 'warning');
                    }
                    
                    if (!opened || opened.closed || typeof opened.closed == 'undefined') {
                        // ถ้า popup ถูกบล็อก ให้แสดงปุ่มและอธิบายปัญหา
                        updateStatus('<strong>⚠️ ไม่สามารถเปิดอัตโนมัติได้</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'warning');
                        document.getElementById('fallback').style.display = 'block';
                        
                        var deviceType = isIPad() ? 'iPad' : 'iPhone';
                        var menuLocation = isIPad() ? 'มุมขวาล่าง' : 'มุมขวาบน';
                        var menuIcon = isIPad() ? 'จุดสามจุด (⋮)' : 'ไอคอน Share (□↑)';
                        
                        showIssueExplanation(
                            'ระบบไม่สามารถเปิด Safari อัตโนมัติได้ อาจเป็นเพราะ:<br>' +
                            '• Popup blocker ของ LINE บล็อกการเปิดเบราว์เซอร์<br>' +
                            '• การตั้งค่าความปลอดภัยของ LINE<br>' +
                            '• จำเป็นต้องเปิดเบราว์เซอร์ด้วยตนเอง',
                            '<strong>วิธีแก้ไขสำหรับ ' + deviceType + ':</strong><ol>' +
                            '<li><strong>กด' + menuIcon + '</strong> ที่' + menuLocation + 'ของหน้าจอ LINE</li>' +
                            (isIPad() ? 
                                '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' :
                                '<li>เลื่อนลงและเลือก <strong>"Safari"</strong> หรือ <strong>"เปิดในเบราว์เซอร์"</strong></li>'
                            ) +
                            '<li>เมื่อ Safari เปิดขึ้นมา ให้อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม</li>' +
                            '</ol>'
                        );
                    } else {
                        // ถ้าเปิดสำเร็จ ให้ปิดหน้าปัจจุบันหลังจาก 1 วินาที
                        updateStatus('<strong>✅ เปิดใน Safari สำเร็จ!</strong><br>กรุณาใช้งานในหน้าต่าง Safari ที่เปิดขึ้นมา', 'success');
                        setTimeout(function() {
                            document.body.innerHTML = '<div class="container"><h2>✅ เปิดใน Safari แล้ว</h2><p>กรุณาใช้งานในหน้าต่าง Safari ที่เปิดขึ้นมา</p></div>';
                        }, 1000);
                    }
                    
                    // ตรวจสอบ slow loading (4 วินาที)
                    setTimeout(function() {
                        // ถ้ายังอยู่ใน LINE browser และยังไม่ได้ redirect สำเร็จ
                        if (isLineBrowser() && redirectAttempted && (!opened || opened.closed || typeof opened.closed == 'undefined') && !warningShown) {
                            updateStatus('<strong>⏱️ ใช้เวลานานกว่าปกติ</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'warning');
                            
                            var warningDiv = document.getElementById('slowLoadWarning');
                            if (warningDiv && document.body) {
                                warningDiv.classList.add('show');
                                warningShown = true;
                            }
                            // Show help popup after 6 seconds if still stuck
                            setTimeout(function() {
                                if (isLineBrowser()) {
                                    showHelpPopup();
                                }
                            }, 2000); // 2 more seconds = 6 total
                        }
                    }, 4000); // 4 seconds
                    
                    // ตรวจสอบ long loading (1-2 minutes) - Show toast notification and update status
                    setTimeout(function() {
                        if (isLineBrowser() && redirectAttempted && (!opened || opened.closed || typeof opened.closed == 'undefined') && !toastShown) {
                            var deviceType = isIPad() ? 'iPad' : 'iPhone';
                            var menuLocation = isIPad() ? 'มุมขวาล่าง' : 'มุมขวาบน';
                            var menuIcon = isIPad() ? 'จุดสามจุด (⋮)' : 'ไอคอน Share (□↑)';
                            
                            updateStatus('<strong>⚠️ ใช้เวลานานมาก</strong><br>กรุณาทำตามขั้นตอนด้านล่างเพื่อเปิดเบราว์เซอร์ด้วยตนเอง', 'error');
                            showToast('⚠️ การเปิดเบราว์เซอร์ใช้เวลานาน', 
                                'กรุณากด' + menuIcon + ' → เลือก "เปิดในเบราว์เซอร์" ที่' + menuLocation, 
                                'warning');
                            toastShown = true;
                            
                            // Show detailed explanation
                            showIssueExplanation(
                                'การเปิดเบราว์เซอร์ใช้เวลานานมาก อาจเป็นเพราะ:<br>' +
                                '• การเชื่อมต่ออินเทอร์เน็ตช้า<br>' +
                                '• LINE ไม่สามารถเปิดเบราว์เซอร์อัตโนมัติได้<br>' +
                                '• จำเป็นต้องเปิดเบราว์เซอร์ด้วยตนเอง',
                                '<strong>วิธีแก้ไขสำหรับ ' + deviceType + ':</strong><ol>' +
                                '<li><strong>กด' + menuIcon + '</strong> ที่' + menuLocation + 'ของหน้าจอ LINE</li>' +
                                (isIPad() ? 
                                    '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' :
                                    '<li>เลื่อนลงและเลือก <strong>"Safari"</strong> หรือ <strong>"เปิดในเบราว์เซอร์"</strong></li>'
                                ) +
                                '<li>เมื่อ Safari เปิดขึ้นมา ให้อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม</li>' +
                                '</ol><p style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 5px;"><strong>💡 เคล็ดลับ:</strong> ถ้าไม่เห็นเมนู ให้ลองเลื่อนหน้าจอ</p>'
                            );
                        }
                    }, 60000); // 1 minute
                    
                    // Check again at 2 minutes
                    setTimeout(function() {
                        if (isLineBrowser() && redirectAttempted && (!opened || opened.closed || typeof opened.closed == 'undefined')) {
                            var deviceType = isIPad() ? 'iPad' : 'iPhone';
                            var menuLocation = isIPad() ? 'มุมขวาล่าง' : 'มุมขวาบน';
                            var menuIcon = isIPad() ? 'จุดสามจุด (⋮)' : 'ไอคอน Share (□↑)';
                            
                            updateStatus('<strong>❌ ไม่สามารถเปิดเบราว์เซอร์อัตโนมัติได้</strong><br>กรุณาทำตามขั้นตอนด้านล่าง', 'error');
                            showToast('⚠️ ยังไม่สามารถเปิดเบราว์เซอร์ได้', 
                                'กรุณาทำตามขั้นตอน: กด' + menuIcon + ' → "เปิดในเบราว์เซอร์" (' + menuLocation + ')', 
                                'error');
                        }
                    }, 120000); // 2 minutes
                    
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
                        <?php if ($isIPad): ?>
                            หรือ:<br>
                            กดจุดสามจุด (⋮) มุมขวาล่าง → เลือก "เปิดในเบราว์เซอร์" หรือ "Open in Browser"
                        <?php else: ?>
                            หรือ:<br>
                            กดไอคอน Share (□↑) → เลือก "Safari" หรือ "เปิดในเบราว์เซอร์"
                        <?php endif; ?>
                    </p>
                    <button onclick="showHelpPopup()" style="margin-top: 15px; padding: 10px 20px; background: #007aff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        📱 ต้องการความช่วยเหลือเพิ่มเติม?
                    </button>
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
                            // Show help popup after 6 seconds if still stuck
                            setTimeout(function() {
                                if (isLineBrowser()) {
                                    showHelpPopup();
                                }
                            }, 2000);
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
            
            <!-- Help Popup Functions -->
            <script>
                function detectDevice() {
                    var ua = navigator.userAgent.toLowerCase();
                    if (ua.indexOf('android') !== -1) return 'android';
                    if (ua.indexOf('ipad') !== -1 || (ua.indexOf('macintosh') !== -1 && 'ontouchend' in document)) return 'ipad';
                    if (/iphone|ipod/.test(ua)) return 'ios';
                    return 'other';
                }
                
                function isIPad() {
                    var ua = navigator.userAgent.toLowerCase();
                    return ua.indexOf('ipad') !== -1 || (ua.indexOf('macintosh') !== -1 && 'ontouchend' in document);
                }
                
                // Toast notification functions
                function showToast(title, message, type) {
                    var toast = document.getElementById('toast');
                    var content = document.getElementById('toastContent');
                    
                    toast.className = 'toast ' + (type || '');
                    content.innerHTML = '<strong>' + title + '</strong><br>' + message;
                    
                    toast.classList.add('show');
                    
                    // Auto-hide after 10 seconds
                    setTimeout(function() {
                        closeToast();
                    }, 10000);
                }
                
                function closeToast() {
                    var toast = document.getElementById('toast');
                    toast.classList.remove('show');
                }
                
                function showHelpPopup() {
                    var popup = document.getElementById('helpPopup');
                    var content = document.getElementById('helpPopupContent');
                    var device = detectDevice();
                    
                    var helpText = '';
                    
                    if (device === 'android') {
                        helpText = '<h3>📱 วิธีเปิดในเบราว์เซอร์ (Android)</h3>' +
                            '<p>ถ้าไม่สามารถเปิดอัตโนมัติได้ กรุณาทำตามขั้นตอนนี้:</p>' +
                            '<ol>' +
                            '<li><strong>กดจุดสามจุด (⋮)</strong> ที่มุมขวาบนของหน้าจอ LINE</li>' +
                            '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' +
                            '<li>เลือก <strong>Chrome</strong> หรือ <strong>"เปิดในเบราว์เซอร์เริ่มต้น"</strong></li>' +
                            '<li>เมื่อ Chrome เปิดขึ้นมา ให้<strong>อนุญาตการเข้าถึงตำแหน่ง</strong>เมื่อเบราว์เซอร์ถาม</li>' +
                            '</ol>' +
                            '<p style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 8px;">' +
                            '<strong>💡 เคล็ดลับ:</strong><br>' +
                            '• ถ้าไม่เห็นเมนู ให้ลองเลื่อนหน้าจอขึ้นลง<br>' +
                            '• บางครั้งเมนูอาจอยู่ที่มุมขวาล่าง<br>' +
                            '• ตรวจสอบว่า Chrome ติดตั้งอยู่บนเครื่องของคุณ' +
                            '</p>';
                    } else if (device === 'ipad') {
                        helpText = '<h3>📱 วิธีเปิดในเบราว์เซอร์ (iPad)</h3>' +
                            '<p>ถ้าไม่สามารถเปิดอัตโนมัติได้ กรุณาทำตามขั้นตอนนี้:</p>' +
                            '<ol>' +
                            '<li><strong>กดจุดสามจุด (⋮)</strong> ที่มุมขวาล่างของหน้าจอ LINE</li>' +
                            '<li>เลือก <strong>"เปิดในเบราว์เซอร์"</strong> หรือ <strong>"Open in Browser"</strong></li>' +
                            '<li>เลือก <strong>Safari</strong> หรือเบราว์เซอร์ที่ต้องการ</li>' +
                            '<li>เมื่อ Safari เปิดขึ้นมา ให้<strong>อนุญาตการเข้าถึงตำแหน่ง</strong>เมื่อเบราว์เซอร์ถาม</li>' +
                            '</ol>' +
                            '<p style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 8px;">' +
                            '<strong>💡 เคล็ดลับสำหรับ iPad:</strong><br>' +
                            '• เมนูมักอยู่ที่มุมขวาล่างของหน้าจอ<br>' +
                            '• บางครั้งอาจต้องกดที่แถบด้านล่างของ LINE<br>' +
                            '• ตรวจสอบว่า Safari เปิดใช้งานได้<br>' +
                            '• iPad รองรับทั้งโหมดแนวนอนและแนวตั้ง' +
                            '</p>';
                    } else if (device === 'ios') {
                        helpText = '<h3>📱 วิธีเปิดในเบราว์เซอร์ (iPhone)</h3>' +
                            '<p>ถ้าไม่สามารถเปิดอัตโนมัติได้ กรุณาทำตามขั้นตอนนี้:</p>' +
                            '<ol>' +
                            '<li><strong>กดไอคอน Share (□↑)</strong> ที่มุมขวาบนของหน้าจอ LINE</li>' +
                            '<li>เลื่อนลงและเลือก <strong>"Safari"</strong> หรือ <strong>"เปิดในเบราว์เซอร์"</strong></li>' +
                            '<li>เมื่อ Safari เปิดขึ้นมา ให้<strong>อนุญาตการเข้าถึงตำแหน่ง</strong>เมื่อเบราว์เซอร์ถาม</li>' +
                            '</ol>' +
                            '<p style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 8px;">' +
                            '<strong>💡 เคล็ดลับ:</strong><br>' +
                            '• ถ้าไม่เห็นไอคอน Share ให้ลองเลื่อนหน้าจอ<br>' +
                            '• บางครั้งอาจต้องกดที่มุมขวาล่าง<br>' +
                            '• ตรวจสอบว่า Safari เปิดใช้งานได้' +
                            '</p>';
    } else {
                        helpText = '<h3>📱 วิธีเปิดในเบราว์เซอร์</h3>' +
                            '<p>กรุณาทำตามขั้นตอนนี้:</p>' +
                            '<ol>' +
                            '<li>กดเมนู (⋮) หรือ Share (□↑) ที่มุมขวาบน</li>' +
                            '<li>เลือก "เปิดในเบราว์เซอร์" หรือ "Open in Browser"</li>' +
                            '<li>เลือกเบราว์เซอร์ที่ต้องการ (Chrome, Safari, etc.)</li>' +
                            '<li>อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม</li>' +
                            '</ol>';
                    }
                    
                    content.innerHTML = helpText;
                    popup.classList.add('show');
                }
                
                function closeHelpPopup() {
                    var popup = document.getElementById('helpPopup');
                    popup.classList.remove('show');
                }
                
                // Close popup when clicking outside
                document.addEventListener('click', function(e) {
                    var popup = document.getElementById('helpPopup');
                    if (e.target === popup) {
                        closeHelpPopup();
                    }
                });
            </script>
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