<?php
session_start();

// 資料庫連線設定（跟 index.php 一樣）
$host = 'db';
$db   = 'test';
$user = 'root';
$pass = 'Dev12876266';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("資料庫連線失敗：" . htmlspecialchars($e->getMessage()));
}

$message = '';

// 如果是表單送出，就處理登入邏輯
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = '請輸入帳號與密碼';
    } else {
        // 1. 先撈出這個 username 的資料
        $stmt = $pdo->prepare("SELECT id, username, password FROM adduser WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $userRow = $stmt->fetch();

        // 2. 判斷資料是否存在 + 密碼是否正確
        if ($userRow && password_verify($password, $userRow['password'])) {
            // 登入成功，寫入 session
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['username'] = $userRow['username'];

            // 導向歡迎頁
            header('Location: welcome.php');
            exit;
        } else {
            // 不刻意區分「帳號錯」或「密碼錯」，避免多透露資訊
            $message = '帳號不存在或密碼錯誤';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>登入頁面</title>
    <style>
        body { font-family: Arial, "Microsoft JhengHei", sans-serif; margin: 20px; }
        .container { max-width: 400px; margin: auto; }
        form div { margin-bottom: 10px; }
        .msg { margin: 10px 0; color: #c00; }
        a { text-decoration: none; color: #06c; }
    </style>
</head>
<body>
<div class="container">
    <h1>登入</h1>

    <?php if ($message !== ''): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>Username：
                <input type="text" name="username">
            </label>
        </div>
        <div>
            <label>Password：
                <input type="password" name="password">
            </label>
        </div>
        <div>
            <button type="submit">登入</button>
        </div>
    </form>

    <p>
        還沒有帳號？你可以先到 <a href="/index.php" target="_self">註冊頁（index.php）</a> 建一個帳號。
    </p>
</div>
</body>
</html>
