<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['video_id'], $_GET['duration'])) {
    $video_id = intval($_GET['video_id']);
    $duration = intval($_GET['duration']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $share_code = $_COOKIE['share_code'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO video_views (video_id, duration, ip, user_agent, share_code, viewed_at) VALUES (?,?,?,?,?,NOW())");
    $stmt->execute([$video_id, $duration, $ip, $ua, $share_code]);
    http_response_code(200);
    echo 'ok';
} else {
    http_response_code(400);
}
