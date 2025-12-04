<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$_SESSION['id'] = $_SESSION['user'][0];
$_SESSION['name'] = $_SESSION['user'][2];
$_SESSION['m'] = $_SESSION['user'][3];
$_SESSION['date'] = $_SESSION['user'][1];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>SMTC User</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@500&display=swap" rel="stylesheet">
</head>

<body>
    <img alt="main" src="images/S__16621602.png" />
    <form method="post" action="user_action.php" onsubmit="return attachLocation()">
        <div class="dashboardContainer">
            <label class="dataMessage">
                <a> ชื่อ: <?= htmlspecialchars($_SESSION['name']) ?> ม.<?= htmlspecialchars($_SESSION['m']) ?> </a>
                <a> เลขบัตรนักเรียน: <?= htmlspecialchars($_SESSION['date']) ?> </a>

                <input type="hidden" id="locationField" name="location">
                <input type="hidden" id="placeField" name="place">

                <input type="submit" value="CHECKIN/CHECKOUT">
            </label>
        </div>
    </form>

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
                warningDiv.style.cssText = 'background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin: 15px 0; border-radius: 5px; text-align: center;';
                
                let warningText = '⚠️ <strong>ตรวจพบ LINE Browser</strong><br>GPS อาจไม่ทำงานใน LINE Browser<br>กรุณาเปิดในเบราว์เซอร์ภายนอก (Chrome/Safari) เพื่อให้ GPS ทำงานได้ถูกต้อง';
                
                if (window.GeolocationUtil.isAndroid()) {
                    warningText += '<br><br><strong>สำหรับ Android:</strong><br>1. กดจุดสามจุด (⋮) มุมขวาบน<br>2. เลือก "เปิดในเบราว์เซอร์"<br>3. เลือก Chrome';
                }
                
                warningDiv.innerHTML = warningText;
                document.querySelector('.dashboardContainer').insertBefore(warningDiv, document.querySelector('.dashboardContainer').firstChild);
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
                console.log('📱 Page became visible, retrying geolocation...');
                setTimeout(requestLocation, 1000);
            }
        });

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
                    
                    // แสดงสถานะสำเร็จ (optional)
                    console.log('✅ GPS location obtained:', lat, lon, locationName);
                    
                    // Android-specific: Show success message briefly
                    if (window.GeolocationUtil.isAndroid()) {
                        console.log('✅ Android GPS location successfully retrieved');
                    }
                })
                .catch(error => {
                    // แม้ reverse geocode จะล้มเหลว แต่เรายังมี coordinates
                    document.getElementById("locationField").value = lat + "," + lon;
                    document.getElementById("placeField").value = "ไม่ทราบชื่อสถานที่";
                    gpsReady = true;
                    window.gpsReady = true; // Set global flag
                    console.warn('⚠️ Reverse geocoding failed, but coordinates saved:', error);
                });
        }

        function errorCallback(error) {
            isRequesting = false;
            gpsError = window.GeolocationUtil.getErrorMessage(error);
            console.error('❌ Geolocation error:', error);
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