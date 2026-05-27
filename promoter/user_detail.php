<?php
session_start();
require '../config.php';
if(empty($_SESSION['promoter_id'])){ header('Location: login.php'); exit; }
$visitor_id=$_GET['visitor_id']??'';
if(!$visitor_id) die('缺少参数');
$stmt=$pdo->prepare("SELECT * FROM visit_logs WHERE visitor_id=? ORDER BY visited_at DESC LIMIT 50");
$stmt->execute([$visitor_id]);
$logs=$stmt->fetchAll();
?>
<h1>访客详情</h1>
<table border="1"><?php foreach($logs as $log): ?>
<tr><td><?= $log['visited_at'] ?></td><td><?= $log['page'] ?></td><td><?= $log['referer'] ?></td></tr>
<?php endforeach; ?>
</table>
