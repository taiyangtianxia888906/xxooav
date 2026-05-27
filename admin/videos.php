<?php
require 'header.php';
require_once __DIR__ . '/../inc/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $tags = trim($_POST['tags'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $cover = trim($_POST['cover'] ?? '');
    $auto_cover = isset($_POST['auto_cover']);

    // 上传封面
    if (!empty($_FILES['cover_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $dir = dirname(__DIR__) . '/uploads/covers';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = 'uploads/covers/' . uniqid('cover_', true) . '.' . $ext;
            $dest = dirname(__DIR__) . '/' . $name;
            if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $dest)) {
                $cover = '/' . $name;
            }
        }
    }

    // 自动提取封面
    if ($auto_cover && empty($cover) && !empty($video_url)) {
        $videoPath = dirname(__DIR__) . '/' . ltrim($video_url, '/');
        if (file_exists($videoPath)) {
            $coverFile = generateSmartCover($videoPath);
            if ($coverFile) $cover = '/' . $coverFile;
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE videos SET title=?, cover=?, video_url=?, category_id=?, tags=? WHERE id=?");
        $stmt->execute([$title, $cover, $video_url, $category_id, $tags, $id]);
        $message = "视频已更新";
    } else {
        $stmt = $pdo->prepare("INSERT INTO videos (title, cover, video_url, category_id, tags, created_at) VALUES (?,?,?,?,?, NOW())");
        $stmt->execute([$title, $cover, $video_url, $category_id, $tags]);
        $message = "视频已添加";
    }
}

// 删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM videos WHERE id=?")->execute([$id]);
    header("Location: videos.php");
    exit;
}

$videos = $pdo->query("SELECT v.*, c.name AS cat_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id ORDER BY v.id DESC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort")->fetchAll();
?>
<h2 class="page-title">视频管理</h2>
<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<div class="card">
    <button class="btn btn-primary" onclick="showAddForm()">➕ 添加视频</button>
    <button class="btn btn-primary" onclick="showChunkUpload()">⚡ 极速上传</button>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>封面</th><th>标题</th><th>分类</th><th>标签</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php foreach ($videos as $v): ?>
            <tr>
                <td><?= $v['id'] ?></td>
                <td><img src="<?= htmlspecialchars($v['cover'] ?? '/uploads/default_cover.png') ?>" width="60"></td>
                <td><?= htmlspecialchars($v['title']) ?></td>
                <td><?= htmlspecialchars($v['cat_name'] ?? '无') ?></td>
                <td><?= htmlspecialchars($v['tags']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editVideo(<?= $v['id'] ?>, '<?= addslashes($v['title']) ?>', '<?= addslashes($v['cover'] ?? '') ?>', '<?= addslashes($v['video_url']) ?>', '<?= $v['category_id'] ?>', '<?= addslashes($v['tags']) ?>')">编辑</button>
                    <a href="?delete=<?= $v['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                 </nav>
            20
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>编辑/添加视频</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" id="video_id">
            <div><label>标题</label><input type="text" name="title" id="title" class="form-inp" required></div>
            <div><label>视频URL</label><input type="text" name="video_url" id="video_url" class="form-inp" required></div>
            <div><label>分类</label><select name="category_id" id="category_id" class="form-inp"><option value="">无</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
            <div><label>标签</label><input type="text" name="tags" id="tags" class="form-inp"></div>
            <div><label>封面URL</label><input type="text" name="cover" id="cover" class="form-inp"></div>
            <div><label>上传封面图片</label><input type="file" name="cover_file" accept="image/*"></div>
            <div><label><input type="checkbox" name="auto_cover" value="1"> 自动提取封面（从视频中）</label></div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>
<script>
function showAddForm() {
    document.getElementById('video_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('video_url').value = '';
    document.getElementById('cover').value = '';
    document.getElementById('tags').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('editModal').style.display = 'block';
}
function editVideo(id, title, cover, url, cat, tags) {
    document.getElementById('video_id').value = id;
    document.getElementById('title').value = title;
    document.getElementById('video_url').value = url;
    document.getElementById('cover').value = cover;
    document.getElementById('tags').value = tags;
    document.getElementById('category_id').value = cat;
    document.getElementById('editModal').style.display = 'block';
}
var span = document.getElementsByClassName('close')[0];
span.onclick = function() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(e) { if (e.target == document.getElementById('editModal')) document.getElementById('editModal').style.display = 'none'; }
</script>
<?php require 'footer.php'; ?>
