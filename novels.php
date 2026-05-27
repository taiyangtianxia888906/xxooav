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

$where = '';
$params = [];
if ($catId > 0) {
    $where = "WHERE n.category_id = ?";
    $params[] = $catId;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM novels n $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT n.id, n.title, nc.name AS cat_name FROM novels n LEFT JOIN novel_categories nc ON n.category_id = nc.id $where ORDER BY n.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$novels = $stmt->fetchAll();

$novelCategories = $pdo->query("SELECT id, name FROM novel_categories ORDER BY sort")->fetchAll();

$pageTitle = '小说列表 - xxoo';
$currentTab = 'novels';
require 'inc/header.php';
?>

<main class="max-w-6xl mx-auto px-3 pb-20" role="region">
    <?php if (!empty($novelCategories)): ?>
    <div id="novel-types-container" class="tags-container expanded" style="margin-bottom:1rem;">
        <div class="tags-inner">
            <div class="tag-group">
                <?php foreach ($novelCategories as $nc): ?>
                    <a href="novels.php?cat=<?=$nc['id']?>" class="menu-item-sub <?=($catId == $nc['id']) ? 'active' : ''?>"><?=htmlspecialchars($nc['name'])?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="button" class="tags-toggle" onclick="toggleNovelTypes()">收起 ▲</button>
    </div>
    <?php endif; ?>

    <div class="mb-4">
        <div class="text-xl font-bold">小说</div>
        <span class="text-sm text-gray-400">共 <?=$total?> 部小说</span>
    </div>

    <div class="novel-grid">
        <?php if (!empty($novels)): foreach ($novels as $n): ?>
            <a href="novel.php?id=<?=$n['id']?>" class="novel-card">
                <div class="novel-card-title"><?=htmlspecialchars($n['title'])?></div>
                <span class="novel-card-category"><?=htmlspecialchars($n['cat_name'] ?? '')?></span>
            </a>
        <?php endforeach; else: ?>
            <div class="empty-state"><p>暂无小说</p></div>
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

<script>
function toggleNovelTypes() {
    var c = document.getElementById('novel-types-container');
    var btn = c.querySelector('.tags-toggle');
    if (c.classList.contains('expanded')) {
        c.classList.remove('expanded'); c.classList.add('collapsed');
        btn.textContent = '展开 ▼';
    } else {
        c.classList.remove('collapsed'); c.classList.add('expanded');
        btn.textContent = '收起 ▲';
    }
}
</script>

<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
