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
    
    <script src="https://static.line-scdn.net/liff/edge/2.1/sdk.js"></script>
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

            <button id="loginBtn" type="button">LOGIN</button>
        
            <div id="status" style="margin-top:10px;color:#444;"></div>
        </form>
    </div>

<script>
// helper: promise wrapper ของ geolocation
function getCurrentPositionPromise(options = {}) {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error("เบราว์เซอร์ไม่รองรับการหาตำแหน่ง"));
            return;
        }
        navigator.geolocation.getCurrentPosition(resolve, reject, options);
    });
}

async function reverseGeocode(lat, lon) {
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`
        );
        if (!res.ok) return null;
        const data = await res.json();
        return data.display_name || null;
    } catch {
        return null;
    }
}

document.getElementById("loginBtn").addEventListener("click", async () => {
    const statusEl = document.getElementById("status");
    const form = document.getElementById("mainForm");
    const btn = document.getElementById("loginBtn");
    btn.disabled = true;
    statusEl.textContent = "กำลังขอตำแหน่ง... กรุณาอนุญาตเมื่อมี popup ขึ้นมา";

    try {
        const pos = await getCurrentPositionPromise({
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        });
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;

        const place = await reverseGeocode(lat, lon) || "ไม่ทราบชื่อสถานที่";
        document.getElementById("locationField").value = `${lat},${lon}`;
        document.getElementById("placeField").value = place;

        statusEl.textContent = "✅ ได้ตำแหน่งแล้ว กำลังเข้าสู่ระบบ...";
        form.submit();
    } catch (err) {
        let msg = "❌ ไม่สามารถรับตำแหน่งได้: " + (err.message || err);
        if (err.code === 1) {
            msg += "\n\nวิธีแก้:\n- iPhone: Settings > LINE > Location > While Using the App\n- Android: Settings > Apps > LINE > Permissions > Location > Allow";
        }
        statusEl.textContent = msg;
        btn.disabled = false;
    }
});
</script
</body>

</html>
