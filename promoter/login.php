<?php
require __DIR__ . "/../config.php";
session_start();
require __DIR__ . '/../inc/GoogleAuthenticator.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $twofa_code = $_POST['twofa_code'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM promoters WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // 检查是否已绑定2FA
        if (!empty($user['totp_secret'])) {
            // 需要验证2FA码
            if (empty($twofa_code)) {
                $error = '请输入谷歌验证器动态码';
            } else {
                $g = new GoogleAuthenticator();
                if ($g->checkCode($user['totp_secret'], $twofa_code)) {
                    $_SESSION['promoter_id'] = $user['id'];
                    $_SESSION['promoter_username'] = $user['username'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = '动态码错误，请重新输入';
                }
            }
        } else {
            // 未绑定2FA，直接登录（不强制绑定）
            $_SESSION['promoter_id'] = $user['id'];
            $_SESSION['promoter_username'] = $user['username'];
            header('Location: index.php');
            exit;
        }
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>推广员登录</title>
    <style>
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 320px; }
        h3 { margin-bottom: 20px; text-align: center; }
        input { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #007ecc; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: #e74c3c; text-align: center; margin-bottom: 10px; }
        .info { color: #f90; font-size: 13px; text-align: center; }
    </style>
</head>
<body>
<div class="login-box">
    <h3>🔑 推广员登录</h3>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="用户名" required>
        <input type="password" name="password" placeholder="密码" required>
        <input type="text" name="twofa_code" placeholder="谷歌验证器动态码（如已绑定）" autocomplete="off">
        <button type="submit">登录</button>
    </form>
    <div class="info">提示：未绑定谷歌验证器的用户可直接登录，登录后建议绑定。</div>
</div>
</body>
</html>
