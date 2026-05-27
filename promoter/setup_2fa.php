<?php
session_start();
require __DIR__ . '/../inc/GoogleAuthenticator.php';

if (empty($_SESSION['promoter_id'])) {
    header('Location: login.php');
    exit;
}

$promoter_id = $_SESSION['promoter_id'];
$stmt = $pdo->prepare("SELECT * FROM promoters WHERE id = ?");
$stmt->execute([$promoter_id]);
$promoter = $stmt->fetch();
if (!$promoter) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 如果已经绑定，跳转回看板
if (!empty($promoter['totp_secret'])) {
    header('Location: index.php?msg=already_bound');
    exit;
}

$g = new GoogleAuthenticator();
$secret = $g->generateSecret();
$issuer = 'xxoo推广系统';
$accountName = $promoter['username'];
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode("otpauth://totp/" . rawurlencode($accountName) . "?secret=" . $secret . "&issuer=" . rawurlencode($issuer));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    if ($g->checkCode($secret, $code)) {
        $stmt = $pdo->prepare("UPDATE promoters SET totp_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $promoter_id]);
        $_SESSION['totp_bound'] = true;
        header('Location: index.php?msg=bound_success');
        exit;
    } else {
        $error = '动态码错误，请重试';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>绑定谷歌验证器</title>
    <style>
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .container { background: #fff; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%; text-align: center; }
        img { margin: 20px auto; max-width: 200px; display: block; }
        input { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007ecc; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .error { color: #e74c3c; margin-bottom: 10px; }
        .secret { background: #f5f5f5; padding: 8px; font-family: monospace; margin: 10px 0; word-break: break-all; }
        .back { display: inline-block; margin-top: 15px; color: #007ecc; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h3>🔐 绑定谷歌验证器</h3>
    <p>使用 Google Authenticator 或类似 App 扫描下方二维码：</p>
    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code">
    <div class="secret">密钥：<?= $secret ?></div>
    <p style="font-size:13px; color:#666;">如果二维码无法显示，请手动输入上方密钥到验证器中。</p>
    <form method="post">
        <input type="text" name="code" placeholder="输入6位动态码" required autocomplete="off">
        <button type="submit">确认绑定</button>
    </form>
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <a href="index.php" class="back">← 返回看板</a>
</div>
</body>
</html>
