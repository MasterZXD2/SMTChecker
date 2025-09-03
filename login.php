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

            <button id="shareBtn" type="button">LOGIN</button>
        
            <div id="status" style="margin-top:10px;color:#444;"></div>
        </form>
    </div>

    <script>
(async () => {
    const LIFF_ID = 'YOUR_LIFF_ID'; // <-- ใส่ LIFF ID ถ้ามี (ไม่ใส่ก็ใช้ navigator.geolocation ตามปกติ)
    let liffInited = false;

    // พยายาม init LIFF เงียบๆ เพื่อให้ใช้ API ของ LIFF ได้ (ถ้ามีและคุณตั้งค่าแล้ว)
    try {
        if (window.liff) {
            await liff.init({ liffId: LIFF_ID });
            liffInited = true;
            console.log('LIFF inited');
        }
    } catch (e) {
        console.warn('LIFF init failed or LIFF_ID not set', e);
    }

    const statusEl = document.getElementById('status');
    const shareBtn = document.getElementById('shareBtn');
    const form = document.getElementById('mainForm');

    // helper: Promise wrapper ของ getCurrentPosition
    function getCurrentPositionPromise(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('เบราว์เซอร์ไม่รองรับ Geolocation'));
                return;
            }
            navigator.geolocation.getCurrentPosition(resolve, reject, options);
        });
    }

    // ฟังก์ชัน reverse geocode (ใช้ Nominatim เป็นตัวอย่าง — ปรับเป็น service ของคุณถ้าต้องการ)
    async function reverseGeocode(lat, lon) {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`);
            if (!res.ok) return null;
            const data = await res.json();
            return data.display_name || null;
        } catch (e) {
            return null;
        }
    }

    // ฟังก์ชันหลัก: ขอพิกัดแล้ว submit form
    async function requestAndSubmit() {
        statusEl.textContent = 'กำลังขอตำแหน่ง... กรุณาอนุญาตเมื่อมี popup ปรากฏ';
        shareBtn.disabled = true;

        try {
            // บาง platform ต้องให้ผู้ใช้กดก่อนจึงเรียก geolocation (เราเรียกจากปุ่มแล้ว)
            const pos = await getCurrentPositionPromise({
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });

            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;

            // reverse geocode (ไม่จำเป็น แต่คุณใช้จากเดิม)
            const place = await reverseGeocode(lat, lon) || 'ไม่ทราบชื่อสถานที่';

            document.getElementById('locationField').value = `${lat},${lon}`;
            document.getElementById('placeField').value = place;

            statusEl.textContent = 'ได้รับตำแหน่งแล้ว กำลังส่งข้อมูล...';
            form.submit();
        } catch (err) {
            console.error(err);
            let msg = 'ไม่สามารถรับตำแหน่งได้: ' + (err.message || err);
            // ถ้า user ปฏิเสธ permission ให้บอกวิธีแก้ปัญหาแบบสั้น ๆ
            if (err.code === 1 || /permission/i.test(err.message)) {
                msg += '\nผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง — ให้ลองเปิด Location ในการตั้งค่า LINE/เบราว์เซอร์:\n' +
                       'iPhone: Settings > LINE > Location (หรือ Settings > Privacy & Security > Location Services > LINE)\n' +
                       'Android: Settings > Apps > LINE > Permissions > Location';
            } else if (err.code === 2) {
                msg += '\nตำแหน่งไม่พร้อม (ลองออกไปที่โล่งแจ้ง หรือเปิด GPS และเชื่อมต่ออินเทอร์เน็ต)';
            } else if (err.code === 3) {
                msg += '\nหมดเวลา (timeout) — ลองกดแชร์อีกครั้งพร้อมเปิด GPS และสัญญาณเน็ต';
            }
            statusEl.textContent = msg;
            shareBtn.disabled = false;
        }
    }

    shareBtn.addEventListener('click', async (e) => {
        // เรียกจากผู้ใช้กด -> ปลอดภัยตามแนวทางของ iOS/Android/LIFF
        await requestAndSubmit();
    });

    // คำแนะนำเพิ่มเติม (แสดงเมื่อเปิดใน LINE แต่ไม่อนุญาต)
    // ถ้าต้องการฟีเจอร์เฉพาะของ LINE (เช่นแสดงหน้าจอ Permission ของ LIFF) ให้ดู LIFF docs
})();
</script>
</body>

</html>
