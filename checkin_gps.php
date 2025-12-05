<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Store check-in data temporarily (will be sent after GPS is retrieved)
$_SESSION['pending_checkin'] = true;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>กำลังขอตำแหน่ง GPS...</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@500&display=swap" rel="stylesheet">
    
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
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #00C300;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status-message {
            font-size: 18px;
            color: #333;
            margin: 20px 0;
            line-height: 1.6;
        }
        .error-container {
            display: none;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            text-align: left;
        }
        .error-container.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-container h3 {
            color: #856404;
            margin-top: 0;
            font-size: 20px;
        }
        .error-container ul {
            margin: 15px 0;
            padding-left: 25px;
        }
        .error-container li {
            margin: 10px 0;
            line-height: 1.8;
        }
        .retry-btn {
            display: inline-block;
            padding: 12px 25px;
            background: #00C300;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 15px 5px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .retry-btn:hover {
            background: #00A000;
        }
        .retry-btn.secondary {
            background: #6c757d;
        }
        .retry-btn.secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="loadingSection">
            <div class="spinner"></div>
            <div class="status-message">
                <strong>กำลังขอตำแหน่ง GPS...</strong><br>
                <span style="font-size: 14px; color: #666;">กรุณารอสักครู่</span>
            </div>
        </div>
        
        <div id="errorSection" class="error-container">
            <h3>❌ ไม่สามารถรับตำแหน่ง GPS ได้</h3>
            <p>กรุณาลองทำตามขั้นตอนต่อไปนี้:</p>
            <ul id="errorSteps">
                <!-- Error steps will be inserted by JavaScript -->
            </ul>
            <div style="text-align: center; margin-top: 20px;">
                <button class="retry-btn" onclick="retryLocation()">ลองใหม่อีกครั้ง</button>
                <button class="retry-btn secondary" onclick="goBack()">กลับไปหน้าหลัก</button>
            </div>
        </div>
    </div>
    
    <script src="geolocation.js"></script>
    <script>
        let retryCount = 0;
        const maxRetries = 3;
        
        function detectDevice() {
            var ua = navigator.userAgent.toLowerCase();
            if (ua.indexOf('android') !== -1) return 'android';
            if (/iphone|ipad|ipod/.test(ua)) return 'ios';
            return 'other';
        }
        
        function getErrorSteps(errorCode) {
            const device = detectDevice();
            const isLine = window.GeolocationUtil && window.GeolocationUtil.isLineBrowser();
            
            let steps = [];
            
            if (errorCode === 1 || errorCode === 'PERMISSION_DENIED') {
                steps.push('กรุณา<strong>อนุญาตการเข้าถึงตำแหน่ง</strong>เมื่อเบราว์เซอร์ถาม');
                if (device === 'android') {
                    steps.push('ไปที่ <strong>Settings > Apps > Chrome > Permissions > Location > Allow</strong>');
                } else if (device === 'ios') {
                    steps.push('ไปที่ <strong>Settings > Safari > Location Services > Allow</strong>');
                }
                if (isLine) {
                    steps.push('<strong>เปิดในเบราว์เซอร์ภายนอก</strong> (Chrome/Safari) เพื่อให้ GPS ทำงานได้');
                }
            } else if (errorCode === 2 || errorCode === 'POSITION_UNAVAILABLE') {
                steps.push('เปิด<strong>Location (GPS)</strong>บนอุปกรณ์ของคุณ');
                if (device === 'android') {
                    steps.push('ไปที่ <strong>Settings > Location > On</strong>');
                    steps.push('เลือก <strong>High accuracy</strong> mode (ใช้ GPS + Wi-Fi + Mobile networks)');
                } else {
                    steps.push('ไปที่ <strong>Settings > Privacy > Location Services > On</strong>');
                }
                steps.push('ตรวจสอบว่ามี<strong>สัญญาณอินเทอร์เน็ต</strong>');
                steps.push('ถ้าใช้ในอาคาร ลอง<strong>ย้ายไปที่โล่งแจ้ง</strong>');
            } else if (errorCode === 3 || errorCode === 'TIMEOUT') {
                steps.push('เปิด<strong>GPS และอินเทอร์เน็ต</strong>พร้อมกัน');
                if (device === 'android') {
                    steps.push('ไปที่ <strong>Settings > Location > Mode > High accuracy</strong>');
                }
                steps.push('ย้ายไปที่<strong>โล่งแจ้ง</strong>เพื่อรับสัญญาณ GPS ได้ดีขึ้น');
                steps.push('ตรวจสอบว่า<strong>Wi-Fi หรือ Mobile data</strong>เปิดอยู่');
            } else {
                steps.push('ตรวจสอบว่าเว็บทำงานผ่าน<strong>HTTPS</strong>');
                steps.push('ตรวจสอบว่าไม่โดนบล็อกโดย<strong>AdBlock หรือ Security App</strong>');
                steps.push('ลอง<strong>รีเฟรชหน้าเว็บ</strong>');
                if (isLine) {
                    steps.push('<strong>เปิดในเบราว์เซอร์ภายนอก</strong> (Chrome/Safari)');
                }
            }
            
            return steps;
        }
        
        function showError(error) {
            document.getElementById('loadingSection').style.display = 'none';
            const errorSection = document.getElementById('errorSection');
            const errorSteps = document.getElementById('errorSteps');
            
            const steps = getErrorSteps(error.code);
            let stepsHtml = '';
            steps.forEach(step => {
                stepsHtml += '<li>' + step + '</li>';
            });
            errorSteps.innerHTML = stepsHtml;
            
            errorSection.classList.add('show');
        }
        
        function retryLocation() {
            retryCount++;
            if (retryCount > maxRetries) {
                alert('ลองหลายครั้งแล้ว กรุณาตรวจสอบการตั้งค่า GPS และลองใหม่อีกครั้ง');
                return;
            }
            
            document.getElementById('errorSection').classList.remove('show');
            document.getElementById('loadingSection').style.display = 'block';
            
            requestLocation();
        }
        
        function goBack() {
            window.location.href = 'user.php';
        }
        
        function requestLocation() {
            if (!window.GeolocationUtil) {
                showError({ code: -1, message: 'ไม่สามารถโหลด Geolocation Utility ได้' });
                return;
            }
            
            window.GeolocationUtil.request(
                {
                    enableHighAccuracy: true,
                    timeout: 30000,
                    maximumAge: 0
                },
                function(position) {
                    // Success! Store location and redirect to confirmation
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    // Get location name
                    window.GeolocationUtil.reverseGeocode(lat, lon)
                        .then(locationName => {
                            // Store in session via form submission
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'user_action.php';
                            
                            const locationInput = document.createElement('input');
                            locationInput.type = 'hidden';
                            locationInput.name = 'location';
                            locationInput.value = lat + ',' + lon;
                            form.appendChild(locationInput);
                            
                            const placeInput = document.createElement('input');
                            placeInput.type = 'hidden';
                            placeInput.name = 'place';
                            placeInput.value = locationName;
                            form.appendChild(placeInput);
                            
                            document.body.appendChild(form);
                            form.submit();
                        })
                        .catch(error => {
                            // Even if reverse geocode fails, still submit with coordinates
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'user_action.php';
                            
                            const locationInput = document.createElement('input');
                            locationInput.type = 'hidden';
                            locationInput.name = 'location';
                            locationInput.value = lat + ',' + lon;
                            form.appendChild(locationInput);
                            
                            const placeInput = document.createElement('input');
                            placeInput.type = 'hidden';
                            placeInput.name = 'place';
                            placeInput.value = 'ไม่ทราบชื่อสถานที่';
                            form.appendChild(placeInput);
                            
                            document.body.appendChild(form);
                            form.submit();
                        });
                },
                function(error) {
                    showError(error);
                },
                3 // retry 3 times
            );
        }
        
        // Start requesting location on page load
        window.onload = function() {
            // Small delay to ensure page is fully loaded
            setTimeout(requestLocation, 500);
        };
    </script>
</body>
</html>

