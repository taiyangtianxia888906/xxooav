<?php
if(session_status()===PHP_SESSION_NONE) session_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/../config.php";
// 从 URL 参数获取 share_code（前台通用）
if (isset($_GET["share_code"]) && !empty($_GET["share_code"])) {
    $share_code = trim($_GET["share_code"]);
    setcookie("share_code", $share_code, time()+86400*30, "/");
    $_COOKIE["share_code"] = $share_code;
}

session_start();
// 强制验证检查
$verified = $_SESSION["site_verified"] ?? false;
$currentUrl = $_SERVER["REQUEST_URI"];
if (!$verified && strpos($currentUrl, "verify.php") === false) {
    header("Location: /verify.php?next=" . urlencode($currentUrl));
    exit;
}

// 确保已经加载数据库配置
if (!isset($pdo)) {
    require_once __DIR__ . '/source_tracking.php';
}
require_once __DIR__ . '/functions.php';

// 获取来源渠道和分享码
$fromParam = $_GET["from"] ?? "";
$shareCode = $_GET["code"] ?? $_GET["ref"] ?? "";
// 如果 from 为空但 referer 包含 t.me，则判定为 telegram
if ($fromParam == "" && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], "t.me") !== false) {
    $fromParam = "telegram";
}
$GLOBALS["_from_channel"] = $fromParam;
$GLOBALS["_share_code"] = $shareCode;

// 记录访问日志，保存日志ID供停留时间上报使用
if (function_exists('logVisit')) {
    $_visit_log_id = logVisit($pdo);
}
// 记录当前页面访问


// 如果未定义页面标题，默认设置
if (!isset($pageTitle)) {
    $pageTitle = 'xxoo - 视频';
}
// 如果未定义当前tab，默认视频
if (!isset($currentTab)) {
    $currentTab = 'video';
}

// 获取公共数据（分类、广告、标签），这些在所有页面都可能用到
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort")->fetchAll();
$banners   = $pdo->query("SELECT * FROM ads WHERE type='banner' ORDER BY sort")->fetchAll();
$icons     = $pdo->query("SELECT * FROM ads WHERE type='icon' ORDER BY sort")->fetchAll();
$textAds   = $pdo->query("SELECT * FROM ads WHERE type='text' ORDER BY sort")->fetchAll();

// 标签分组
$tagGroups = $pdo->query("SELECT DISTINCT group_name FROM tags WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name")->fetchAll();
$tagsData = [];
foreach ($tagGroups as $grp) {
    $stmt = $pdo->prepare("SELECT id, name FROM tags WHERE group_name = ? ORDER BY sort");
    $stmt->execute([$grp['group_name']]);
    $tagsData[$grp['group_name']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<?php
if(session_status()===PHP_SESSION_NONE) session_start(); include __DIR__ . "/meta_tags.php"; ?>
require __DIR__ . "/../config.php";
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
<link rel="manifest" href="/manifest.json">
</head>
<body class="bg-gray-950 text-white min-h-screen">

<header class="site-header sticky top-0 z-50">
    <div class="topbar bg-gray-900">
        <div class="max-w-6xl mx-auto px-3">
            <table class="header-bar"><tbody><tr>
                <td class="header-logo-cell"><a href="home.php" style="color:#f90;font-weight:700;">xxoo</a></td>
                <td class="header-nav-cell">
                    <a href="home.php" class="nav-tab <?= $currentTab === 'video' ? 'active' : '' ?>">视频</a>
                    <a href="novels.php" class="nav-tab <?= $currentTab === 'novels' ? 'active' : '' ?>">小说</a>
                    <a href="images.php" class="nav-tab <?= $currentTab === 'images' ? 'active' : '' ?>">图库</a>
                    <a href="search.php" class="nav-tab <?= $currentTab === 'search' ? 'active' : '' ?>">搜索</a>
                </td>
            </tr></tbody></table>
        </div>
    </div>
    <nav class="category-nav bg-gray-900">
        <div class="overflow-x-auto hide-scrollbar">
            <div class="max-w-6xl mx-auto px-3">
                <div class="cat-row">
                    <a href="home.php" class="menu-item <?= $currentTab === 'video' && !isset($_GET['cat']) ? 'active' : '' ?>">首页</a>
                    <?php
if(session_status()===PHP_SESSION_NONE) session_start(); if (!empty($categories)): ?>
                        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); foreach ($categories as $c): ?>
                            <a href="category.php?cat=<?=$c['id']?>" class="menu-item"><?=htmlspecialchars($c['name'])?></a>
                        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endforeach; ?>
                    <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endif; ?>
                </div>
            </div>
        </div>
        <!-- 标签（只在需要时显示） -->
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); if (in_array($currentTab, ['video', 'category']) && !empty($tagsData)): ?>
        <div>
            <div class="max-w-6xl mx-auto px-3">
                <div id="tags-container" class="tags-container collapsed">
                    <div class="tags-inner">
                        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); foreach ($tagsData as $groupName => $tags): if(empty($tags)) continue; ?>
                        <div class="tag-group">
                            <span class="tag-group-label"><?=htmlspecialchars($groupName)?></span>
                            <?php
if(session_status()===PHP_SESSION_NONE) session_start(); foreach ($tags as $tag): ?>
                                <a href="category.php?tag=<?=urlencode($tag['name'])?>" class="menu-item-sub"><?=htmlspecialchars($tag['name'])?></a>
                            <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endforeach; ?>
                        </div>
                        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endforeach; ?>
                    </div>
                    <button type="button" id="tags-toggle" class="tags-toggle" onclick="toggleTags()">展开 ▼</button>
                </div>
            </div>
        </div>
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endif; ?>
    </nav>
</header>

<!-- 公共广告位 -->
<?php
if(session_status()===PHP_SESSION_NONE) session_start(); if (in_array($currentTab, ['video','category','novels','images','search'])): ?>
require __DIR__ . "/../config.php";
<div id="ad-banners" class="max-w-6xl mx-auto px-3">
    <div class="ad-banners-list">
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); if (!empty($banners)): foreach ($banners as $b): ?>
            <a href="adclick.php?id=<?=$b['id']?>&url=<?=urlencode($b['link_url']?:'#')?>&ref=<?=urlencode(($_COOKIE['xxoo_share_code'] ?? $_GET['ref'] ?? ''))?>" target="_blank" rel="nofollow" class="ad-banner-item">
                <div class="lazy-img-wrap" style="position:relative;overflow:hidden;border-radius:0.5rem;background:#ffffff0d;aspect-ratio:480/100;">
                    <img class="w-full rounded-lg lazy-loaded" alt="<?=htmlspecialchars($b['title'])?>" style="width:100%;height:100%;object-fit:cover;" src="<?=htmlspecialchars($b['image_url']?:'https://via.placeholder.com/480x100/333/fff?text=AD')?>">
                </div>
            </a>
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endforeach; endif; ?>
    </div>
</div>

<div id="ad-hot-items" class="max-w-6xl mx-auto px-3">
  <div class="ad-hot-grid">
    <?php if (!empty($icons)): foreach ($icons as $ico): ?>
        <div style="position:relative; display:inline-block;">
            <a href="adclick.php?id=<?=$ico['id']?>&url=<?=urlencode($ico['link_url']?:'#')?>" class="ad-hot-item">
                <div class="ad-hot-img-wrap"><img class="ad-hot-img lazy-loaded" alt="<?=htmlspecialchars($ico['title'])?>" src="<?=htmlspecialchars($ico['image_url'])?>"></div>
                <span class="ad-hot-title"><?=htmlspecialchars($ico['title'])?></span>
            </a>
            <button class="close-ad" data-id="<?=$ico['id']?>" style="position:absolute; top:-8px; right:-8px; background:#ff4444; color:#fff; border:none; border-radius:50%; width:22px; height:22px; font-size:14px; cursor:pointer; z-index:10;" onclick="fetch('/close_ad.php?id=<?=$ico['id']?>'); this.parentNode.style.display='none';">✕</button>
        </div>
    <?php endforeach; endif; ?>
</div>
</div>

<div id="ad-tag-ads" class="max-w-6xl mx-auto px-3">
    <div class="ad-tag-grid">
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); if (!empty($textAds)): foreach ($textAds as $t): ?>
            <a href="adclick.php?id=<?=$t['id']?>&url=<?=urlencode($t['link_url']?:'#')?>&ref=<?=urlencode(($_COOKIE['xxoo_share_code'] ?? $_GET['ref'] ?? ''))?>" class="ad-tag-item"><span class="ad-tag-text"><?=htmlspecialchars($t['title'])?></span></a>
        <?php
if(session_status()===PHP_SESSION_NONE) session_start(); endforeach; endif; ?>
    </div>
</div>
<?php
if(session_status()===PHP_SESSION_NONE) session_start(); endif; ?>
require __DIR__ . "/../config.php";

<div class="max-w-6xl mx-auto px-3 pb-20" role="region">
    <div class="domain-tip">
        <a href="#">下载防封浏览器</a>
        <a href="index.php">最新地址发布页</a>
        <p class="domain-tip-info">永久域名：<a href="https://tytdwpt.cn" style="color:#f90;" id="domainLink">xxoo免费成人视频</a> <button onclick="copyDomain()" style="background:#f90; color:#000; border:none; padding:2px 8px; border-radius:4px; cursor:pointer; font-size:12px;">复制链接</button></p>
        <p class="domain-tip-info">联系邮箱：<a href="mailto:zhugeyang7@gmail.com" style="color:#f90;">zhugeyang7@gmail.com</a></p>
        <p class="domain-tip-info">站长 ✈️Telegram：<a href="https://t.me/xxoowebmasterbot" target="_blank" style="color:#f90;">@xxoowebmasterbot</a></p>
    </div>