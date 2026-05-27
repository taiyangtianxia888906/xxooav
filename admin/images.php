<?php
require 'header.php';
require_once __DIR__ . '/../inc/telegram.php';

$message = '';
$uploadDir = dirname(__DIR__) . '/uploads/images/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 删除
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: images.php"); exit;
}

// 批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids']) && isset($_POST['batch_action'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    switch ($_POST['batch_action']) {
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM images WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute($ids);
            $message = "已删除 " . $stmt->rowCount() . " 张图片。";
            break;
        case 'update_category':
            $catId = !empty($_POST['batch_category_id']) ? intval($_POST['batch_category_id']) : null;
            $stmt = $pdo->prepare("UPDATE images SET category_id = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$catId], $ids));
            $message = "已更新 " . count($ids) . " 张图片的分类。";
            break;
        case 'update_tags':
            $tags = $_POST['batch_tags'] ?? '';
            $stmt = $pdo->prepare("UPDATE images SET tags = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
            $stmt->execute(array_merge([$tags], $ids));
            $message = "已更新 " . count($ids) . " 张图片的标签。";
            break;
    }
}

// 上传处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['batch_action'])) {
    // 读取系统设置（提前读取，避免重复）
    $settings = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pushEnabled = ($settings["telegram_notify_enabled"] ?? "0") === "1";

    // 单张上传
    if (!empty($_FILES['image_file']['tmp_name'])) {
        $title = trim($_POST['title'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $tags = trim($_POST['tags'] ?? '');
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $newName = 'uploads/images/img_' . uniqid('', true) . '.' . $ext;
            $destPath = dirname(__DIR__) . '/' . $newName;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destPath)) {
                $stmt = $pdo->prepare("INSERT INTO images (album_id, title, image_url, category_id, tags) VALUES (?,?,?,?,?)");
                $albumId = 'album_' . uniqid('', true);
                $stmt->execute([$albumId, $title, '/' . $newName, $categoryId, $tags]);
                $message = "图片上传成功！";
            } else { $message = "文件移动失败。"; }
        } else { $message = "不支持的图片格式。"; }
    }
    // 批量上传（图集模式）
    if (!empty($_FILES['image_files']['tmp_name'][0])) {
        $files = $_FILES['image_files'];
        $albumTitle = trim($_POST['album_title'] ?? '');
        $albumCategoryId = !empty($_POST['album_category_id']) ? intval($_POST['album_category_id']) : null;
        $albumTags = trim($_POST['album_tags'] ?? '');
        $titles = $_POST['titles'] ?? [];
        $categoryIds = $_POST['category_ids'] ?? [];
        $tagsList = $_POST['tags_list'] ?? [];
        $albumId = 'album_' . uniqid('', true);
        $count = 0;
        foreach ($files['tmp_name'] as $i => $tmpName) {
            if (empty($tmpName)) continue;
            $origName = $files['name'][$i] ?? 'image.jpg';
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
            $imgTitle = trim($titles[$i] ?? ($albumTitle ? $albumTitle . ' No.' . ($i + 1) : pathinfo($origName, PATHINFO_FILENAME)));
            $imgCatId = !empty($categoryIds[$i]) ? intval($categoryIds[$i]) : $albumCategoryId;
            $imgTags = trim($tagsList[$i] ?? $albumTags);
            $newName = 'uploads/images/img_' . uniqid('', true) . '.' . $ext;
            $destPath = dirname(__DIR__) . '/' . $newName;
            if (move_uploaded_file($tmpName, $destPath)) {
                $stmt = $pdo->prepare("INSERT INTO images (album_id, title, image_url, category_id, tags) VALUES (?,?,?,?,?)");
                $stmt->execute([$albumId, $imgTitle, '/' . $newName, $imgCatId, $imgTags]);
                $count++;
            }
        }
        
        file_put_contents("/tmp/push_debug.log", date("Y-m-d H:i:s") . " pushEnabled=" . ($pushEnabled ? "1" : "0") . " func=" . (function_exists("sendTelegramImageNotify") ? "1" : "0") . " album=" . $albumId . "\n", FILE_APPEND);
$message = "批量上传完成，共 {$count} 张图片。";
        // 推送图集到 Telegram
        if (function_exists("sendTelegramImageNotify") && $pushEnabled) {
            $firstPic = $pdo->query("SELECT image_url FROM images WHERE album_id = " . $pdo->quote($albumId) . " ORDER BY id LIMIT 1")->fetchColumn();
            sendTelegramImageNotify([
                "title" => $albumTitle ?: "新图集",
                "cat_name" => $pdo->query("SELECT name FROM image_categories WHERE id = " . intval($albumCategoryId))->fetchColumn() ?: "",
                "tags" => $albumTags ?: "",
                "image_url" => $firstPic ?: ""
            ]);
        }
    }
    // 编辑
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE images SET title=?, category_id=?, tags=? WHERE id=?");
        $stmt->execute([trim($_POST['title'] ?? ''), intval($_POST['category_id'] ?? 0), trim($_POST['tags'] ?? ''), intval($_POST['id'])]);
        $message = "图片信息已更新。";
    }
}

// 搜索与分页
$search = trim($_GET['search'] ?? '');
$where = '';
if ($search !== '') {
    $where = " AND i.title LIKE " . $pdo->quote("%{$search}%");
}
$perPage = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$total = $pdo->query("SELECT COUNT(DISTINCT album_id) FROM images i WHERE 1=1 {$where}")->fetchColumn();
$totalPages = ceil($total / $perPage);
$pages = $totalPages;

$albums = $pdo->query("SELECT album_id, MAX(title) AS title, MAX(category_id) AS category_id, MAX(ic.name) AS cat_name, COUNT(*) AS img_count FROM images i LEFT JOIN image_categories ic ON i.category_id = ic.id WHERE 1=1 {$where} GROUP BY album_id ORDER BY MAX(i.id) DESC LIMIT {$perPage} OFFSET {$offset}")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM image_categories ORDER BY sort")->fetchAll();
?>

<h2 class="page-title">图库管理</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px;">
    <form method="get" style="display:flex; gap:8px;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索标题..." class="form-inp" style="margin-bottom:0; flex:1;">
        <button class="btn btn-primary">搜索</button>
    </form>
</div>

<div style="margin-bottom:20px; display:flex; gap:8px; flex-wrap:wrap;">
    <button class="btn btn-primary" onclick="showSingleForm()">📁 上传单张图片</button>
    <button class="btn btn-primary" onclick="showBatchUpload()">📂 批量上传（图集模式）</button>
</div>

<!-- 单张上传 -->
<div id="singleForm" style="display:none; margin-bottom:20px;">
    <div class="card">
        <h4 style="color:#007ecc;">上传单张图片</h4>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="image_file" accept="image/*" class="form-inp" required>
            <input class="form-inp" name="title" placeholder="标题" required>
            <select class="form-inp" name="category_id">
                <option value="">-- 选择分类 --</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input class="form-inp" name="tags" placeholder="标签（逗号分隔）">
            <button type="submit" class="btn btn-primary">上传</button>
        </form>
    </div>
</div>

<!-- 批量上传 -->
<div id="batchUpload" style="display:none; margin-bottom:20px;">
    <div class="card">
        <h4 style="color:#007ecc;">批量上传图片（图集模式）</h4>
        <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">💡 图集标题统一命名，自动编号，统一分类和标签。</p>
        <form method="post" enctype="multipart/form-data">
            <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                <input class="form-inp" name="album_title" placeholder="图集标题" style="flex:1; min-width:200px; margin-bottom:0;">
                <select class="form-inp" name="album_category_id" style="flex:1; min-width:200px; margin-bottom:0;">
                    <option value="">-- 选择分类 --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="form-inp" name="album_tags" placeholder="统一标签" style="flex:1; min-width:200px; margin-bottom:0;">
            </div>
            <input type="file" name="image_files[]" accept="image/*" multiple class="form-inp">
            <button type="submit" class="btn btn-primary">批量上传</button>
        </form>
    </div>
</div>

<!-- 编辑弹窗 -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:20px; width:400px; max-width:90%;">
        <h4 style="color:#007ecc;">编辑图片</h4>
        <form method="post">
            <input type="hidden" name="id" id="editId">
            <input class="form-inp" name="title" id="editTitle" placeholder="标题" required>
            <select class="form-inp" name="category_id" id="editCategory">
                <option value="">-- 选择分类 --</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input class="form-inp" name="tags" id="editTags" placeholder="标签（逗号分隔）">
            <button type="submit" class="btn btn-primary">保存</button>
            <button type="button" class="btn btn-sm" onclick="document.getElementById('editModal').style.display='none'">取消</button>
        </form>
    </div>
</div>

<!-- 图集列表（折叠显示） -->
<div class="card" style="margin-top:20px;">
    <form id="batchEditForm" method="post">
        <?php foreach ($albums as $album): 
            $pics = $pdo->query("SELECT * FROM images WHERE album_id = " . $pdo->quote($album['album_id']) . " ORDER BY id")->fetchAll();
            $groupId = md5($album['album_id']);
        ?>
        <div style="margin-bottom:10px; border:1px solid #e0e0e0; border-radius:4px;">
            <div onclick="toggleGroup('group_<?= $groupId ?>')" style="cursor:pointer; padding:12px 16px; background:#f5f7fa; display:flex; justify-content:space-between; align-items:center; border-radius:4px 4px 0 0;">
                <strong style="color:#007ecc;"><?= htmlspecialchars(mb_substr($album['title'], 0, 40)) ?><?= mb_strlen($album['title']) > 40 ? '...' : '' ?></strong>
                <span style="color:#666; font-size:13px; margin-left:12px;"><?= htmlspecialchars($album['cat_name'] ?? '') ?> | <?= $album['img_count'] ?> 张</span>
                <span id="group_<?= $groupId ?>_icon" style="color:#007ecc;">▶</span>
            </div>
            <div id="group_<?= $groupId ?>" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="group-check" onclick="var cbs=this.closest('table').querySelectorAll('input[name=\'selected_ids[]\']'); cbs.forEach(cb=>cb.checked=this.checked)"></th>
                            <th>ID</th><th>缩略图</th><th>标题</th><th>分类</th><th>标签</th><th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pics as $img): 
                            $imgId = $img['id'] ?? 0;
                            $imgUrl = $img['image_url'] ?? '';
                            $imgTitle = $img['title'] ?? '';
                            $imgCatId = $img['category_id'] ?? 0;
                            $imgTags = $img['tags'] ?? '';
                        ?>
                        <tr>
                            <td><input type="checkbox" name="selected_ids[]" value="<?= $imgId ?>"></td>
                            <td><?= $imgId ?></td>
                            <td><img src="<?= htmlspecialchars($imgUrl) ?>" style="max-width:60px; max-height:40px; border-radius:4px;"></td>
                            <td><?= htmlspecialchars($imgTitle) ?></td>
                            <td><?= htmlspecialchars($album['cat_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($imgTags) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="editImage(<?= $imgId ?>, '<?= addslashes($imgTitle) ?>', '<?= $imgCatId ?>', '<?= addslashes($imgTags) ?>')">编辑</button>
                                <a href="?delete=<?= $imgId ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 批量操作 -->
        <div style="margin-top:15px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <select id="batchAction" name="batch_action" class="form-inp" style="width:auto; margin-bottom:0;">
                <option value="">-- 批量操作 --</option>
                <option value="delete">删除选中</option>
                <option value="update_category">修改分类</option>
                <option value="update_tags">修改标签</option>
            </select>
            <span id="batchCategoryPicker" style="display:none;">
                <select name="batch_category_id" class="form-inp" style="width:auto; margin-bottom:0;">
                    <option value="">-- 选择分类 --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            <span id="batchTagsInput" style="display:none;">
                <input type="text" name="batch_tags" placeholder="标签（逗号分隔）" class="form-inp" style="width:auto; margin-bottom:0;">
            </span>
            <button type="submit" class="btn btn-primary" onclick="return confirm('确认执行批量操作？')">执行</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../inc/pagination.php'; ?>

<script>
function hideAll() { ['singleForm','batchUpload'].forEach(id => document.getElementById(id).style.display='none'); }
function showSingleForm() { hideAll(); document.getElementById('singleForm').style.display='block'; }
function showBatchUpload() { hideAll(); document.getElementById('batchUpload').style.display='block'; }
function editImage(id, title, cat, tags) {
    document.getElementById('editId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editCategory').value = cat;
    document.getElementById('editTags').value = tags;
    document.getElementById('editModal').style.display = 'flex';
}
function toggleGroup(groupId) {
    var div = document.getElementById(groupId);
    var icon = document.getElementById(groupId + '_icon');
    if (div.style.display === 'none') {
        div.style.display = 'block';
        icon.textContent = '▼';
    } else {
        div.style.display = 'none';
        icon.textContent = '▶';
    }
}
document.getElementById('batchAction').addEventListener('change', function() {
    document.getElementById('batchCategoryPicker').style.display = this.value === 'update_category' ? 'inline' : 'none';
    document.getElementById('batchTagsInput').style.display = this.value === 'update_tags' ? 'inline' : 'none';
});
</script>
<?php require 'footer.php'; ?>
