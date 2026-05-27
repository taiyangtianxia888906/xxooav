<?php
session_start();

if (empty($_SESSION['promoter_id'])) {
    header('Location: login.php');
    exit;
}

$promoter_id = $_SESSION['promoter_id'];
$stmt = $pdo->prepare("SELECT * FROM promoters WHERE id = ?");
$stmt->execute([$promoter_id]);
$promoter = $stmt->fetch();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = '所有字段都不能为空';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } elseif (!password_verify($old_password, $promoter['password'])) {
        $error = '原密码错误';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE promoters SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $promoter_id]);
        $message = '密码修改成功，请重新登录。';
        // 可选：强制退出登录，让用户重新登录
        session_destroy();
        echo '<script>alert("密码已修改，请重新登录"); location.href="login.php";</script>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>修改密码 - 推广员看板</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; color: #333; padding: 40px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; max-width: 400px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h2 { color: #007ecc; margin-bottom: 20px; }
        .form-inp { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007ecc; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
        .error { color: #e74c3c; margin-bottom: 10px; }
        .success { color: #27ae60; margin-bottom: 10px; }
        a { display: inline-block; margin-top: 15px; color: #007ecc; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <h2>🔐 修改密码</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="password" name="old_password" class="form-inp" placeholder="当前密码" required>
        <input type="password" name="new_password" class="form-inp" placeholder="新密码" required>
        <input type="password" name="confirm_password" class="form-inp" placeholder="确认新密码" required>
        <button type="submit">确认修改</button>
    </form>
    <a href="index.php">← 返回看板</a>
</div>
</body>
</html>
