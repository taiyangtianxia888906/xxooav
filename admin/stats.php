<?php
require 'header.php';

$tab = $_GET['tab'] ?? 'overview';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');

function formatDuration($sec) {
    $sec = intval($sec);
    if ($sec <= 0) return '0秒';
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    $s = $sec % 60;
    $str = '';
    if ($h > 0) $str .= $h . '小时';
    if ($m > 0) $str .= $m . '分';
    if ($s > 0 || $str == '') $str .= $s . '秒';
    return $str;
}

function deviceDetail($row) {
    $ua = $row['user_agent'] ?? '';
    if (empty($ua)) return '未知';
    require_once __DIR__ . '/../inc/functions.php';
    if (function_exists('parseUA')) {
        $info = parseUA($ua);
        return $info['device_detail'] ?: '未知';
    }
    return '未知';
}
?>

<h2 class="page-title">📊 全链路数据看板</h2>

<div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
    <a href="?tab=overview" class="btn" style="background:<?= $tab=='overview'?'#007ecc':'#e0e0e0' ?>; color:<?= $tab=='overview'?'#fff':'#333' ?>; font-size:16px; padding:12px 24px;">📋 概览</a>
    <a href="?tab=visits" class="btn" style="background:<?= $tab=='visits'?'#007ecc':'#e0e0e0' ?>; color:<?= $tab=='visits'?'#fff':'#333' ?>; font-size:16px; padding:12px 24px;">🌐 访问日志</a>
    <a href="?tab=adclicks" class="btn" style="background:<?= $tab=='adclicks'?'#007ecc':'#e0e0e0' ?>; color:<?= $tab=='adclicks'?'#fff':'#333' ?>; font-size:16px; padding:12px 24px;">🖱️ 广告点击</a>
    <a href="?tab=videoviews" class="btn" style="background:<?= $tab=='videoviews'?'#007ecc':'#e0e0e0' ?>; color:<?= $tab=='videoviews'?'#fff':'#333' ?>; font-size:16px; padding:12px 24px;">🎬 视频观看</a>
    <a href="?tab=shares" class="btn" style="background:<?= $tab=='shares'?'#007ecc':'#e0e0e0' ?>; color:<?= $tab=='shares'?'#fff':'#333' ?>; font-size:16px; padding:12px 24px;">🔗 分享记录</a>
</div>

<?php if ($tab === 'overview'):
    $totalVisits = $pdo->query("SELECT COUNT(*) FROM visit_logs")->fetchColumn();
    $totalAdClicks = $pdo->query("SELECT COUNT(*) FROM ad_clicks")->fetchColumn();
    $totalVideoViews = $pdo->query("SELECT COUNT(*) FROM video_views")->fetchColumn();
    $totalShares = $pdo->query("SELECT COUNT(*) FROM shares")->fetchColumn();
    $topCountries = $pdo->query("SELECT country, COUNT(*) cnt FROM visit_logs GROUP BY country ORDER BY cnt DESC LIMIT 5")->fetchAll();
    $topReferers = $pdo->query("SELECT referer, COUNT(*) cnt FROM visit_logs WHERE referer != '' GROUP BY referer ORDER BY cnt DESC LIMIT 5")->fetchAll();
?>
<div class="card">
    <h4>核心指标</h4>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap:15px;">
        <div><strong>总访问量：</strong><?= number_format($totalVisits) ?></div>
        <div><strong>总广告点击：</strong><?= number_format($totalAdClicks) ?></div>
        <div><strong>总视频观看：</strong><?= number_format($totalVideoViews) ?></div>
        <div><strong>总分享次数：</strong><?= number_format($totalShares) ?></div>
    </div>
</div>
<div class="card"><h4>🌍 热门国家/地区</h4><table><thead><tr><th>国家</th><th>访问次数</th></tr></thead><tbody><?php foreach($topCountries as $c): ?><tr><td><?= htmlspecialchars($c['country'] ?: '未知') ?></td><td><?= $c['cnt'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h4>🔗 热门来源</h4><table><thead><tr><th>来源</th><th>次数</th></tr></thead><tbody><?php foreach($topReferers as $r): ?><tr><td><?= htmlspecialchars($r['referer'] ?: '直接访问') ?></td><td><?= $r['cnt'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<?php if ($tab === 'visits'):
    $where = "1=1";
    if ($search !== '') $where .= " AND (visitor_id LIKE " . $pdo->quote("%{$search}%") . " OR ip LIKE " . $pdo->quote("%{$search}%") . " OR page LIKE " . $pdo->quote("%{$search}%") . ")";

    $total = $pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM visit_logs WHERE {$where}")->fetchColumn();
    $pages = ceil($total / $limit);

    $groups = $pdo->query("SELECT visitor_id, MIN(ip) as ip, MIN(country) as country, MIN(region) as region, MIN(city) as city, COUNT(*) as visits, SUM(stay_seconds) as total_stay FROM visit_logs WHERE {$where} GROUP BY visitor_id ORDER BY MAX(visited_at) DESC LIMIT {$limit} OFFSET {$offset}")->fetchAll();
?>
<div class="card">
    <form method="get" style="margin-bottom:15px; display:flex; gap:8px;">
        <input type="hidden" name="tab" value="visits">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索访客ID / IP / 页面" style="flex:1; padding:8px; border:1px solid #d9d9d9; border-radius:4px;">
        <button type="submit" class="btn btn-primary">🔍 搜索</button>
        <?php if ($search !== ''): ?><a href="?tab=visits" class="btn" style="background:#e0e0e0; color:#333;">清除</a><?php endif; ?>
    </form>

    <?php foreach($groups as $g): 
        $vid = $g['visitor_id'];
        $details = $pdo->query("SELECT * FROM visit_logs WHERE visitor_id = " . $pdo->quote($vid) . " ORDER BY visited_at ASC")->fetchAll();
    ?>
    <div style="border:1px solid #e0e0e0; border-radius:8px; margin-bottom:10px; overflow:hidden;">
        <div onclick="this.nextElementSibling.style.display = (this.nextElementSibling.style.display == 'none' ? 'block' : 'none')" style="cursor:pointer; padding:12px 16px; background:#f5f7fa; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color:#007ecc;"><?= substr(htmlspecialchars($vid), 0, 8) ?></strong>
                <span style="margin-left:12px; color:#666;">IP: <?= htmlspecialchars($g['ip']) ?></span>
                <span style="margin-left:12px; color:#666;"><?= htmlspecialchars($g['country'] ?? '') ?></span>
                <span style="margin-left:12px; color:#666;"><?= htmlspecialchars($g['region'] ?? '') ?></span>
            </div>
            <div style="color:#999; font-size:13px;">
                访问 <?= $g['visits'] ?> 次 | 总停留 <?= formatDuration($g['total_stay']) ?>
                <span style="margin-left:8px;">▶</span>
            </div>
        </div>
        <div style="display:none;">
            <table>
                <thead><tr><th>ID</th><th>IP</th><th>页面</th><th>设备</th><th>来源渠道</th><th>分享码</th><th>停留</th><th>时间</th></tr></thead>
                <tbody>
                <?php foreach($details as $d): ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= htmlspecialchars($d['ip']) ?></td>
                    <td><?= htmlspecialchars($d['page']) ?></td>
                    <td><?= deviceDetail($d) ?></td>
                    <td><?= htmlspecialchars($d['referer'] ?? '') ?></td>
                    <td><?= htmlspecialchars($d['referer_code'] ?? '') ?></td>
                    <td><?= htmlspecialchars($d['share_code'] ?? '') ?></td>
                    <td><?= formatDuration($d['stay_seconds']) ?></td>
                    <td><?= $d['visited_at'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php include __DIR__ . '/../inc/pagination.php'; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'adclicks'):
    $total = $pdo->query("SELECT COUNT(*) FROM ad_clicks")->fetchColumn();
    $pages = ceil($total / $limit);
    $rows = $pdo->query("SELECT * FROM ad_clicks ORDER BY clicked_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<div class="card">
    <table>
        <thead><tr><th>ID</th><th>广告ID</th><th>广告标题</th><th>类型</th><th>位置</th><th>IP</th><th>国家</th><th>设备</th><th>来源页面</th><th>点击时间</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['ad_id'] ?></td>
            <td><?= htmlspecialchars($r['ad_title'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['ad_type'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['position'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['country'] ?? '') ?></td>
            <td><?= deviceDetail($r) ?></td>
            <td><?= htmlspecialchars($r['referer'] ?? '') ?></td>
            <td><?= $r['clicked_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php include __DIR__ . '/../inc/pagination.php'; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'videoviews'):
    $total = $pdo->query("SELECT COUNT(*) FROM video_views")->fetchColumn();
    $pages = ceil($total / $limit);
    $rows = $pdo->query("SELECT vv.*, v.title as video_title FROM video_views vv LEFT JOIN videos v ON vv.video_id = v.id ORDER BY vv.viewed_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<div class="card">
    <table>
        <thead><tr><th>ID</th><th>视频ID</th><th>视频标题</th><th>IP</th><th>国家</th><th>地区</th><th>城市</th><th>设备</th><th>观看时长</th><th>观看时间</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['video_id'] ?></td>
            <td><?= htmlspecialchars($r['video_title'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['country'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['region'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['city'] ?? '') ?></td>
            <td><?= deviceDetail($r) ?></td>
            <td><?= formatDuration($r['duration'] ?? 0) ?></td>
            <td><?= $r['viewed_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php include __DIR__ . '/../inc/pagination.php'; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'shares'):
    $total = $pdo->query("SELECT COUNT(*) FROM shares")->fetchColumn();
    $pages = ceil($total / $limit);
    $rows = $pdo->query("SELECT * FROM shares ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<div class="card">
    <table>
        <thead><tr><th>ID</th><th>类型</th><th>内容ID</th><th>内容标题</th><th>分享码</th><th>分享者IP</th><th>国家</th><th>地区</th><th>来源</th><th>分享时间</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['type'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['item_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['content_title'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['share_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['country'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['region'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['referer'] ?? '') ?></td>
            <td><?= $r['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php include __DIR__ . '/../inc/pagination.php'; ?>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
