<?php
require __DIR__ . "/config.php";
/**
 * 标签库接口 - 无需登录，返回全部标签
 * 用法：
 *   ajax_tags.php?all=1       返回所有标签
 *   ajax_tags.php?search=xxx  搜索标签
 */

// 全部标签
if (isset($_GET['all'])) {
    $tags = $pdo->query("SELECT id, name FROM tags ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($tags);
    exit;
}

// 搜索标签
if (isset($_GET['search'])) {
    $keyword = $_GET['search'];
    $stmt = $pdo->prepare("SELECT id, name FROM tags WHERE name LIKE ? ORDER BY id");
    $stmt->execute(['%' . $keyword . '%']);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($tags);
    exit;
}

// 非法请求
http_response_code(400);
echo json_encode([]);
