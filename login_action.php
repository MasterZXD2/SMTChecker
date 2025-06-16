<?php
session_start();

$url = "https://script.google.com/macros/s/AKfycbyEhbvOgCHRdVmMQVvvp_4iQ3AwuidPh-Lz0IQk03He4DE0gGZZz4fTjydwvEbLAAg/exec";
$postData = [
    "action" => "login",
    "id" => $_POST['id'],
    "password" => $_POST['password']
];

$ch = curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $postData
]);


$result = curl_exec($ch);
$result = json_decode($result, 1);

if ($result['status'] == "success") {
    $_SESSION['user'] = $result['data'];
    header("location: user.php");
} else {
    $_SESSION['error'] = $result['message'];
    header("location: login.php");
}