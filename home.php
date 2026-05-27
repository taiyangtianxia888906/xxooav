<?php
require __DIR__ . "/config.php";
session_start();
$_SESSION["site_verified"] = true;
if (!isset($_SESSION["site_verified"]) && strpos($_SERVER["REQUEST_URI"], "verify.php") === false) {
    header("Location: /verify.php?next=" . urlencode($_SERVER["REQUEST_URI"]));
    exit;
}
?>
<?php
require __DIR__ . "/config.php";
error_reporting(E_ALL); ini_set('display_errors', 1);

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort")->fetchAll();
$banners   = $pdo->query("SELECT * FROM ads WHERE type='banner' ORDER BY sort")->fetchAll();
$icons     = $pdo->query("SELECT * FROM ads WHERE type='icon' ORDER BY sort")->fetchAll();
$textAds   = $pdo->query("SELECT * FROM ads WHERE type='text' ORDER BY sort")->fetchAll();
$nativeAds = $pdo->query("SELECT * FROM ads WHERE type='native' ORDER BY sort")->fetchAll();

$catVideos = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("SELECT id, title, cover FROM videos WHERE category_id = ? ORDER BY id DESC LIMIT 6");
    $stmt->execute([$cat['id']]);
    $catVideos[$cat['id']] = $stmt->fetchAll();
}
$recommends = $pdo->query("SELECT id, title, cover FROM videos ORDER BY RAND() LIMIT 12")->fetchAll();

$tagGroups = $pdo->query("SELECT DISTINCT group_name FROM tags WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name")->fetchAll();
$tagsData = [];
foreach ($tagGroups as $grp) {
    $stmt = $pdo->prepare("SELECT id, name FROM tags WHERE group_name = ? ORDER BY sort");
    $stmt->execute([$grp['group_name']]);
    $tagsData[$grp['group_name']] = $stmt->fetchAll();
}

function pickNative($ads, $catId = null) {
    foreach ($ads as $ad) if ($ad['category_id'] == $catId) return $ad;
    foreach ($ads as $ad) if ($ad['category_id'] === null) return $ad;
    return null;
}
function vCard($v) {
    return '<a href="video.php?id='.$v['id'].'" class="card group"><div class="card-img-wrap"><img class="card-img lazy-loaded" src="'.htmlspecialchars($v['cover'] ?: 'uploads/default_cover.png').'" alt="'.htmlspecialchars($v['title']).'"></div><div class="card-title">'.htmlspecialchars($v['title']).'</div></a>';
}
function adCard($ad) {
    $url = 'adclick.php?id='.$ad['id'].'&url='.urlencode($ad['link_url'] ?: '#');
    return '<a href="'.$url.'" target="_blank" rel="nofollow" class="card native-ad-card"><div class="card-img-wrap"><span class="native-ad-badge">推广</span><img class="card-img lazy-loaded" src="'.htmlspecialchars($ad['image_url'] ?: 'uploads/default_cover.png').'" alt="'.htmlspecialchars($ad['title']).'"></div><h3 class="card-title">'.htmlspecialchars($ad['title']).'</h3></a>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>xxoo - 首页</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-950 text-white min-h-screen">

<header class="site-header sticky top-0 z-50">
  <div class="topbar bg-gray-900">
    <div class="max-w-6xl mx-auto px-3">
      <table class="header-bar"><tbody><tr>
        <td class="header-logo-cell"><a href="home.php" style="color:#f90;font-weight:700;">xxoo</a></td>
        <td class="header-nav-cell">
          <a href="home.php" class="nav-tab active">视频</a>
          <a href="novels.php" class="nav-tab">小说</a>
          <a href="images.php" class="nav-tab">图库</a>
          <a href="search.php" class="nav-tab">搜索</a>
        </td>
      </tr></tbody></table>
    </div>
  </div>
  <nav class="category-nav bg-gray-900">
    <div class="overflow-x-auto hide-scrollbar">
      <div class="max-w-6xl mx-auto px-3">
        <div class="cat-row">
          <a href="home.php" class="menu-item active">首页</a>
          <?php foreach ($categories as $c): ?>
            <a href="category.php?cat=<?=$c['id']?>" class="menu-item"><?=htmlspecialchars($c['name'])?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div>
      <div class="max-w-6xl mx-auto px-3">
        <div id="tags-container" class="tags-container collapsed">
          <div class="tags-inner">
            <?php foreach ($tagsData as $groupName => $tags): if(empty($tags)) continue; ?>
            <div class="tag-group"><span class="tag-group-label"><?=htmlspecialchars($groupName)?></span>
              <?php foreach ($tags as $tag): ?>
                <a href="category.php?tag=<?=urlencode($tag['name'])?>" class="menu-item-sub"><?=htmlspecialchars($tag['name'])?></a>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" id="tags-toggle" class="tags-toggle" onclick="toggleTags()">展开 ▼</button>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="max-w-6xl mx-auto px-3 pb-20" role="region">
  <!-- 广告位 -->
  <?php if (!empty($banners)): ?>
  <div id="ad-banners"><div class="ad-banners-list">
    <?php foreach ($banners as $b): ?>
      <a href="adclick.php?id=<?=$b['id']?>&url=<?=urlencode($b['link_url']?:'#')?>" target="_blank" rel="nofollow" class="ad-banner-item">
        <div class="lazy-img-wrap" style="position:relative;overflow:hidden;border-radius:0.5rem;background:#ffffff0d;aspect-ratio:480/100;">
          <img class="w-full rounded-lg lazy-loaded" alt="<?=htmlspecialchars($b['title'])?>" style="width:100%;height:100%;object-fit:cover;" src="<?=htmlspecialchars($b['image_url']?:'uploads/default_cover.png')?>">
        </div>
      </a>
    <?php endforeach; ?>
  </div></div>
  <?php endif; ?>

  <?php if (!empty($icons)): ?>
  <div id="ad-hot-items"><div class="ad-hot-grid">
    <?php foreach ($icons as $ico): ?>
      <a href="adclick.php?id=<?=$ico['id']?>&url=<?=urlencode($ico['link_url']?:'#')?>" class="ad-hot-item">
        <div class="ad-hot-img-wrap"><img class="ad-hot-img lazy-loaded" alt="<?=htmlspecialchars($ico['title'])?>" src="<?=htmlspecialchars($ico['image_url']?:'uploads/default_cover.png')?>"></div>
        <span class="ad-hot-title"><?=htmlspecialchars($ico['title'])?></span>
      </a>
    <?php endforeach; ?>
  </div></div>
  <?php endif; ?>

  <?php if (!empty($textAds)): ?>
  <div id="ad-tag-ads"><div class="ad-tag-grid">
    <?php foreach ($textAds as $t): ?>
      <a href="adclick.php?id=<?=$t['id']?>&url=<?=urlencode($t['link_url']?:'#')?>" class="ad-tag-item"><span class="ad-tag-text"><?=htmlspecialchars($t['title'])?></span></a>
    <?php endforeach; ?>
  </div></div>
  <?php endif; ?>

  <div class="domain-tip">
    <a href="#">下载防封浏览器</a>
    <a href="index.php">最新地址发布页</a>
    <p class="domain-tip-info">永久域名：<a href="https://tytdwpt.cn" style="color:#f90;" id="domainLink">xxoo免费成人视频</a> <button onclick="copyDomain()" style="background:#f90; color:#000; border:none; padding:2px 8px; border-radius:4px; cursor:pointer; font-size:12px;">复制链接</button></p>
    <p class="domain-tip-info">联系邮箱：<a href="mailto:zhugeyang7@gmail.com" style="color:#f90;">zhugeyang7@gmail.com</a></p>
    <p class="domain-tip-info">站长 ✈️Telegram：<a href="https://t.me/xxoowebmasterbot" target="_blank" style="color:#f90;">@xxoowebmasterbot</a></p>
  </div>

  <?php if (!empty($recommends)): ?>
  <div class="home-section">
    <div class="section-title">推荐</div>
    <div class="home-grid">
      <?php $i=0; foreach ($recommends as $v): echo vCard($v); if ($i===0 && ($nr=pickNative($nativeAds))) echo adCard($nr); $i++; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php foreach ($categories as $cat): ?>
    <?php $vids = $catVideos[$cat['id']] ?? []; if (empty($vids)) continue; ?>
    <div class="home-section">
      <div class="section-title"><?=htmlspecialchars($cat['name'])?></div>
      <div class="home-grid">
        <?php $j=0; foreach ($vids as $v): echo vCard($v); if ($j===0 && ($na=pickNative($nativeAds, $cat['id']))) echo adCard($na); $j++; endforeach; ?>
      </div>
      <a href="category.php?cat=<?=$cat['id']?>" class="more-btn">更多 <?=htmlspecialchars($cat['name'])?> »</a>
    </div>
  <?php endforeach; ?>
</main>

<footer class="bg-gray-900 border-t border-gray-800 py-6 mt-10">
  <div class="max-w-6xl mx-auto px-3 text-center text-gray-300 text-xs">
    <p>© 2026 xxoo 温馨提示：本网站内容仅适合18岁及以上用户浏览</p>
  </div>
</footer>
<button class="back-to-top" style="display:none;" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<script>
window.addEventListener('scroll', function(){
    var btn = document.querySelector('.back-to-top');
    if (window.scrollY > 300) btn.style.display='flex'; else btn.style.display='none';
});
function toggleTags() {
    var c = document.getElementById('tags-container');
    var btn = document.getElementById('tags-toggle');
    if (c.classList.contains('expanded')) {
        c.classList.remove('expanded'); c.classList.add('collapsed');
        btn.textContent = '展开 ▼';
    } else {
        c.classList.remove('collapsed'); c.classList.add('expanded');
        btn.textContent = '展开 ▼';
    }
}
</script>
</body>
</html>