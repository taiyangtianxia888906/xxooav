<?php session_start(); if (!isset($_SESSION["site_verified"]) && strpos($_SERVER["REQUEST_URI"], "verify.php") === false) { header("Location: /verify.php?next=" . urlencode($_SERVER["REQUEST_URI"])); exit; } ?>
require __DIR__ . "/config.php";
<?php
require __DIR__ . "/config.php";
require 'inc/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: novels.php"); exit; }

$stmt = $pdo->prepare("SELECT n.*, nc.name AS cat_name FROM novels n LEFT JOIN novel_categories nc ON n.category_id = nc.id WHERE n.id = ?");
$stmt->execute([$id]);
$novel = $stmt->fetch();
if (!$novel) { header("Location: novels.php"); exit; }

$pageTitle = htmlspecialchars($novel['title']);
$currentTab = 'novels';
require 'inc/header.php';
?>

<main class="max-w-6xl mx-auto px-3 pb-20">
    <a href="novels.php" class="back-btn">← 返回书库</a>
<button class="share-btn" data-type="novel" data-id="<?= $id ?>" data-title="<?= htmlspecialchars($novel["title"]) ?>">📤 分享</button>
    <h2 style="font-size:1.1rem;margin:0.5rem 0;"><?=htmlspecialchars($novel['title'])?></h2>
    <?php if (!empty($novel['cat_name'])): ?>
        <span class="text-sm text-gray-400">分类：<?=htmlspecialchars($novel['cat_name'])?></span>
    <?php endif; ?>
    <div class="novel-reader mt-4">
        <div class="novel-text"><?=nl2br(htmlspecialchars($novel['content']))?></div>
    </div>
</main>

<?php require 'inc/footer.php'; ?>
require __DIR__ . "/config.php";
