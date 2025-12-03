<?php 
session_start();

// STRICT: Only allow access if token exists (which means user came through LINE)
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
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
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #00C300;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🔒</div>
            <h2>❌ Token ไม่ถูกต้อง หรือหมดอายุ</h2>
            <p><strong>เว็บไซต์นี้สามารถเข้าถึงได้ผ่าน LINE เท่านั้น</strong></p>
            <p>กรุณาเปิดเว็บไซต์ผ่านลิงก์ที่ส่งใน LINE</p>
            <a href="index.php" class="btn">กลับไปหน้าแรก</a>
        </div>
    </body>
    </html>
    <?php
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
            // ตรวจสอบว่าเป็น LINE browser และแจ้งเตือน
            if (window.GeolocationUtil && window.GeolocationUtil.isLineBrowser()) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'errorMsg';
                warningDiv.style.cssText = 'background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin: 15px 0; border-radius: 5px;';
                warningDiv.innerHTML = '⚠️ <strong>ตรวจพบ LINE Browser</strong><br>GPS อาจไม่ทำงานใน LINE Browser<br>กรุณาเปิดในเบราว์เซอร์ภายนอก (Chrome/Safari) เพื่อให้ GPS ทำงานได้ถูกต้อง';
                document.querySelector('.formContainer').insertBefore(warningDiv, document.querySelector('.formContainer').firstChild);
            }
            
            requestLocation();
        };

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
                    
                    // แสดงสถานะสำเร็จ (optional)
                    console.log('✅ GPS location obtained:', lat, lon, locationName);
                })
                .catch(error => {
                    // แม้ reverse geocode จะล้มเหลว แต่เรายังมี coordinates
                    document.getElementById("locationField").value = lat + "," + lon;
                    document.getElementById("placeField").value = "ไม่ทราบชื่อสถานที่";
                    gpsReady = true;
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