<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

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
    <form method="post" action="user_action.php">
        <div class="dashboardContainer">
            <label class="dataMessage">
                <a> ชื่อ: <?= htmlspecialchars($_SESSION['name']) ?> ม.<?= htmlspecialchars($_SESSION['m']) ?> </a>
                <a> เกิด: <?= htmlspecialchars($_SESSION['date']) ?> </a>
                <input type="submit" value="CHECKIN">
            </label>
        </div>
    </form>

    <script>
        function sendLocation(form) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    document.getElementById("locationField").value = lat + "," + lon;
                    form.submit(); // ส่งฟอร์มหลังจากได้ location แล้ว
                }, function(error) {
                    alert("ไม่สามารถดึงตำแหน่งได้: " + error.message);
                });
                return false; // รอจนกว่าจะได้ location ก่อนส่งฟอร์ม
            } else {
                alert("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง");
                return false;
            }
        }
    </script>
</body>

</html>