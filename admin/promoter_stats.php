<?php
require __DIR__ . "/../config.php";
require 'header.php';

// 导出 CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    require_once __DIR__ . '/../config.php';
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT 
            p.username,
            p.view_key,
            p.created_at,
            (SELECT COUNT(*) FROM shares WHERE view_key = p.view_key) as shares,
            (SELECT COUNT(*) FROM ad_clicks WHERE share_code = p.view_key AND DATE(clicked_at) BETWEEN ? AND ?) as ad_clicks,
            (SELECT COUNT(*) FROM visit_logs WHERE share_code = p.view_key AND DATE(visited_at) BETWEEN ? AND ?) as visits,
            (SELECT COUNT(*) FROM video_views WHERE video_id IN (SELECT item_id FROM shares WHERE view_key = p.view_key AND type='video') AND DATE(viewed_at) BETWEEN ? AND ?) as video_views
        FROM promoters p
        ORDER BY ad_clicks DESC
    ");
    $stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=promoters_data_{$start_date}_to_{$end_date}.csv");
    $output = fopen('php://output', 'w');
    fputcsv($output, ['用户名', '邀请码', '注册时间', '分享数', '广告点击', '访问量', '视频观看数']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['username'], $row['view_key'], $row['created_at'], $row['shares'], $row['ad_clicks'], $row['visits'], $row['video_views']]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../config.php';

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$total_promoters = $pdo->query("SELECT COUNT(*) FROM promoters")->fetchColumn();
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM ad_clicks WHERE DATE(clicked_at) = CURDATE() AND share_code IS NOT NULL AND share_code != '') as today_ad_clicks,
        (SELECT COUNT(*) FROM visit_logs WHERE DATE(visited_at) = CURDATE() AND share_code IS NOT NULL AND share_code != '') as today_visits,
        (SELECT COUNT(*) FROM shares WHERE DATE(created_at) = CURDATE()) as today_shares
");
$stmt->execute();
$today_stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT 
        p.username,
        p.view_key,
        COUNT(DISTINCT ac.id) as ad_clicks,
        COUNT(DISTINCT vl.id) as visits,
        COUNT(DISTINCT s.id) as shares
    FROM promoters p
    LEFT JOIN shares s ON s.view_key = p.view_key
    LEFT JOIN ad_clicks ac ON ac.share_code = p.view_key
    LEFT JOIN visit_logs vl ON vl.share_code = p.view_key
    GROUP BY p.id
    ORDER BY ad_clicks DESC
    LIMIT 10
");
$stmt->execute();
$leaderboard = $stmt->fetchAll();

$trend_labels = [];
$trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = $date;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_clicks WHERE DATE(clicked_at) = ? AND share_code IS NOT NULL AND share_code != ''");
    $stmt->execute([$date]);
    $trend_data[] = $stmt->fetchColumn();
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_rows = $pdo->query("SELECT COUNT(*) FROM promoters")->fetchColumn();
$total_pages = ceil($total_rows / $per_page);
$sql = "
    SELECT 
        p.id,
        p.username,
        p.view_key,
        p.created_at,
        (SELECT COUNT(*) FROM shares WHERE view_key = p.view_key) as shares,
        (SELECT COUNT(*) FROM ad_clicks WHERE share_code = p.view_key) as ad_clicks,
        (SELECT COUNT(*) FROM visit_logs WHERE share_code = p.view_key) as visits,
        (SELECT COUNT(*) FROM video_views WHERE video_id IN (SELECT item_id FROM shares WHERE view_key = p.view_key AND type='video')) as video_views
    FROM promoters p
    ORDER BY ad_clicks DESC
    LIMIT $per_page OFFSET $offset
";
$promoters_list = $pdo->query($sql)->fetchAll();
?>
<style>body { background: #f5f7fa !important; } .stat-card, .card { background: #fff !important; border: 1px solid #e8e8e8 !important; }
    .stat-card { background: #fff; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 15px; }
    .stat-card h3 { font-size: 14px; color: #999; margin-bottom: 8px; }
    .stat-card p { font-size: 2rem; font-weight: 600; margin: 0; }
    .chart-container { max-width: 800px; margin: 0 auto; }
    .pagination { margin-top: 20px; text-align: center; }
    .pagination a { display: inline-block; padding: 6px 12px; margin: 0 3px; background: #f5f5f5; color: #333; text-decoration: none; border-radius: 4px; }
    .pagination a.active { background: #007ecc; color: #333; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<h2 class="page-title">📊 推广员数据看板</h2>
<div class="card" style="margin-bottom:16px;">
    <form method="get" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div><label>开始日期</label><input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-inp" style="width:auto;"></div>
        <div><label>结束日期</label><input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-inp" style="width:auto;"></div>
        <div><button type="submit" class="btn btn-primary">筛选</button><a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-primary" style="background-color:#28a745; margin-left:8px;">📥 导出 CSV</a></div>
    </form>
</div>
<div class="stat-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="stat-card"><h3>推广员总数</h3><p><?= $total_promoters ?></p></div>
    <div class="stat-card"><h3>今日广告点击</h3><p><?= number_format($today_stats['today_ad_clicks']) ?></p></div>
    <div class="stat-card"><h3>今日访问量</h3><p><?= number_format($today_stats['today_visits']) ?></p></div>
    <div class="stat-card"><h3>今日分享</h3><p><?= number_format($today_stats['today_shares']) ?></p></div>
</div>
<div class="card"><h3>📈 最近7天广告点击趋势</h3><div class="chart-container"><canvas id="adTrendChart" width="800" height="300"></canvas></div></div>
<div class="card"><h3>🏆 推广员排行榜（总广告点击 Top 10）</h3><table style="width:100%;"><thead><tr><th>排名</th><th>用户名</th><th>邀请码</th><th>总广告点击</th><th>总访问量</th><th>分享数</th></tr></thead><tbody><?php $rank=1; foreach($leaderboard as $row): ?><tr><td><?= $rank++ ?></td><td><?= htmlspecialchars($row['username']) ?></td><td><code><?= htmlspecialchars($row['view_key']) ?></code></td><td><?= number_format($row['ad_clicks']) ?></td><td><?= number_format($row['visits']) ?></td><td><?= number_format($row['shares']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card"><h3>📋 推广员详细列表</h3><div style="overflow-x:auto;"><table style="width:100%;"><thead><tr><th>ID</th><th>用户名</th><th>邀请码</th><th>注册时间</th><th>分享数</th><th>广告点击</th><th>访问量</th><th>视频观看</th></tr></thead><tbody><?php foreach($promoters_list as $p): ?><tr><td><?= $p['id'] ?></td><td><?= htmlspecialchars($p['username']) ?></td><td><code><?= htmlspecialchars($p['view_key']) ?></code></td><td><?= $p['created_at'] ?></td><td><?= number_format($p['shares']) ?></td><td><?= number_format($p['ad_clicks']) ?></td><td><?= number_format($p['visits']) ?></td><td><?= number_format($p['video_views']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php if ($total_pages > 1): ?><div class="pagination"><?php for($i=1;$i<=$total_pages;$i++): ?><a href="?page=<?= $i ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?></div>
<script>
    var ctx = document.getElementById('adTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: { labels: <?= json_encode($trend_labels) ?>, datasets: [{ label: '广告点击量', data: <?= json_encode($trend_data) ?>, borderColor: '#f90', backgroundColor: 'rgba(255,153,0,0.1)', tension: 0.3, fill: true }] },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, title: { display: true, text: '点击次数' } }, x: { title: { display: true, text: '日期' } } } }
    });
</script>
<?php require 'footer.php'; ?>
require __DIR__ . "/../config.php";
