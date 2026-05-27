<?php
require __DIR__ . "/../config.php";
session_start();
require __DIR__ . '/../inc/functions.php';

$error = '';

// 如果已经登录，跳转
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// 检查是否在两步验证阶段
$step = $_SESSION['admin_2fa_step'] ?? 1;
$adminId = $_SESSION['admin_2fa_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // 第一步：验证用户名密码
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = '请输入用户名和密码。';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                // 检查是否需要两步验证
                if (!empty($user['totp_secret'])) {
                    // 需要两步验证，进入第二步
                    $_SESSION['admin_2fa_step'] = 2;
                    $_SESSION['admin_2fa_id'] = $user['id'];
                    header('Location: login.php');
                    exit;
                } else {
                    // 没有绑定，直接登录
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    unset($_SESSION['admin_2fa_step'], $_SESSION['admin_2fa_id']);
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = '用户名或密码错误。';
            }
        }
    } elseif ($step === 2 && $adminId) {
        // 第二步：验证 TOTP
        $code = trim($_POST['totp_code'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();
        if ($user && !empty($user['totp_secret']) && verifyTOTP($user['totp_secret'], $code)) {
            // 验证成功
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            unset($_SESSION['admin_2fa_step'], $_SESSION['admin_2fa_id']);
            header('Location: index.php');
            exit;
        } else {
            $error = '动态码错误或已过期。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>管理后台 - 登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .login-box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 360px; max-width: 90%; }
        h2 { color: #333; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #f90; color: #000; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { background: #e68a00; }
        .error { color: #e74c3c; margin-bottom: 10px; font-size: 14px; text-align: center; }
        .step-info { text-align: center; color: #666; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔐 后台管理登录</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($step === 1): ?>
            <form method="post">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">登录</button>
            </form>
        <?php elseif ($step === 2): ?>
            <p class="step-info">请输入谷歌验证器中的 6 位动态码</p>
            <form method="post">
                <div class="form-group">
                    <label>动态码</label>
                    <input type="text" name="totp_code" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
                </div>
                <button type="submit">验证</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
