<?php
require 'header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理 TOTP 操作（独立处理，避免与 settings 冲突）
    if (isset($_POST['totp_action'])) {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$_SESSION['admin_username']]);
        $currentUser = $stmt->fetch();
        $totpSecret = $currentUser['totp_secret'] ?? '';
        $totpMsg = '';

        if ($_POST['totp_action'] === 'bind' && !empty($_POST['totp_code'])) {
            $code = trim($_POST['totp_code']);
            if (isset($_SESSION['temp_totp_secret']) && verifyTOTP($_SESSION['temp_totp_secret'], $code)) {
                $stmt = $pdo->prepare("UPDATE admin_users SET totp_secret = ? WHERE id = ?");
                $stmt->execute([$_SESSION['temp_totp_secret'], $currentUser['id']]);
                unset($_SESSION['temp_totp_secret']);
                $totpMsg = "✅ 绑定成功！请妥善保管密钥。";
            } else {
                $totpMsg = "❌ 验证码错误，请重试。";
            }
        } elseif ($_POST['totp_action'] === 'unbind') {
            $stmt = $pdo->prepare("UPDATE admin_users SET totp_secret = NULL WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $totpMsg = "已解除绑定。";
        } elseif ($_POST['totp_action'] === 'generate') {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $secret = '';
            for ($i = 0; $i < 16; $i++) {
                $secret .= $chars[random_int(0, 31)];
            }
            $_SESSION['temp_totp_secret'] = $secret;
            $totpMsg = "密钥已生成，请用谷歌验证器扫描下方二维码或手动输入密钥。";
        }
        $message = $totpMsg;
    } else {
        // 普通设置保存
        foreach ($_POST as $key => $value) {
            $key = trim($key);
            $value = trim($value);
            if ($key === '') continue;
            if ($key === 'telegram_notify_enabled') {
                $value = '1';
            }
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            $stmt->execute([$key, $value, $value]);
        }
        if (!isset($_POST['telegram_notify_enabled'])) {
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('telegram_notify_enabled', '0') ON DUPLICATE KEY UPDATE `value` = '0'");
            $stmt->execute();
        }
        $message = '设置已保存。';
    }
}

$settings = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<h2 class="page-title">系统设置</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <h4 style="color:#007ecc; margin-bottom:1rem;">基础设置</h4>
        <label style="color:#b0b0b0;">域名提示（显示在每页顶部）</label>
        <input class="form-inp" name="domain_tip" value="<?= htmlspecialchars($settings['domain_tip'] ?? '') ?>">

        <label style="color:#b0b0b0;">联系邮箱</label>
        <input class="form-inp" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">

        <hr style="border-color:#333; margin:20px 0;">

        <h4 style="color:#007ecc; margin-bottom:1rem;">📢 Telegram 通知设置</h4>
        <label style="color:#b0b0b0; display:flex; align-items:center; gap:8px; margin-bottom:14px;">
            <input type="checkbox" name="telegram_notify_enabled" value="1" <?= ($settings['telegram_notify_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
            启用新视频/图集自动推送到 Telegram
        </label>

        <label style="color:#b0b0b0;">Bot Token（从 @BotFather 获取）</label>
        <input class="form-inp" name="telegram_bot_token" value="<?= htmlspecialchars($settings['telegram_bot_token'] ?? '') ?>">

        <label style="color:#b0b0b0;">频道 ID 或 @用户名</label>
        <input class="form-inp" name="telegram_channel_id" value="<?= htmlspecialchars($settings['telegram_channel_id'] ?? '') ?>">

        <label style="color:#b0b0b0;">群组 ID（可选）</label>
        <input class="form-inp" name="telegram_group_id" value="<?= htmlspecialchars($settings['telegram_group_id'] ?? '') ?>">

        <button type="submit" class="btn btn-primary" style="margin-top:10px;">保存设置</button>
    </form>
</div>

<h2 class="page-title" style="margin-top:30px;">🔐 谷歌验证器绑定</h2>
<div class="card">
    <?php
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$_SESSION['admin_username']]);
    $currentUser = $stmt->fetch();
    $totpSecret = $currentUser['totp_secret'] ?? '';
    $displaySecret = $_SESSION['temp_totp_secret'] ?? $totpSecret;
    ?>
    <?php if ($totpSecret): ?>
        <p>当前状态：<strong style="color:green;">已绑定</strong></p>
        <p>密钥：<code><?= htmlspecialchars($totpSecret) ?></code></p>
        <form method="post">
            <input type="hidden" name="totp_action" value="unbind">
            <button type="submit" class="btn btn-danger" onclick="return confirm('确认解除绑定？')">解除绑定</button>
        </form>
    <?php else: ?>
        <p>当前状态：<strong style="color:red;">未绑定</strong></p>
        <?php if (!empty($displaySecret)): ?>
            <p>密钥：<code><?= htmlspecialchars($displaySecret) ?></code></p>
            <?php
            $qrUrl = 'otpauth://totp/xxoo后台:' . urlencode($currentUser['username']) . '?secret=' . $displaySecret . '&issuer=xxoo';
            $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrUrl);
            ?>
            <img src="<?= $qrImg ?>" style="max-width:200px; display:block; margin:10px 0;">
            <form method="post">
                <input type="hidden" name="totp_action" value="bind">
                <label>输入动态码确认绑定：</label>
                <input type="text" name="totp_code" placeholder="000000" maxlength="6" pattern="\d{6}" required class="form-inp" style="width:150px; display:inline-block;">
                <button type="submit" class="btn btn-primary">确认绑定</button>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="totp_action" value="generate">
                <button type="submit" class="btn btn-primary">生成密钥并绑定</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>