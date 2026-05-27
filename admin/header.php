<?php
require __DIR__ . "/../config.php";
// 从 URL 参数获取 share_code，覆盖旧 Cookie（解决跨子域名问题）
if (isset($_GET["share_code"]) && !empty($_GET["share_code"])) {
    $share_code = trim($_GET["share_code"]);
    setcookie("share_code", $share_code, time()+86400*30, "/");
    $_COOKIE["share_code"] = $share_code;
} elseif (isset($_COOKIE["share_code"])) {
    $share_code = $_COOKIE["share_code"];
} else {
    $share_code = "";
}
function getCurrentShareCode() { global $share_code; return $share_code; }
require 'auth.php';
require_once __DIR__ . '/../inc/functions.php';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? '管理后台' ?> - xxoo 管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif; background: #f0f2f5; color: #333; display: flex; min-height: 100vh; }
        .sidebar { width: 230px; background: #fff; padding: 0; flex-shrink: 0; border-right: 1px solid #e8e8e8; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .sidebar .logo { padding: 20px; font-size: 1.4rem; font-weight: 700; color: #1d1d1f; text-align: center; border-bottom: 1px solid #f0f0f0; }
        .sidebar nav a { display: block; padding: 12px 20px; color: #555; text-decoration: none; font-size: 14px; border-left: 3px solid transparent; transition: all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: #e6f7ff; color: #007ecc; border-left-color: #007ecc; }
        .main-content { flex: 1; padding: 24px; overflow-x: auto; background: #f0f2f5; }
        .page-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 16px; color: #1d1d1f; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e8e8e8; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { color: #666; font-weight: 500; font-size: 13px; background: #fafafa; }
        tr:hover td { background: #f5f7fa; }
        .btn { display: inline-block; padding: 7px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .btn-primary { background: #007ecc; color: #fff; }
        .btn-primary:hover { background: #006bb3; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .form-inp { width: 100%; padding: 9px 12px; margin-bottom: 14px; border: 1px solid #d9d9d9; background: #fff; color: #333; border-radius: 4px; font-size: 14px; outline: none; }
        .form-inp:focus { border-color: #007ecc; box-shadow: 0 0 0 2px rgba(0,126,204,0.15); }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e8e8e8; }
        .stat-card h3 { font-size: 14px; color: #999; }
        .stat-card p { font-size: 2rem; font-weight: 600; color: #1d1d1f; margin-top: 8px; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 16px; }
        .alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
    </style>
<script>window.addEventListener("error", function(e) { if (e.message.includes("message port")) e.preventDefault(); });</script>
<script>
window.safeJs = function(str) { return JSON.stringify(str); };
</script>
</head>
<body>
<div class="sidebar">
    <div class="logo">xxoo 管理</div>
    <nav>
        <?php
        $links = [
            'index.php' => '仪表盘',
            'videos.php' => '视频管理',
            'categories.php' => '视频分类',
            'tags.php' => '标签管理',
            'novels.php' => '小说管理',
            'novel_categories.php' => '小说分类',
            'images.php' => '图库管理',
            'image_categories.php' => '图库分类',
            'ads.php' => '广告管理',
            'stats.php' => '数据统计',
        'promoters.php' => '推广员管理',
            'promoter_stats.php' => '推广员看板',
            'settings.php' => '系统设置',
            'logout.php' => '退出',
        ];
        $currentPage = basename($_SERVER['SCRIPT_NAME']);
        foreach ($links as $file => $name) {
            $active = ($currentPage === $file) ? ' class="active"' : '';
            echo '<a href="'.$file.'"'.$active.'>'.$name.'</a>';
        }
        ?>
    </nav>
</div>
<div class="main-content">
