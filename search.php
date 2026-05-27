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

$keyword = trim($_GET['keyword'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$results = [];
$total = 0;
$totalPages = 0;

if (!empty($keyword)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE title LIKE ? OR tags LIKE ?");
    $stmt->execute(['%'.$keyword.'%', '%'.$keyword.'%']);
    $total = $stmt->fetchColumn();
    $totalPages = ceil($total / $perPage);

    $stmt = $pdo->prepare("SELECT id, title, cover FROM videos WHERE title LIKE ? OR tags LIKE ? ORDER BY id DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute(['%'.$keyword.'%', '%'.$keyword.'%']);
    $results = $stmt->fetchAll();
}

$pageTitle = '搜索 - xxoo';
$currentTab = 'search';
require 'inc/header.php';
?>

<main class="max-w-6xl mx-auto px-3 pb-20">
    <div class="search-bar">
        <form method="get" style="display:flex;width:100%;gap:.5rem">
            <input class="search-input" name="keyword" value="<?=htmlspecialchars($keyword)?>" placeholder="搜索视频...">
            <button class="search-btn">搜索</button>
        </form>
    </div>

    <?php if (!empty($keyword)): ?>
        <div class="mb-4"><span class="text-sm text-gray-400">搜索 "<?=htmlspecialchars($keyword)?>" 共 <?=$total?> 个结果</span></div>
        <?php if (!empty($results)): ?>
            <div class="video-grid">
                <?php foreach ($results as $v): ?>
                    <a href="video.php?id=<?=$v['id']?>" class="card group">
                        <div class="card-img-wrap"><img class="card-img lazy-loaded" alt="<?=htmlspecialchars($v['title'])?>" src="<?=htmlspecialchars($v['cover'] ?: 'uploads/default_cover.png')?>"></div>
                        <div class="card-title"><?=htmlspecialchars($v['title'])?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><p>没有找到相关视频</p></div>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
        <nav class="pagination-bar">
            <?php if ($page > 1): ?><a href="?keyword=<?=urlencode($keyword)?>&page=<?=$page-1?>" class="page-btn">上一页</a><?php endif; ?>
            <span class="page-info">第 <?=$page?> / <?=$totalPages?> 页</span>
            <?php if ($page < $totalPages): ?><a href="?keyword=<?=urlencode($keyword)?>&page=<?=$page+1?>" class="page-btn">下一页</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
