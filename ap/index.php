<?php
// 簡單設定
$host = 'db';
$db   = 'test';
$user = 'root';
$pass = 'Dev12876266';
$charset = 'utf8mb4';

// 建立 PDO 連線
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

// 若有表單送出，就寫入資料庫
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = '請輸入完整的 username 與 password。';
    } else {
        try {
            // 改用雜湊存密碼
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO adduser (username, password) VALUES (:username, :password)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
            ]);
            $message = '新增成功！（密碼已用雜湊方式儲存）';
        } catch (Exception $e) {
            $message = '寫入失敗：' . htmlspecialchars($e->getMessage());
        }
    }
}


// 撈出最新 10 筆資料
$rows = [];
try {
    $stmt = $pdo->query("SELECT id, username, password FROM adduser ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $message .= '（讀取資料失敗：' . htmlspecialchars($e->getMessage()) . '）';
}
?>

<?php
$hostName = gethostname();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>AP 測試首頁</title>
    <style>
        body { font-family: Arial, "Microsoft JhengHei", sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: auto; }
        form div { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .msg { margin: 10px 0; color: #c00; }
    </style>
</head>
<body>
<div class="container">
    <h1>AP 首頁 - 新增使用者資料</h1>

    <p>
    Served by： <?= htmlspecialchars($hostName) ?>
    </p>

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
            <button type="submit">確認新增</button>
        </div>
    </form>

    <h2>最新 10 筆 adduser 記錄</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password（示範用）</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="3">目前尚無資料。</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['id']) ?></td>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td><?= htmlspecialchars($r['password']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
