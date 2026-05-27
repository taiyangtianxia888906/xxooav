<?php session_start(); if (!isset($_SESSION["site_verified"]) && strpos($_SERVER["REQUEST_URI"], "verify.php") === false) { header("Location: /verify.php?next=" . urlencode($_SERVER["REQUEST_URI"])); exit; } ?>
require __DIR__ . "/config.php";
<?php
require __DIR__ . "/config.php";
require 'inc/functions.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: home.php"); exit; }
$stmt = $pdo->prepare("SELECT v.*, c.name AS cat_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.id = ?");
$stmt->execute([$id]);
$video = $stmt->fetch();
if (!$video) { header("Location: home.php"); exit; }
logVideoView($id);
$stmt = $pdo->prepare("SELECT id, title, cover FROM videos WHERE category_id = ? AND id != ? ORDER BY id DESC LIMIT 6");
$stmt->execute([$video['category_id'], $id]);
$related = $stmt->fetchAll();
$pageTitle = htmlspecialchars($video['title']);
$currentTab = 'video';
require 'inc/header.php';
?>
<main class="max-w-6xl mx-auto px-3 pb-20">
<a href="javascript:history.back()" class="back-btn">← 返回</a>
<button class="share-btn" data-type="video" data-id="<?php echo $id; ?>" data-title="<?php echo htmlspecialchars(json_encode($video['title']), ENT_QUOTES); ?>">📤 分享</button>
<h2 style="font-size:1.1rem;margin:0.5rem 0;"><?=htmlspecialchars($video['title'])?></h2>
<div class="video-player-wrap">
<video controls autoplay>
<source src="<?=htmlspecialchars($video['video_url'])?>" type="video/mp4">
您的浏览器不支持视频标签。
</video>
</div>
<div style="margin-top:0.5rem;font-size:0.85rem;color:#b0b0b0;">
分类：<?=htmlspecialchars($video['cat_name'] ?? '无')?> | 标签：<?=htmlspecialchars($video['tags'] ?: '无')?>
</div>
<?php if (!empty($related)): ?>
require __DIR__ . "/config.php";
<div class="home-section" style="margin-top:1.5rem;">
<div class="section-title">相关推荐</div>
<div class="home-grid">
<?php foreach ($related as $r): ?>
require __DIR__ . "/config.php";
<a href="video.php?id=<?=$r['id']?>" class="card group">
<div class="card-img-wrap"><img class="card-img lazy-loaded" alt="<?=htmlspecialchars($r['title'])?>" src="<?=htmlspecialchars($r['cover'] ?: 'uploads/default_cover.png')?>"></div>
<div class="card-title"><?=htmlspecialchars($r['title'])?></div>
</a>
<?php endforeach; ?>
require __DIR__ . "/config.php";
</div>
</div>
<?php endif; ?>
require __DIR__ . "/config.php";
<!-- 视频观看时长上报（修复版） -->
<script>
(function() {
var startTime = Date.now();
var videoId = <?= $id ?? 0 ?>;
var reported = false;
function report() {
if (reported || !videoId) return;
reported = true;
var duration = Math.round((Date.now() - startTime) / 1000);
if (duration > 0) {
navigator.sendBeacon("update_video_duration.php?video_id=" + videoId + "&duration=" + duration);
}
}
window.addEventListener("beforeunload", report);
document.addEventListener("visibilitychange", function() {
if (document.hidden) report();
});
// 额外保险：页面隐藏时再次触发（部分浏览器不支持visibilitychange）
window.addEventListener("pagehide", report);
})();
</script>
</main>
<!-- 视频观看时长上报 -->
<script>
(function() {
var startTime = Date.now();
var videoId = <?= $id ?>;
window.addEventListener("beforeunload", function() {
var duration = Math.round((Date.now() - startTime) / 1000);
if (duration > 0) {
navigator.sendBeacon("update_video_duration.php?video_id=" + videoId + "&duration=" + duration);
}
});
})();
</script>
<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
