<?php
require 'header.php';

// 今日统计
$todayVisits = $pdo->query("SELECT COUNT(*) FROM visit_logs WHERE DATE(visited_at) = CURDATE()")->fetchColumn();
$todayAdClicks = $pdo->query("SELECT COUNT(*) FROM ad_clicks WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
$todayVideoViews = $pdo->query("SELECT COUNT(*) FROM video_views WHERE DATE(viewed_at) = CURDATE()")->fetchColumn();
$todayShares = $pdo->query("SELECT COUNT(*) FROM shares WHERE DATE(created_at) = CURDATE()")->fetchColumn();

$totalVideos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$totalNovels = $pdo->query("SELECT COUNT(*) FROM novels")->fetchColumn();
$totalImages = $pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
$totalAds = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
?>

<h2 class="page-title">📊 数据仪表盘</h2>

<!-- 关键指标卡片 -->
<div class="stat-grid">
    <div class="stat-card">
        <h3>今日访问</h3>
        <p><?= number_format($todayVisits) ?></p>
    </div>
    <div class="stat-card">
        <h3>今日广告点击</h3>
        <p><?= number_format($todayAdClicks) ?></p>
    </div>
    <div class="stat-card">
        <h3>今日视频观看</h3>
        <p><?= number_format($todayVideoViews) ?></p>
    </div>
    <div class="stat-card">
        <h3>今日分享</h3>
        <p><?= number_format($todayShares) ?></p>
    </div>
</div>

<!-- 内容概览 -->
<div class="card">
    <h4 style="margin-bottom:15px; color:#333;">内容库概览</h4>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(120px,1fr)); gap:15px;">
        <div style="text-align:center; padding:10px; background:#f5f7fa; border-radius:6px;">
            <div style="font-size:24px; font-weight:600; color:#007ecc;"><?= $totalVideos ?></div>
            <div style="font-size:13px; color:#666;">视频</div>
        </div>
        <div style="text-align:center; padding:10px; background:#f5f7fa; border-radius:6px;">
            <div style="font-size:24px; font-weight:600; color:#007ecc;"><?= $totalNovels ?></div>
            <div style="font-size:13px; color:#666;">小说</div>
        </div>
        <div style="text-align:center; padding:10px; background:#f5f7fa; border-radius:6px;">
            <div style="font-size:24px; font-weight:600; color:#007ecc;"><?= $totalImages ?></div>
            <div style="font-size:13px; color:#666;">图集</div>
        </div>
        <div style="text-align:center; padding:10px; background:#f5f7fa; border-radius:6px;">
            <div style="font-size:24px; font-weight:600; color:#007ecc;"><?= $totalAds ?></div>
            <div style="font-size:13px; color:#666;">广告</div>
        </div>
    </div>
</div>

<!-- 快捷入口 -->
<div class="card">
    <h4 style="margin-bottom:15px; color:#333;">⚡ 快捷管理</h4>
    <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <a href="videos.php" class="btn btn-primary" style="background:#007ecc;">📹 视频管理</a>
        <a href="novels.php" class="btn btn-primary" style="background:#007ecc;">📚 小说管理</a>
        <a href="images.php" class="btn btn-primary" style="background:#007ecc;">🖼️ 图库管理</a>
        <a href="ads.php" class="btn btn-primary" style="background:#007ecc;">📢 广告管理</a>
        <a href="stats.php" class="btn btn-primary" style="background:#f90; color:#000;">📈 全链路数据看板</a>
        <a href="settings.php" class="btn btn-primary" style="background:#007ecc;">⚙️ 系统设置</a>
    </div>
</div>

<?php require 'footer.php'; ?>
