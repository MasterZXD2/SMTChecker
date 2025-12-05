<?php 
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

if (strpos($userAgent, "line") === false) {
    //echo "กรุณาเปิดจากเว็บจากลิ้งที่อาจารส่งใน LINE และผ่านในโทรศัพท์เท่านั้น";
    //exit;
}

// Presence-based token validation: accept any non-empty token from URL, session, or cookie
$token = null;

// 1. Check token from URL parameter (highest priority)
if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);
    $_SESSION['token'] = $token;
    $_SESSION['access_token'] = $token;
    // Store in cookie as backup
    setcookie('smtc_token', $token, time() + (86400 * 30), '/', '', true, true);
}
// 2. Check token from session
elseif (isset($_SESSION['token']) && !empty(trim($_SESSION['token']))) {
    $token = trim($_SESSION['token']);
}
// 3. Check token from cookie (fallback)
elseif (isset($_COOKIE['smtc_token']) && !empty(trim($_COOKIE['smtc_token']))) {
    $token = trim($_COOKIE['smtc_token']);
    $_SESSION['token'] = $token;
    $_SESSION['access_token'] = $token;
}

// If no token found anywhere, deny access
if (!$token || empty($token)) {
    echo "❌ Token ไม่ถูกต้อง หรือหมดอายุ";
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>SMTC Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@500&display=swap" rel="stylesheet">
</head>

<body>
    <img alt="main" src="images/S__16621602.png" />
    <div class = "formContainer">
        <form method="post" action = "login_action.php" onsubmit="return attachLocation()">
            <h2 class = "title"> เข้าสู่ระบบ </h2>
            <div class = "tip">
                เข้าสู่ระบบด้วยเลขบัตรประชาชนและรหัสผ่านของคุณ
            </div>

            <?php
            if(isset($_SESSION['error'])){
                echo "<div class = 'errorMsg'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }
            ?>

            <label for = "id"> เลขบัตรประชาชน </label>
            <input type = "text" name = "id">
            <label for = "password"> รหัสผ่าน </label>
            <input type = "password" name = "password">

            <input type="hidden" id="locationField" name="location">
            <input type="hidden" id="placeField" name="place">

            <input type = "submit" value = "LOGIN">
        </form>
    </div>

    <script src="geolocation.js"></script>
    <script>
        let gpsReady = false;
        let gpsError = null;
        let isRequesting = false;

        // ฟังก์ชันขอพิกัดตอนโหลดเว็บ
        window.onload = function () {
            // Initialize global flag
            window.gpsReady = false;
            
            // ตรวจสอบว่าเป็น LINE browser และแจ้งเตือน
            if (window.GeolocationUtil && window.GeolocationUtil.isLineBrowser()) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'errorMsg';
                warningDiv.style.cssText = 'background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin: 15px 0; border-radius: 5px;';
                
                let warningText = '⚠️ <strong>ตรวจพบ LINE Browser</strong><br>GPS อาจไม่ทำงานใน LINE Browser<br>กรุณาเปิดในเบราว์เซอร์ภายนอก (Chrome/Safari) เพื่อให้ GPS ทำงานได้ถูกต้อง';
                
                if (window.GeolocationUtil.isAndroid()) {
                    warningText += '<br><br><strong>สำหรับ Android:</strong><br>1. กดจุดสามจุด (⋮) มุมขวาบน<br>2. เลือก "เปิดในเบราว์เซอร์"<br>3. เลือก Chrome';
                }
                
                warningDiv.innerHTML = warningText;
                document.querySelector('.formContainer').insertBefore(warningDiv, document.querySelector('.formContainer').firstChild);
            }
            
            // Delay request slightly for Android to ensure page is fully loaded
            if (window.GeolocationUtil && window.GeolocationUtil.isAndroid()) {
                setTimeout(requestLocation, 500);
            } else {
                requestLocation();
            }
        };
        
        // Handle page visibility change (user switched to external browser)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && !gpsReady && !isRequesting) {
                // Show UI notification instead of console log
                showNotification('📱 กำลังลองขอตำแหน่งอีกครั้ง...', 'info');
                setTimeout(requestLocation, 1000);
            }
        });
        
        // Notification function
        function showNotification(message, type) {
            // Create or get notification element
            let notification = document.getElementById('gpsNotification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'gpsNotification';
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #333; color: white; padding: 15px 20px; border-radius: 8px; z-index: 10000; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s;';
                document.body.appendChild(notification);
            }
            
            if (type === 'error') notification.style.background = '#f44336';
            else if (type === 'success') notification.style.background = '#4caf50';
            else if (type === 'warning') notification.style.background = '#ff9800';
            else notification.style.background = '#2196f3';
            
            notification.textContent = message;
            notification.style.opacity = '1';
            
            setTimeout(function() {
                notification.style.opacity = '0';
                setTimeout(function() {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function requestLocation() {
            if (isRequesting) return;
            isRequesting = true;
            
            if (!window.GeolocationUtil) {
                gpsError = "ไม่สามารถโหลด Geolocation Utility ได้";
                isRequesting = false;
                return;
            }

            window.GeolocationUtil.request(
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0
                },
                successCallback,
                errorCallback,
                2 // retry 2 times
            );
        }

        function successCallback(position) {
            isRequesting = false;
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            // ใช้ reverse geocoding จาก utility
            window.GeolocationUtil.reverseGeocode(lat, lon)
                .then(locationName => {
                    document.getElementById("locationField").value = lat + "," + lon;
                    document.getElementById("placeField").value = locationName;
                    gpsReady = true;
                    window.gpsReady = true; // Set global flag
                    
                    // Show success notification
                    showNotification('✅ ได้รับตำแหน่ง GPS เรียบร้อย', 'success');
                })
                .catch(error => {
                    // แม้ reverse geocode จะล้มเหลว แต่เรายังมี coordinates
                    document.getElementById("locationField").value = lat + "," + lon;
                    document.getElementById("placeField").value = "ไม่ทราบชื่อสถานที่";
                    gpsReady = true;
                    window.gpsReady = true; // Set global flag
                    // Coordinates saved even if reverse geocoding failed
                    showNotification('⚠️ บันทึกตำแหน่งแล้ว แต่ไม่สามารถแปลงเป็นชื่อสถานที่ได้', 'warning');
                });
        }

        function errorCallback(error) {
            isRequesting = false;
            gpsError = window.GeolocationUtil.getErrorMessage(error);
            // Show error notification
            let errorMsg = '❌ ไม่สามารถรับตำแหน่ง GPS ได้';
            if (error.code === 1 || error.code === error.PERMISSION_DENIED) {
                errorMsg = '❌ ถูกปฏิเสธการเข้าถึงตำแหน่ง';
            } else if (error.code === 2 || error.code === error.POSITION_UNAVAILABLE) {
                errorMsg = '❌ ไม่สามารถระบุตำแหน่งได้';
            } else if (error.code === 3 || error.code === error.TIMEOUT) {
                errorMsg = '⏱️ หมดเวลาในการขอตำแหน่ง';
            }
            showNotification(errorMsg, 'error');
        }

        // ฟังก์ชันที่ทำงานก่อนส่งฟอร์ม
        function attachLocation() {
            if (!gpsReady) {
                let errorMsg = "❌ ไม่สามารถส่งฟอร์มได้ เพราะยังไม่ได้รับตำแหน่ง\n\n";
                
                if (gpsError) {
                    errorMsg += gpsError;
                } else {
                    errorMsg += "กรุณารอสักครู่เพื่อให้ระบบขอตำแหน่ง GPS\n\n";
                    errorMsg += "ถ้ายังไม่ได้ตำแหน่ง:\n";
                    errorMsg += "1. ตรวจสอบว่าได้อนุญาตการเข้าถึงตำแหน่ง\n";
                    errorMsg += "2. เปิด GPS และอินเทอร์เน็ต\n";
                    errorMsg += "3. ลองรีเฟรชหน้าเว็บ";
                    
                    // ลองขออีกครั้ง
                    if (!isRequesting) {
                        requestLocation();
                    }
                }
                
                alert(errorMsg);
                return false;
            }
            return true;
        }
    </script>
</body>

</html>