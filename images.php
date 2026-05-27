<?php
require __DIR__ . "/config.php";
session_start();
if (!isset($_SESSION["site_verified"]) && strpos($_SERVER["REQUEST_URI"], "verify.php") === false) {
    header("Location: /verify.php?next=" . urlencode($_SERVER["REQUEST_URI"]));
    exit;
}

require 'inc/functions.php';

$albumId = $_GET['album'] ?? null;

// ========== 图集详情页 ==========
if ($albumId) {
    $stmt = $pdo->prepare("SELECT i.*, ic.name AS cat_name FROM images i LEFT JOIN image_categories ic ON i.category_id = ic.id WHERE i.album_id = ? ORDER BY i.title");
    $stmt->execute([$albumId]);
    $images = $stmt->fetchAll();
    if (empty($images)) {
        header("Location: images.php");
        exit;
    }
    $firstTitle = $images[0]['title'];
    $albumTitle = preg_replace('/\s*No\.\d+$/u', '', $firstTitle);
    $totalImages = count($images);
    $catName = $images[0]['cat_name'] ?? '';
    $pageTitle = htmlspecialchars($albumTitle) . ' - xxoo 图库';
    $currentTab = 'images';
    require 'inc/header.php';
    ?>
    <main class="max-w-6xl mx-auto px-3 pb-20" role="region">
        <div class="image-detail">
            <a href="javascript:history.back()" class="back-btn">← 返回</a>
            <button class="share-btn" data-type="image" data-id="<?= $albumId ?>" data-title="<?= htmlspecialchars(addslashes($albumTitle)) ?>">📤 分享</button>
            <div class="mb-3">
                <div class="text-lg font-bold mb-1"><?= htmlspecialchars($albumTitle) ?></div>
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-400">
                    <?php if ($catName): ?><span class="tag-badge"><?= htmlspecialchars($catName) ?></span><?php endif; ?>
                    <span><?= $totalImages ?>张图片</span>
                </div>
            </div>
            <div class="image-viewer">
                <?php foreach ($images as $img): ?>
                    <div class="image-item-auto">
                        <img class="image-auto lazy-loaded" src="<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['title']) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <?php require 'inc/footer.php';
    exit;
}

// ========== 图库首页（按图集展示） ==========
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 图集总数
$stmt = $pdo->query("SELECT COUNT(DISTINCT i.album_id) FROM images i WHERE i.album_id IS NOT NULL");
$totalAlbums = $stmt->fetchColumn();
$totalPages = ceil($totalAlbums / $perPage);

// 图集列表（所有字段都加表别名，避免歧义）
$stmt = $pdo->prepare("SELECT i.album_id, MIN(i.id) AS first_id, COUNT(*) AS img_count, MAX(i.category_id) AS category_id, MAX(ic.name) AS cat_name, MAX(i.title) AS title FROM images i LEFT JOIN image_categories ic ON i.category_id = ic.id WHERE i.album_id IS NOT NULL GROUP BY i.album_id ORDER BY first_id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$albums = $stmt->fetchAll();

// 取出每个图集的封面图片
$albumList = [];
foreach ($albums as $album) {
    $firstImg = $pdo->prepare("SELECT image_url, title FROM images WHERE album_id = ? ORDER BY images.id LIMIT 1");
    $firstImg->execute([$album['album_id']]);
    $img = $firstImg->fetch();
    if ($img) {
        $album['cover_url'] = $img['image_url'];
        $album['display_title'] = preg_replace('/\s*No\.\d+$/u', '', $img['title']);
    } else {
        $album['cover_url'] = 'uploads/default_cover.png';
        $album['display_title'] = $album['title'] ?? '图集';
    }
    $albumList[] = $album;
}

$imageCategories = $pdo->query("SELECT id, name FROM image_categories ORDER BY sort")->fetchAll();
$pageTitle = '图库 - xxoo';
$currentTab = 'images';
require 'inc/header.php';
?>
<main class="max-w-6xl mx-auto px-3 pb-20" role="region">
    <?php if (!empty($imageCategories)): ?>
    <div id="image-types-container" class="tags-container collapsed" style="margin-bottom:1rem;">
        <div class="tags-inner"><div class="tag-group">
            <?php foreach ($imageCategories as $ic): ?>
                <a href="images.php?cat=<?= $ic['id'] ?>" class="menu-item-sub <?= (($_GET['cat'] ?? '') == $ic['id']) ? 'active' : '' ?>"><?= htmlspecialchars($ic['name']) ?></a>
            <?php endforeach; ?>
        </div></div>
        <button type="button" class="tags-toggle" onclick="toggleImageTypes()">展开 ▼</button>
    </div>
    <?php endif; ?>
    <div class="mb-4"><div class="text-xl font-bold">图库</div><span class="text-sm text-gray-400">共 <?= $totalAlbums ?> 套图集</span></div>
    <div class="image-grid">
        <?php foreach ($albumList as $album): ?>
            <a href="images.php?album=<?= urlencode($album['album_id']) ?>" class="gallery-card">
                <div class="gallery-card-img"><img class="gallery-cover lazy-loaded" src="<?= htmlspecialchars($album['cover_url']) ?>" alt="<?= htmlspecialchars($album['display_title']) ?>"></div>
                <div class="card-title"><?= htmlspecialchars($album['display_title']) ?> (<?= $album['img_count'] ?>张)</div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav class="pagination-bar">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" class="page-btn">上一页</a><?php endif; ?>
        <span class="page-info">第 <?= $page ?> / <?= $totalPages ?> 页</span>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>" class="page-btn">下一页</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
<script>
function toggleImageTypes() {
    var c = document.getElementById('image-types-container');
    var btn = c.querySelector('.tags-toggle');
    if (c.classList.contains('collapsed')) {
        c.classList.remove('collapsed'); c.classList.add('expanded');
        btn.textContent = '收起 ▲';
    } else {
        c.classList.remove('expanded'); c.classList.add('collapsed');
        btn.textContent = '展开 ▼';
    }
}
</script>
<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
