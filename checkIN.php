<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Check-In สำเร็จ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@500&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            text-align: center;
        }
        .success-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .checkmark {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #00C300;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }
        .checkmark::after {
            content: '✓';
            color: white;
            font-size: 60px;
            font-weight: bold;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .success-message {
            font-size: 24px;
            color: #00C300;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .info-text {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin: 20px 0;
        }
        .location-info {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .location-info strong {
            color: #333;
        }
        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #00C300;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 16px;
        }
        .back-btn:hover {
            background: #00A000;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="checkmark"></div>
        <div class="success-message">✅ Check-In/Check-Out สำเร็จ!</div>
        <div class="info-text">
            ข้อมูลของคุณถูกบันทึกเรียบร้อยแล้ว
        </div>
        
        <?php if (isset($_SESSION['place']) && !empty($_SESSION['place'])): ?>
        <div class="location-info">
            <strong>📍 ตำแหน่ง:</strong><br>
            <?= htmlspecialchars($_SESSION['place']) ?>
        </div>
        <?php endif; ?>
        
        <div class="info-text" style="font-size: 14px; color: #999; margin-top: 30px;">
            เวลา: <?= date('Y-m-d H:i:s') ?>
        </div>
        
        <a href="user.php" class="back-btn">กลับไปหน้าหลัก</a>
    </div>
    
    <script>
        // Auto redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'user.php';
        }, 5000);
    </script>
</body>
</html>