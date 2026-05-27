<?php
session_start();
require __DIR__ . "/config.php";
require __DIR__ . '/inc/functions.php';

if (isset($_GET['check_key'])) {
    $key = $_GET['check_key'];
    $stmt = $pdo->prepare("SELECT id, username, view_key FROM promoters WHERE view_key = ?");
    $stmt->execute([$key]);
    $promoter = $stmt->fetch();
    header('Content-Type: application/json');
    if ($promoter) {
        echo json_encode(['valid' => true, 'promoter_name' => $promoter['username'], 'promoter_id' => $promoter['id']]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit;
}

$type   = $_GET['type']   ?? '';
$itemId = $_GET['id']     ?? '';
$code   = $_GET['code']   ?? '';
$title  = $_GET['title']  ?? '';
$viewKey = $_GET['view_key'] ?? '';

if (empty($type) || empty($itemId) || empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => '参数不完整']);
    exit;
}

// 获取有效的推广员邀请码
$validKey = '';
if (!empty($viewKey)) {
    $stmt = $pdo->prepare("SELECT id FROM promoters WHERE view_key = ?");
    $stmt->execute([$viewKey]);
    if ($stmt->fetch()) $validKey = $viewKey;
}
if (empty($validKey) && !empty($code)) {
    $stmt = $pdo->prepare("SELECT id FROM promoters WHERE view_key = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) $validKey = $code;
}

if (empty($validKey)) {
    // 没有有效邀请码，生成随机分享码（仍可分享，但不关联推广员）
    $finalShareCode = $code;
    $viewKeyForDb = substr(md5(uniqid()), 0, 12);
} else {
    $finalShareCode = $validKey;
    $viewKeyForDb = $validKey;
    // 避免重复
    $stmt = $pdo->prepare("SELECT id FROM shares WHERE share_code = ?");
    $stmt->execute([$finalShareCode]);
    if ($stmt->fetch()) {
        $finalShareCode = $validKey . '_' . substr(md5(uniqid()), 0, 4);
    }
}

// 最终检查唯一性
$stmt = $pdo->prepare("SELECT id FROM shares WHERE share_code = ?");
$stmt->execute([$finalShareCode]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => '分享码已存在，请稍后重试']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$geo = function_exists('getIpLocation') ? getIpLocation($ip) : [];
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';

$stmt = $pdo->prepare("INSERT INTO shares (type, item_id, share_code, content_title, ip, country, region, city, user_agent, referer, view_key, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute([
    $type, $itemId, $finalShareCode, $title, $ip,
    $geo['country'] ?? null, $geo['regionName'] ?? null, $geo['city'] ?? null,
    $ua, $ref, $viewKeyForDb
]);

$domains = ['fakakuai.com', 'fakakuai.shop'];
$domain  = $domains[array_rand($domains)];
$prefix  = substr(md5(uniqid()), 0, rand(5, 8));
$shareUrl = "https://{$prefix}.{$domain}/s.php?code=" . urlencode($finalShareCode);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($shareUrl);

header('Content-Type: application/json');
echo json_encode([
    'share_url' => $shareUrl,
    'qr_url'    => $qrUrl,
    'view_key'  => $viewKeyForDb
]);
exit;
