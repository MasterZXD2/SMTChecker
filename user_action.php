<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    $location =  $_POST['placeField'] + "(" + $_POST['location'] + ")" ?? 'ไม่พบตำแหน่ง';

    // ตัวอย่าง: บันทึกลง log หรือเก็บใน database
    //file_put_contents("checkin_log.txt", "ชื่อ: $name ม.$m เกิด: $date สถานที่: $location\n", FILE_APPEND);

    echo "เช็คอินเรียบร้อย!<br>";
    echo "ชื่อ: $name<br>ม.: $m<br>เกิด: $date<br>ตำแหน่ง: $location";
} else {
    echo "Method ไม่ถูกต้อง";
}