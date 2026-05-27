<?php
require 'header.php';

// 删除
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: tags.php");
    exit;
}

// 新增/编辑
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $groupName = trim($_POST['group_name'] ?? '');
    $sort = intval($_POST['sort'] ?? 0);
    if ($name !== '') {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE tags SET name = ?, group_name = ?, sort = ? WHERE id = ?");
            $stmt->execute([$name, $groupName, $sort, intval($id)]);
            $message = "标签已更新。";
        } else {
            $stmt = $pdo->prepare("INSERT INTO tags (name, group_name, sort) VALUES (?, ?, ?)");
            $stmt->execute([$name, $groupName, $sort]);
            $message = "标签已添加。";
        }
    }
}

// 获取所有标签（按分组和排序）
$tags = $pdo->query("SELECT * FROM tags ORDER BY group_name, sort, id")->fetchAll();
?>

<h2 class="page-title">标签管理</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<button class="btn btn-primary" onclick="showForm()">+ 新增标签</button>

<div id="formBox" style="display:none; margin:20px 0;">
    <div class="card">
        <form method="post">
            <input type="hidden" name="id" id="tid">
            <label style="color:#b0b0b0;">标签名称</label>
            <input class="form-inp" name="name" id="tname" required>
            <label style="color:#b0b0b0;">分组名称（如：年龄、职业、姿势等）</label>
            <input class="form-inp" name="group_name" id="tgroup" placeholder="例如：年龄">
            <label style="color:#b0b0b0;">排序数字（越小越前）</label>
            <input class="form-inp" name="sort" id="tsort" value="0" type="number">
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
            <tr><th>ID</th><th>标签名</th><th>分组</th><th>排序</th><th>操作</th></tr>
        </thead>
        <tbody>
            <?php foreach ($tags as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['group_name'] ?? '') ?></td>
                <td><?= $t['sort'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editTag(<?= $t['id'] ?>, '<?= addslashes($t['name']) ?>', '<?= addslashes($t['group_name'] ?? '') ?>', <?= $t['sort'] ?>)">编辑</button>
                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showForm() { document.getElementById('formBox').style.display='block'; clearForm(); }
function hideForm() { document.getElementById('formBox').style.display='none'; }
function clearForm() {
    document.getElementById('tid').value = '';
    document.getElementById('tname').value = '';
    document.getElementById('tgroup').value = '';
    document.getElementById('tsort').value = '0';
}
function editTag(id, name, group, sort) {
    document.getElementById('tid').value = id;
    document.getElementById('tname').value = name;
    document.getElementById('tgroup').value = group;
    document.getElementById('tsort').value = sort;
    document.getElementById('formBox').style.display = 'block';
}
</script>

<?php require 'footer.php'; ?>