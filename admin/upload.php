<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

header('Content-Type: application/json');
$uploadDir = dirname(__DIR__) . '/uploads/';

// 合并分片
if (($action = $_POST['action'] ?? '') === 'merge') {
    $fileName = $_POST['fileName'] ?? '';
    $totalChunks = intval($_POST['totalChunks'] ?? 0);
    if (!$fileName || $totalChunks <= 0) {
        echo json_encode(['success' => false, 'error' => '参数错误']);
        exit;
    }
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $newName = uniqid('file_', true) . '.' . $ext;
    $destPath = $uploadDir . $newName;
    $fp = fopen($destPath, 'wb');
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkPath = $uploadDir . $fileName . '.part' . $i;
        if (!file_exists($chunkPath)) {
            fclose($fp); unlink($destPath);
            echo json_encode(['success' => false, 'error' => "分片 $i 缺失"]);
            exit;
        }
        fwrite($fp, file_get_contents($chunkPath));
        unlink($chunkPath);
    }
    fclose($fp);

    $url = '/uploads/' . $newName;
    $coverUrl = '';
    if (in_array(strtolower($ext), ['mp4','webm'])) {
        $coverName = uniqid('cover_', true) . '.jpg';
        $coverPath = $uploadDir . $coverName;
        exec("ffmpeg -i " . escapeshellarg($destPath) . " -ss 00:00:05 -vframes 1 -q:v 2 " . escapeshellarg($coverPath) . " -y 2>/dev/null", $out, $ret);
        if ($ret === 0 && file_exists($coverPath)) {
            $coverUrl = '/uploads/' . $coverName;
        }
    }
    echo json_encode(['success' => true, 'url' => $url, 'cover' => $coverUrl]);
    exit;
}

// 接收分片
$chunk = $_FILES['chunk'] ?? null;
if ($chunk && !empty($_POST['fileName']) && isset($_POST['chunkIndex'])) {
    $chunkPath = $uploadDir . $_POST['fileName'] . '.part' . intval($_POST['chunkIndex']);
    if (move_uploaded_file($chunk['tmp_name'], $chunkPath)) {
        echo json_encode(['success' => true, 'chunk' => intval($_POST['chunkIndex'])]);
    } else {
        echo json_encode(['success' => false, 'error' => '分片保存失败']);
    }
    exit;
}

// 兼容普通上传（保留）
$response = ['success' => false, 'files' => []];
$files = $_FILES['files'] ?? [];
if (!empty($files['name'])) {
    foreach ($files['name'] as $i => $name) {
        $tmp = $files['tmp_name'][$i];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4','webm','jpg','jpeg','png','gif','webp'])) continue;
        $newName = uniqid('file_', true) . '.' . $ext;
        $destPath = $uploadDir . $newName;
        if (move_uploaded_file($tmp, $destPath)) {
            $url = '/uploads/' . $newName;
            $coverUrl = '';
            if (in_array($ext, ['mp4','webm'])) {
                $coverName = uniqid('cover_', true) . '.jpg';
                $coverPath = $uploadDir . $coverName;
                exec("ffmpeg -i " . escapeshellarg($destPath) . " -ss 00:00:05 -vframes 1 -q:v 2 " . escapeshellarg($coverPath) . " -y 2>/dev/null", $out, $ret);
                if ($ret === 0 && file_exists($coverPath)) $coverUrl = '/uploads/' . $coverName;
            }
            $response['files'][] = ['index' => $i, 'url' => $url, 'cover' => $coverUrl, 'type' => 'video'];
        }
    }
    $response['success'] = true;
}
echo json_encode($response);
