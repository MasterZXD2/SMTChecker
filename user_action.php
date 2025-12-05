<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    date_default_timezone_set('Asia/Bangkok');
    $timestamp = date("Y-m-d H:i:s");
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    
    // Get location from POST (sent from checkin_gps.php)
    $place = $_POST['place'] ?? 'ไม่ทราบชื่อสถานที่';
    $coords = $_POST['location'] ?? '';
    
    // Store in session for confirmation page
    $_SESSION['location'] = $coords;
    $_SESSION['place'] = $place;
    
    $data = [
        'time' => $timestamp,
        'name' => $name,
        'level' => $m,
        'coords' => $coords
    ];

    $url = "https://script.google.com/macros/s/AKfycbxdHFeLHwZQyELGlEUX0MgneDpbBrDnv3lw0WtKs6sHzd4UnjbTFBttW-bep6q2V_2rvg/exec";
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    // Always redirect to confirmation page after submission
    header("location: checkIN.php");
    exit();
} else {
    // If accessed directly without POST, redirect to GPS retrieval
    header("location: checkin_gps.php");
    exit();
}