<?php
session_start();

// 如果沒有登入，就導回 login.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? '使用者';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>歡迎頁面</title>
    <style>
        body { font-family: Arial, "Microsoft JhengHei", sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: auto; }
        a { text-decoration: none; color: #06c; }
    </style>
</head>
<body>
<div class="container">
    <h1>歡迎，<?= htmlspecialchars($username) ?>！</h1>
    <p>你已成功登入系統。</p>

    <p>
        <a href="logout.php">登出</a> |
        <a href="index.php">回註冊頁 (index.php)</a>
    </p>
</div>
</body>
</html>
