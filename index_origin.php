<?php
require __DIR__ . "/config.php";
// 如果是 TG 机器人带来的用户，直接跳转
if (!empty($_GET['from']) && $_GET['from'] === 'tg') {
    $domains = ['fakakuai.com', 'fakakuai.shop'];
    $target = $domains[array_rand($domains)];
    header('Location: https://' . $target . '/verify.php?next=home.php');
    exit;
}

function getActiveDomains() {
    return ['fakakuai.com', 'fakakuai.shop'];
}
$domains = getActiveDomains();
$defaultUrl = "https://{$domains[0]}/verify.php?next=home.php";
$qrApi = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=10&data=" . urlencode($defaultUrl);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>xxoo 官方永久入口 · 安全稳定</title>
    <style>
        :root {
            --primary: #165DFF; --accent: #FF7D00; --success: #00B42A;
            --bg: #121212; --card-bg: #1E1E1E; --text: #FFFFFF;
            --text-secondary: #B0B0B0; --text-tertiary: #808080;
            --border: #333333; --green: #00CD00; --red: #ff3333; --gold: #FFC107;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.2); --shadow-md: 0 4px 16px rgba(0,0,0,0.3);
            --radius-sm: 6px; --radius-md: 12px; --radius-lg: 16px;
            --transition: all 0.3s ease;
            --font: -apple-system, BlinkMacSystemFont, "Segoe UI", "Microsoft YaHei", sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: var(--font); line-height: 1.7; min-height: 100vh; padding-bottom: 20px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 20px 0; border-bottom: 1px solid var(--border); margin-bottom: 32px; }
        .logo { font-size: 26px; font-weight: 800; color: var(--primary); letter-spacing: 0.5px; }
        .logo span { color: var(--accent); }
        .contact-btns { display: flex; gap: 20px; flex-wrap: wrap; }
        .contact-btns a { background-color: var(--green); color: white; text-decoration: none; font-size: 18px; font-weight: 600; padding: 12px 28px; border-radius: 50px; transition: var(--transition); box-shadow: var(--shadow-sm); }
        .contact-btns a:hover { background-color: #009900; transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .banner { background: linear-gradient(135deg, #1A2333 0%, #243452 100%); border-radius: var(--radius-lg); padding: 48px 24px; text-align: center; margin-bottom: 32px; box-shadow: var(--shadow-sm); }
        .banner h1 { font-size: 30px; font-weight: 700; color: var(--gold); margin-bottom: 12px; }
        .banner p { font-size: 20px; color: var(--text-secondary); max-width: 700px; margin: 0 auto; }
        .red-alert { color: var(--red); font-weight: bold; font-size: 20px; text-align: center; padding: 12px 16px; background: rgba(255, 51, 51, 0.1); border-radius: var(--radius-md); }
        .green-line { height: 3px; background-color: var(--green); width: 100%; margin: 24px 0; border-radius: 2px; }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .card-title { font-size: 20px; font-weight: 700; color: var(--gold); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .card-title::before { content: ""; width: 4px; height: 22px; background: var(--gold); border-radius: 2px; }
        .contact-methods { display: flex; flex-direction: column; gap: 14px; margin: 16px 0; }
        .contact-methods div { color: var(--text-secondary); font-size: 17px; }
        .contact-methods a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .main-grid { display: flex; gap: 32px; margin: 32px 0; }
        .address-list { flex: 1; display: flex; flex-direction: column; gap: 24px; }
        .entry-item { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .entry-item:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); border-color: var(--primary); }
        .entry-label { font-size: 19px; color: var(--red); margin-bottom: 14px; font-weight: 700; }
        .url-box { display: flex; align-items: center; background: #252525; border-radius: var(--radius-md); padding: 14px 18px; border: 1px solid var(--border); gap: 14px; }
        .url-text { flex: 1; font-family: 'Courier New', monospace; color: var(--accent); word-break: break-all; user-select: all; font-size: 18px; }
        .copy-btn { background: var(--accent); color: white; border: none; padding: 10px 24px; border-radius: 50px; font-weight: 700; cursor: pointer; transition: var(--transition); white-space: nowrap; font-size: 16px; }
        .copy-btn:hover { background: #E67000; transform: scale(1.05); }
        .copy-btn.copied { background: var(--success); pointer-events: none; }
        .qr-section { width: 280px; display: flex; flex-direction: column; align-items: center; background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 32px 20px; box-shadow: var(--shadow-sm); position: sticky; top: 32px; height: fit-content; border-top: 3px solid var(--green); border-bottom: 3px solid var(--green); }
        .qr-img { width: 180px; height: 180px; background: #fff; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .qr-img img { width: 100%; height: 100%; object-fit: cover; }
        .qr-tip { color: var(--text-secondary); font-size: 16px; text-align: center; line-height: 1.6; }
        .footer { text-align: center; padding: 36px 0; margin-top: 40px; border-top: 3px solid var(--green); font-size: 17px; line-height: 2.2; color: var(--text-secondary); }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .footer a:hover { color: var(--accent); }
        @media (max-width: 768px) {
            .main-grid { flex-direction: column; gap: 24px; }
            .qr-section { width: 100%; position: static; }
            .header { flex-direction: column; gap: 16px; text-align: center; }
            .contact-btns { width: 100%; justify-content: center; }
            .banner h1 { font-size: 24px; }
            .banner p { font-size: 17px; }
            .url-box { flex-direction: column; gap: 12px; }
            .copy-btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">xx<span>oo</span> · 永久入口</div>
            <div class="contact-btns">
                <a href="mailto:zhugeyang7@gmail.com">📧 邮件联系</a>
                <a href="https://t.me/xxoovideonsfw_bot" target="_blank">✈️ Telegram</a>
            </div>
        </div>
        <div class="banner">
            <h1>xxoo 官方永久地址发布页</h1>
            <p>本页面永久有效，建议立即收藏，最新官方地址实时自动更新</p>
        </div>
        <div class="green-line"></div>
        <div class="red-alert">⚠️ 重要提醒！请立即收藏本页面，避免走丢！ ⚠️</div>
        <div class="green-line"></div>
        <div class="card">
            <div class="card-title">永久回家方式（建议截图保存）</div>
            <div class="contact-methods">
                <div>📧 发送任意邮件至 <a href="mailto:zhugeyang7@gmail.com">zhugeyang7@gmail.com</a>，自动回复最新地址</div>
                <div>✈️ Telegram 发送任意消息至 <a href="https://t.me/xxoovideonsfw_bot" target="_blank">@xxoovideonsfw_bot</a>，自动获取地址</div>
            </div>
        </div>
        <div class="green-line"></div>
        <div class="main-grid">
            <div class="address-list">
                <?php foreach ($domains as $i => $domain): ?>
                <?php $url = "https://{$domain}/verify.php?next=home.php"; ?>
                <div class="entry-item">
                    <div class="entry-label">🔗 官方最新入口 <?= $i + 1 ?></div>
                    <div class="url-box">
                        <div class="url-text" id="addr<?= $i + 1 ?>"><?= htmlspecialchars($url) ?></div>
                        <button class="copy-btn" id="btn<?= $i + 1 ?>" onclick="copyText('addr<?= $i + 1 ?>', 'btn<?= $i + 1 ?>')">复制地址</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="qr-section">
                <div class="qr-img">
                    <img src="<?= htmlspecialchars($qrApi) ?>" alt="扫码直达">
                </div>
                <div class="qr-tip">手机扫描二维码<br>一键打开官方最新地址</div>
            </div>
        </div>
        <div class="green-line"></div>
        <div class="footer">
            <div>站长联系方式 ✈️ <a href="https://t.me/xxoowebmasterbot" target="_blank">@xxoowebmasterbot</a></div>
            <div>备用邮箱 📥 <a href="mailto:tytdwpt888@gmail.com">tytdwpt888@gmail.com</a></div>
            <div style="margin-top:12px; color: var(--text-tertiary); font-size:14px;">多重保障防丢失，建议收藏本页 + 保存联系方式</div>
        </div>
    </div>
    <script>
        function copyText(id, btnId) {
            const textEl = document.getElementById(id);
            const btn = document.getElementById(btnId);
            if (!textEl || !btn) return;
            const text = textEl.textContent.trim();
            const originalText = btn.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    setCopiedStatus(btn, originalText);
                }).catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
            function fallbackCopy() {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
                document.body.appendChild(textarea);
                textarea.select();
                try { document.execCommand('copy'); setCopiedStatus(btn, originalText); }
                catch { alert('复制失败，请手动复制'); }
                document.body.removeChild(textarea);
            }
        }
        function setCopiedStatus(btn, originalText) {
            btn.classList.add('copied');
            btn.textContent = '✓ 复制成功';
            setTimeout(() => { btn.classList.remove('copied'); btn.textContent = originalText; }, 1500);
        }
    </script>
</body>
</html>