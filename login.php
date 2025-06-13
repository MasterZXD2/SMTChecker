<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>SMTC Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@500&display=swap" rel="stylesheet">
</head>

<body>
    <img alt="main" src="images/S__16621602.png" />
    <div class = "formContainer">
        <form method="post" action = "login_action.php">
            <h2 class = "title"> เข้าสู่ระบบ </h2>
            <div class = "tip">
                เข้าสู่ระบบด้วยเลขบัตรประชาชนและรหัสผ่านของคุณ
            </div>

            <?php
            session_start();
            if(isset($_SESSION['error'])){
                echo "<div class = 'errorMsg'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }
            ?>

            <label for = "id"> เลขบัตรประชาชน </label>
            <input type = "text" name = "id">
            <label for = "password"> รหัสผ่าน </label>
            <input type = "password" name = "password">
            <input type = "submit" value = "LOGIN">
        </form>
    </div>
</body>

</html>