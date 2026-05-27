<?php
require 'header.php';

// 删除
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM advertisers WHERE id=?")->execute([intval($_GET['delete'])]);
    header("Location: advertisers.php");
    exit;
}

$message = '';
$uploadDir = dirname(__DIR__) . '/uploads/advertisers';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $sort = intval($_POST['sort'] ?? 0);
    $bannerUrl = $_POST['banner_url'] ?? '';
    $iconUrl = $_POST['icon_url'] ?? '';
    $nativeUrl = $_POST['native_url'] ?? '';

    // 上传横幅
    if (!empty($_FILES['banner_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['banner_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $newName = 'uploads/advertisers/banner_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['banner_file']['tmp_name'], dirname(__DIR__) . '/' . $newName);
            $bannerUrl = $newName;
        }
    }
    // 上传图标
    if (!empty($_FILES['icon_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $newName = 'uploads/advertisers/icon_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['icon_file']['tmp_name'], dirname(__DIR__) . '/' . $newName);
            $iconUrl = $newName;
        }
    }
    // 上传原生广告
    if (!empty($_FILES['native_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['native_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $newName = 'uploads/advertisers/native_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['native_file']['tmp_name'], dirname(__DIR__) . '/' . $newName);
            $nativeUrl = $newName;
        }
    }

    if ($name !== '') {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE advertisers SET name=?, website=?, banner_url=?, icon_url=?, native_url=?, sort=? WHERE id=?");
            $stmt->execute([$name, $website, $bannerUrl, $iconUrl, $nativeUrl, $sort, intval($id)]);
            $message = "商家「{$name}」已更新。";
        } else {
            $stmt = $pdo->prepare("INSERT INTO advertisers (name, website, banner_url, icon_url, native_url, sort) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $website, $bannerUrl, $iconUrl, $nativeUrl, $sort]);
            $message = "商家「{$name}」已添加。";
        }
    }
}

$advertisers = $pdo->query("SELECT * FROM advertisers ORDER BY sort, id")->fetchAll();
?>

<h2 class="page-title">商家管理</h2>

<?php if (!empty($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<button class="btn btn-primary" onclick="showForm()">+ 新增商家</button>

<div id="formBox" style="display:none; margin:20px 0;">
    <div class="card">
        <form method="post" enctype="multipart/form-data" id="advertiserForm">
            <input type="hidden" name="id" id="adv_id">
            <label style="color:#b0b0b0;">商家名称</label>
            <input class="form-inp" name="name" id="adv_name" required>
            <label style="color:#b0b0b0;">商家网址</label>
            <input class="form-inp" name="website" id="adv_website" placeholder="https://...">
            <label style="color:#b0b0b0;">排序</label>
            <input class="form-inp" name="sort" id="adv_sort" value="0" type="number">

            <label style="color:#007ecc; margin-top:10px;">广告图片</label>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label style="color:#b0b0b0;">横幅 480×100</label>
                    <input type="file" name="banner_file" accept="image/*" style="width:100%; padding:10px; margin-bottom:10px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                    <input class="form-inp" name="banner_url" id="adv_banner" placeholder="或填写现有URL">
                </div>
                <div>
                    <label style="color:#b0b0b0;">小图标 52×52</label>
                    <input type="file" name="icon_file" accept="image/*" style="width:100%; padding:10px; margin-bottom:10px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                    <input class="form-inp" name="icon_url" id="adv_icon" placeholder="或填写现有URL">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label style="color:#b0b0b0;">文字标签（将使用商家名称）</label>
                    <input class="form-inp" value="" disabled placeholder="自动使用商家名称">
                </div>
                <div>
                    <label style="color:#b0b0b0;">原生广告 (16:9)</label>
                    <input type="file" name="native_file" accept="image/*" style="width:100%; padding:10px; margin-bottom:10px; background:#fff; border:1px solid #d9d9d9; color:#333; border-radius:4px;">
                    <input class="form-inp" name="native_url" id="adv_native" placeholder="或填写现有URL">
                </div>
            </div>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-sm" style="background:#555; color:#333;" onclick="hideForm()">取消</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <table>
        <thead>
            <tr><th>ID</th><th>名称</th><th>横幅</th><th>图标</th><th>原生</th><th>网址</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php foreach ($advertisers as $adv): ?>
            <tr>
                <td><?= $adv['id'] ?></td>
                <td><?= htmlspecialchars($adv['name']) ?></td>
                <td><?php if ($adv['banner_url']): ?><img src="<?= htmlspecialchars('../' . $adv['banner_url']) ?>" width="80"><?php else: ?><span style="color:#888;">-</span><?php endif; ?></td>
                <td><?php if ($adv['icon_url']): ?><img src="<?= htmlspecialchars('../' . $adv['icon_url']) ?>" width="30"><?php else: ?><span style="color:#888;">-</span><?php endif; ?></td>
                <td><?php if ($adv['native_url']): ?><img src="<?= htmlspecialchars('../' . $adv['native_url']) ?>" width="80"><?php else: ?><span style="color:#888;">-</span><?php endif; ?></td>
                <td><?= htmlspecialchars($adv['website'] ?? '') ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editAdv(<?= $adv['id'] ?>, '<?= addslashes($adv['name']) ?>', '<?= addslashes($adv['website'] ?? '') ?>', '<?= addslashes($adv['banner_url'] ?? '') ?>', '<?= addslashes($adv['icon_url'] ?? '') ?>', '<?= addslashes($adv['native_url'] ?? '') ?>', <?= $adv['sort'] ?>)">编辑</button>
                    <a href="?delete=<?= $adv['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showForm() {
    document.getElementById('formBox').style.display = 'block';
    // 彻底清空表单
    document.getElementById('advertiserForm').reset();
    document.getElementById('adv_id').value = '';
    document.getElementById('adv_name').value = '';
    document.getElementById('adv_website').value = '';
    document.getElementById('adv_banner').value = '';
    document.getElementById('adv_icon').value = '';
    document.getElementById('adv_native').value = '';
    document.getElementById('adv_sort').value = '0';
}
function hideForm() { document.getElementById('formBox').style.display = 'none'; }
function editAdv(id, name, website, banner, icon, native, sort) {
    document.getElementById('formBox').style.display = 'block';
    document.getElementById('adv_id').value = id;
    document.getElementById('adv_name').value = name;
    document.getElementById('adv_website').value = website;
    document.getElementById('adv_banner').value = banner;
    document.getElementById('adv_icon').value = icon;
    document.getElementById('adv_native').value = native;
    document.getElementById('adv_sort').value = sort;
}
</script>

<?php require 'footer.php'; ?>
