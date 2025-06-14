<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    $place = $_POST['place'] ?? 'ไม่ทราบสถานที่';
    $coords = $_POST['location'] ?? 'ไม่ทราบพิกัด';

    //$location = $place . " (" . $coords . ")";

    $googleMapLink = $coords
        ? "https://www.google.com/maps?q=" . urlencode($coords)
        : '';

    // ตัวอย่าง: บันทึกลง log หรือเก็บใน database
    //file_put_contents("checkin_log.txt", "ชื่อ: $name ม.$m เกิด: $date สถานที่: $location\n", FILE_APPEND);

    echo "เช็คอินเรียบร้อย!<br>";
    echo "ชื่อ: $name<br>ม.: $m<br>เกิด: $date<br>ตำแหน่ง: $place";

     if ($googleMapLink) {
        echo "ดูแผนที่: <a href='$googleMapLink' target='_blank'>$coords</a>";
    } else {
        echo "ไม่พบพิกัด";
    }
} else {
    echo "Method ไม่ถูกต้อง";
}