<?php
require 'config.php';
require 'inc/functions.php';

$adId = intval($_GET['id'] ?? 0);
$url = $_GET['url'] ?? '';

// 获取分享码（优先级：URL 参数 > Cookie ref_code > 旧参数 ref）
$share_code = $_GET['share_code'] ?? $_COOKIE['share_code'] ?? $_GET['ref'] ?? '';
// ★ 如果以上都没有，尝试从 Cookie 中的 ref_code 获取（来源推广员）
if (empty($share_code) && isset($_COOKIE['ref_code'])) {
    $share_code = $_COOKIE['ref_code'];
}

if ($adId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();
    if ($ad) {
        $positionMap = ['banner' => '顶部横幅', 'icon' => '小图标', 'text' => '文字标签', 'native' => '视频网格'];
        logAdClick(
            $adId,
            $ad['title'] ?? '',
            $ad['type'],
            $positionMap[$ad['type']] ?? $ad['type'],
            $url ?: ($ad['link_url'] ?? ''),
            $share_code
        );
    }
}

if ($url) {
    header('Location: ' . $url);
} else {
    header('Location: home.php');
}
exit;
