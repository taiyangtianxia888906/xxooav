<?php
require 'config.php';
if (isset($_GET['log_id'], $_GET['stay'])) {
    $log_id = intval($_GET['log_id']);
    $stay = intval($_GET['stay']);
    $stmt = $pdo->prepare("UPDATE visit_logs SET stay_seconds = ? WHERE id = ?");
    $stmt->execute([$stay, $log_id]);
    http_response_code(200);
    echo 'ok';
} else {
    http_response_code(400);
}
