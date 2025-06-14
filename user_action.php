<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_SESSION['name'];
    $m = $_SESSION['m'];
    $date = $_SESSION['date'];
    $place = $_POST['place'] ?? 'ไม่ทราบสถานที่';
    $coords = $_POST['location'] ?? '';

    $data = [
        'idcard' => $idcard,
        'name' => $name,
        'level' => $level,
        'place' => $place,
        'coords' => $coords
    ];

    $url = "https://script.google.com/macros/s/AKfycbysvqb20wuG3mg1v4vUgS3YKB1f0hqR2Is1wUBo2Un8gShjy81Z5RN7vjPgiIK-ayfO/exec"; // <- เปลี่ยนตรงนี้
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