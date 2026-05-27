<?php
session_start();

// 如果已经验证过，直接放行
if (isset($_SESSION['site_verified']) && $_SESSION['site_verified'] === true) {
    $next = $_GET['next'] ?? 'home.php';
    if (strpos($next, '/') !== 0) $next = 'home.php';
    header('Location: adwall.php?next=' . urlencode($next));
    exit;
}

$next = $_GET['next'] ?? 'home.php';
// 安全检查
if (parse_url($next, PHP_URL_HOST) || !preg_match('#^/[a-zA-Z0-9_\./?=&-]*$#', $next)) {
    $next = 'home.php';
}

// 如果用户提交了验证码，检查是否正确
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = strtoupper(trim($_POST['code'] ?? ''));
    $correct = $_SESSION['captcha_code'] ?? '';
    if ($input === $correct && $correct !== '') {
        $_SESSION['site_verified'] = true;
        header('Location: adwall.php?next=' . urlencode($next));
        exit;
    } else {
        $error = '验证码错误，请重试';
    }
}

// 生成新的验证码
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha_code'] = $code;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title>安全验证</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#0f172a;color:#fff;font-family:sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center}.box{background:#1e293b;padding:28px 24px;border-radius:12px;width:320px}h1{margin:0 0 18px;font-size:17px;text-align:center}.row{display:flex;gap:10px;align-items:center;margin-bottom:14px}#cap{background:#f8fafc;border-radius:6px;cursor:pointer;width:120px;height:40px;position:relative;overflow:hidden;user-select:none}#cap span{position:absolute;top:50%;font:700 24px sans-serif;transform:translateY(-50%)}#cap i{position:absolute;width:2px;height:2px;border-radius:50%}#refresh{flex:1;background:#334155;color:#fff;border:none;padding:10px;border-radius:6px;cursor:pointer}#code{width:100%;padding:12px;border:1px solid #334155;background:#0f172a;color:#fff;border-radius:6px;font-size:16px;letter-spacing:4px;text-align:center;text-transform:uppercase}#code:focus{outline:none;border-color:#f90}#submit{width:100%;padding:12px;background:#f90;color:#fff;border:none;border-radius:6px;font-size:15px;cursor:pointer;margin-top:12px}#msg{min-height:18px;margin-top:10px;font-size:13px;text-align:center;color:#f87171}.shake{animation:shake .4s}@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-8px)}40%,80%{transform:translateX(8px)}}
    </style>
</head>
<body>
<form class="box" method="post" autocomplete="off">
    <h1>安全验证</h1>
    <p style="font-size:12px;color:#94a3b8;text-align:center;margin:-10px 0 16px">防止机器人访问，完成后即可继续</p>
    <div class="row">
        <div id="cap" title="点击换一张">
            <?php
            for ($i = 0; $i < 4; $i++) {
                $ch = $code[$i];
                $left = 10 + $i * 26;
                $dy = random_int(-4, 4);
                $deg = random_int(-20, 20);
                $color = 'rgb(' . random_int(30,120) . ',' . random_int(30,120) . ',' . random_int(30,120) . ')';
                echo '<span style="left:' . $left . 'px;transform:translateY(-50%) translateY(' . $dy . 'px) rotate(' . $deg . 'deg);color:' . $color . '">' . $ch . '</span>';
            }
            for ($i = 0; $i < 30; $i++) {
                $x = random_int(0, 118);
                $y = random_int(0, 38);
                $bg = 'rgb(' . random_int(0,200) . ',' . random_int(0,200) . ',' . random_int(0,200) . ')';
                echo '<i style="left:' . $x . 'px;top:' . $y . 'px;background:' . $bg . '"></i>';
            }
            ?>
        </div>
        <button type="button" id="refresh" onclick="location.reload()">换一张</button>
    </div>
    <input id="code" name="code" maxlength="4" placeholder="4 位验证码" required>
    <button type="submit" id="submit">确认</button>
    <div id="msg"><?= isset($error) ? htmlspecialchars($error) : '' ?></div>
</form>
<script>
// 页面加载时聚焦输入框
document.getElementById('code').focus();
// 点击验证码图片刷新
document.getElementById('cap').addEventListener('click', function(){ location.reload(); });
</script>
</body>
</html>
