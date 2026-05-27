<?php
require __DIR__ . "/config.php";
session_start();
if (!isset($_SESSION["site_verified"]) && strpos($_SERVER["REQUEST_URI"], "verify.php") === false) {
    header("Location: /verify.php?next=" . urlencode($_SERVER["REQUEST_URI"]));
    exit;
}
?>
<?php
require __DIR__ . "/config.php";
require 'inc/functions.php';

$catId = intval($_GET['cat'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->execute([$catId]);
$cat = $stmt->fetch();
if (!$cat) { header("Location: home.php"); exit; }

$stmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE category_id = ?");
$stmt->execute([$catId]);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT id, title, cover FROM videos WHERE category_id = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([$catId]);
$videos = $stmt->fetchAll();

// 原生广告
$nativeAd = $pdo->prepare("SELECT * FROM ads WHERE type='native' AND (category_id = ? OR category_id IS NULL) ORDER BY sort LIMIT 1");
$nativeAd->execute([$catId]);
$nativeAd = $nativeAd->fetch();

$pageTitle = htmlspecialchars($cat['name']) . ' - 视频列表';
$currentTab = 'video'; // 用于头部导航高亮和广告显示
require 'inc/header.php';
?>

<main class="max-w-6xl mx-auto px-3 pb-20" role="region">
    <div class="mb-4">
        <div class="text-xl font-bold"><?=htmlspecialchars($cat['name'])?></div>
        <span class="text-sm text-gray-400">共 <?=$total?> 个视频</span>
    </div>

    <div class="video-grid">
        <?php if (!empty($videos)): ?>
            <?php $k = 0; foreach ($videos as $v): ?>
                <a href="video.php?id=<?=$v['id']?>" class="card group">
                    <div class="card-img-wrap">
                        <img class="card-img lazy-loaded" alt="<?=htmlspecialchars($v['title'])?>" src="<?=htmlspecialchars($v['cover'] ?: 'uploads/default_cover.png')?>">
                        <div class="card-overlay"><svg class="w-8 h-8 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg></div>
                    </div>
                    <div class="card-title"><?=htmlspecialchars($v['title'])?></div>
                </a>
                <?php if ($k === 1 && $nativeAd): ?>
                    <a href="adclick.php?id=<?=$nativeAd['id']?>&url=<?=urlencode($nativeAd['link_url']?:'#')?>" target="_blank" rel="nofollow" class="card native-ad-card">
                        <div class="card-img-wrap"><span class="native-ad-badge">推广</span><img class="card-img lazy-loaded" alt="<?=htmlspecialchars($nativeAd['title'])?>" src="<?=htmlspecialchars($nativeAd['image_url']?:'uploads/default_cover.png')?>"></div>
                        <h3 class="card-title"><?=htmlspecialchars($nativeAd['title'])?></h3>
                    </a>
                <?php endif; $k++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state"><p>暂无视频</p></div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination-bar">
        <?php if ($page > 1): ?><a href="?cat=<?=$catId?>&page=<?=$page-1?>" class="page-btn">上一页</a><?php endif; ?>
        <span class="page-info">第 <?=$page?> / <?=$totalPages?> 页</span>
        <?php if ($page < $totalPages): ?><a href="?cat=<?=$catId?>&page=<?=$page+1?>" class="page-btn">下一页</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>

<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
