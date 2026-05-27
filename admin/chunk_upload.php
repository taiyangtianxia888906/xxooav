<?php
require '../config.php';
require '../inc/functions.php';

$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$chunkDir = $uploadDir . 'chunks/';
if (!is_dir($chunkDir)) mkdir($chunkDir, 0755, true);

$originalName = $_POST['originalName'] ?? '';
$chunkIndex = (int)($_POST['chunkIndex'] ?? 0);
$totalChunks = (int)($_POST['totalChunks'] ?? 0);
$title = trim($_POST['title'] ?? '');
$categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
$tags = trim($_POST['tags'] ?? '');
$autoCover = isset($_POST['auto_cover']) && $_POST['auto_cover'] == 1;

if (empty($_FILES['chunk']['tmp_name'])) {
    http_response_code(400);
    exit('No chunk data');
}

$chunkFile = $chunkDir . md5($originalName) . '_' . $chunkIndex . '.part';
move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFile);

$allPartsExist = true;
for ($i = 0; $i < $totalChunks; $i++) {
    if (!file_exists($chunkDir . md5($originalName) . '_' . $i . '.part')) {
        $allPartsExist = false;
        break;
    }
}

if ($allPartsExist) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $finalName = 'uploads/' . uniqid('chunk_', true) . '.' . $ext;
    $finalPath = dirname(__DIR__) . '/' . $finalName;
    $out = fopen($finalPath, 'wb');
    for ($i = 0; $i < $totalChunks; $i++) {
        $partPath = $chunkDir . md5($originalName) . '_' . $i . '.part';
        $in = fopen($partPath, 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        unlink($partPath);
    }
    fclose($out);

    $cover = null;
    if ($autoCover) {
        $coverFile = generateSmartCover($finalPath);
        if ($coverFile) $cover = '/' . $coverFile;
    }
    if ($title === '') $title = pathinfo($originalName, PATHINFO_FILENAME);
    $stmt = $pdo->prepare("INSERT INTO videos (title, cover, video_url, category_id, tags, created_at) VALUES (?,?,?,?,?, NOW())");
    $stmt->execute([$title, $cover, '/' . $finalName, $categoryId, $tags]);

    echo json_encode(['success' => true, 'message' => '上传完成']);
} else {
    echo json_encode(['success' => true, 'chunkIndex' => $chunkIndex]);
}
?>
