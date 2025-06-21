<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    date_default_timezone_set('Asia/Bangkok');
    $timestamp = date("Y-m-d H:i:s");
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    $place = $_POST['place'] ?? 'ไม่ทราบสถานที่';
    $coords = $_POST['location'] ?? '';

    $data = [
        'time' => $timestamp,
        'name' => $name,
        'level' => $m,
        'coords' => $coords
    ];

    $url = "https://script.google.com/macros/s/AKfycbxdHFeLHwZQyELGlEUX0MgneDpbBrDnv3lw0WtKs6sHzd4UnjbTFBttW-bep6q2V_2rvg/exec"; // <- เปลี่ยนตรงนี้
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    header("location: checkIN.php");
}