<?php
require 'config.php';
require 'inc/functions.php';
session_start();

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: home.php');
    exit;
}

// 查询分享记录
$stmt = $pdo->prepare("SELECT * FROM shares WHERE share_code = ? LIMIT 1");
$stmt->execute([$code]);
$share = $stmt->fetch();

if (!$share) {
    header('Location: home.php');
    exit;
}

// 获取 IP 和地理位置
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$geo = function_exists('getIpLocation') ? getIpLocation($ip) : [];

$visitorId = md5($ip . '_' . $ua);
$stmt = $pdo->prepare("INSERT INTO visit_logs (visitor_id, ip, page, entry_page, user_agent, referer, device_type, share_code, country, region, city, referer_code, visited_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute([
    $visitorId, $ip, '/s.php', '/s.php', $ua, $ref, 'desktop',
    $code, $geo['country'] ?? null, $geo['regionName'] ?? null, $geo['city'] ?? null, $code
]);

// 跳转到目标内容
switch ($share['type']) {
    case 'video': $url = "video.php?id=" . $share['item_id']; break;
    case 'novel': $url = "novel.php?id=" . $share['item_id']; break;
    case 'image': $url = "images.php?album=" . urlencode($share['item_id']); break;
    default: $url = "home.php";
}

// ★ 关键修改：将来源分享码存入 Cookie（30天有效）★
setcookie('ref_code', $code, time() + 86400 * 30, '/');

// 跳转时也带上 share_code 参数
$url .= (strpos($url, '?') === false ? '?' : '&') . 'share_code=' . urlencode($code);
header('Location: ' . $url);
exit;
