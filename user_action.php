<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['id'];
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    $place = $_POST['place'] ?? 'ไม่ทราบสถานที่';
    $coords = $_POST['location'] ?? '';

    $data = [
        'id' => $id,
        'name' => $name,
        'level' => $m,
        'place' => $place,
        'coords' => $coords
    ];

    $url = "https://script.google.com/macros/s/AKfycbzSInzmtr7Rn00U7CXMo_GA3vxGilqAuYSQ457QUoyRq6M8X-YhEx-ac0QhQENjxL0l/exec"; // <- เปลี่ยนตรงนี้
    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    echo "✅ เช็คอินสำเร็จ!<br><pre>";
    echo htmlspecialchars($result);
    echo "</pre>";
}