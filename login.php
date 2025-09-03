<?php 
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

if (strpos($userAgent, "line") === false) {
    echo "กรุณาเปิดจากเว็บจากลิ้งที่อาจารส่งใน LINE และผ่านในโทรศัพท์เท่านั้น";
    exit;
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

    <script>
        let gpsReady = false;
        let gpsError = null;

        // ฟังก์ชันขอพิกัดตอนโหลดเว็บ
        window.onload = function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            } else {
                gpsError = "เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง";
            }
        };

        function successCallback(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    const locationName = data.display_name || "ไม่ทราบชื่อสถานที่";

                    document.getElementById("locationField").value = lat + "," + lon;
                    document.getElementById("placeField").value = locationName;

                    gpsReady = true;
                })
                .catch(error => {
                    gpsError = "เกิดข้อผิดพลาดจากการแปลงพิกัดเป็นสถานที่: " + error;
                });
        }

        function errorCallback(error) {
            let message;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = "ผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง \nวิธีแก้ลอง:\nตรวจสอบว่าผู้ใช้กด Allow Location ตอนที่เบราว์เซอร์ถามหรือไม่\niPhone: ไปที่ Settings > LINE > Location แล้วเลือก While Using the App\nAndroid: ไปที่ Settings > Apps > LINE > Permissions > Location แล้วกด Allow";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "ไม่สามารถระบุตำแหน่งได้ (สัญญาณ GPS หรือเครือข่ายไม่พร้อม)\nวิธีแก้ลอง:\nเปิด Location (GPS) Mode และตรวจสอบว่าเครื่องมีสัญญาณอินเทอร์เน็ตหรือไม่\nตรวจสอบว่าเครื่องมีสัญญาณอินเทอร์เน็ตหรือไม่ (บางครั้งต้องใช้ Network ช่วย)\nถ้าใช้ในอาคาร ลองย้ายออกไปที่โล่งแจ้ง";
                    break;
                case error.TIMEOUT:
                    message = "หมดเวลาในการขอตำแหน่ง (Timeout)\nวิธีแก้ลอง:\nเปิด GPS + อินเทอร์เน็ตพร้อมกัน";
                    break;
                default:
                    message = "ไม่ทราบข้อผิดพลาด\nลองตรวจสอบว่าเว็บทำงานผ่าน HTTPS ไหม\nวิธีแก้ลอง:\nตรวจสอบว่า code ไม่โดนบล็อกโดย AdBlock / Security App";
                    break;
            }
            gpsError = `${message} (รายละเอียด: ${error.message}\n(ErrorCode: ${error.code})`;
        }

        // ฟังก์ชันที่ทำงานก่อนส่งฟอร์ม
        function attachLocation() {
            if (!gpsReady) {
                alert("❌ ไม่สามารถส่งฟอร์มได้ เพราะยังไม่ได้รับตำแหน่ง\n" + (gpsError || "กรุณาลองใหม่อีกครั้ง"));
                return false;
            }
            return true;
        }
    </script>
</body>

</html>