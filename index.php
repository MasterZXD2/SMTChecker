<?php
session_start();

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

if (strpos($userAgent, "line") === false) {
    echo "กรุณาเปิดจากเว็บจากลิ้งที่อาจารส่งใน LINE เท่านั้น";
    exit;
}

if(!isset($_SESSION["user"])){
    header("location: login.php");
} else {
    header("location: user.php");
}