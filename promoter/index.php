<?php
require __DIR__ . "/../config.php";
session_start();
if (empty($_SESSION['promoter_id'])) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM promoters WHERE id = ?");
$stmt->execute([$_SESSION['promoter_id']]);
$promoter = $stmt->fetch();
$viewKey = $promoter['view_key'] ?? '';

// 统计函数
function getCount($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// 今日数据
$todayVisits = getCount($pdo,
    "SELECT COUNT(*) FROM visit_logs WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?) AND DATE(visited_at) = CURDATE()",
    [$viewKey]
);
$todayVideoViews = getCount($pdo,
    "SELECT COUNT(*) FROM video_views WHERE video_id IN (SELECT item_id FROM shares WHERE type='video' AND view_key = ?) AND DATE(viewed_at) = CURDATE()",
    [$viewKey]
);
$todayAdClicks = getCount($pdo,
    "SELECT COUNT(*) FROM ad_clicks WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?) AND DATE(clicked_at) = CURDATE()",
    [$viewKey]
);

// 总计
$totalShares = getCount($pdo, "SELECT COUNT(*) FROM shares WHERE view_key = ?", [$viewKey]);
$totalVisits = getCount($pdo, "SELECT COUNT(*) FROM visit_logs WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?)", [$viewKey]);
$totalVideoViews = getCount($pdo, "SELECT COUNT(*) FROM video_views WHERE video_id IN (SELECT item_id FROM shares WHERE type='video' AND view_key = ?)", [$viewKey]);
$totalAdClicks = getCount($pdo, "SELECT COUNT(*) FROM ad_clicks WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?)", [$viewKey]);
$avgStay = getCount($pdo,
    "SELECT ROUND(AVG(stay_seconds), 1) FROM visit_logs WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?)",
    [$viewKey]
);

// 来源 Top5
$stmt = $pdo->prepare("SELECT referer, COUNT(*) AS cnt FROM visit_logs WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?) AND referer != '' GROUP BY referer ORDER BY cnt DESC LIMIT 5");
$stmt->execute([$viewKey]);
$topReferers = $stmt->fetchAll();

// 设备分布
$stmt = $pdo->prepare("SELECT device_type, COUNT(*) AS cnt FROM visit_logs WHERE share_code IN (SELECT share_code FROM shares WHERE view_key = ?) AND device_type != '' GROUP BY device_type");
$stmt->execute([$viewKey]);
$deviceStats = $stmt->fetchAll();

// 热门内容
$stmt = $pdo->prepare("SELECT content_title, COUNT(*) AS cnt FROM shares WHERE view_key = ? AND content_title != '' GROUP BY content_title ORDER BY cnt DESC LIMIT 5");
$stmt->execute([$viewKey]);
$topContents = $stmt->fetchAll();

// 所有分享链接
$stmt = $pdo->prepare("SELECT * FROM shares WHERE view_key = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$viewKey]);
$allShares = $stmt->fetchAll();

// 近期访问
$stmt = $pdo->prepare("SELECT vl.*, s.content_title FROM visit_logs vl JOIN shares s ON vl.share_code = s.share_code WHERE s.view_key = ? ORDER BY vl.visited_at DESC LIMIT 20");
$stmt->execute([$viewKey]);
$recentVisits = $stmt->fetchAll();

// 近期视频观看
$stmt = $pdo->prepare("SELECT vv.*, v.title AS video_title FROM video_views vv LEFT JOIN videos v ON vv.video_id = v.id WHERE vv.video_id IN (SELECT item_id FROM shares WHERE type='video' AND view_key = ?) ORDER BY vv.viewed_at DESC LIMIT 20");
$stmt->execute([$viewKey]);
$recentVideoViews = $stmt->fetchAll();

// 近期广告点击
$stmt = $pdo->prepare("SELECT ac.* FROM ad_clicks ac WHERE ac.share_code IN (SELECT share_code FROM shares WHERE view_key = ?) ORDER BY ac.clicked_at DESC LIMIT 20");
$stmt->execute([$viewKey]);
$recentAdClicks = $stmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>推广员后台 - xxoo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif; background: #f0f2f5; color: #333; display: flex; min-height: 100vh; }
        /* 侧边栏样式 */
        .sidebar { width: 260px; background: #fff; padding: 0; flex-shrink: 0; border-right: 1px solid #e8e8e8; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; flex-direction: column; height: 100vh; position: sticky; top: 0; overflow-y: auto; }
        .sidebar .logo { padding: 20px; font-size: 1.4rem; font-weight: 700; color: #1d1d1f; text-align: center; border-bottom: 1px solid #f0f0f0; }
        .sidebar nav ul { list-style: none; padding: 0; margin: 0; }
        .sidebar nav li { margin: 0; }
        .sidebar nav a, .sidebar .nav-header { display: block; padding: 12px 20px; color: #555; text-decoration: none; font-size: 14px; border-left: 3px solid transparent; transition: all 0.2s; cursor: pointer; }
        .sidebar nav a:hover, .sidebar nav a.active { background: #e6f7ff; color: #007ecc; border-left-color: #007ecc; }
        .sidebar .nav-header { font-weight: 600; color: #1d1d1f; border-left: none; background: #fafafa; margin-top: 10px; }
        .sidebar .sub-menu { padding-left: 20px; display: none; }
        .sidebar .sub-menu.show { display: block; }
        .sidebar .sub-menu a { font-size: 13px; padding: 8px 20px; }
        .toggle-icon { float: right; font-size: 12px; }
        /* 主内容区 */
        .main-content { flex: 1; padding: 24px; overflow-x: auto; background: #f0f2f5; }
        .page-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 16px; color: #1d1d1f; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e8e8e8; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; text-align: center; border: 1px solid #e8e8e8; }
        .stat-card h3 { font-size: 14px; color: #999; margin-bottom: 8px; }
        .stat-card p { font-size: 2rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 500; }
        .btn-sm { padding: 4px 10px; font-size: 12px; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007ecc; color: #fff; border: none; }
        .copy-btn { background: #007ecc; color: #fff; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; }
        .section-title { font-size: 18px; font-weight: 700; margin: 20px 0 15px 0; color: #007ecc; border-left: 4px solid #f90; padding-left: 12px; }
        .sub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        @media (max-width: 768px) { .sidebar { width: 200px; } .main-content { padding: 12px; } }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">xxoo 推广员</div>
    <nav>
        <ul>
            <li><a href="#dashboard" data-target="dashboard" class="nav-link active">📊 数据总览</a></li>
            <li><a href="#shares" data-target="shares" class="nav-link">🔗 我的分享链接</a></li>
            <li><a href="#visits" data-target="visits" class="nav-link">🌐 访问日志</a></li>
            <li><a href="#video-views" data-target="video-views" class="nav-link">🎬 视频观看</a></li>
            <li><a href="#ad-clicks" data-target="ad-clicks" class="nav-link">🖱️ 广告点击</a></li>
            <li><div class="nav-header">⚙️ 账户设置</div></li>
            <li><a href="change_password.php">🔑 修改密码</a></li>
            <li><a href="setup_2fa.php">🔐 绑定谷歌验证器</a></li>
            <li><a href="logout.php">🚪 退出登录</a></li>
        </ul>
    </nav>
</div>
<div class="main-content">
    <div class="page-title">您好，<?= htmlspecialchars($promoter['username']) ?></div>
    
    <!-- 数据总览区块 -->
    <div id="dashboard" class="content-section">
        <div class="card">
            <h3 class="section-title">📅 今日数据</h3>
            <div class="stat-grid">
                <div class="stat-card"><h3>今日访问</h3><p><?= $todayVisits ?></p></div>
                <div class="stat-card"><h3>今日视频观看</h3><p><?= $todayVideoViews ?></p></div>
                <div class="stat-card"><h3>今日广告点击</h3><p><?= $todayAdClicks ?></p></div>
            </div>
        </div>
        <div class="card">
            <h3 class="section-title">📈 累计数据</h3>
            <div class="stat-grid">
                <div class="stat-card"><h3>总分享数</h3><p><?= number_format($totalShares) ?></p></div>
                <div class="stat-card"><h3>总访问量</h3><p><?= number_format($totalVisits) ?></p></div>
                <div class="stat-card"><h3>总视频观看</h3><p><?= number_format($totalVideoViews) ?></p></div>
                <div class="stat-card"><h3>总广告点击</h3><p><?= number_format($totalAdClicks) ?></p></div>
                <div class="stat-card"><h3>平均停留</h3><p><?= $avgStay ?>秒</p></div>
            </div>
        </div>
        <div class="sub-grid">
            <div class="card">
                <h3 class="section-title">🌐 来源 Top5</h3>
                <table><thead><tr><th>来源</th><th>次数</th></tr></thead><tbody>
                <?php foreach ($topReferers as $r): ?>
                    <tr><td><?= htmlspecialchars($r['referer'] ?: '直接访问') ?></td><td><?= $r['cnt'] ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
            <div class="card">
                <h3 class="section-title">📱 设备分布</h3>
                <table><thead><tr><th>设备</th><th>次数</th></tr></thead><tbody>
                <?php foreach ($deviceStats as $d): ?>
                    <tr><td><?= htmlspecialchars($d['device_type']) ?></td><td><?= $d['cnt'] ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
        <div class="card">
            <h3 class="section-title">🔥 热门分享内容</h3>
            <table><thead><tr><th>内容标题</th><th>分享次数</th></tr></thead><tbody>
            <?php foreach ($topContents as $c): ?>
                <tr><td><?= htmlspecialchars($c['content_title']) ?></td><td><?= $c['cnt'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>

    <!-- 我的分享链接区块 -->
    <div id="shares" class="content-section" style="display:none;">
        <div class="card">
            <h3 class="section-title">🔗 我的分享链接</h3>
            <table><thead><tr><th>类型</th><th>内容标题</th><th>分享码</th><th>时间</th><th>链接</th></tr></thead><tbody>
            <?php foreach ($allShares as $s):
                $shareUrl = "https://tytdwpt.cn/s.php?code=" . urlencode($s['share_code']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($s['type']) ?></td>
                    <td><?= htmlspecialchars($s['content_title'] ?? '无') ?></td>
                    <td><?= htmlspecialchars($s['share_code']) ?></td>
                    <td><?= $s['created_at'] ?></td>
                    <td><button class="copy-btn" onclick="copyText('<?= htmlspecialchars($shareUrl) ?>')">复制</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>

    <!-- 访问日志区块 -->
    <div id="visits" class="content-section" style="display:none;">
        <div class="card">
            <h3 class="section-title">🌐 近期访问</h3>
            <table><thead><tr><th>IP</th><th>国家</th><th>内容</th><th>停留</th><th>时间</th></tr></thead><tbody>
            <?php foreach ($recentVisits as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['ip']) ?></td>
                    <td><?= htmlspecialchars($v['country'] ?? '') ?></td>
                    <td><?= htmlspecialchars($v['content_title'] ?? '') ?></td>
                    <td><?= $v['stay_seconds'] ?>秒</td>
                    <td><?= $v['visited_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>

    <!-- 视频观看区块 -->
    <div id="video-views" class="content-section" style="display:none;">
        <div class="card">
            <h3 class="section-title">🎬 近期视频观看</h3>
            <table><thead><tr><th>视频</th><th>IP</th><th>设备</th><th>时长</th><th>时间</th></tr></thead><tbody>
            <?php foreach ($recentVideoViews as $vv): ?>
                <tr>
                    <td><?= htmlspecialchars($vv['video_title'] ?? '') ?></td>
                    <td><?= htmlspecialchars($vv['ip']) ?></td>
                    <td><?= htmlspecialchars($vv['device_type'] ?? '') ?></td>
                    <td><?= $vv['duration'] ?>秒</td>
                    <td><?= $vv['viewed_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>

    <!-- 广告点击区块 -->
    <div id="ad-clicks" class="content-section" style="display:none;">
        <div class="card">
            <h3 class="section-title">🖱️ 近期广告点击</h3>
            <table><thead><tr><th>广告</th><th>IP</th><th>设备</th><th>时间</th></tr></thead><tbody>
            <?php foreach ($recentAdClicks as $ac): ?>
                <tr>
                    <td><?= htmlspecialchars($ac['ad_title'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ac['ip']) ?></td>
                    <td><?= htmlspecialchars($ac['device_type'] ?? '') ?></td>
                    <td><?= $ac['clicked_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
</div>
<script>
function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => alert('已复制'));
    } else {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); alert('已复制'); } catch(e) { alert('复制失败'); }
        document.body.removeChild(textarea);
    }
}
// 菜单切换显示区域，并高亮
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('data-target');
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });
        document.getElementById(targetId).style.display = 'block';
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        // 可选：更新URL hash
        window.location.hash = targetId;
    });
});
// 根据URL hash显示对应区域
function showFromHash() {
    let hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('data-target') === hash) {
                link.click();
            }
        });
    } else {
        document.querySelector('.nav-link[data-target="dashboard"]').click();
    }
}
showFromHash();
window.addEventListener('hashchange', showFromHash);
</script>
</body>
</html>
